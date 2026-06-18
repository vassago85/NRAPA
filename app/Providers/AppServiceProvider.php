<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register DocumentRenderer implementation
        // Use Spatie PDF renderer (Browsershot/Chromium) in production, fallback to fake renderer if not available
        $this->app->singleton(
            \App\Contracts\DocumentRenderer::class,
            function ($app) {
                // Check if Spatie Laravel PDF is available
                if (class_exists(\Spatie\LaravelPdf\Facades\Pdf::class)) {
                    return new \App\Services\PdfDocumentRenderer;
                }

                // Fallback to fake renderer for development/testing
                return new \App\Services\FakeDocumentRenderer;
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerMailEventListeners();
        $this->registerAuthEventListeners();
    }

    /**
     * Auth event listeners.
     *
     * NOTE: Laravel 11/12 DOES auto-discover class listeners in app/Listeners
     * (RecordLoginLog, TrackLoginWithout2FA, LogSentEmail, LogFailedEmail are
     * all picked up automatically — verify with `php artisan event:list`).
     * Registering them here AS WELL made every listener fire twice, which
     * duplicated email_logs and login_logs rows. Only closure-based listeners
     * belong in this provider.
     */
    protected function registerAuthEventListeners(): void
    {
        // Intentionally empty: RecordLoginLog and TrackLoginWithout2FA are
        // auto-discovered. Kept as an anchor for future closure listeners.
    }

    /**
     * Closure listeners for mail/queue observability. Class listeners
     * (LogSentEmail, LogFailedEmail) are auto-discovered — do NOT also
     * register them here or every email gets logged twice.
     */
    protected function registerMailEventListeners(): void
    {
        // Global safety throttle: enforce a minimum gap between any two outgoing
        // emails so batches/scheduled runs never burst the mail provider. Runs
        // first (before the logging listener) and blocks until this send's slot.
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $this->throttleOutgoingMail();
        });

        // MessageSending fires BEFORE transport dispatch. Useful for observability
        // in logs only; it does not — and cannot — detect send failures.
        Event::listen(MessageSending::class, function (MessageSending $event) {
            try {
                $to = [];
                foreach ($event->message->getTo() as $addr) {
                    $to[] = $addr->getAddress();
                }
                Log::info('[MAIL_SENDING] '.implode(',', $to).' subject="'.$event->message->getSubject().'"');
            } catch (\Throwable $e) {
                // Observability only — never break sending because of logging.
            }
        });

        // Queued mailables land here on failure. Covers the bulk-import welcome flow.
        Event::listen(JobFailed::class, function (JobFailed $event) {
            try {
                $job = $event->job?->resolveName();
                if (! $job || ! str_contains($job, 'Mail')) {
                    return;
                }
                Log::error('[QUEUED_MAIL_FAILED] '.$job.' — '.$event->exception->getMessage(), [
                    'queue' => $event->job?->getQueue(),
                    'attempts' => $event->job?->attempts(),
                    'payload_preview' => \Illuminate\Support\Str::limit((string) $event->job?->getRawBody(), 500),
                ]);
            } catch (\Throwable $inner) {
                Log::warning('[QUEUED_MAIL_FAILED] reporter failed: '.$inner->getMessage());
            }

            // Flip the matching "queued" email_logs audit row to "failed" so the
            // admin Email Logs page reflects reality instead of showing the mail
            // as queued forever. Best-effort: recipient + subject come from the
            // serialized SendQueuedMailable inside the job payload.
            try {
                $command = $event->job?->payload()['data']['command'] ?? null;
                $sendJob = is_string($command) ? unserialize($command) : null;

                if ($sendJob instanceof \Illuminate\Mail\SendQueuedMailable) {
                    $mailable = $sendJob->mailable;
                    $toEmail = collect($mailable->to ?? [])->pluck('address')->first();
                    $subject = $mailable->subject ?? null;

                    if ($toEmail) {
                        \App\Models\EmailLog::where('to_email', $toEmail)
                            ->where('status', 'queued')
                            ->when($subject, fn ($q) => $q->where('subject', $subject))
                            ->orderBy('created_at')
                            ->first()
                            ?->update([
                                'status' => 'failed',
                                'error_message' => \Illuminate\Support\Str::limit($event->exception->getMessage(), 500),
                            ]);
                    }
                }
            } catch (\Throwable $inner) {
                // unserialize can fail (deleted models, payload changes) — observability only.
                Log::warning('[QUEUED_MAIL_FAILED] email_logs status update failed: '.$inner->getMessage());
            }
        });
    }

    /**
     * Enforce a global minimum gap between outgoing emails.
     *
     * Each send atomically reserves the next available time slot in the cache
     * (last reserved slot + gap) and then sleeps until that slot arrives. Because
     * the reservation is monotonic, concurrent senders coordinate without
     * colliding. Only enforced for CLI / queue-worker / scheduled sends — that is
     * where bulk and scheduled batches run — so interactive web requests (e.g. a
     * single admin "resend") are never blocked and can't time out.
     */
    protected function throttleOutgoingMail(): void
    {
        $gap = (int) config('mail.min_gap_seconds', 15);

        if ($gap <= 0 || ! app()->runningInConsole()) {
            return;
        }

        $key = 'mail:next-send-at';
        $ttl = now()->addHour();

        // Read-modify-write of the next-slot timestamp. Returns seconds to wait.
        $reserve = function () use ($key, $gap, $ttl): float {
            $now = microtime(true);
            $next = (float) (Cache::get($key) ?? 0);
            $slot = max($now, $next);
            Cache::put($key, $slot + $gap, $ttl);

            return $slot - $now;
        };

        $wait = 0.0;

        try {
            $store = Cache::getStore();

            if ($store instanceof LockProvider) {
                $lock = $store->lock('mail:send-gap', 10);
                $lock->block(10);

                try {
                    $wait = $reserve();
                } finally {
                    $lock->release();
                }
            } else {
                // No atomic lock available (e.g. file cache in local dev). The
                // queue worker is single-process here, so a plain RMW is fine.
                $wait = $reserve();
            }
        } catch (\Throwable $e) {
            // Never let throttling block delivery if the cache/lock misbehaves.
            Log::warning('[MAIL_THROTTLE] skipped: '.$e->getMessage());

            return;
        }

        // Clamp to a sane window so a corrupted cache value can't hang a worker.
        $wait = max(0.0, min($wait, 3600.0));

        if ($wait > 0) {
            usleep((int) round($wait * 1_000_000));
        }
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}

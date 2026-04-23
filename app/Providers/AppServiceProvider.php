<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobFailed;
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
    }

    /**
     * Register listeners that log every outgoing email to the `email_logs`
     * table (and to laravel.log for failed sends).
     *
     * These MUST be wired here rather than relying on implicit auto-discovery
     * because Laravel 11 does not auto-register listeners in app/Listeners.
     * Without this, the admin "Email Logs" page stays empty even though mails
     * are actually being dispatched — which makes it look like welcome emails
     * aren't going out during bulk imports.
     */
    protected function registerMailEventListeners(): void
    {
        Event::listen(MessageSent::class, \App\Listeners\LogSentEmail::class);

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
        });
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

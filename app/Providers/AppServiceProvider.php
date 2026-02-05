<?php

namespace App\Providers;

use App\Listeners\LogSentEmail;
use App\Listeners\TrackLoginWithout2FA;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
        // Use PDF renderer in production, fallback to fake renderer if DomPDF not available
        $this->app->singleton(
            \App\Contracts\DocumentRenderer::class,
            function ($app) {
                // Check if DomPDF is available
                if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                    return new \App\Services\PdfDocumentRenderer();
                }
                // Fallback to fake renderer for development/testing
                return new \App\Services\FakeDocumentRenderer();
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerEventListeners();
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        Event::listen(MessageSent::class, LogSentEmail::class);
        Event::listen(Login::class, TrackLoginWithout2FA::class);
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

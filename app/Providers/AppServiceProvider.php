<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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

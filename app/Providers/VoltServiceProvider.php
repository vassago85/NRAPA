<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;

class VoltServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Mount Volt directories for functional API components
        Volt::mount([
            resource_path('views/livewire'),
            resource_path('views/pages'),
        ]);

        // Register route to handle Livewire JS module requests (Volt components)
        // This prevents 404 errors when Livewire tries to load module files that don't exist
        \Illuminate\Support\Facades\Route::get('/livewire-{hash}/js/{path}', function ($hash, $path) {
            return response('// Empty module - using inline scripts instead', 200)
                ->header('Content-Type', 'application/javascript')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        })->where('hash', '[a-z0-9]+')->where('path', '.*')->name('livewire.js-module');
    }
}

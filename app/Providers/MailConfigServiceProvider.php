<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
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
        // Only load from DB if table exists (prevents errors during migration)
        if (!app()->runningInConsole() || app()->runningUnitTests()) {
            $this->loadMailConfigFromDatabase();
        }
    }

    /**
     * Load mail configuration from database.
     */
    protected function loadMailConfigFromDatabase(): void
    {
        try {
            // Check if the table exists
            if (!\Schema::hasTable('system_settings')) {
                return;
            }

            $mailHost = SystemSetting::get('mail_host');

            // Only override if we have settings in database
            if ($mailHost) {
                config([
                    'mail.default' => SystemSetting::get('mail_mailer', config('mail.default')),
                    'mail.mailers.smtp.host' => $mailHost,
                    'mail.mailers.smtp.port' => SystemSetting::get('mail_port', config('mail.mailers.smtp.port')),
                    'mail.mailers.smtp.username' => SystemSetting::get('mail_username', config('mail.mailers.smtp.username')),
                    'mail.mailers.smtp.password' => SystemSetting::get('mail_password', config('mail.mailers.smtp.password')),
                    'mail.mailers.smtp.encryption' => SystemSetting::get('mail_encryption', config('mail.mailers.smtp.encryption')),
                    'mail.from.address' => SystemSetting::get('mail_from_address', config('mail.from.address')),
                    'mail.from.name' => SystemSetting::get('mail_from_name', config('mail.from.name')),
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail - database might not be available yet
        }
    }
}

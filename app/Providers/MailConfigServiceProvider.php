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
        // Load mail config from database for all contexts (web, queue worker, etc.)
        // Safe to always call — method checks Schema::hasTable() and wraps in try/catch
        $this->loadMailConfigFromDatabase();
    }

    /**
     * Load mail configuration from database.
     */
    protected function loadMailConfigFromDatabase(): void
    {
        try {
            // Check if the table exists
            if (! \Schema::hasTable('system_settings')) {
                return;
            }

            $mailMailer = SystemSetting::get('mail_mailer');

            // Only override if we have a mailer setting in database
            if (! $mailMailer) {
                return;
            }

            // Common settings
            $commonConfig = [
                'mail.default' => $mailMailer,
                'mail.from.address' => SystemSetting::get('mail_from_address', config('mail.from.address')),
                'mail.from.name' => SystemSetting::get('mail_from_name', config('mail.from.name')),
            ];

            // Configure based on mailer type
            if ($mailMailer === 'mailgun') {
                // Mailgun API configuration
                $mailgunConfig = [
                    'services.mailgun.domain' => SystemSetting::get('mailgun_domain', config('services.mailgun.domain')),
                    'services.mailgun.secret' => SystemSetting::get('mailgun_secret', config('services.mailgun.secret')),
                    'services.mailgun.endpoint' => SystemSetting::get('mailgun_endpoint', config('services.mailgun.endpoint', 'api.mailgun.net')),
                ];
                config(array_merge($commonConfig, $mailgunConfig));
            } else {
                // SMTP or other mailer configuration
                $smtpConfig = [
                    'mail.mailers.smtp.host' => SystemSetting::get('mail_host', config('mail.mailers.smtp.host')),
                    'mail.mailers.smtp.port' => SystemSetting::get('mail_port', config('mail.mailers.smtp.port')),
                    'mail.mailers.smtp.username' => SystemSetting::get('mail_username', config('mail.mailers.smtp.username')),
                    'mail.mailers.smtp.password' => SystemSetting::get('mail_password', config('mail.mailers.smtp.password')),
                    'mail.mailers.smtp.encryption' => SystemSetting::get('mail_encryption', config('mail.mailers.smtp.encryption')),
                ];
                config(array_merge($commonConfig, $smtpConfig));
            }
        } catch (\Exception $e) {
            // Silently fail - database might not be available yet
        }
    }
}

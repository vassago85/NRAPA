<?php

use App\Models\SystemSetting;
use Livewire\Component;
use Illuminate\Support\Facades\Mail;

new class extends Component {
    // Email/SMTP Settings
    public string $mail_mailer = 'smtp';
    public string $mail_host = '';
    public int $mail_port = 587;
    public string $mail_username = '';
    public string $mail_password = '';
    public string $mail_encryption = 'tls';
    public string $mail_from_address = '';
    public string $mail_from_name = 'NRAPA';

    // Mailgun Settings
    public string $mailgun_domain = '';
    public string $mailgun_secret = '';
    public string $mailgun_endpoint = 'api.mailgun.net';

    public string $test_email = '';
    public bool $showPassword = false;
    public bool $showMailgunSecret = false;

    public function mount(): void
    {
        $this->mail_mailer = SystemSetting::get('mail_mailer', 'smtp');
        $this->mail_host = SystemSetting::get('mail_host', '');
        $this->mail_port = (int) SystemSetting::get('mail_port', 587);
        $this->mail_username = SystemSetting::get('mail_username', '');
        $this->mail_password = SystemSetting::get('mail_password', '');
        $this->mail_encryption = SystemSetting::get('mail_encryption', 'tls');
        $this->mail_from_address = SystemSetting::get('mail_from_address', '');
        $this->mail_from_name = SystemSetting::get('mail_from_name', 'NRAPA');

        // Mailgun settings
        $this->mailgun_domain = SystemSetting::get('mailgun_domain', '');
        $this->mailgun_secret = SystemSetting::get('mailgun_secret', '');
        $this->mailgun_endpoint = SystemSetting::get('mailgun_endpoint', 'api.mailgun.net');

        $this->test_email = auth()->user()->email;
    }

    public function saveEmailSettings(): void
    {
        $rules = [
            'mail_mailer' => 'required|string|in:smtp,mailgun,log',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
        ];

        // Add conditional rules based on mailer
        if ($this->mail_mailer === 'smtp') {
            $rules['mail_host'] = 'required|string|max:255';
            $rules['mail_port'] = 'required|integer|min:1|max:65535';
            $rules['mail_username'] = 'nullable|string|max:255';
            $rules['mail_password'] = 'nullable|string|max:255';
            $rules['mail_encryption'] = 'required|string|in:tls,ssl,null';
        } elseif ($this->mail_mailer === 'mailgun') {
            $rules['mailgun_domain'] = 'required|string|max:255';
            $rules['mailgun_secret'] = 'required|string|max:255';
            $rules['mailgun_endpoint'] = 'required|string|max:255';
        }

        $this->validate($rules);

        // Save common settings
        SystemSetting::set('mail_mailer', $this->mail_mailer, 'string', 'email', 'Mail driver');
        SystemSetting::set('mail_from_address', $this->mail_from_address, 'string', 'email', 'From email address');
        SystemSetting::set('mail_from_name', $this->mail_from_name, 'string', 'email', 'From name');

        // Save SMTP settings
        SystemSetting::set('mail_host', $this->mail_host, 'string', 'email', 'SMTP host');
        SystemSetting::set('mail_port', $this->mail_port, 'integer', 'email', 'SMTP port');
        SystemSetting::set('mail_username', $this->mail_username, 'string', 'email', 'SMTP username');
        SystemSetting::set('mail_password', $this->mail_password, 'string', 'email', 'SMTP password');
        SystemSetting::set('mail_encryption', $this->mail_encryption, 'string', 'email', 'SMTP encryption');

        // Save Mailgun settings
        SystemSetting::set('mailgun_domain', $this->mailgun_domain, 'string', 'email', 'Mailgun domain');
        SystemSetting::set('mailgun_secret', $this->mailgun_secret, 'string', 'email', 'Mailgun API secret');
        SystemSetting::set('mailgun_endpoint', $this->mailgun_endpoint, 'string', 'email', 'Mailgun endpoint');

        // Update runtime config
        if ($this->mail_mailer === 'mailgun') {
            config([
                'mail.default' => 'mailgun',
                'services.mailgun.domain' => $this->mailgun_domain,
                'services.mailgun.secret' => $this->mailgun_secret,
                'services.mailgun.endpoint' => $this->mailgun_endpoint,
                'mail.from.address' => $this->mail_from_address,
                'mail.from.name' => $this->mail_from_name,
            ]);
        } else {
            config([
                'mail.default' => $this->mail_mailer,
                'mail.mailers.smtp.host' => $this->mail_host,
                'mail.mailers.smtp.port' => $this->mail_port,
                'mail.mailers.smtp.username' => $this->mail_username,
                'mail.mailers.smtp.password' => $this->mail_password,
                'mail.mailers.smtp.encryption' => $this->mail_encryption === 'null' ? null : $this->mail_encryption,
                'mail.from.address' => $this->mail_from_address,
                'mail.from.name' => $this->mail_from_name,
            ]);
        }

        session()->flash('success', 'Email settings saved successfully.');
    }

    public function sendTestEmail(): void
    {
        $this->validate([
            'test_email' => 'required|email',
        ]);

        try {
            // Apply settings temporarily based on mailer type
            if ($this->mail_mailer === 'mailgun') {
                config([
                    'mail.default' => 'mailgun',
                    'services.mailgun.domain' => $this->mailgun_domain,
                    'services.mailgun.secret' => $this->mailgun_secret,
                    'services.mailgun.endpoint' => $this->mailgun_endpoint,
                    'mail.from.address' => $this->mail_from_address,
                    'mail.from.name' => $this->mail_from_name,
                ]);
            } else {
                config([
                    'mail.default' => $this->mail_mailer,
                    'mail.mailers.smtp.host' => $this->mail_host,
                    'mail.mailers.smtp.port' => $this->mail_port,
                    'mail.mailers.smtp.username' => $this->mail_username,
                    'mail.mailers.smtp.password' => $this->mail_password,
                    'mail.mailers.smtp.encryption' => $this->mail_encryption === 'null' ? null : $this->mail_encryption,
                    'mail.from.address' => $this->mail_from_address,
                    'mail.from.name' => $this->mail_from_name,
                ]);
            }

            Mail::raw('This is a test email from NRAPA to verify your email configuration is working correctly.', function ($message) {
                $message->to($this->test_email)
                    ->subject('NRAPA - Test Email');
            });

            session()->flash('success', 'Test email sent successfully to ' . $this->test_email);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    public function togglePassword(): void
    {
        $this->showPassword = !$this->showPassword;
    }

    public function toggleMailgunSecret(): void
    {
        $this->showMailgunSecret = !$this->showMailgunSecret;
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Email Settings</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Configure SMTP settings for sending emails.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Settings Navigation -->
        <div class="lg:col-span-1">
            <nav class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">Settings</h2>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('owner.settings.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Bank Account
                        </a>
                    </li>
                    <li>
                        <a href="#email-settings" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Email / SMTP
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('owner.settings.storage') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                            Storage / R2
                        </a>
                    </li>
                </ul>

                <!-- Zoho Mail Preset -->
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Zoho Mail (SMTP)</h3>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mb-2">Use these settings for Zoho Mail:</p>
                    <ul class="text-xs text-blue-600 dark:text-blue-400 space-y-1">
                        <li><strong>Host:</strong> smtp.zoho.com</li>
                        <li><strong>Port:</strong> 587 (TLS) or 465 (SSL)</li>
                        <li><strong>Encryption:</strong> TLS or SSL</li>
                    </ul>
                </div>

                <!-- Mailgun Preset -->
                <div class="mt-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                    <h3 class="text-sm font-semibold text-orange-800 dark:text-orange-200 mb-2">Mailgun API</h3>
                    <p class="text-xs text-orange-700 dark:text-orange-300 mb-2">For Mailgun, you'll need:</p>
                    <ul class="text-xs text-orange-600 dark:text-orange-400 space-y-1">
                        <li><strong>Domain:</strong> Your sending domain</li>
                        <li><strong>API Key:</strong> From Mailgun dashboard</li>
                        <li><strong>Endpoint:</strong> api.mailgun.net (US) or api.eu.mailgun.net (EU)</li>
                    </ul>
                </div>
            </nav>
        </div>

        <!-- Email Settings Form -->
        <div class="lg:col-span-2">
            <div id="email-settings" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Email Configuration</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Configure outgoing email settings</p>
                    </div>
                </div>

                <form wire:submit="saveEmailSettings" class="space-y-4">
                    <!-- Mail Driver Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label for="mail_mailer" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Mail Driver</label>
                            <select id="mail_mailer" wire:model.live="mail_mailer"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="smtp">SMTP (Zoho, Gmail, etc.)</option>
                                <option value="mailgun">Mailgun API</option>
                                <option value="log">Log (Testing Only)</option>
                            </select>
                        </div>
                    </div>

                    <!-- SMTP Settings (shown when mail_mailer is smtp) -->
                    @if($mail_mailer === 'smtp')
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                            SMTP Server Settings
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="mail_host" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SMTP Host</label>
                                <input type="text" id="mail_host" wire:model="mail_host" placeholder="smtp.zoho.com"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                @error('mail_host') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="mail_port" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SMTP Port</label>
                                <input type="number" id="mail_port" wire:model="mail_port" placeholder="587"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                @error('mail_port') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="mail_encryption" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Encryption</label>
                                <select id="mail_encryption" wire:model="mail_encryption"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                    <option value="null">None</option>
                                </select>
                            </div>

                            <div>
                                <label for="mail_username" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SMTP Username</label>
                                <input type="text" id="mail_username" wire:model="mail_username" placeholder="your@email.com"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                @error('mail_username') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="mail_password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SMTP Password</label>
                                <div class="relative">
                                    <input type="{{ $showPassword ? 'text' : 'password' }}" id="mail_password" wire:model="mail_password" placeholder="••••••••"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent pr-10">
                                    <button type="button" wire:click="togglePassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                                        @if($showPassword)
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        @endif
                                    </button>
                                </div>
                                @error('mail_password') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Mailgun Settings (shown when mail_mailer is mailgun) -->
                    @if($mail_mailer === 'mailgun')
                    <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                        <h3 class="text-sm font-semibold text-orange-800 dark:text-orange-200 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Mailgun API Settings
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="mailgun_domain" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Mailgun Domain</label>
                                <input type="text" id="mailgun_domain" wire:model="mailgun_domain" placeholder="mg.yourdomain.com"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                @error('mailgun_domain') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="mailgun_endpoint" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Mailgun Endpoint</label>
                                <select id="mailgun_endpoint" wire:model="mailgun_endpoint"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="api.mailgun.net">US Region (api.mailgun.net)</option>
                                    <option value="api.eu.mailgun.net">EU Region (api.eu.mailgun.net)</option>
                                </select>
                                @error('mailgun_endpoint') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="mailgun_secret" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Mailgun API Key</label>
                                <div class="relative">
                                    <input type="{{ $showMailgunSecret ? 'text' : 'password' }}" id="mailgun_secret" wire:model="mailgun_secret" placeholder="key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent pr-10">
                                    <button type="button" wire:click="toggleMailgunSecret" class="absolute inset-y-0 right-0 px-3 flex items-center text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                                        @if($showMailgunSecret)
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        @endif
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Find this in your Mailgun dashboard under API Keys</p>
                                @error('mailgun_secret') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Log Mode Notice -->
                    @if($mail_mailer === 'log')
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div>
                                <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">Testing Mode</h4>
                                <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">Emails will be written to the Laravel log file instead of being sent. Use this for testing only.</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Common Settings (From Address & Name) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <div>
                            <label for="mail_from_address" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">From Email Address</label>
                            <input type="email" id="mail_from_address" wire:model="mail_from_address" placeholder="noreply@nrapa.co.za"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            @error('mail_from_address') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="mail_from_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">From Name</label>
                            <input type="text" id="mail_from_name" wire:model="mail_from_name" placeholder="NRAPA"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            @error('mail_from_name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-wrap gap-3">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">
                            Save Email Settings
                        </button>
                    </div>
                </form>

                <!-- Test Email Section -->
                <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-3">Send Test Email</h3>
                    <div class="flex gap-3">
                        <input type="email" wire:model="test_email" placeholder="test@example.com"
                            class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <button type="button" wire:click="sendTestEmail" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors whitespace-nowrap">
                            Send Test
                        </button>
                    </div>
                    @error('test_email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>
</div>

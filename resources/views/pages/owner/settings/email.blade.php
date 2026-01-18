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

    public string $test_email = '';
    public bool $showPassword = false;

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
        $this->test_email = auth()->user()->email;
    }

    public function saveEmailSettings(): void
    {
        $this->validate([
            'mail_mailer' => 'required|string|in:smtp,sendmail,mailgun,ses,postmark,log',
            'mail_host' => 'required_if:mail_mailer,smtp|string|max:255',
            'mail_port' => 'required_if:mail_mailer,smtp|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'required|string|in:tls,ssl,null',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
        ]);

        SystemSetting::set('mail_mailer', $this->mail_mailer, 'string', 'email', 'Mail driver');
        SystemSetting::set('mail_host', $this->mail_host, 'string', 'email', 'SMTP host');
        SystemSetting::set('mail_port', $this->mail_port, 'integer', 'email', 'SMTP port');
        SystemSetting::set('mail_username', $this->mail_username, 'string', 'email', 'SMTP username');
        SystemSetting::set('mail_password', $this->mail_password, 'string', 'email', 'SMTP password');
        SystemSetting::set('mail_encryption', $this->mail_encryption, 'string', 'email', 'SMTP encryption');
        SystemSetting::set('mail_from_address', $this->mail_from_address, 'string', 'email', 'From email address');
        SystemSetting::set('mail_from_name', $this->mail_from_name, 'string', 'email', 'From name');

        // Update runtime config
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

        session()->flash('success', 'Email settings saved successfully.');
    }

    public function sendTestEmail(): void
    {
        $this->validate([
            'test_email' => 'required|email',
        ]);

        try {
            // Apply settings temporarily
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
                </ul>

                <!-- Zoho Mail Preset -->
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Zoho Mail Settings</h3>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mb-2">Use these settings for Zoho Mail:</p>
                    <ul class="text-xs text-blue-600 dark:text-blue-400 space-y-1">
                        <li><strong>Host:</strong> smtp.zoho.com</li>
                        <li><strong>Port:</strong> 587 (TLS) or 465 (SSL)</li>
                        <li><strong>Encryption:</strong> TLS or SSL</li>
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
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">SMTP Configuration</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Configure outgoing email settings</p>
                    </div>
                </div>

                <form wire:submit="saveEmailSettings" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="mail_mailer" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Mail Driver</label>
                            <select id="mail_mailer" wire:model="mail_mailer"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="smtp">SMTP</option>
                                <option value="sendmail">Sendmail</option>
                                <option value="log">Log (Testing)</option>
                            </select>
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
                            <label for="mail_username" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SMTP Username</label>
                            <input type="text" id="mail_username" wire:model="mail_username" placeholder="your@email.com"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            @error('mail_username') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
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

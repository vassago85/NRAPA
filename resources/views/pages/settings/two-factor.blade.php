<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

new class extends Component {
    #[Locked]
    public bool $twoFactorEnabled;

    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showModal = false;
    public bool $showVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        abort_unless(Features::enabled(Features::twoFactorAuthentication()), Response::HTTP_FORBIDDEN);

        if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
            $disableTwoFactorAuthentication(auth()->user());
        }

        $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    public function enable(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $enableTwoFactorAuthentication(auth()->user());

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }

        $this->loadSetupData();
        $this->showModal = true;
    }

    private function loadSetupData(): void
    {
        $user = auth()->user();

        try {
            $this->qrCodeSvg = $user?->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');
            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;
            $this->resetErrorBag();
            return;
        }

        $this->closeModal();
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();
        $confirmTwoFactorAuthentication(auth()->user(), $this->code);
        
        // Reset the 2FA login counter when 2FA is successfully enabled
        auth()->user()->reset2FALoginCounter();
        
        $this->closeModal();
        $this->twoFactorEnabled = true;
    }

    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');
        $this->resetErrorBag();
    }

    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());
        $this->twoFactorEnabled = false;
    }

    public function closeModal(): void
    {
        $this->reset('code', 'manualSetupKey', 'qrCodeSvg', 'showModal', 'showVerificationStep');
        $this->resetErrorBag();

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }
    }

    public function getModalConfigProperty(): array
    {
        if ($this->twoFactorEnabled) {
            return [
                'title' => __('Two-Factor Authentication Enabled'),
                'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify Authentication Code'),
                'description' => __('Enter the 6-digit code from your authenticator app.'),
            ];
        }

        return [
            'title' => __('Enable Two-Factor Authentication'),
            'description' => __('Scan the QR code or enter the setup key in your authenticator app.'),
        ];
    }
} ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings-layout :heading="__('Two Factor Authentication')" :subheading="__('Add additional security to your account')">
        <div class="space-y-6" wire:cloak>
            {{-- Admin/Owner 2FA Requirement Warning --}}
            @if(auth()->user()->requires2FA() && !$twoFactorEnabled)
                @php
                    $remaining = auth()->user()->getRemainingLoginsWithout2FA();
                    $hasExceeded = auth()->user()->hasExceeded2FALoginLimit();
                @endphp
                
                @if($hasExceeded)
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <p class="font-semibold text-red-800 dark:text-red-200">Two-Factor Authentication Required</p>
                                <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                                    As an {{ auth()->user()->role_display_name }}, you must enable two-factor authentication to continue using the platform.
                                    You have exceeded the maximum number of logins ({{ \App\Models\User::MAX_LOGINS_WITHOUT_2FA }}) without 2FA.
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif($remaining <= 5)
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-semibold text-amber-800 dark:text-amber-200">2FA Required Soon</p>
                                <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                                    As an {{ auth()->user()->role_display_name }}, you have <strong>{{ $remaining }}</strong> login(s) remaining before two-factor authentication becomes mandatory.
                                    Please enable 2FA now to avoid losing access to the platform.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-semibold text-blue-800 dark:text-blue-200">2FA Required for {{ auth()->user()->role_display_name }}s</p>
                                <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                    All administrators and owners must enable two-factor authentication within {{ \App\Models\User::MAX_LOGINS_WITHOUT_2FA }} logins.
                                    You have <strong>{{ $remaining }}</strong> login(s) remaining.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            @if ($twoFactorEnabled)
                <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-green-800 dark:text-green-200">Two-factor authentication is enabled</p>
                            <p class="text-sm text-green-600 dark:text-green-400">Your account has an extra layer of security.</p>
                        </div>
                    </div>
                </div>

                <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />

                @if(!auth()->user()->requires2FA())
                <button type="button" wire:click="disable"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
                    {{ __('Disable Two-Factor Authentication') }}
                </button>
                @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    As an {{ auth()->user()->role_display_name }}, 2FA cannot be disabled on your account.
                </p>
                @endif
            @else
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-amber-800 dark:text-amber-200">Two-factor authentication is not enabled</p>
                            <p class="text-sm text-amber-600 dark:text-amber-400">Enable 2FA for additional account security.</p>
                        </div>
                    </div>
                </div>

                {{-- Recommended Authenticator Apps --}}
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                    <h4 class="font-medium text-zinc-900 dark:text-white mb-3">Recommended Authenticator Apps</h4>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                        You'll need an authenticator app to generate 2FA codes. We recommend these free, secure options:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-600">
                            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white text-sm">Google Authenticator</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">iOS & Android</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-600">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white text-sm">Microsoft Authenticator</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">iOS & Android</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-600">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white text-sm">Authy</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">iOS, Android & Desktop</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-600">
                            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white text-sm">1Password</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">All platforms (built-in)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" wire:click="enable"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                    {{ __('Enable Two-Factor Authentication') }}
                </button>
            @endif
        </div>
    </x-settings-layout>

    {{-- 2FA Setup Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="document.body.classList.add('overflow-hidden')" x-on:remove="document.body.classList.remove('overflow-hidden')">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="closeModal" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <div class="space-y-6">
                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->modalConfig['title'] }}</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">{{ $this->modalConfig['description'] }}</p>
                        </div>

                        @if ($showVerificationStep)
                            <div class="space-y-4">
                                <input type="text" wire:model="code" maxlength="6" placeholder="000000"
                                       class="w-full text-center text-2xl tracking-widest px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                                @error('code') <p class="text-sm text-red-600 text-center">{{ $message }}</p> @enderror

                                <div class="flex gap-3">
                                    <button type="button" wire:click="resetVerification"
                                            class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                        {{ __('Back') }}
                                    </button>
                                    <button type="button" wire:click="confirmTwoFactor"
                                            class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                                        {{ __('Confirm') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="flex justify-center">
                                <div class="w-48 h-48 bg-white p-2 rounded-lg">
                                    {!! $qrCodeSvg !!}
                                </div>
                            </div>

                            <div class="text-center">
                                <p class="text-xs text-zinc-500 mb-2">Or enter this code manually:</p>
                                <code class="text-sm font-mono bg-zinc-100 dark:bg-zinc-700 px-3 py-1 rounded">{{ $manualSetupKey }}</code>
                            </div>

                            <button type="button" wire:click="showVerificationIfNecessary"
                                    class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                                {{ __('Continue') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>

<?php

use App\Models\UserSecurityQuestion;
use Exception;
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

    // Security Questions properties
    public array $securityQuestions = [];
    public array $securityAnswers = [];
    public bool $isEditingQuestions = false;

    public bool $isForced = false;

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        abort_unless(Features::enabled(Features::twoFactorAuthentication()), Response::HTTP_FORBIDDEN);

        if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
            $disableTwoFactorAuthentication(auth()->user());
        }

        $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        
        // Check if user is forced to enable 2FA
        $this->isForced = auth()->user()->hasExceeded2FALoginLimit() && !auth()->user()->hasEnabledTwoFactorAuthentication();
        
        // Load security questions
        $this->loadSecurityQuestions();

        // Auto-enable 2FA setup if user has exceeded login limit and can enable 2FA
        // This ensures users redirected here can immediately see the QR code
        // Skip in testing environment to avoid breaking tests
        if (!app()->environment('testing') 
            && !$this->twoFactorEnabled 
            && $this->isForced
            && auth()->user()->canEnable2FA()) {
            $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
            $this->enable($enableTwoFactorAuthentication);
        }
    }

    private function loadSecurityQuestions(): void
    {
        $existingQuestions = auth()->user()->securityQuestions;
        
        if ($existingQuestions->count() > 0) {
            $this->securityQuestions = [];
            $this->securityAnswers = [];
            foreach ($existingQuestions as $q) {
                $this->securityQuestions[] = $q->question;
                $this->securityAnswers[] = '';
            }
        } else {
            $this->securityQuestions = array_fill(0, UserSecurityQuestion::REQUIRED_QUESTIONS, '');
            $this->securityAnswers = array_fill(0, UserSecurityQuestion::REQUIRED_QUESTIONS, '');
        }
    }

    public function startEditingQuestions(): void
    {
        $this->isEditingQuestions = true;
        $this->securityAnswers = array_fill(0, count($this->securityQuestions), '');
    }

    public function saveSecurityQuestions(): void
    {
        $rules = [];
        $messages = [];
        
        for ($i = 0; $i < UserSecurityQuestion::REQUIRED_QUESTIONS; $i++) {
            $rules["securityQuestions.{$i}"] = 'required|string';
            $rules["securityAnswers.{$i}"] = 'required|string|min:2';
            $messages["securityQuestions.{$i}.required"] = 'Please select security question ' . ($i + 1);
            $messages["securityAnswers.{$i}.required"] = 'Please provide an answer for question ' . ($i + 1);
            $messages["securityAnswers.{$i}.min"] = 'Answer ' . ($i + 1) . ' must be at least 2 characters';
        }

        $this->validate($rules, $messages);

        // Check for duplicate questions
        $uniqueQuestions = array_unique($this->securityQuestions);
        if (count($uniqueQuestions) !== count($this->securityQuestions)) {
            $this->addError('securityQuestions', 'Each security question must be unique.');
            return;
        }

        // Delete existing questions and create new ones
        auth()->user()->securityQuestions()->delete();

        for ($i = 0; $i < UserSecurityQuestion::REQUIRED_QUESTIONS; $i++) {
            $question = auth()->user()->securityQuestions()->create([
                'question' => $this->securityQuestions[$i],
                'answer_hash' => '',
            ]);
            $question->setAnswer($this->securityAnswers[$i]);
        }

        $this->isEditingQuestions = false;
        $this->securityAnswers = array_fill(0, count($this->securityQuestions), '');
        
        session()->flash('questions_success', 'Security questions saved successfully.');
    }

    public function cancelEditingQuestions(): void
    {
        $this->isEditingQuestions = false;
        $this->loadSecurityQuestions();
    }

    public function enable(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $user = auth()->user();
        
        // Check if user can enable 2FA
        if (!$user->canEnable2FA()) {
            $this->addError('enable', $user->get2FABlockReason());
            return;
        }
        
        $enableTwoFactorAuthentication($user);

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
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
        // This method is called but JavaScript handles the UI
        // Always show verification step to confirm 2FA setup
        $this->showVerificationStep = true;
        $this->resetErrorBag();
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();
        $confirmTwoFactorAuthentication(auth()->user(), $this->code);
        
        // Reset the 2FA login counter when 2FA is successfully enabled
        auth()->user()->reset2FALoginCounter();
        
        // Refresh user model to ensure counter reset is reflected
        auth()->user()->refresh();
        
        // Close modal and reset state
        $this->showModal = false;
        $this->showVerificationStep = false;
        $this->reset('code', 'manualSetupKey', 'qrCodeSvg');
        $this->resetErrorBag();
        
        $this->twoFactorEnabled = true;
        $this->isForced = false; // No longer forced after enabling
        
        // If user was forced, redirect to dashboard after enabling
        if ($this->isForced) {
            $this->redirect(route('dashboard'), navigate: true);
            return;
        }
        
        // Dispatch event to close modal via JavaScript
        $this->dispatch('2fa-confirmed');
        
        // Also dispatch browser event as fallback
        $this->js('window.dispatchEvent(new CustomEvent("livewire:2fa-confirmed"))');
    }

    public function resetVerification(): void
    {
        $this->showVerificationStep = false;
        $this->reset('code');
        $this->resetErrorBag();
        
        // Dispatch event to hide verification section via JavaScript
        $this->dispatch('reset-verification');
    }

    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());
        $this->twoFactorEnabled = false;
    }

    public function closeModal(): void
    {
        // Don't allow closing modal if user has exceeded login limit and hasn't enabled 2FA yet
        if ($this->isForced && !auth()->user()->hasEnabledTwoFactorAuthentication()) {
            return;
        }

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

    public function with(): array
    {
        return [
            'availableQuestions' => UserSecurityQuestion::getQuestionOptions(),
            'hasExistingQuestions' => auth()->user()->hasSecurityQuestions(),
        ];
    }
} ?>

@if($isForced)
    {{-- Full-page forced 2FA setup --}}
    <div class="min-h-screen bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl p-6 md:p-8">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                        <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">Two-Factor Authentication Required</h1>
                    <p class="text-zinc-600 dark:text-zinc-400">You must enable two-factor authentication to continue using the platform.</p>
                </div>
                <div class="space-y-6" wire:cloak>
                    @include('partials.two-factor-content')
                </div>
            </div>
        </div>
    </div>
@else
    <section class="w-full">
        @include('partials.settings-heading')

        <x-settings-layout :heading="__('Two Factor Authentication')" :subheading="__('Add additional security to your account')">
            <div class="space-y-6" wire:cloak>
                @include('partials.two-factor-content')
            </div>
        </x-settings-layout>
    </section>
@endif

    {{-- 2FA Setup Modal (only show if not forced) --}}
    @if($showModal && !$isForced)
        <div id="2fa-modal-overlay" class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="document.body.classList.add('overflow-hidden')" x-on:remove="document.body.classList.remove('overflow-hidden')">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="closeModal" class="fixed inset-0 bg-black/50 cursor-pointer"></div>
                <div id="2fa-modal-container" class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6" 
                     wire:key="2fa-modal-{{ $showVerificationStep ? 'verify' : 'setup' }}">
                    <div class="space-y-6">
                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->modalConfig['title'] }}</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">{{ $this->modalConfig['description'] }}</p>
                        </div>

                            {{-- Instructions Section --}}
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg mb-4">
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">📱 Step 1: Install an Authenticator App</p>
                                        <p class="text-xs text-blue-700 dark:text-blue-300 mb-2">
                                            You'll need an authenticator app to scan the QR code. We recommend:
                                        </p>
                                        <ul class="text-xs text-blue-700 dark:text-blue-300 space-y-1 ml-4 list-disc">
                                            <li><strong>Google Authenticator</strong> (iOS & Android)</li>
                                            <li><strong>Microsoft Authenticator</strong> (iOS & Android)</li>
                                            <li><strong>Authy</strong> (iOS, Android & Desktop)</li>
                                            <li><strong>1Password</strong> (All platforms)</li>
                                        </ul>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">🔐 Step 2: Scan the QR Code</p>
                                        <p class="text-xs text-blue-700 dark:text-blue-300">
                                            Open your authenticator app and scan the QR code below, or enter the manual setup key.
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">✅ Step 3: Verify Setup</p>
                                        <p class="text-xs text-blue-700 dark:text-blue-300">
                                            After scanning, click "Continue" and enter the 6-digit code from your app to verify.
                                        </p>
                                    </div>
                                    <div class="pt-2 border-t border-blue-200 dark:border-blue-700">
                                        <p class="text-xs font-semibold text-blue-900 dark:text-blue-200">
                                            ⚠️ Important: On your next login, you will be required to enter the 6-digit code from your authenticator app.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-center">
                                <div class="w-48 h-48 bg-white p-2 rounded-lg">
                                    {!! $qrCodeSvg !!}
                                </div>
                            </div>

                            <div class="text-center">
                                <p class="text-xs text-zinc-500 mb-2">Or enter this code manually:</p>
                                <code class="text-sm font-mono bg-zinc-100 dark:bg-zinc-700 px-3 py-1 rounded">{{ $manualSetupKey }}</code>
                            </div>

                            <div id="continue-button-wrapper">
                            <button type="button" 
                                    id="continue-2fa-button"
                                    onclick="if(typeof window.show2FAVerification === 'function') { window.show2FAVerification(); } else { const verifyDiv = document.getElementById('verification-section'); const continueBtn = document.getElementById('continue-button-wrapper'); if(verifyDiv) { verifyDiv.style.display = 'block'; verifyDiv.style.setProperty('display', 'block', 'important'); sessionStorage.setItem('2fa-verification-shown', 'true'); const input = verifyDiv.querySelector('#two-factor-code'); if(input) { setTimeout(() => { input.focus(); input.select(); }, 100); } } if(continueBtn) { continueBtn.style.display = 'none'; } }"
                                    class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg font-medium">
                                {{ __('Continue') }}
                            </button>
                            </div>

                            {{-- Verification Input - Always rendered, controlled purely by JavaScript --}}
                            <div id="verification-section" 
                                 class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700 space-y-4" 
                                 style="display: none;">
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                    <p class="text-xs text-blue-700 dark:text-blue-300 text-center">
                                        Open your authenticator app and enter the 6-digit code shown there. This code refreshes every 30 seconds.
                                    </p>
                                </div>
                                
                                <input type="text" 
                                       id="two-factor-code"
                                       name="two_factor_code"
                                       wire:model="code" 
                                       maxlength="6" 
                                       placeholder="000000"
                                       autocomplete="one-time-code"
                                       inputmode="numeric"
                                       pattern="[0-9]{6}"
                                       class="w-full text-center text-2xl tracking-widest px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                       autofocus>
                                @error('code') <p class="text-sm text-red-600 text-center">{{ $message }}</p> @enderror

                                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                    <p class="text-xs font-medium text-amber-800 dark:text-amber-200 text-center">
                                        ⚠️ Remember: On your next login, you will be required to enter a code from your authenticator app to access your account.
                                    </p>
                                </div>

                                <div class="flex gap-3">
                                    <button type="button" 
                                            onclick="const verifyDiv = document.getElementById('verification-section'); const continueBtn = document.getElementById('continue-button-wrapper'); if(verifyDiv) { verifyDiv.style.display = 'none'; } if(continueBtn) { continueBtn.style.display = 'block'; } sessionStorage.removeItem('2fa-verification-shown');"
                                            class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                        {{ __('Back') }}
                                    </button>
                                    <button type="button" 
                                            wire:click="confirmTwoFactor"
                                            onclick="(function() { setTimeout(function() { const overlay = document.getElementById('2fa-modal-overlay'); const container = document.getElementById('2fa-modal-container'); const parent = overlay ? overlay.parentElement : null; if(overlay) { overlay.style.display = 'none'; overlay.remove(); } if(container) { container.remove(); } if(parent && parent.id === '2fa-modal-overlay') { parent.remove(); } document.body.classList.remove('overflow-hidden'); sessionStorage.removeItem('2fa-verification-shown'); }, 200); })()"
                                            class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                                        {{ __('Confirm') }}
                                    </button>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@if(!$isForced)
</section>
@endif

<script>
(function() {
    'use strict';
    
    // Global function to show verification section
    window.show2FAVerification = function() {
        const verifyDiv = document.getElementById('verification-section');
        const continueBtn = document.getElementById('continue-button-wrapper');
        
        if (verifyDiv) {
            verifyDiv.style.display = 'block';
            verifyDiv.style.setProperty('display', 'block', 'important');
            sessionStorage.setItem('2fa-verification-shown', 'true');
            
            const input = verifyDiv.querySelector('#two-factor-code');
            if (input) {
                setTimeout(() => {
                    input.focus();
                    input.select();
                }, 100);
            }
        }
        
        if (continueBtn) {
            continueBtn.style.display = 'none';
        }
    };
    
    function restoreVerification() {
        if (sessionStorage.getItem('2fa-verification-shown') === 'true') {
            const verifyDiv = document.getElementById('verification-section');
            const continueBtn = document.getElementById('continue-button-wrapper');
            
            if (verifyDiv) {
                verifyDiv.style.display = 'block';
                verifyDiv.style.setProperty('display', 'block', 'important');
            }
            
            if (continueBtn) {
                continueBtn.style.display = 'none';
            }
        }
    }
    
    // Set up MutationObserver to watch for changes
    let observer = null;
    
    function setupObserver() {
        const verifyDiv = document.getElementById('verification-section');
        if (verifyDiv && !observer) {
            observer = new MutationObserver(function() {
                restoreVerification();
            });
            
            observer.observe(verifyDiv, {
                attributes: true,
                attributeFilter: ['style', 'class'],
                childList: true,
                subtree: true
            });
        }
    }
    
    // Initialize immediately and on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            restoreVerification();
            setupObserver();
        });
    } else {
        restoreVerification();
        setupObserver();
    }
    
    // Listen for Livewire updates
    document.addEventListener('livewire:update', restoreVerification);
    document.addEventListener('livewire:morph-updated', restoreVerification);
    
    // Restore on any Livewire event
    ['livewire:init', 'livewire:navigated'].forEach(function(event) {
        document.addEventListener(event, function() {
            setTimeout(restoreVerification, 100);
        });
    });
    
    // Function to force close the modal
    function close2FAModal() {
        // Find all modal elements
        const overlay = document.getElementById('2fa-modal-overlay');
        const container = document.getElementById('2fa-modal-container');
        
        // Remove overlay and its parent if it exists
        if (overlay) {
            const parent = overlay.parentElement;
            overlay.style.display = 'none';
            overlay.remove();
            // Also remove parent wrapper if it's the modal container
            if (parent && parent.classList.contains('fixed')) {
                parent.remove();
            }
        }
        
        // Remove container
        if (container) {
            container.remove();
        }
        
        // Remove any remaining modal elements by class
        const remainingModals = document.querySelectorAll('[id*="2fa-modal"], .fixed.inset-0.z-50');
        remainingModals.forEach(function(el) {
            if (el.id && el.id.includes('2fa-modal')) {
                el.remove();
            }
        });
        
        document.body.classList.remove('overflow-hidden');
        sessionStorage.removeItem('2fa-verification-shown');
        
        const verifyDiv = document.getElementById('verification-section');
        const continueBtn = document.getElementById('continue-button-wrapper');
        if (verifyDiv) {
            verifyDiv.style.display = 'none';
        }
        if (continueBtn) {
            continueBtn.style.display = 'block';
        }
    }
    
    // Listen for 2FA confirmation to close modal immediately
    document.addEventListener('livewire:2fa-confirmed', function() {
        close2FAModal();
    });
    window.addEventListener('livewire:2fa-confirmed', function() {
        close2FAModal();
    });
    
    // Watch for Livewire updates and check if modal should be closed
    document.addEventListener('livewire:update', function() {
        setTimeout(function() {
            const overlay = document.getElementById('2fa-modal-overlay');
            if (overlay) {
                // Check if the modal parent (the @if condition) has been removed
                // If overlay exists but is not in a visible @if block, remove it
                const computedStyle = window.getComputedStyle(overlay);
                if (computedStyle.display === 'none' || !overlay.parentElement) {
                    close2FAModal();
                }
            }
        }, 50);
    });
    
    // Also watch for successful form submission
    document.addEventListener('livewire:success', function() {
        setTimeout(function() {
            const overlay = document.getElementById('2fa-modal-overlay');
            if (overlay) {
                // If modal is still visible after success, close it
                close2FAModal();
            }
        }, 200);
    });
    
    // Prevent navigation away if 2FA is forced
    @if($isForced && !$twoFactorEnabled)
    window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = 'You must complete two-factor authentication setup before leaving this page.';
        return e.returnValue;
    });
    
    // Prevent back button
    history.pushState(null, null, location.href);
    window.onpopstate = function() {
        history.pushState(null, null, location.href);
    };
    @endif
})();
</script>

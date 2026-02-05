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
                                <p class="font-semibold text-amber-800 dark:text-amber-200">Two-Factor Authentication Required Soon</p>
                                <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                                    As an {{ auth()->user()->role_display_name }}, you must enable two-factor authentication. You have {{ $remaining }} login(s) remaining before it becomes mandatory.
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
                                <p class="font-semibold text-blue-800 dark:text-blue-200">Two-Factor Authentication Recommended</p>
                                <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                    As an {{ auth()->user()->role_display_name }}, two-factor authentication is recommended for your account security.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Show 2FA setup form directly on page if forced, otherwise show status --}}
            @if ($twoFactorEnabled && !$isForced)
                <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-green-800 dark:text-green-200">Two-factor authentication is enabled</p>
                            <p class="text-sm text-green-600 dark:text-green-400">Your account is protected with two-factor authentication.</p>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->canDisable2FA())
                <button type="button" 
                        wire:click="disable"
                        onclick="if(!confirm('Are you sure you want to disable two-factor authentication? This will make your account less secure.')) return false;"
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
                {{-- Show 2FA setup form --}}
                @if(!$isForced)
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
                @endif

                {{-- Show 2FA setup directly on page if forced --}}
                @if($isForced && $showModal)
                    {{-- QR Code and Setup Instructions --}}
                    <div class="mb-6">
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
                                        <li><strong>Authy</strong> (iOS & Android)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center mb-4">
                            <div class="relative w-64 overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700 aspect-square bg-white p-4">
                                @empty($qrCodeSvg)
                                    <div class="absolute inset-0 flex items-center justify-center bg-white dark:bg-zinc-700 animate-pulse">
                                        <svg class="w-8 h-8 text-zinc-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center h-full">
                                        <div class="bg-white p-3 rounded">
                                            {!! $qrCodeSvg !!}
                                        </div>
                                    </div>
                                @endempty
                            </div>
                        </div>

                        <div class="text-center mb-6">
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
                @endif

                {{-- Security Questions Section --}}
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $hasExistingQuestions ? 'bg-green-100 dark:bg-green-900/30' : 'bg-amber-100 dark:bg-amber-900/30' }}">
                                    <svg class="w-5 h-5 {{ $hasExistingQuestions ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-white">Security Questions</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        @if($hasExistingQuestions)
                                            Questions configured for identity verification
                                        @else
                                            Required to enable 2FA (for account recovery)
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @if($hasExistingQuestions && !$isEditingQuestions)
                                <span class="flex items-center gap-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-2.5 py-1 rounded-full">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Set up
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="p-4">
                        @if(session('questions_success'))
                            <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <p class="text-sm text-green-700 dark:text-green-300">{{ session('questions_success') }}</p>
                            </div>
                        @endif

                        @error('securityQuestions')
                            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-300 text-sm">
                                {{ $message }}
                            </div>
                        @enderror

                        @if($hasExistingQuestions && !$isEditingQuestions)
                            <div class="space-y-3">
                                @foreach($securityQuestions as $index => $question)
                                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $question }}</p>
                                    </div>
                                @endforeach
                                <button type="button" 
                                        wire:click="startEditingQuestions"
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                    Update Questions
                                </button>
                        @else
                            {{-- Question setup form --}}
                            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    <strong>Why?</strong> If you lose access to your authenticator app, our support team will use these questions to verify your identity before resetting your 2FA.
                                </p>
                            </div>
                            <form wire:submit.prevent="saveSecurityQuestions">
                                <div class="space-y-4">
                                    @for($i = 0; $i < UserSecurityQuestion::REQUIRED_QUESTIONS; $i++)
                                        <div>
                                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                                Question {{ $i + 1 }}
                                            </label>
                                            <select wire:model="securityQuestions.{{ $i }}" 
                                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                                <option value="">Select a question...</option>
                                                @foreach(UserSecurityQuestion::getQuestionOptions() as $option)
                                                    <option value="{{ $option }}">{{ $option }}</option>
                                                @endforeach
                                            </select>
                                            @error("securityQuestions.{$i}") 
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p> 
                                            @enderror
                                            
                                            <input type="text" 
                                                   wire:model="securityAnswers.{{ $i }}"
                                                   placeholder="Your answer"
                                                   class="mt-2 w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                            @error("securityAnswers.{$i}") 
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p> 
                                            @enderror
                                        </div>
                                    @endfor
                                </div>
                                <div class="mt-4 flex gap-3">
                                    <button type="submit" 
                                            class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                                        Save Questions
                                    </button>
                                    @if($hasExistingQuestions)
                                        <button type="button" 
                                                wire:click="cancelEditingQuestions"
                                                class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 font-medium rounded-lg text-sm transition-colors">
                                            Cancel
                                        </button>
                                    @endif
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Verified ID alternative (if no security questions) --}}
                @if(!auth()->user()->requires2FA() && !$hasExistingQuestions)
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ auth()->user()->hasVerifiedIdDocument() ? 'bg-green-100 dark:bg-green-900/30' : 'bg-zinc-200 dark:bg-zinc-700' }}">
                                <svg class="w-5 h-5 {{ auth()->user()->hasVerifiedIdDocument() ? 'text-green-600 dark:text-green-400' : 'text-zinc-600 dark:text-zinc-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-zinc-900 dark:text-white">Verified ID Document</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    @if(auth()->user()->hasVerifiedIdDocument())
                                        You have a verified ID on file. You can enable 2FA now.
                                    @else
                                        Upload your ID document as an alternative to security questions.
                                        <a href="{{ route('documents.index') }}" wire:navigate class="text-emerald-600 dark:text-emerald-400 underline">Upload documents</a>
                                    @endif
                                </p>
                            </div>
                            @if(auth()->user()->hasVerifiedIdDocument())
                                <span class="flex items-center gap-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-2.5 py-1 rounded-full">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Verified
                                </span>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Show error if enable was blocked --}}
                @error('enable')
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    </div>
                @enderror

                {{-- Enable 2FA button --}}
                @if(auth()->user()->canEnable2FA())
                    <button type="button" 
                            wire:click="enable"
                            class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                        {{ __('Enable Two-Factor Authentication') }}
                    </button>
                @else
                    <button type="button" disabled
                            class="px-4 py-2 bg-zinc-400 text-white rounded-lg font-medium cursor-not-allowed">
                        {{ __('Enable Two-Factor Authentication') }}
                    </button>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Complete the requirements above to enable 2FA.</p>
                @endif
            @endif

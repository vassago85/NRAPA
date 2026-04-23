<?php

use App\Mail\PaymentInstructions;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\SystemSetting;
use App\Services\NtfyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Apply for Membership')] class extends Component {
    public ?int $selectedTypeId = null;
    public bool $agreedToTerms = false;
    public bool $agreedToDeclaration = false;

    public function mount(?string $type = null): void
    {
        // Pre-select based on URL parameter (slug) or use basic type
        if ($type) {
            $preselected = MembershipType::active()->displayOnSignup()->where('slug', $type)->first();
            if ($preselected) {
                $this->selectedTypeId = $preselected->id;
                return;
            }
        }

        // Default to basic membership
        $basic = MembershipType::active()->displayOnSignup()->where('slug', 'basic')->first();
        if ($basic) {
            $this->selectedTypeId = $basic->id;
            return;
        }

        // Fall back to featured type, then first available
        $featured = MembershipType::active()->displayOnSignup()->featured()->first();
        if ($featured) {
            $this->selectedTypeId = $featured->id;
            return;
        }

        $firstType = MembershipType::active()->displayOnSignup()->ordered()->first();
        if ($firstType) {
            $this->selectedTypeId = $firstType->id;
        }
    }

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[Computed]
    public function membershipTypes()
    {
        return MembershipType::active()->displayOnSignup()->ordered()->get();
    }

    #[Computed]
    public function basicType()
    {
        return $this->membershipTypes->firstWhere('slug', 'basic');
    }

    #[Computed]
    public function dedicatedTypes()
    {
        return $this->membershipTypes->filter(fn($type) => $type->dedicated_type !== null);
    }

    #[Computed]
    public function selectedType()
    {
        return $this->selectedTypeId
            ? MembershipType::find($this->selectedTypeId)
            : null;
    }

    #[Computed]
    public function signupTotal(): float
    {
        if (!$this->selectedType) {
            return 0;
        }

        // For basic: initial_price
        // For dedicated: basic initial_price + dedicated upgrade_price
        if ($this->selectedType->hasUpgradeFee()) {
            $basicInitial = $this->basicType?->initial_price ?? 0;
            return (float) $basicInitial + (float) $this->selectedType->upgrade_price;
        }

        return (float) $this->selectedType->initial_price;
    }

    #[Computed]
    public function canApply()
    {
        // Check for pending applications first
        $hasPending = $this->user->memberships()
            ->whereIn('status', ['applied', 'approved'])
            ->exists();

        if ($hasPending) {
            return false;
        }

        // Check for active membership
        $activeMembership = $this->user->memberships()
            ->where('status', 'active')
            ->first();

        if (!$activeMembership) {
            return true; // No active membership, can apply
        }

        // If the active membership has lapsed beyond the grace period, allow re-application
        if ($activeMembership->isExpiredBeyondGracePeriod()) {
            return true;
        }

        return false;
    }

    #[Computed]
    public function existingMembership()
    {
        return $this->user->memberships()
            ->whereIn('status', ['applied', 'approved', 'active'])
            ->with('type')
            ->first();
    }

    public function selectType(int $typeId): void
    {
        $this->selectedTypeId = $typeId;
        $this->agreedToDeclaration = false;
    }

    #[Computed]
    public function isDedicatedType(): bool
    {
        return $this->selectedType?->allows_dedicated_status ?? false;
    }

    public function submit(): void
    {
        if (!$this->canApply) {
            $this->addError('membership', 'You already have an active or pending membership.');
            return;
        }

        $rules = [
            'selectedTypeId' => ['required', 'exists:membership_types,id'],
            'agreedToTerms' => ['accepted'],
        ];
        $messages = [
            'selectedTypeId.required' => 'Please select a membership type.',
            'agreedToTerms.accepted' => 'You must agree to the terms and conditions.',
        ];

        if ($this->isDedicatedType) {
            $rules['agreedToDeclaration'] = ['accepted'];
            $messages['agreedToDeclaration.accepted'] = 'You must accept the Dedicated Status Declaration.';
        }

        $this->validate($rules, $messages);

        $membership = Membership::create([
            'user_id' => $this->user->id,
            'membership_type_id' => $this->selectedTypeId,
            'status' => 'applied',
            'applied_at' => now(),
            'source' => 'web',
            'dedicated_declaration_accepted_at' => $this->isDedicatedType ? now() : null,
        ]);

        // Send payment instructions email
        $this->sendPaymentInstructionsEmail($membership);

        // Notify admins of new application
        try {
            $typeName = $membership->type?->name ?? 'Unknown';
            app(NtfyService::class)->notifyAdmins(
                'new_member',
                'New Membership Application',
                "{$this->user->name} applied for {$typeName}.",
                'default'
            );
        } catch (\Exception $e) {
            // Non-critical — don't block the application
        }

        session()->flash('success', 'Your membership application has been submitted! Payment instructions have been sent to your email.');

        $this->redirect(route('membership.show', $membership), navigate: true);
    }

    protected function sendPaymentInstructionsEmail(Membership $membership): void
    {
        try {
            $bankAccount = SystemSetting::getBankAccount();

            Mail::to($membership->user->email)
                ->send(new PaymentInstructions(
                    $membership->load('type', 'user'),
                    $bankAccount,
                    $membership->payment_reference
                ));

            $membership->update(['payment_email_sent_at' => now()]);
        } catch (\Exception $e) {
            \Log::error('Failed to send payment instructions email', [
                'membership_id' => $membership->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}; ?>

<div>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Apply for Membership</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Choose your membership type and submit your application.
            </p>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">

    @if(!$this->canApply)
    {{-- Already has membership --}}
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
        <div class="flex items-start gap-3">
            <svg class="size-5 flex-shrink-0 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <div class="flex-1">
                <h3 class="font-semibold text-amber-800 dark:text-amber-200">Existing Membership Found</h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    You already have a {{ $this->existingMembership->status }} membership application
                    ({{ $this->existingMembership->type->name }}).
                </p>
                <div class="mt-3">
                    <a href="{{ route('membership.index') }}" wire:navigate class="inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-900/50 dark:text-amber-200 dark:hover:bg-amber-900">
                        View My Membership
                    </a>
                </div>
            </div>
        </div>
    </div>
    @else
    <form wire:submit="submit" class="space-y-6">
        {{-- Membership Type Selection --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Select Membership Type</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Choose basic for occasional use, or a dedicated status for full benefits.</p>
            </div>

            <div class="p-6">
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($this->membershipTypes as $type)
                    <div
                        wire:click="selectType({{ $type->id }})"
                        class="relative cursor-pointer rounded-xl border-2 p-5 transition-all
                            {{ $this->selectedTypeId === $type->id
                                ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-950/20'
                                : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}"
                    >
                        @if($this->selectedTypeId === $type->id)
                        <div class="absolute right-3 top-3">
                            <svg class="size-6 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        @endif

                        <div class="mb-3 flex items-center gap-3">
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $type->name }}</h3>
                            @if($type->is_featured)
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Popular</span>
                            @endif
                            @if($type->isBasic())
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">Starter</span>
                            @endif
                        </div>

                        <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $type->description }}
                        </p>

                        <div class="space-y-2 text-sm">
                            @if($type->isBasic())
                            {{-- Basic type: show sign-up fee --}}
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Sign-up Fee</span>
                                <span class="font-semibold text-zinc-900 dark:text-white">
                                    R{{ number_format($type->initial_price, 2) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Annual Renewal</span>
                                <span class="text-zinc-900 dark:text-white">
                                    R{{ number_format($type->renewal_price, 2) }}<span class="font-normal text-zinc-500">/year</span>
                                </span>
                            </div>
                            @else
                            {{-- Dedicated type: show total sign-up fee + renewal --}}
                            @php $dedicatedSignup = ($this->basicType?->initial_price ?? 0) + ($type->upgrade_price ?? 0); @endphp
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Sign-up Fee</span>
                                <span class="font-semibold text-zinc-900 dark:text-white">
                                    R{{ number_format($dedicatedSignup, 2) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Annual Renewal</span>
                                <span class="text-zinc-900 dark:text-white">
                                    R{{ number_format($type->renewal_price, 2) }}<span class="font-normal text-zinc-500">/year</span>
                                </span>
                            </div>
                            @endif

                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Duration</span>
                                <span class="text-zinc-900 dark:text-white">
                                    @if($type->isLifetime())
                                        Lifetime
                                    @else
                                        {{ $type->duration_months }} months
                                    @endif
                                </span>
                            </div>
                            @if($type->allows_dedicated_status)
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Dedicated Status</span>
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $type->dedicated_type === 'both' ? 'Hunter & Sport' : ucfirst($type->dedicated_type ?? 'Eligible') }}
                                </span>
                            </div>
                            @endif
                            @if($type->requires_knowledge_test)
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Knowledge Test</span>
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">Required</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @error('selectedTypeId')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Application Summary --}}
        @if($this->selectedType)
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Application Summary</h2>
            </div>

            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Membership Type</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $this->selectedType->name }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">Sign-up Fee</span>
                        <span class="text-zinc-900 dark:text-white">R{{ number_format($this->signupTotal, 2) }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Applicant</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $this->user->name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Email</span>
                        <span class="text-zinc-900 dark:text-white">{{ $this->user->email }}</span>
                    </div>
                    <hr class="border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between text-lg">
                        <span class="font-semibold text-zinc-900 dark:text-white">Amount Due</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">R{{ number_format($this->signupTotal, 2) }}</span>
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Payment details will be sent to your email after submitting your application.
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Dedicated Status Declaration --}}
        @if($this->isDedicatedType)
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="border-b border-amber-200 p-6 dark:border-amber-800">
                <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Dedicated Status Declaration</h2>
                <p class="text-sm text-amber-700 dark:text-amber-300">Please read and accept the following declaration before submitting your application.</p>
            </div>
            <div class="p-6">
                <div class="mb-4 max-h-64 overflow-y-auto rounded-lg border border-amber-200 bg-white p-4 text-sm leading-relaxed text-zinc-700 dark:border-amber-700 dark:bg-zinc-800 dark:text-zinc-300">
                    <p class="mb-3">I realise I may not practise a trade related to hunting on the grounds that I possess Dedicated Hunter Status in terms of Section 16 of the Firearms Control Act, 60 of 2000.</p>

                    <p class="mb-3">I undertake to submit proof of my hunting and/or sport shooting activities before the end of October annually.</p>

                    <p class="mb-3">I realise it is my own responsibility to maintain the above and to continue to be involved in hunting or branch related activities. At the time of renewal of my annual membership, upon request I shall be able to present proof of my involvement in hunting and/or sport shooting activities during the previous 12 months, and that the NRAPA <strong>MUST</strong> and <strong>SHALL</strong> inform the SAPS should I not comply herewith.</p>

                    <p class="mb-3">I acknowledge that in such event I will lose my Dedicated Status and I will have no claim whatsoever against the NRAPA or its management or any of its officials. The responsibility of meeting the conditions of maintaining my up-to-date Status and to stay active as prescribed in the Dedicated Status Manual, lies solely with me.</p>

                    <p class="font-semibold">I have read the above and understand the contents and have made myself aware of the contents and requirements of the Dedicated Status Manual.</p>
                </div>

                <label class="flex items-start gap-3">
                    <input type="checkbox" wire:model.live="agreedToDeclaration" class="mt-1 size-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500 dark:border-amber-600 dark:bg-zinc-700">
                    <span class="text-sm font-medium text-amber-800 dark:text-amber-200">I accept the Dedicated Status Declaration above</span>
                </label>
                @error('agreedToDeclaration')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>
        @endif

        {{-- Terms and Submit --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="p-6">
                <div class="space-y-4">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" wire:model.live="agreedToTerms" class="mt-1 size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">I agree to the NRAPA membership <a href="{{ route('terms-and-conditions') }}" target="_blank" class="text-blue-600 dark:text-blue-400 underline hover:text-blue-800 dark:hover:text-blue-300">terms and conditions</a>, code of conduct, and <a href="{{ route('privacy-policy') }}" target="_blank" class="text-blue-600 dark:text-blue-400 underline hover:text-blue-800 dark:hover:text-blue-300">privacy policy</a>.</span>
                    </label>
                    @error('agreedToTerms')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-between border-t border-zinc-200 p-6 dark:border-zinc-700">
                <a href="{{ route('membership.index') }}" wire:navigate class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark disabled:cursor-not-allowed disabled:opacity-50 transition-colors" {{ (!$this->agreedToTerms || ($this->isDedicatedType && !$this->agreedToDeclaration)) ? 'disabled' : '' }}>
                    Submit Application
                </button>
            </div>
        </div>
    </form>
    @endif
    </div>
</div>

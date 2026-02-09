<?php

use App\Mail\PaymentInstructions;
use App\Models\AffiliatedClub;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Apply for Membership')] class extends Component {
    public ?int $selectedTypeId = null;
    public ?int $selectedClubId = null;
    public string $applicationPath = 'standard'; // 'standard' or 'affiliated'
    public bool $agreedToTerms = false;

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
    public function affiliatedClubs()
    {
        return AffiliatedClub::active()->ordered()->get();
    }

    #[Computed]
    public function selectedType()
    {
        return $this->selectedTypeId
            ? MembershipType::find($this->selectedTypeId)
            : null;
    }

    #[Computed]
    public function selectedClub()
    {
        return $this->selectedClubId
            ? AffiliatedClub::find($this->selectedClubId)
            : null;
    }

    #[Computed]
    public function amountDue(): float
    {
        if ($this->applicationPath === 'affiliated' && $this->selectedClub) {
            return (float) $this->selectedClub->initial_fee;
        }

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
        $existingMembership = $this->user->memberships()
            ->whereIn('status', ['applied', 'approved', 'active'])
            ->exists();

        return !$existingMembership;
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
        $this->applicationPath = 'standard';
        $this->selectedClubId = null;
    }

    public function selectClub(int $clubId): void
    {
        $this->selectedClubId = $clubId;
        $this->applicationPath = 'affiliated';
        $this->selectedTypeId = null;
    }

    public function setPath(string $path): void
    {
        $this->applicationPath = $path;
        if ($path === 'standard') {
            $this->selectedClubId = null;
            if (!$this->selectedTypeId && $this->basicType) {
                $this->selectedTypeId = $this->basicType->id;
            }
        } else {
            $this->selectedTypeId = null;
        }
    }

    public function submit(): void
    {
        if (!$this->canApply) {
            $this->addError('membership', 'You already have an active or pending membership.');
            return;
        }

        if ($this->applicationPath === 'affiliated') {
            $this->validate([
                'selectedClubId' => ['required', 'exists:affiliated_clubs,id'],
                'agreedToTerms' => ['accepted'],
            ], [
                'selectedClubId.required' => 'Please select your affiliated club.',
                'agreedToTerms.accepted' => 'You must agree to the terms and conditions.',
            ]);

            $club = AffiliatedClub::findOrFail($this->selectedClubId);

            // Find the basic membership type for affiliated club members
            $basicType = MembershipType::where('slug', 'basic')->first();
            if (!$basicType) {
                $this->addError('membership', 'Basic membership type not found. Please contact support.');
                return;
            }

            $membership = Membership::create([
                'user_id' => $this->user->id,
                'membership_type_id' => $basicType->id,
                'status' => 'applied',
                'applied_at' => now(),
                'source' => 'web',
                'affiliated_club_id' => $club->id,
                'notes' => "Affiliated club application: {$club->name} ({$club->dedicated_type_label})",
            ]);
        } else {
            $this->validate([
                'selectedTypeId' => ['required', 'exists:membership_types,id'],
                'agreedToTerms' => ['accepted'],
            ], [
                'selectedTypeId.required' => 'Please select a membership type.',
                'agreedToTerms.accepted' => 'You must agree to the terms and conditions.',
            ]);

            $membership = Membership::create([
                'user_id' => $this->user->id,
                'membership_type_id' => $this->selectedTypeId,
                'status' => 'applied',
                'applied_at' => now(),
                'source' => 'web',
            ]);
        }

        // Send payment instructions email
        $this->sendPaymentInstructionsEmail($membership);

        session()->flash('success', 'Your membership application has been submitted! Payment instructions have been sent to your email.');

        $this->redirect(route('membership.show', $membership), navigate: true);
    }

    protected function sendPaymentInstructionsEmail(Membership $membership): void
    {
        try {
            $bankAccount = SystemSetting::getBankAccount();

            Mail::to($membership->user->email)
                ->queue(new PaymentInstructions(
                    $membership->load('type', 'user', 'affiliatedClub'),
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

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Apply for Membership</h1>
        <p class="text-zinc-600 dark:text-zinc-400">
            Choose your membership type and submit your application.
        </p>
    </div>

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
        {{-- Application Path Selection --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Membership Path</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Choose how you'd like to join NRAPA.</p>
            </div>
            <div class="p-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div
                        wire:click="setPath('standard')"
                        class="relative cursor-pointer rounded-xl border-2 p-5 transition-all
                            {{ $applicationPath === 'standard'
                                ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-950/20'
                                : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}"
                    >
                        @if($applicationPath === 'standard')
                        <div class="absolute right-3 top-3">
                            <svg class="size-6 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        @endif
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Standard Membership</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            Join as a basic member, or apply directly for a dedicated status (sport, hunter, or both).
                        </p>
                    </div>

                    @if($this->affiliatedClubs->count() > 0)
                    <div
                        wire:click="setPath('affiliated')"
                        class="relative cursor-pointer rounded-xl border-2 p-5 transition-all
                            {{ $applicationPath === 'affiliated'
                                ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-950/20'
                                : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}"
                    >
                        @if($applicationPath === 'affiliated')
                        <div class="absolute right-3 top-3">
                            <svg class="size-6 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        @endif
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Affiliated Club Member</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            I'm a member of an affiliated club and want to apply at a discounted rate.
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if($applicationPath === 'standard')
        {{-- Standard Membership Type Selection --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Select Membership Type</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Everyone starts with a basic membership. Dedicated status is an upgrade on top of basic.</p>
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
                            {{-- Dedicated type: show upgrade fee + renewal --}}
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Upgrade Fee</span>
                                <span class="font-semibold text-zinc-900 dark:text-white">
                                    R{{ number_format($type->upgrade_price ?? 0, 2) }}
                                    <span class="font-normal text-zinc-500">(once-off)</span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Annual Renewal</span>
                                <span class="text-zinc-900 dark:text-white">
                                    R{{ number_format($type->renewal_price, 2) }}<span class="font-normal text-zinc-500">/year</span>
                                </span>
                            </div>
                            @if($this->basicType)
                            <div class="mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-700">
                                <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                                    <span>Includes basic sign-up (R{{ number_format($this->basicType->initial_price, 2) }}) + upgrade</span>
                                </div>
                            </div>
                            @endif
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
        @endif

        @if($applicationPath === 'affiliated')
        {{-- Affiliated Club Selection --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Select Your Club</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Choose the affiliated club you belong to. Your application will require manual approval.</p>
            </div>

            <div class="p-6">
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($this->affiliatedClubs as $club)
                    <div
                        wire:click="selectClub({{ $club->id }})"
                        class="relative cursor-pointer rounded-xl border-2 p-5 transition-all
                            {{ $this->selectedClubId === $club->id
                                ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-950/20'
                                : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}"
                    >
                        @if($this->selectedClubId === $club->id)
                        <div class="absolute right-3 top-3">
                            <svg class="size-6 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        @endif

                        <div class="mb-2">
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $club->name }}</h3>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium mt-1 {{ $club->dedicated_type === 'both' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : ($club->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                {{ $club->dedicated_type_label }}
                            </span>
                        </div>

                        @if($club->description)
                        <p class="mb-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $club->description }}</p>
                        @endif

                        <div class="space-y-1 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Sign-up Fee</span>
                                <span class="font-semibold text-zinc-900 dark:text-white">R{{ number_format($club->initial_fee, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Annual Renewal</span>
                                <span class="text-zinc-900 dark:text-white">R{{ number_format($club->renewal_fee, 2) }}/year</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($this->selectedClub)
                <div class="mt-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Club Member Requirements</h4>
                    <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                        @if($this->selectedClub->requires_competency)
                        <li class="flex items-center gap-2">
                            <svg class="size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                            Upload your SAPS Firearm Competency Certificate
                        </li>
                        @endif
                        @if($this->selectedClub->required_activities_per_year > 0)
                        <li class="flex items-center gap-2">
                            <svg class="size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                            Log {{ $this->selectedClub->required_activities_per_year }} activities per year (match results with your name)
                        </li>
                        @endif
                        <li class="flex items-center gap-2">
                            <svg class="size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                            All applications require manual admin approval
                        </li>
                    </ul>
                </div>
                @endif

                @error('selectedClubId')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>
        @endif

        {{-- Application Summary --}}
        @if(($applicationPath === 'standard' && $this->selectedType) || ($applicationPath === 'affiliated' && $this->selectedClub))
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Application Summary</h2>
            </div>

            <div class="p-6">
                <div class="space-y-4">
                    @if($applicationPath === 'standard' && $this->selectedType)
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Membership Type</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $this->selectedType->name }}</span>
                    </div>
                    @if($this->selectedType->hasUpgradeFee() && $this->basicType)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">Basic Sign-up Fee</span>
                        <span class="text-zinc-900 dark:text-white">R{{ number_format($this->basicType->initial_price, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">Dedicated Upgrade Fee (once-off)</span>
                        <span class="text-zinc-900 dark:text-white">R{{ number_format($this->selectedType->upgrade_price, 2) }}</span>
                    </div>
                    @endif
                    @elseif($applicationPath === 'affiliated' && $this->selectedClub)
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Affiliated Club</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $this->selectedClub->name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Dedicated Status</span>
                        <span class="text-zinc-900 dark:text-white">{{ $this->selectedClub->dedicated_type_label }}</span>
                    </div>
                    @endif

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
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">R{{ number_format($this->amountDue, 2) }}</span>
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Payment details will be sent to your email after submitting your application.
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Terms and Submit --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="p-6">
                <div class="space-y-4">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" wire:model.live="agreedToTerms" class="mt-1 size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">I agree to the NRAPA membership terms and conditions, code of conduct, and privacy policy.</span>
                    </label>
                    @error('agreedToTerms')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-between border-t border-zinc-200 p-6 dark:border-zinc-700">
                <a href="{{ route('membership.index') }}" wire:navigate class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-emerald-500 dark:hover:bg-emerald-600" {{ !$this->agreedToTerms ? 'disabled' : '' }}>
                    Submit Application
                </button>
            </div>
        </div>
    </form>
    @endif
</div>

<?php

use App\Models\MembershipType;
use App\Models\Membership;
use Livewire\Component;

new class extends Component {
    public function with(): array
    {
        // Check if user already has a pending or active membership
        $existingMembership = Membership::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'active', 'pending_payment'])
            ->first();

        $types = MembershipType::active()->displayOnSignup()->ordered()->get();

        return [
            // Show memberships marked for signup (controlled separately from landing page)
            'membershipTypes' => $types,
            'basicType' => $types->firstWhere('slug', 'basic'),
            'existingMembership' => $existingMembership,
        ];
    }

    public function selectPackage(int $membershipTypeId): void
    {
        $membershipType = MembershipType::findOrFail($membershipTypeId);

        // Check if user already has a pending membership
        $existing = Membership::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'active', 'pending_payment'])
            ->first();

        if ($existing) {
            session()->flash('error', 'You already have a pending or active membership.');
            return;
        }

        // Create membership with pending_payment status
        $membership = Membership::create([
            'user_id' => auth()->id(),
            'membership_type_id' => $membershipType->id,
            'status' => 'pending_payment',
            'applied_at' => now(),
            'source' => 'web', // Billable - member applied via website
        ]);

        // Redirect to payment instructions
        $this->redirect(route('membership.payment', $membership), navigate: true);
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900 py-12 px-4">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="flex justify-center mb-4">
                <div class="flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg">
                    <svg class="size-9 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">Choose Your Membership</h1>
            <p class="text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
                Welcome to NRAPA! Select the membership package that best suits your needs. All memberships include full access to our compliance platform.
            </p>
        </div>

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg max-w-2xl mx-auto">
                <p class="text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        @if($existingMembership)
            <div class="max-w-2xl mx-auto">
                <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h2 class="text-xl font-semibold text-blue-800 dark:text-blue-200 mb-2">Membership Application in Progress</h2>
                    <p class="text-blue-700 dark:text-blue-300 mb-4">
                        You have a {{ $existingMembership->status === 'pending_payment' ? 'pending payment' : $existingMembership->status }} membership application.
                    </p>
                    @if($existingMembership->status === 'pending_payment')
                        <a href="{{ route('membership.payment', $existingMembership) }}" wire:navigate
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            View Payment Instructions
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    @else
                        <a href="{{ route('membership.index') }}" wire:navigate
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            View Membership Status
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    @endif
                </div>
            </div>
        @else
            <!-- Membership Packages Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($membershipTypes as $type)
                    <div class="relative rounded-xl border {{ $type->is_featured ? 'border-emerald-500 ring-2 ring-emerald-500' : 'border-zinc-200 dark:border-zinc-700' }} bg-white p-6 shadow-sm dark:bg-zinc-800">
                        @if($type->is_featured)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 transform">
                                <span class="inline-flex items-center rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white">
                                    Recommended
                                </span>
                            </div>
                        @endif
                        
                        <div class="mb-4">
                            <div class="flex items-center gap-3">
                                @if($type->icon)
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <x-membership-icon :icon="$type->icon" class="h-6 w-6" />
                                    </div>
                                @endif
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $type->name }}</h3>
                            </div>
                            @if($type->dedicated_type)
                                <span class="mt-1 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ ucfirst($type->dedicated_type === 'both' ? 'Hunter & Sport Shooter' : ($type->dedicated_type === 'sport' ? 'Sport Shooter' : 'Hunter')) }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="mb-4">
                            @if($type->hasUpgradeFee() && $basicType)
                            @php $totalSignup = ($basicType->initial_price ?? 0) + ($type->upgrade_price ?? 0); @endphp
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-bold text-zinc-900 dark:text-white">R{{ number_format($totalSignup, 0) }}</span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">sign-up</span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Renewal: R{{ number_format($type->renewal_price, 0) }}/year</p>
                            @else
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-bold text-zinc-900 dark:text-white">R{{ number_format($type->initial_price, 0) }}</span>
                                @if($type->duration_type === 'annual')
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">/year</span>
                                @elseif($type->duration_type === 'lifetime')
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">once-off</span>
                                @elseif($type->duration_months)
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">/{{ $type->duration_months }}mo</span>
                                @endif
                            </div>
                            @endif
                        </div>
                        
                        @if($type->description)
                            <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400 line-clamp-3">{{ $type->description }}</p>
                        @endif
                        
                        <ul class="mb-6 space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Virtual Safe
                            </li>
                            @if($type->allows_dedicated_status)
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Virtual Loading Bench
                            </li>
                            @endif
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Learning Center
                            </li>
                            @if($type->allows_dedicated_status)
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Dedicated Status & Endorsements
                            </li>
                            @endif
                        </ul>
                        
                        <button wire:click="selectPackage({{ $type->id }})"
                                class="block w-full rounded-lg {{ $type->is_featured ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'border border-emerald-600 text-emerald-600 hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-400 dark:hover:bg-emerald-900/20' }} px-4 py-2.5 text-center text-sm font-semibold transition-colors">
                            Select Membership
                        </button>
                    </div>
                @endforeach
            </div>

            <!-- Info Section -->
            <div class="mt-12 max-w-3xl mx-auto">
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <h3 class="font-semibold text-amber-800 dark:text-amber-200 mb-1">Payment Information</h3>
                            <p class="text-sm text-amber-700 dark:text-amber-300">
                                After selecting a package, you'll receive bank account details for EFT payment.
                                Your membership will be activated once payment is confirmed by our administrators.
                                Please use your ID number as the payment reference.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<?php

use App\Mail\PaymentInstructions;
use App\Models\AffiliatedClub;
use App\Models\AffiliatedClubInvite;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\SystemSetting;
use App\Services\NtfyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Club Membership Application')] class extends Component {
    public ?AffiliatedClub $club = null;
    public ?AffiliatedClubInvite $invite = null;
    public bool $agreedToTerms = false;
    public string $error = '';

    public function mount(AffiliatedClub $club, ?string $token = null): void
    {
        if (!$token) {
            $this->error = 'Invalid invitation link. A valid invite token is required.';
            return;
        }

        $invite = AffiliatedClubInvite::where('token', $token)
            ->where('affiliated_club_id', $club->id)
            ->first();

        if (!$invite) {
            $this->error = 'This invitation link is invalid or has already been used.';
            return;
        }

        if ($invite->isExpired()) {
            $this->error = 'This invitation has expired. Please contact your club administrator for a new invite.';
            return;
        }

        if ($invite->isAccepted()) {
            $this->error = 'This invitation has already been used.';
            return;
        }

        $this->club = $club;
        $this->invite = $invite;
    }

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[Computed]
    public function canApply()
    {
        if (!$this->club || !$this->invite) {
            return false;
        }

        return !$this->user->memberships()
            ->whereIn('status', ['applied', 'approved', 'active'])
            ->exists();
    }

    #[Computed]
    public function existingMembership()
    {
        return $this->user->memberships()
            ->whereIn('status', ['applied', 'approved', 'active'])
            ->with('type')
            ->first();
    }

    public function submit(): void
    {
        if (!$this->canApply || !$this->club || !$this->invite) {
            return;
        }

        $this->validate([
            'agreedToTerms' => ['accepted'],
        ], [
            'agreedToTerms.accepted' => 'You must agree to the terms and conditions.',
        ]);

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
            'affiliated_club_id' => $this->club->id,
            'notes' => "Affiliated club application via invite: {$this->club->name} ({$this->club->dedicated_type_label})",
        ]);

        // Mark invite as accepted
        $this->invite->markAccepted();

        // Send payment instructions email
        $this->sendPaymentInstructionsEmail($membership);

        // Notify admins of new application
        try {
            app(NtfyService::class)->notifyAdmins(
                'new_member',
                'New Club Membership Application',
                "{$this->user->name} applied via {$this->club->name}.",
                'default'
            );
        } catch (\Exception $e) {
            // Non-critical
        }

        session()->flash('success', 'Your club membership application has been submitted! Payment instructions have been sent to your email.');

        $this->redirect(route('membership.show', $membership), navigate: true);
    }

    protected function sendPaymentInstructionsEmail(Membership $membership): void
    {
        try {
            $bankAccount = SystemSetting::getBankAccount();

            Mail::to($membership->user->email)
                ->send(new PaymentInstructions(
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

<div>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Club Membership Application</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Apply for NRAPA membership via your affiliated club.
            </p>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">

    @if($error)
    {{-- Error state --}}
    <div class="rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-start gap-3">
            <svg class="size-6 flex-shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <div>
                <h3 class="font-semibold text-red-800 dark:text-red-200">Invalid Invitation</h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $error }}</p>
                <div class="mt-4">
                    <a href="{{ route('membership.apply') }}" wire:navigate class="inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:bg-red-900/50 dark:text-red-200 dark:hover:bg-red-900">
                        Apply for Standard Membership Instead
                    </a>
                </div>
            </div>
        </div>
    </div>

    @elseif(!$this->canApply)
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
        {{-- Club Details --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $club->name }}</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">You've been invited to join NRAPA as a member of this affiliated club.</p>
            </div>

            <div class="p-6">
                @if($club->description)
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $club->description }}</p>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900/50 p-4">
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Dedicated Status</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $club->dedicated_type === 'both' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : ($club->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                    {{ $club->dedicated_type_label }}
                                </span>
                            </div>
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

                    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
                        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Club Requirements</h4>
                        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                            @if($club->requires_competency)
                            <li class="flex items-center gap-2">
                                <svg class="size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                                Upload SAPS Firearm Competency Certificate
                            </li>
                            @endif
                            @if($club->required_activities_per_year > 0)
                            <li class="flex items-center gap-2">
                                <svg class="size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                                Log {{ $club->required_activities_per_year }} activities per year
                            </li>
                            @endif
                            <li class="flex items-center gap-2">
                                <svg class="size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                                Requires manual admin approval
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Application Summary --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Application Summary</h2>
            </div>

            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Affiliated Club</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $club->name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Dedicated Status</span>
                        <span class="text-zinc-900 dark:text-white">{{ $club->dedicated_type_label }}</span>
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
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">R{{ number_format($club->initial_fee, 2) }}</span>
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Payment details will be sent to your email after submitting your application.
                    </p>
                </div>
            </div>
        </div>

        {{-- Terms and Submit --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="p-6">
                <div class="space-y-4">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" wire:model.live="agreedToTerms" class="mt-1 size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">I agree to the NRAPA membership <a href="{{ route('terms-and-conditions') }}" target="_blank" class="text-blue-600 dark:text-blue-400 underline hover:text-blue-800 dark:hover:text-blue-300">terms and conditions</a>, code of conduct, and <a href="{{ route('privacy-policy') }}" target="_blank" class="text-blue-600 dark:text-blue-400 underline hover:text-blue-800 dark:hover:text-blue-300">privacy policy</a>.</span>
                    </label>
                    @error('agreedToTerms')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    @error('membership')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-between border-t border-zinc-200 p-6 dark:border-zinc-700">
                <a href="{{ route('membership.apply') }}" wire:navigate class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                    Apply for Standard Membership Instead
                </a>
                <button type="submit" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark disabled:cursor-not-allowed disabled:opacity-50 transition-colors" {{ !$this->agreedToTerms ? 'disabled' : '' }}>
                    Submit Club Application
                </button>
            </div>
        </div>
    </form>
    @endif
    </div>
</div>

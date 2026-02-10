<?php

use App\Models\Certificate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] #[Title('Certificates & Endorsements')] class extends Component {
    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[Computed]
    public function certificates()
    {
        // Dev, owner, and admin can view all certificates
        // Members can only view their own
        if ($this->user->isDeveloper() || $this->user->isOwner() || $this->user->isAdmin()) {
            return \App\Models\Certificate::with(['certificateType', 'membership.type', 'user'])
                ->latest('issued_at')
                ->get();
        }
        
        return $this->user->certificates()
            ->with(['certificateType', 'membership.type'])
            ->latest('issued_at')
            ->get();
    }

    /**
     * Get the member's current membership certificate (valid or expiring).
     */
    #[Computed]
    public function currentMembershipCert()
    {
        return $this->user->certificates()
            ->whereHas('certificateType', fn ($q) => $q->where('slug', 'membership-certificate'))
            ->whereNull('revoked_at')
            ->latest('issued_at')
            ->first();
    }

    /**
     * Check if member has uploaded an ID document.
     */
    #[Computed]
    public function hasIdDocument(): bool
    {
        return \App\Models\MemberDocument::where('user_id', $this->user->id)
            ->whereHas('documentType', fn ($q) => $q->whereIn('slug', \App\Models\MemberDocument::ID_DOCUMENT_SLUGS))
            ->whereIn('status', ['pending', 'verified'])
            ->exists();
    }

    /**
     * Determine if the member can request a new/renewed membership certificate.
     * Returns [bool $canRequest, string $reason]
     */
    #[Computed]
    public function renewalStatus(): array
    {
        $membership = $this->user->activeMembership;

        // No active membership
        if (!$membership) {
            return [false, 'You need an active membership to receive a certificate.'];
        }

        // No ID uploaded
        if (!$this->hasIdDocument) {
            return [false, 'Upload your ID document to receive your membership certificate.'];
        }

        $currentCert = $this->currentMembershipCert;

        // No certificate yet — eligible for first issue
        if (!$currentCert) {
            return [true, 'request_first'];
        }

        // Certificate is still valid and not within 30 days of expiry
        if ($currentCert->isValid() && $currentCert->valid_until && $currentCert->valid_until->diffInDays(now()) > 30) {
            $daysLeft = (int) now()->diffInDays($currentCert->valid_until);
            return [false, "Your current certificate is valid until {$currentCert->valid_until->format('d M Y')} ({$daysLeft} days remaining)."];
        }

        // Certificate is expiring within 30 days or already expired — check membership validity
        if ($membership->expires_at && $membership->expires_at->diffInMonths(now()) < 3) {
            return [false, 'Your membership expires on ' . $membership->expires_at->format('d M Y') . ' — less than 3 months remaining. Please renew your membership first.'];
        }

        // All good — can renew
        if ($currentCert->isValid()) {
            return [true, 'request_renewal'];
        }

        // Expired cert — can get a new one
        return [true, 'request_new'];
    }

    /**
     * Request a new membership certificate.
     */
    public function requestCertificate(): void
    {
        [$canRequest] = $this->renewalStatus;
        if (!$canRequest) {
            session()->flash('error', 'You are not eligible to request a certificate at this time.');
            return;
        }

        try {
            $service = app(\App\Services\CertificateIssueService::class);
            $certificate = $service->issueMembershipCertificate($this->user, $this->user, skipChecks: true);

            if ($certificate) {
                session()->flash('success', 'Your Membership Certificate has been issued successfully!');
            } else {
                session()->flash('error', 'Unable to generate certificate. Please try again later.');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Member certificate request failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', $e->getMessage());
        }
    }

    #[Computed]
    public function issuedEndorsements()
    {
        // Get issued endorsement requests (status = 'issued')
        $query = \App\Models\EndorsementRequest::where('status', 'issued')
            ->with(['user', 'firearm.firearmCalibre', 'firearm.firearmMake', 'firearm.firearmModel']);

        // Dev, owner, and admin can view all endorsements
        // Members can only view their own
        if (!$this->user->isDeveloper() && !$this->user->isOwner() && !$this->user->isAdmin()) {
            $query->where('user_id', $this->user->id);
        }

        return $query->latest('issued_at')->get();
    }

    #[Computed]
    public function allDocuments()
    {
        // Combine certificates and issued endorsements into a unified collection
        $documents = collect();

        // Add certificates
        foreach ($this->certificates as $cert) {
            $documents->push([
                'type' => 'certificate',
                'id' => $cert->id,
                'certificate_number' => $cert->certificate_number,
                'name' => $cert->certificateType->name,
                'issued_at' => $cert->issued_at,
                'valid_until' => $cert->valid_until,
                'revoked_at' => $cert->revoked_at,
                'is_valid' => $cert->isValid(),
                'user' => $cert->user ?? $this->user,
                'certificate' => $cert,
            ]);
        }

        // Add issued endorsements
        foreach ($this->issuedEndorsements as $endorsement) {
            $documents->push([
                'type' => 'endorsement',
                'id' => $endorsement->id,
                'certificate_number' => $endorsement->letter_reference ?? 'END-' . $endorsement->id,
                'name' => 'Endorsement Letter' . ($endorsement->request_type === 'renewal' ? ' (Renewal)' : ''),
                'issued_at' => $endorsement->issued_at,
                'valid_until' => $endorsement->expires_at, // Endorsements expire 1 year after issue
                'revoked_at' => null,
                'is_valid' => !$endorsement->is_expired, // Valid if not expired
                'user' => $endorsement->user,
                'endorsement' => $endorsement,
            ]);
        }

        // Sort by issued_at descending
        return $documents->sortByDesc('issued_at')->values();
    }

    #[Computed]
    public function validCertificates()
    {
        return $this->allDocuments->filter(fn ($doc) => $doc['is_valid'] && $doc['type'] === 'certificate')
            ->map(fn ($doc) => $doc['certificate']);
    }

    #[Computed]
    public function validDocuments()
    {
        return $this->allDocuments->filter(fn ($doc) => $doc['is_valid']);
    }

    #[Computed]
    public function expiredCertificates()
    {
        return $this->certificates->filter(fn ($cert) => !$cert->isValid());
    }

    #[Computed]
    public function isAdmin(): bool
    {
        return $this->user->isAdmin() || $this->user->isOwner() || $this->user->isDeveloper();
    }

    public ?int $deletingCertificateId = null;

    public function confirmDelete(int $id): void
    {
        if (!$this->isAdmin) {
            return;
        }
        $this->deletingCertificateId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingCertificateId = null;
    }

    public function deleteCertificate(): void
    {
        if (!$this->isAdmin || !$this->deletingCertificateId) {
            return;
        }

        $certificate = Certificate::find($this->deletingCertificateId);
        if (!$certificate) {
            session()->flash('error', 'Certificate not found.');
            $this->deletingCertificateId = null;
            return;
        }

        try {
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'certificate_deleted',
                'auditable_type' => Certificate::class,
                'auditable_id' => $certificate->id,
                'old_values' => [
                    'certificate_number' => $certificate->certificate_number,
                    'certificate_type' => $certificate->certificateType->name ?? null,
                    'user_id' => $certificate->user_id,
                    'member_name' => $certificate->user->name ?? 'N/A',
                ],
                'new_values' => ['deleted' => true],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            if ($certificate->file_path) {
                $disk = app()->environment(['local', 'development', 'testing']) ? 'local' : 'r2';
                \Illuminate\Support\Facades\Storage::disk($disk)->delete($certificate->file_path);
            }

            $certNumber = $certificate->certificate_number;
            $certificate->delete();

            $this->deletingCertificateId = null;
            session()->flash('success', "Certificate {$certNumber} has been deleted.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to delete certificate', [
                'certificate_id' => $this->deletingCertificateId,
                'error' => $e->getMessage(),
            ]);
            $this->deletingCertificateId = null;
            session()->flash('error', 'Failed to delete certificate: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Certificates & Endorsements</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                @if($this->user->isDeveloper() || $this->user->isOwner() || $this->user->isAdmin())
                    View and manage all NRAPA membership certificates and endorsement letters.
                @else
                    View and download your NRAPA membership certificates and endorsement letters.
                @endif
            </p>
        </div>
        @include('partials.member-nav-tabs')
    </x-slot>

    <div class="flex flex-col gap-6">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-300 dark:border-emerald-700 rounded-xl text-emerald-800 dark:text-emerald-200 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Membership Certificate Info & Request (members only) --}}
    @if(!$this->isAdmin)
    @php
        [$canRequest, $renewalReason] = $this->renewalStatus;
        $membership = $this->user->activeMembership;
        $currentCert = $this->currentMembershipCert;
    @endphp
    <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-900/10 overflow-hidden">
        <div class="p-5">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-nrapa-blue/10 dark:bg-nrapa-blue/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-nrapa-blue dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">Membership Certificate</h3>
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 space-y-1.5">
                        <p>To receive your Membership Certificate, you need:</p>
                        <ul class="space-y-1 ml-1">
                            <li class="flex items-center gap-2">
                                @if($membership)
                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="w-4 h-4 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg>
                                @endif
                                <span class="{{ $membership ? 'text-emerald-700 dark:text-emerald-400' : '' }}">An active NRAPA membership</span>
                            </li>
                            <li class="flex items-center gap-2">
                                @if($this->hasIdDocument)
                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="w-4 h-4 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg>
                                @endif
                                <span class="{{ $this->hasIdDocument ? 'text-emerald-700 dark:text-emerald-400' : '' }}">Your ID document uploaded (full name & ID number)</span>
                            </li>
                        </ul>

                        @if($currentCert && $currentCert->isValid() && $currentCert->valid_until)
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                                You can request a renewed certificate once your current one is within 30 days of expiry.
                                Your membership must also be valid for at least 3 more months.
                            </p>
                        @endif
                    </div>

                    {{-- Status & Action --}}
                    <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-3">
                        @if($canRequest)
                            <button wire:click="requestCertificate" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-nrapa-blue hover:bg-nrapa-blue-dark rounded-lg transition-colors disabled:opacity-50">
                                <span wire:loading.remove wire:target="requestCertificate">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </span>
                                <svg wire:loading wire:target="requestCertificate" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                @if($renewalReason === 'request_first')
                                    Request Membership Certificate
                                @elseif($renewalReason === 'request_renewal')
                                    Renew Certificate
                                @else
                                    Request New Certificate
                                @endif
                            </button>
                        @endif

                        @if(!$canRequest && !in_array($renewalReason, ['request_first', 'request_renewal', 'request_new']))
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">
                                {{ $renewalReason }}
                            </p>
                        @endif

                        @if(!$this->hasIdDocument)
                            <a href="{{ route('documents.index') }}" wire:navigate
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-nrapa-blue hover:text-nrapa-blue-dark dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                Upload ID Document
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Valid Certificates --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-center gap-3 border-b border-zinc-200 p-6 dark:border-zinc-700">
            <svg class="size-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" />
            </svg>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Valid Certificates & Endorsements</h2>
            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $this->validDocuments->count() }}</span>
        </div>

        @if($this->validDocuments->count() > 0)
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach($this->validDocuments as $document)
            @php
                $isEndorsement = $document['type'] === 'endorsement';
                $certificate = $document['certificate'] ?? null;
                $endorsement = $document['endorsement'] ?? null;
            @endphp
            <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex size-12 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900">
                        <svg class="size-6 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $document['name'] }}</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $document['certificate_number'] }}
                            <span class="mx-2">•</span>
                            Issued {{ $document['issued_at']->format('d M Y') }}
                            @if($isEndorsement && $endorsement->firearm)
                                <span class="mx-2">•</span>
                                {{ $endorsement->firearm->make }} {{ $endorsement->firearm->model }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Valid Until</p>
                        <p class="font-medium text-zinc-900 dark:text-white">
                            @if($document['valid_until'])
                                {{ $document['valid_until']->format('d M Y') }}
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Indefinite</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        @if($isEndorsement)
                            {{-- Endorsement Actions --}}
                            @php
                                // Set view route based on user role
                                if ($this->user->isDeveloper()) {
                                    $showRoute = 'developer.endorsements.show';
                                } elseif ($this->user->isOwner() || $this->user->isAdmin()) {
                                    $showRoute = 'admin.endorsements.show';
                                } else {
                                    $showRoute = 'member.endorsements.show';
                                }
                                
                                // Set download route based on user role
                                if ($this->user->isDeveloper() || $this->user->isOwner() || $this->user->isAdmin()) {
                                    $downloadRoute = 'admin.endorsements.download';
                                } else {
                                    $downloadRoute = 'member.endorsements.letter';
                                }
                            @endphp
                            <a href="{{ route($showRoute, $endorsement->uuid) }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                View
                            </a>
                            @if($endorsement->letter_file_path)
                            <a href="{{ route($downloadRoute, $endorsement->uuid) }}" 
                                class="inline-flex items-center gap-1 rounded-lg bg-nrapa-blue px-3 py-1.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Download PDF
                            </a>
                            @endif
                        @else
                            {{-- Certificate Actions --}}
                            @php
                                $showRoute = 'certificates.show';
                                if ($this->user->isDeveloper()) {
                                    $showRoute = 'developer.certificates.show';
                                } elseif ($this->user->isOwner() || $this->user->isAdmin()) {
                                    $showRoute = 'admin.certificates.show';
                                }
                                $isMembershipCard = $certificate->certificateType->slug === 'membership-card';
                                $walletService = app(\App\Services\WalletPassService::class);
                                $walletEnabled = $walletService->isEnabled();
                            @endphp
                            <a href="{{ route($showRoute, $certificate) }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                View
                            </a>
                            @if($certificate->file_path)
                            @php
                                $downloadRoute = 'certificates.download';
                                if ($this->user->isDeveloper()) {
                                    $downloadRoute = 'developer.certificates.download';
                                } elseif ($this->user->isOwner() || $this->user->isAdmin()) {
                                    $downloadRoute = 'admin.certificates.download';
                                }
                            @endphp
                            <a href="{{ route($downloadRoute, $certificate) }}" 
                                class="inline-flex items-center gap-1 rounded-lg bg-nrapa-blue px-3 py-1.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Download PDF
                            </a>
                            @endif
                            @if($this->isAdmin)
                            <button wire:click="confirmDelete({{ $certificate->id }})"
                                class="inline-flex items-center gap-1 rounded-lg border border-red-300 dark:border-red-700 px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                title="Delete certificate">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete
                            </button>
                            @endif
                            @if($isMembershipCard && $walletEnabled)
                            <div class="flex gap-2">
                                @if($walletService->isAppleEnabled())
                                <a href="{{ route('certificates.wallet.apple', $certificate) }}" class="inline-flex items-center gap-1 rounded-lg bg-black px-3 py-1.5 text-sm font-medium text-white hover:bg-zinc-800 transition-colors">
                                    <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.96-3.24-.96-1.15 0-1.36.93-2.85.93-1.5 0-2.14-.91-3.27-1.97-1.13-1.05-2.14-2.98-2.14-5.05 0-3.11 2.04-4.85 4.11-4.85 1.02 0 1.84.37 2.49.37.63 0 1.62-.38 2.74-.38 2.35 0 3.98 1.35 3.98 3.89 0 .78-.13 1.56-.38 2.32-.25.75-.56 1.5-.99 2.18zM12.03 3.5c.57-1.29 1.28-2.35 2.31-3.18.99-.81 2.4-1.27 3.44-1.05.11.6.41 1.17.89 1.67.48.5 1.05.86 1.66 1.11.6.25 1.25.38 1.88.38-.08 1.3-.41 2.54-1.03 3.66-.61 1.11-1.45 2.07-2.47 2.83-1.01.75-2.18 1.28-3.4 1.57-.12-.6-.41-1.17-.89-1.67-.48-.5-1.05-.86-1.66-1.11-.6-.25-1.25-.38-1.88-.38.08-1.3.41-2.54 1.03-3.66.61-1.11 1.45-2.07 2.47-2.83z"/>
                                    </svg>
                                    Apple Wallet
                                </a>
                                @endif
                                @if($walletService->isGoogleEnabled())
                                <a href="{{ route('certificates.wallet.google', $certificate) }}" class="inline-flex items-center gap-1 rounded-lg bg-white border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-900 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:hover:bg-zinc-600 transition-colors">
                                    <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                    </svg>
                                    Google Wallet
                                </a>
                                @endif
                            </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="p-8 text-center">
            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No Valid Certificates or Endorsements</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                @if($this->user->hasActiveMembership())
                    Certificates and endorsement letters will be issued once your membership is fully processed.
                @else
                    Apply for membership to receive your certificates and endorsements.
                @endif
            </p>
        </div>
        @endif
    </div>

    {{-- Expired/Revoked Certificates --}}
    @if($this->expiredCertificates->count() > 0)
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-center gap-3 border-b border-zinc-200 p-6 dark:border-zinc-700">
            <svg class="size-5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Expired / Revoked Certificates</h2>
            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">{{ $this->expiredCertificates->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Certificate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Certificate #</th>
                        @if($this->user->isDeveloper() || $this->user->isOwner() || $this->user->isAdmin())
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Member</th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Issued</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Expired/Revoked</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->expiredCertificates as $certificate)
                    <tr class="opacity-60 hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $certificate->certificateType->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-zinc-900 dark:text-white">{{ $certificate->certificate_number }}</td>
                        @if($this->user->isDeveloper() || $this->user->isOwner() || $this->user->isAdmin())
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">
                            {{ $certificate->user->name ?? 'N/A' }}
                        </td>
                        @endif
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $certificate->issued_at->format('d M Y') }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($certificate->revoked_at)
                                {{ $certificate->revoked_at->format('d M Y') }}
                            @elseif($certificate->valid_until)
                                {{ $certificate->valid_until->format('d M Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($certificate->revoked_at)
                                {{ $certificate->revoked_at->format('d M Y') }}
                            @elseif($certificate->valid_until)
                                {{ $certificate->valid_until->format('d M Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            @if($certificate->isRevoked())
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Revoked</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">Expired</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            @php
                                $showRoute = 'certificates.show';
                                if ($this->user->isDeveloper()) {
                                    $showRoute = 'developer.certificates.show';
                                } elseif ($this->user->isOwner() || $this->user->isAdmin()) {
                                    $showRoute = 'admin.certificates.show';
                                }
                            @endphp
                            <div class="flex items-center gap-2">
                                <a href="{{ route($showRoute, $certificate) }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                                    View
                                </a>
                                @if($this->isAdmin)
                                <button wire:click="confirmDelete({{ $certificate->id }})"
                                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors" title="Delete">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- QR Verification Info --}}
    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900/50">
        <div class="flex items-start gap-4">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                <svg class="size-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-zinc-900 dark:text-white">QR Code Verification</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    All NRAPA certificates include a QR code that can be scanned to verify authenticity.
                    When presenting your certificate, the verifying party can scan the code to confirm
                    your membership status in real-time.
                </p>
            </div>
        </div>
    </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    @if($deletingCertificateId)
    @php
        $deletingCert = $this->certificates->firstWhere('id', $deletingCertificateId);
    @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl max-w-md w-full mx-4 overflow-hidden">
            <div class="bg-red-50 dark:bg-red-900/20 px-6 py-4 border-b border-red-200 dark:border-red-700">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-red-800 dark:text-red-200">Delete Certificate</h3>
                </div>
            </div>
            <div class="p-6">
                @if($deletingCert)
                <p class="text-zinc-600 dark:text-zinc-400 mb-2">
                    Are you sure you want to delete this certificate?
                </p>
                <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 mb-4 text-sm space-y-1">
                    <div><span class="text-zinc-500 dark:text-zinc-400">Type:</span> <span class="font-medium text-zinc-900 dark:text-white">{{ $deletingCert->certificateType->name }}</span></div>
                    <div><span class="text-zinc-500 dark:text-zinc-400">Number:</span> <span class="font-mono font-medium text-zinc-900 dark:text-white">{{ $deletingCert->certificate_number }}</span></div>
                    <div><span class="text-zinc-500 dark:text-zinc-400">Member:</span> <span class="font-medium text-zinc-900 dark:text-white">{{ $deletingCert->user->name ?? 'N/A' }}</span></div>
                </div>
                <p class="text-sm text-red-600 dark:text-red-400 mb-6">
                    This action cannot be undone and will permanently remove the certificate and its associated PDF file.
                </p>
                @endif
                <div class="flex gap-3 justify-end">
                    <button wire:click="cancelDelete"
                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="deleteCertificate"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                        Delete Certificate
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

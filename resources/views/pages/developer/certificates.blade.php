<?php

use App\Models\Certificate;
use App\Models\CertificateType;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app.sidebar')] #[Title('Certificate Management')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $typeFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public array $selected = [];
    public bool $selectAll = false;

    public ?int $deletingCertificateId = null;
    public bool $showBulkDeleteModal = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selected = $this->certificates->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    #[Computed]
    public function certificateTypes()
    {
        return CertificateType::orderBy('name')->get();
    }

    #[Computed]
    public function certificates()
    {
        $query = Certificate::with(['certificateType', 'user', 'membership.type', 'issuer'])
            ->latest('issued_at');

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($this->typeFilter) {
            $query->whereHas('certificateType', fn ($q) => $q->where('slug', $this->typeFilter));
        }

        if ($this->statusFilter === 'valid') {
            $query->valid();
        } elseif ($this->statusFilter === 'expired') {
            $query->whereNull('revoked_at')
                  ->whereNotNull('valid_until')
                  ->where('valid_until', '<', now());
        } elseif ($this->statusFilter === 'revoked') {
            $query->revoked();
        }

        return $query->paginate(25);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total' => Certificate::count(),
            'valid' => Certificate::valid()->count(),
            'expired' => Certificate::whereNull('revoked_at')->whereNotNull('valid_until')->where('valid_until', '<', now())->count(),
            'revoked' => Certificate::revoked()->count(),
        ];
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingCertificateId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingCertificateId = null;
    }

    public function deleteCertificate(): void
    {
        if (!$this->deletingCertificateId) {
            return;
        }

        $certificate = Certificate::find($this->deletingCertificateId);
        if (!$certificate) {
            session()->flash('error', 'Certificate not found.');
            $this->deletingCertificateId = null;
            return;
        }

        $this->performDelete($certificate);
        $this->deletingCertificateId = null;
    }

    public function confirmBulkDelete(): void
    {
        if (empty($this->selected)) {
            return;
        }
        $this->showBulkDeleteModal = true;
    }

    public function cancelBulkDelete(): void
    {
        $this->showBulkDeleteModal = false;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            $this->showBulkDeleteModal = false;
            return;
        }

        $certificates = Certificate::whereIn('id', $this->selected)->get();
        $count = 0;

        foreach ($certificates as $certificate) {
            $this->performDelete($certificate, false);
            $count++;
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->showBulkDeleteModal = false;
        session()->flash('success', "{$count} certificate(s) deleted successfully.");
    }

    private function performDelete(Certificate $certificate, bool $flash = true): void
    {
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
                Storage::disk($disk)->delete($certificate->file_path);
            }

            $certNumber = $certificate->certificate_number;
            $certificate->delete();

            if ($flash) {
                session()->flash('success', "Certificate {$certNumber} deleted.");
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to delete certificate', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);
            if ($flash) {
                session()->flash('error', 'Failed to delete: ' . $e->getMessage());
            }
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->typeFilter = '';
        $this->statusFilter = '';
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-full text-xs font-medium">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Developer
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Certificate Management</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">View, filter, and manage all certificates across the platform.</p>
            </div>
            <a href="{{ route('developer.dashboard') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors whitespace-nowrap self-start">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-300 dark:border-emerald-700 rounded-xl text-emerald-800 dark:text-emerald-200 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['total'] }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-emerald-200 dark:border-emerald-700 p-4">
                <p class="text-sm text-emerald-600 dark:text-emerald-400">Valid</p>
                <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $this->stats['valid'] }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-amber-200 dark:border-amber-700 p-4">
                <p class="text-sm text-amber-600 dark:text-amber-400">Expired</p>
                <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $this->stats['expired'] }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-red-200 dark:border-red-700 p-4">
                <p class="text-sm text-red-600 dark:text-red-400">Revoked</p>
                <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $this->stats['revoked'] }}</p>
            </div>
        </div>

        {{-- Filters & Actions Bar --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                {{-- Filters --}}
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center flex-1">
                    {{-- Search --}}
                    <div class="relative flex-1 max-w-sm">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search name, email, cert #..."
                            class="w-full pl-10 pr-4 py-2 text-sm border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg focus:ring-2 focus:ring-nrapa-blue focus:border-nrapa-blue placeholder-zinc-400">
                    </div>

                    {{-- Type Filter --}}
                    <select wire:model.live="typeFilter"
                        class="text-sm border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-nrapa-blue focus:border-nrapa-blue">
                        <option value="">All Types</option>
                        @foreach($this->certificateTypes as $type)
                            <option value="{{ $type->slug }}">{{ $type->name }}</option>
                        @endforeach
                    </select>

                    {{-- Status Filter --}}
                    <select wire:model.live="statusFilter"
                        class="text-sm border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-nrapa-blue focus:border-nrapa-blue">
                        <option value="">All Statuses</option>
                        <option value="valid">Valid</option>
                        <option value="expired">Expired</option>
                        <option value="revoked">Revoked</option>
                    </select>

                    @if($search || $typeFilter || $statusFilter)
                    <button wire:click="clearFilters" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
                    </button>
                    @endif
                </div>

                {{-- Bulk Actions --}}
                @if(count($selected) > 0)
                <div class="flex items-center gap-3">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ count($selected) }}</span> selected
                    </span>
                    <button wire:click="confirmBulkDelete"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete Selected
                    </button>
                </div>
                @endif
            </div>
        </div>

        {{-- Certificates Table --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" wire:model.live="selectAll"
                                    class="rounded border-zinc-300 dark:border-zinc-600 text-nrapa-blue focus:ring-nrapa-blue">
                            </th>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Member</th>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Certificate #</th>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Issued</th>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Valid Until</th>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">PDF</th>
                            <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($this->certificates as $certificate)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors {{ in_array((string) $certificate->id, $selected) ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}">
                            <td class="px-4 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $certificate->id }}"
                                    class="rounded border-zinc-300 dark:border-zinc-600 text-nrapa-blue focus:ring-nrapa-blue">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-nrapa-blue/10 flex items-center justify-center">
                                        <span class="text-xs font-bold text-nrapa-blue">{{ strtoupper(substr($certificate->user->name ?? '?', 0, 1)) }}{{ strtoupper(substr(explode(' ', $certificate->user->name ?? '? ?')[1] ?? '', 0, 1)) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-zinc-900 dark:text-white truncate">{{ $certificate->user->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $certificate->user->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $slug = $certificate->certificateType->slug ?? '';
                                    $typeColors = match(true) {
                                        str_contains($slug, 'membership-card') => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                        str_contains($slug, 'membership-cert') || str_contains($slug, 'good-standing') || str_contains($slug, 'paid-up') => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                        str_contains($slug, 'dedicated') => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                                        str_contains($slug, 'welcome') => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                        str_contains($slug, 'endorsement') => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
                                        default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColors }}">
                                    {{ $certificate->certificateType->name ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $certificate->certificate_number }}</span>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                {{ $certificate->issued_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($certificate->valid_until)
                                    <span class="{{ $certificate->valid_until->isPast() ? 'text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                        {{ $certificate->valid_until->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">Indefinite</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($certificate->isRevoked())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Revoked
                                    </span>
                                @elseif($certificate->isExpired())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Valid
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($certificate->file_path)
                                    <a href="{{ route('developer.certificates.download', $certificate) }}"
                                        class="inline-flex items-center gap-1 text-nrapa-blue hover:text-nrapa-blue-dark dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                                        title="Download PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span class="text-xs">PDF</span>
                                    </a>
                                @else
                                    <span class="text-xs text-zinc-400">None</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('developer.certificates.show', $certificate) }}" wire:navigate
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white transition-colors rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                        title="View">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View
                                    </a>
                                    <a href="{{ route('developer.certificates.preview', $certificate) }}" target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white transition-colors rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                        title="Preview document">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        Preview
                                    </a>
                                    <button wire:click="confirmDelete({{ $certificate->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors rounded-md hover:bg-red-50 dark:hover:bg-red-900/20"
                                        title="Delete">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <svg class="mx-auto w-12 h-12 text-zinc-300 dark:text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                <p class="font-medium text-zinc-900 dark:text-white mb-1">No certificates found</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($search || $typeFilter || $statusFilter)
                                        Try adjusting your filters.
                                    @else
                                        No certificates have been issued yet.
                                    @endif
                                </p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($this->certificates->hasPages())
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
                {{ $this->certificates->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- Single Delete Confirmation Modal --}}
    @if($deletingCertificateId)
    @php
        $deletingCert = \App\Models\Certificate::with(['certificateType', 'user'])->find($deletingCertificateId);
    @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="cancelDelete">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl max-w-md w-full mx-4 overflow-hidden">
            <div class="bg-red-50 dark:bg-red-900/20 px-6 py-4 border-b border-red-200 dark:border-red-700">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-red-800 dark:text-red-200">Delete Certificate</h3>
                </div>
            </div>
            <div class="p-6">
                @if($deletingCert)
                <p class="text-zinc-600 dark:text-zinc-400 mb-3">Are you sure you want to permanently delete this certificate?</p>
                <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 mb-4 text-sm space-y-1.5">
                    <div><span class="text-zinc-500 dark:text-zinc-400">Type:</span> <span class="font-medium text-zinc-900 dark:text-white">{{ $deletingCert->certificateType->name ?? 'Unknown' }}</span></div>
                    <div><span class="text-zinc-500 dark:text-zinc-400">Number:</span> <span class="font-mono font-medium text-zinc-900 dark:text-white">{{ $deletingCert->certificate_number }}</span></div>
                    <div><span class="text-zinc-500 dark:text-zinc-400">Member:</span> <span class="font-medium text-zinc-900 dark:text-white">{{ $deletingCert->user->name ?? 'N/A' }}</span></div>
                    <div><span class="text-zinc-500 dark:text-zinc-400">Email:</span> <span class="text-zinc-700 dark:text-zinc-300">{{ $deletingCert->user->email ?? 'N/A' }}</span></div>
                </div>
                <p class="text-sm text-red-600 dark:text-red-400 mb-5">This will permanently remove the certificate record and its PDF file from storage.</p>
                @endif
                <div class="flex gap-3 justify-end">
                    <button wire:click="cancelDelete"
                        class="px-4 py-2 text-sm font-medium border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="deleteCertificate"
                        class="px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        Delete Certificate
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Bulk Delete Confirmation Modal --}}
    @if($showBulkDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="cancelBulkDelete">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl max-w-md w-full mx-4 overflow-hidden">
            <div class="bg-red-50 dark:bg-red-900/20 px-6 py-4 border-b border-red-200 dark:border-red-700">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-red-800 dark:text-red-200">Bulk Delete Certificates</h3>
                </div>
            </div>
            <div class="p-6">
                <p class="text-zinc-600 dark:text-zinc-400 mb-3">
                    You are about to delete <span class="font-bold text-zinc-900 dark:text-white">{{ count($selected) }}</span> certificate(s).
                </p>
                <p class="text-sm text-red-600 dark:text-red-400 mb-5">This action cannot be undone. All selected certificate records and their PDF files will be permanently removed.</p>
                <div class="flex gap-3 justify-end">
                    <button wire:click="cancelBulkDelete"
                        class="px-4 py-2 text-sm font-medium border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="bulkDelete"
                        class="px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        Delete {{ count($selected) }} Certificate(s)
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

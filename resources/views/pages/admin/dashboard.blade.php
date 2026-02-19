<?php

use App\Models\CalibreRequest;
use App\Models\EndorsementRequest;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\ShootingActivity;
use App\Models\User;
use Livewire\Component;

new class extends Component {
    public int $totalMembers = 0;
    public int $activeMembers = 0;
    public int $pendingDocuments = 0;
    public int $pendingMemberships = 0;
    public int $pendingActivities = 0;
    public int $pendingCalibres = 0;
    public int $pendingEndorsements = 0;
    public int $totalOutstandingApprovals = 0;
    
    public function mount(): void
    {
        $this->loadStats();
    }
    
    public function loadStats(): void
    {
        // Total members
        $this->totalMembers = User::where('role', User::ROLE_MEMBER)->count();
        
        // Active members (users with active memberships)
        $this->activeMembers = User::where('role', User::ROLE_MEMBER)
            ->whereHas('memberships', function ($query) {
                $query->where('status', 'active');
            })
            ->count();
        
        // Pending approvals
        try {
            $this->pendingDocuments = MemberDocument::where('status', 'pending')->count();
            $this->pendingMemberships = Membership::where('status', 'applied')->count();
            $this->pendingActivities = ShootingActivity::where('status', 'pending')->count();
            $this->pendingCalibres = CalibreRequest::where('status', 'pending')->count();
            // Pending endorsements (submitted, under review, pending documents – approved are no longer "pending")
            $this->pendingEndorsements = EndorsementRequest::whereIn('status', [
                EndorsementRequest::STATUS_SUBMITTED,
                EndorsementRequest::STATUS_UNDER_REVIEW,
                EndorsementRequest::STATUS_PENDING_DOCUMENTS,
            ])->count();
        } catch (\Exception $e) {
            // Tables might not exist yet
        }
        
        // All pending approvals combined
        $this->totalOutstandingApprovals = $this->pendingDocuments
            + $this->pendingMemberships
            + $this->pendingActivities
            + $this->pendingCalibres
            + $this->pendingEndorsements;
    }
    
    public function refresh(): void
    {
        $this->loadStats();
        $this->dispatch('stats-refreshed');
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Admin Dashboard</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Overview of membership activity, approvals, and system health</p>
    </x-slot>

    <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button 
                wire:click="refresh"
                class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
            @if(auth()->user()->activeMembership)
            <a 
                href="{{ route('dashboard') }}" 
                wire:navigate
                class="px-4 py-2 text-sm font-medium text-white bg-nrapa-blue rounded-lg hover:bg-nrapa-blue-dark transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                View as Member
            </a>
            @endif
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Members --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Members</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalMembers) }}</p>
                </div>
            </div>
            <a href="{{ route('admin.members.index') }}" wire:navigate class="mt-4 block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors">
                View all members →
            </a>
        </div>

        {{-- Active Members --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Active Members</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($activeMembers) }}</p>
                    @if($totalMembers > 0)
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        {{ number_format(($activeMembers / $totalMembers) * 100, 1) }}% of total
                    </p>
                    @endif
                </div>
            </div>
            <a href="{{ route('admin.members.index', ['filter' => 'active']) }}" wire:navigate class="mt-4 block text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium transition-colors">
                View active members →
            </a>
        </div>

        {{-- Outstanding Approvals --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Outstanding Approvals</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalOutstandingApprovals) }}</p>
                    @if($totalOutstandingApprovals > 0)
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        Requires attention
                    </p>
                    @endif
                </div>
            </div>
            <a href="{{ route('admin.approvals.index') }}" wire:navigate class="mt-4 block text-sm text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-medium transition-colors">
                Review approvals →
            </a>
        </div>

        {{-- Pending Breakdown (clickable) --}}
        <a href="{{ route('admin.approvals.index') }}" wire:navigate class="block bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 hover:border-purple-300 dark:hover:border-purple-600 hover:shadow-md transition-all group">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Breakdown</p>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Documents:</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $pendingDocuments }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Memberships:</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $pendingMemberships }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Activities:</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $pendingActivities }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Calibres:</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $pendingCalibres }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Endorsements:</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $pendingEndorsements }}</span>
                        </div>
                    </div>
                </div>
                <svg class="w-5 h-5 text-zinc-400 group-hover:text-purple-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Quick Actions</h2>
            <div class="space-y-3">
                <a href="{{ route('admin.approvals.index') }}" wire:navigate
                    class="flex items-center justify-between gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors {{ $totalOutstandingApprovals > 0 ? 'ring-2 ring-amber-500' : '' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300 font-medium">Review Approvals</span>
                    </div>
                    @if($totalOutstandingApprovals > 0)
                    <span class="px-2 py-1 text-xs font-bold text-white bg-amber-600 rounded-full">{{ $totalOutstandingApprovals }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.members.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="text-zinc-700 dark:text-zinc-300 font-medium">Manage Members</span>
                </a>
                <a href="{{ route('admin.documents.index') }}" wire:navigate
                    class="flex items-center justify-between gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors {{ $pendingDocuments > 0 ? 'ring-2 ring-amber-500' : '' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300 font-medium">Document Approvals</span>
                    </div>
                    @if($pendingDocuments > 0)
                    <span class="px-2 py-1 text-xs font-bold text-white bg-amber-600 rounded-full">{{ $pendingDocuments }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.endorsements.index') }}" wire:navigate
                    class="flex items-center justify-between gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors {{ $pendingEndorsements > 0 ? 'ring-2 ring-amber-500' : '' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300 font-medium">Endorsement Requests</span>
                    </div>
                    @if($pendingEndorsements > 0)
                    <span class="px-2 py-1 text-xs font-bold text-white bg-amber-600 rounded-full">{{ $pendingEndorsements }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.settings.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-zinc-700 dark:text-zinc-300 font-medium">System Settings</span>
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Approval Summary</h2>
            <div class="space-y-4">
                <a href="{{ route('admin.documents.index') }}" wire:navigate
                    class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300">Documents</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $pendingDocuments }}</span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">pending</span>
                    </div>
                </a>
                <a href="{{ route('admin.approvals.index', ['type' => 'memberships']) }}" wire:navigate
                    class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300">Memberships</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $pendingMemberships }}</span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">pending</span>
                    </div>
                </a>
                <a href="{{ route('admin.activities.index') }}" wire:navigate
                    class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300">Activities</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $pendingActivities }}</span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">pending</span>
                    </div>
                </a>
                <a href="{{ route('admin.calibre-requests.index') }}" wire:navigate
                    class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300">Calibre Requests</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $pendingCalibres }}</span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">pending</span>
                    </div>
                </a>
                <a href="{{ route('admin.endorsements.index') }}" wire:navigate
                    class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors {{ $pendingEndorsements > 0 ? 'ring-2 ring-amber-500' : '' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300">Endorsements</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $pendingEndorsements }}</span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">pending</span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- Alert for Outstanding Approvals --}}
    @if($totalOutstandingApprovals > 0)
    <div class="mb-8 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-amber-800 dark:text-amber-200">{{ $totalOutstandingApprovals }} Approval{{ $totalOutstandingApprovals > 1 ? 's' : '' }} Require{{ $totalOutstandingApprovals > 1 ? '' : 's' }} Attention</p>
                    <p class="text-sm text-amber-600 dark:text-amber-400">Review and process pending approvals to keep the system running smoothly.</p>
                </div>
            </div>
            <a href="{{ route('admin.approvals.index') }}" wire:navigate class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
                Review Now
            </a>
        </div>
    </div>
    @endif
</div>

<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Members - Admin')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function members()
    {
        return User::query()
            ->with(['memberships.type', 'activeMembership.type'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhereHas('memberships', function ($mq) {
                            $mq->where('membership_number', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status === 'active', function ($query) {
                $query->whereHas('memberships', fn ($q) => $q->where('status', 'active'));
            })
            ->when($this->status === 'pending', function ($query) {
                $query->whereHas('memberships', fn ($q) => $q->where('status', 'applied'));
            })
            ->when($this->status === 'expired', function ($query) {
                $query->whereHas('memberships', fn ($q) => $q->where('status', 'expired'));
            })
            ->when($this->status === 'none', function ($query) {
                $query->whereDoesntHave('memberships');
            })
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total' => User::where('is_admin', false)->count(),
            'active' => User::whereHas('memberships', fn ($q) => $q->where('status', 'active'))->count(),
            'pending' => User::whereHas('memberships', fn ($q) => $q->where('status', 'applied'))->count(),
            'expired' => User::whereHas('memberships', fn ($q) => $q->where('status', 'expired'))->count(),
        ];
    }

    public function getMembershipStatus($user): array
    {
        $activeMembership = $user->activeMembership;
        if ($activeMembership) {
            return ['status' => 'Active', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'];
        }

        $latestMembership = $user->memberships->first();
        if (!$latestMembership) {
            return ['status' => 'No Membership', 'class' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200'];
        }

        return match($latestMembership->status) {
            'applied' => ['status' => 'Pending', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
            'approved' => ['status' => 'Approved', 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
            'suspended' => ['status' => 'Suspended', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
            'revoked' => ['status' => 'Revoked', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
            'expired' => ['status' => 'Expired', 'class' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'],
            default => ['status' => ucfirst($latestMembership->status), 'class' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200'],
        };
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Members</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Manage all registered members and their memberships.</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Users</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['total'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Active Members</p>
            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->stats['active'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Approval</p>
            <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $this->stats['pending'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Expired</p>
            <p class="mt-1 text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $this->stats['expired'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="flex-1">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name, email, or membership number..."
                class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400"
            >
        </div>
        <div class="flex gap-2">
            <select
                wire:model.live="status"
                class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="expired">Expired</option>
                <option value="none">No Membership</option>
            </select>
        </div>
    </div>

    {{-- Members Table --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Member</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Membership</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->members as $user)
                    @php
                        $membershipStatus = $this->getMembershipStatus($user);
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex size-10 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                    {{ $user->initials() }}
                                </div>
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($user->activeMembership)
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $user->activeMembership->type->name }}</p>
                                <p class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $user->activeMembership->membership_number }}</p>
                            @elseif($user->memberships->first())
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $user->memberships->first()->type->name ?? 'N/A' }}</p>
                            @else
                                <p class="text-sm text-zinc-400 dark:text-zinc-500">—</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $membershipStatus['class'] }}">
                                {{ $membershipStatus['status'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $user->created_at->format('d M Y') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <a href="{{ route('admin.members.show', $user) }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No members found</h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                @if($this->search || $this->status)
                                    Try adjusting your search or filter criteria.
                                @else
                                    No members have registered yet.
                                @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->members->hasPages())
        <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
            {{ $this->members->links() }}
        </div>
        @endif
    </div>
</div>

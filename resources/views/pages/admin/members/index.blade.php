<?php

use App\Mail\ImportWelcome;
use App\Mail\MemberMessageMail;
use App\Models\ImportFailure;
use App\Models\MemberMessage;
use App\Models\User;
use App\Models\MembershipType;
use App\Services\ExcelMemberImporter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Members - Admin')] class extends Component {
    use WithPagination;
    use WithFileUploads;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';
    
    // Import properties
    public $excelFile = null;
    public bool $showImportModal = false;
    public string $defaultPassword = 'password123';
    public string $defaultMembershipType = '';
    public bool $skipDuplicates = true;
    public bool $autoApprove = true;
    public bool $autoActivate = true;
    public bool $sendWelcomeEmail = true;
    public ?array $importResults = null;

    // Bulk message to selected members
    public array $selectedUserIds = [];
    public bool $showBulkMessageModal = false;
    public string $bulkMessageSubject = '';
    public string $bulkMessageBody = '';

    public function toggleSelectAll(bool $checked): void
    {
        if ($checked) {
            $this->selectedUserIds = $this->members->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        } else {
            $this->selectedUserIds = [];
        }
    }

    public function clearSelection(): void
    {
        $this->selectedUserIds = [];
    }

    public function openBulkMessage(): void
    {
        if (empty($this->selectedUserIds)) {
            session()->flash('error', 'Select at least one member first.');
            return;
        }
        $this->bulkMessageSubject = '';
        $this->bulkMessageBody = '';
        $this->showBulkMessageModal = true;
    }

    public function closeBulkMessage(): void
    {
        $this->showBulkMessageModal = false;
        $this->bulkMessageSubject = '';
        $this->bulkMessageBody = '';
    }

    public function sendBulkMessage(): void
    {
        $this->validate([
            'bulkMessageSubject' => 'required|string|max:255',
            'bulkMessageBody' => 'required|string|max:5000',
        ]);

        $recipients = User::whereIn('id', $this->selectedUserIds)->get();
        if ($recipients->isEmpty()) {
            session()->flash('error', 'No recipients selected.');
            $this->closeBulkMessage();
            return;
        }

        $sent = 0;
        $emailed = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                $message = MemberMessage::create([
                    'user_id' => $recipient->id,
                    'sent_by_user_id' => Auth::id(),
                    'direction' => \App\Models\MemberMessage::DIRECTION_ADMIN_TO_MEMBER,
                    'subject' => trim($this->bulkMessageSubject),
                    'body' => trim($this->bulkMessageBody),
                ]);
                $sent++;

                if ($recipient->email) {
                    try {
                        Mail::to($recipient->email)->send(new MemberMessageMail($message));
                        $message->update(['email_sent_at' => now()]);
                        $emailed++;
                    } catch (\Throwable $e) {
                        Log::warning('Failed to send bulk member message email', [
                            'user_id' => $recipient->id,
                            'message_id' => $message->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Failed to create bulk member message', [
                    'user_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            app(\App\Services\NtfyService::class)->notifyAdmins(
                'new_member',
                'Bulk Message Sent',
                Auth::user()->name . " sent \"{$this->bulkMessageSubject}\" to {$sent} member(s). {$emailed} emails sent.",
                'low',
            );
        } catch (\Exception $e) {}

        $this->closeBulkMessage();
        $this->selectedUserIds = [];

        $parts = ["{$sent} sent", "{$emailed} emailed"];
        if ($failed > 0) $parts[] = "{$failed} failed";
        session()->flash('success', 'Bulk message: ' . implode(', ', $parts) . '.');
    }

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
                        ->orWhere('id_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('memberships', function ($mq) {
                            $mq->where('membership_number', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status === 'active', function ($query) {
                $query->whereHas('memberships', fn ($q) => $q->where('status', 'active')->where(fn ($sq) => $sq->whereNull('expires_at')->orWhere('expires_at', '>', now())));
            })
            ->when($this->status === 'pending', function ($query) {
                $query->whereHas('memberships', fn ($q) => $q->where('status', 'applied'));
            })
            ->when($this->status === 'expired', function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('memberships', fn ($mq) => $mq->where('status', 'expired'))
                      ->orWhereHas('memberships', fn ($mq) => $mq->where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<=', now()));
                });
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
        return \Illuminate\Support\Facades\Cache::remember('admin_members_stats', 120, function () {
            return [
                'total' => User::where('role', User::ROLE_MEMBER)->count(),
                'active' => User::whereHas('memberships', fn ($q) => $q->where('status', 'active')->where(fn ($sq) => $sq->whereNull('expires_at')->orWhere('expires_at', '>', now())))->count(),
                'pending' => User::whereHas('memberships', fn ($q) => $q->where('status', 'applied'))->count(),
                'expired' => User::where(function ($q) {
                    $q->whereHas('memberships', fn ($mq) => $mq->where('status', 'expired'))
                      ->orWhereHas('memberships', fn ($mq) => $mq->where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<=', now()));
                })->count(),
            ];
        });
    }

    public function getMembershipStatus($user): array
    {
        $activeMembership = $user->activeMembership;
        if ($activeMembership) {
            if ($activeMembership->expires_at && $activeMembership->expires_at->isPast()) {
                return ['status' => 'Expired', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'];
            }
            return ['status' => 'Active', 'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'];
        }

        $latestMembership = $user->memberships->first();
        if (!$latestMembership) {
            return ['status' => 'No Membership', 'class' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200'];
        }

        $displayStatus = $latestMembership->status;
        if ($displayStatus === 'active' && $latestMembership->expires_at && $latestMembership->expires_at->isPast()) {
            $displayStatus = 'expired';
        }

        return match($displayStatus) {
            'applied' => ['status' => 'Pending', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300'],
            'approved' => ['status' => 'Approved', 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
            'suspended' => ['status' => 'Suspended', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
            'revoked' => ['status' => 'Revoked', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
            'expired' => ['status' => 'Expired', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
            default => ['status' => ucfirst($latestMembership->status), 'class' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200'],
        };
    }
    
    public function openImportModal(): void
    {
        $this->showImportModal = true;
        $this->reset(['excelFile', 'importResults']);
    }
    
    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->reset(['excelFile', 'importResults', 'defaultPassword', 'defaultMembershipType', 'skipDuplicates', 'autoApprove', 'autoActivate', 'sendWelcomeEmail']);
    }
    
    // Template download is handled by a dedicated route: admin.members.download-template
    
    public function importMembers(): void
    {
        $this->validate([
            'excelFile' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
            'defaultPassword' => 'required|string|min:6',
        ]);
        
        try {
            $importer = new ExcelMemberImporter();
            $filePath = $this->excelFile->storeAs('temp', 'import_' . time() . '.' . $this->excelFile->getClientOriginalExtension(), 'local');
            $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($filePath);
            
            $options = [
                'default_password' => $this->defaultPassword,
                'default_membership_type' => $this->defaultMembershipType,
                'skip_duplicates' => $this->skipDuplicates,
                'auto_approve' => $this->autoApprove,
                'auto_activate' => $this->autoActivate,
                'send_welcome_email' => $this->sendWelcomeEmail,
            ];
            
            $this->importResults = $importer->importFromExcel($fullPath, $options);
            
            // Cleanup temp file
            \Illuminate\Support\Facades\File::delete($fullPath);
            
            $failedCount = ($this->importResults['skipped'] ?? 0) + ($this->importResults['failed'] ?? 0);
            
            if ($this->importResults['success']) {
                $emailsSent = $this->importResults['emails_sent'] ?? 0;
                $msg = "Import completed: {$this->importResults['imported']} members imported.";
                if ($emailsSent > 0) {
                    $msg .= " {$emailsSent} welcome emails queued.";
                }
                if ($failedCount > 0) {
                    $msg .= " {$failedCount} rows need attention — review them on the Import Failures page.";
                }
                session()->flash('success', $msg);
                $this->resetPage(); // Refresh the members list
            } else {
                session()->flash('error', 'Import failed. Please check the errors below.');
            }
        } catch (\Exception $e) {
            $this->importResults = [
                'success' => false,
                'imported' => 0,
                'skipped' => 0,
                'failed' => 0,
                'errors' => ['Import error: ' . $e->getMessage()],
                'batch_id' => null,
            ];
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        }
    }
    
    public function resendWelcomeEmail(int $userId): void
    {
        $user = User::with(['memberships' => fn ($q) => $q->latest()])->findOrFail($userId);
        $membership = $user->memberships->first();

        if (!$membership) {
            session()->flash('error', "No membership found for {$user->name}.");
            return;
        }

        try {
            Mail::to($user->email)->send(new ImportWelcome(
                $user,
                $membership,
                'Use the password provided during import',
            ));
            session()->flash('success', "Welcome email sent to {$user->name} ({$user->email}).");
        } catch (\Exception $e) {
            Log::warning('Failed to send welcome email resend', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', "Failed to send email to {$user->name}: {$e->getMessage()}");
        }
    }

    #[Computed]
    public function membershipTypes()
    {
        return MembershipType::where('is_active', true)->orderBy('name')->get();
    }
    
    #[Computed]
    public function unresolvedFailuresCount(): int
    {
        return ImportFailure::unresolved()->count();
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Members</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manage member accounts, profiles, and membership details</p>
    </x-slot>

    {{-- Action Bar --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex gap-2 flex-wrap">
            @if($this->unresolvedFailuresCount > 0)
            <a href="{{ route('admin.members.import-failures') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-300 rounded-lg hover:bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30 dark:border-amber-700 dark:hover:bg-amber-900/50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                Import Failures
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-amber-500 rounded-full">{{ $this->unresolvedFailuresCount }}</span>
            </a>
            @endif
            <a href="{{ route('admin.members.create') }}" wire:navigate
                class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Add Member
            </a>
            <a href="{{ route('admin.members.download-template') }}" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-600 dark:hover:bg-zinc-700 transition-colors">
                <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download Template
            </a>
            <button wire:click="openImportModal" class="px-4 py-2 text-sm font-medium text-white bg-nrapa-blue rounded-lg hover:bg-nrapa-blue-dark transition-colors">
                <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Import Members
            </button>
        </div>
    </div>
    
    @if(session('success'))
        <div class="p-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl">
            <p class="text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
        </div>
    @endif
    
    @if(session('error'))
        <div class="p-4 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl">
            <p class="text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Users</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['total'] }}</p>
        </div>
        <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Active Members</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $this->stats['active'] }}</p>
        </div>
        <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Approval</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['pending'] }}</p>
        </div>
        <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
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
                placeholder="Search by name, email, ID number, or membership number..."
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

    {{-- Bulk Actions Bar (visible when rows are selected) --}}
    @if(count($selectedUserIds) > 0)
    <div class="flex items-center justify-between rounded-2xl border border-blue-300 bg-blue-50 px-4 py-3 dark:border-blue-700 dark:bg-blue-900/20">
        <p class="text-sm text-blue-800 dark:text-blue-200">
            <strong>{{ count($selectedUserIds) }}</strong> member(s) selected
        </p>
        <div class="flex items-center gap-2">
            <button wire:click="openBulkMessage"
                class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                Send Message to Selected
            </button>
            <button wire:click="clearSelection"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 transition-colors">
                Clear
            </button>
        </div>
    </div>
    @endif

    {{-- Members Table --}}
    <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/50">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox"
                                @checked(count($selectedUserIds) > 0 && count($selectedUserIds) === $this->members->count())
                                wire:click="toggleSelectAll($event.target.checked)"
                                class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Member</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Membership</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($this->members as $user)
                    @php
                        $membershipStatus = $this->getMembershipStatus($user);
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 {{ in_array($user->id, $selectedUserIds) ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                        <td class="w-10 px-4 py-4">
                            <input type="checkbox" value="{{ $user->id }}" wire:model.live="selectedUserIds"
                                class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700">
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex size-10 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                    {{ $user->initials() }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.members.show', $user) }}" wire:navigate class="font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ $user->name }}</a>
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
                                @if($user->memberships->first()->membership_number)
                                    <p class="font-mono text-sm text-zinc-400 dark:text-zinc-500">{{ $user->memberships->first()->membership_number }}</p>
                                @endif
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
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm flex items-center justify-end gap-3">
                            <button wire:click="resendWelcomeEmail({{ $user->id }})"
                                wire:confirm="Send welcome email to {{ $user->name }} ({{ $user->email }})?"
                                title="Resend welcome email"
                                class="text-zinc-400 hover:text-nrapa-blue dark:text-zinc-500 dark:hover:text-nrapa-blue-light transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </button>
                            <a href="{{ route('admin.members.show', $user) }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
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
        <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-800">
            {{ $this->members->links() }}
        </div>
        @endif
    </div>
    
    {{-- Import Modal --}}
    @if($showImportModal)
    <div x-data="{ show: @entangle('showImportModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @click.away="show = false">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="show = false"></div>
            
            <div class="relative w-full max-w-3xl rounded-xl bg-white shadow-xl dark:bg-zinc-800">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Import Members from Excel</h2>
                    <button wire:click="closeImportModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="px-6 py-4">
                    {{-- Expected Format Info --}}
                    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                        <p class="text-xs font-semibold text-blue-800 dark:text-blue-200 mb-1">Expected Excel columns (in order):</p>
                        <p class="text-xs text-blue-700 dark:text-blue-300">
                            Date Joined &bull; Member Number <span class="text-blue-400">(ignored)</span> &bull; Initials &bull; Surname &bull; ID Number &bull; Tel Number &bull; Email &bull; Membership Type &bull; Renewal Date &bull; Status (Active / blank)
                        </p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                            Matches the old DS spreadsheet layout — copy-paste directly. Member Number &amp; DS Activity columns are ignored. New NRAPA numbers are assigned automatically.
                        </p>
                    </div>

                    <form wire:submit="importMembers" class="space-y-6">
                        {{-- File Upload --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Excel File (.xlsx, .xls)</label>
                            <div class="mt-1 flex justify-center rounded-lg border border-dashed border-zinc-300 px-6 py-10 dark:border-zinc-600">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <div class="mt-4 flex text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                        <label class="relative cursor-pointer rounded-md bg-white font-semibold text-emerald-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-emerald-600 focus-within:ring-offset-2 hover:text-emerald-500 dark:bg-zinc-800">
                                            <span>Upload a file</span>
                                            <input wire:model="excelFile" type="file" accept=".xlsx,.xls" class="sr-only">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs leading-5 text-zinc-600 dark:text-zinc-400">XLSX, XLS up to 10MB</p>
                                    @if($excelFile)
                                    <p class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">{{ $excelFile->getClientOriginalName() }}</p>
                                    @endif
                                </div>
                            </div>
                            @error('excelFile') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        
                        {{-- Import Options --}}
                        <div class="space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Import Options</h3>
                            
                            <div>
                                <label for="defaultPassword" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Default Password <span class="text-red-500">*</span></label>
                                <input type="text" id="defaultPassword" wire:model="defaultPassword" placeholder="password123"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">All imported members will use this password initially</p>
                                @error('defaultPassword') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            
                            <div>
                                <label for="defaultMembershipType" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Default Membership Type</label>
                                <select id="defaultMembershipType" wire:model="defaultMembershipType"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                    <option value="">None (use from Excel file)</option>
                                    @foreach($this->membershipTypes as $type)
                                    <option value="{{ $type->slug }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Applied if membership type is not specified in Excel</p>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="skipDuplicates" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Skip duplicate emails/ID numbers</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="autoApprove" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Auto-approve memberships</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="autoActivate" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Auto-activate memberships</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="sendWelcomeEmail" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Send welcome email to imported members</span>
                                </label>
                            </div>

                        </div>
                        
                        {{-- Import Results --}}
                        @if($importResults)
                        <div class="border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <div class="rounded-lg p-4 {{ $importResults['success'] ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                                <div class="flex items-start gap-3">
                                    @if($importResults['success'])
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @else
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                    <div class="flex-1">
                                        <p class="font-medium {{ $importResults['success'] ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200' }}">
                                            Import {{ $importResults['success'] ? 'Completed' : 'Failed' }}
                                        </p>
                                        <p class="mt-1 text-sm {{ $importResults['success'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                            Imported: {{ $importResults['imported'] }},
                                            Skipped: {{ $importResults['skipped'] ?? 0 }},
                                            Failed: {{ $importResults['failed'] ?? 0 }}
                                            @if(($importResults['emails_sent'] ?? 0) > 0)
                                            , Emails queued: {{ $importResults['emails_sent'] }}
                                            @endif
                                        </p>
                                        @php $totalFailed = ($importResults['skipped'] ?? 0) + ($importResults['failed'] ?? 0); @endphp
                                        @if($totalFailed > 0)
                                        <a href="{{ route('admin.members.import-failures') }}" wire:navigate
                                            class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                            Review {{ $totalFailed }} failed row{{ $totalFailed !== 1 ? 's' : '' }}
                                        </a>
                                        @endif
                                        @if(!empty($importResults['errors']))
                                        <div class="mt-3 max-h-40 overflow-y-auto">
                                            <p class="text-xs font-medium text-red-800 dark:text-red-200 mb-1">Errors:</p>
                                            <ul class="text-xs text-red-700 dark:text-red-300 space-y-1 list-disc list-inside">
                                                @foreach(array_slice($importResults['errors'], 0, 10) as $error)
                                                <li>{{ $error }}</li>
                                                @endforeach
                                                @if(count($importResults['errors']) > 10)
                                                <li>... and {{ count($importResults['errors']) - 10 }} more errors</li>
                                                @endif
                                            </ul>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <div class="flex justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <button type="button" wire:click="closeImportModal" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-200 dark:border-zinc-600 dark:hover:bg-zinc-600 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-nrapa-blue rounded-lg hover:bg-nrapa-blue-dark transition-colors">
                                Import Members
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Bulk Message Modal --}}
    @if($showBulkMessageModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="closeBulkMessage" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-zinc-800">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Send Message</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">To {{ count($selectedUserIds) }} selected member(s)</p>
                    </div>
                    <button wire:click="closeBulkMessage" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="sendBulkMessage" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Subject <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="bulkMessageSubject" maxlength="255"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        @error('bulkMessageSubject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Message <span class="text-red-500">*</span></label>
                        <textarea wire:model="bulkMessageBody" rows="6" maxlength="5000" placeholder="Each selected member will get their own copy in their inbox and by email."
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                        @error('bulkMessageBody') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
                        <p class="text-xs text-amber-800 dark:text-amber-200">
                            This will create <strong>{{ count($selectedUserIds) }}</strong> individual messages and queue an email for each member with an address on file.
                        </p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                        <button type="button" wire:click="closeBulkMessage" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-200 dark:border-zinc-600 transition-colors">Cancel</button>
                        <button type="submit" wire:confirm="Send this message to {{ count($selectedUserIds) }} member(s)?"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">Send to {{ count($selectedUserIds) }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

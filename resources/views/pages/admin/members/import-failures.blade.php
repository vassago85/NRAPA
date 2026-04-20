<?php

use App\Models\ImportFailure;
use App\Models\MembershipType;
use App\Models\User;
use App\Services\ExcelMemberImporter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Import Failures - Admin')] class extends Component {
    use WithPagination;

    public string $filterBatch = '';
    public bool $showEditModal = false;
    public ?int $editingId = null;

    // Editable fields
    public string $editDateJoined = '';
    public string $editInitials = '';
    public string $editSurname = '';
    public string $editIdNumber = '';
    public string $editPhone = '';
    public string $editEmail = '';
    public string $editMembershipType = '';
    public string $editRenewalDate = '';
    public string $editStatus = '';

    public ?string $retryError = null;
    public ?string $retrySuccess = null;

    // Existing-user match detected from the current form values
    public ?int $matchedUserId = null;
    public ?string $matchedBy = null;

    public function with(): array
    {
        $query = ImportFailure::unresolved()
            ->with('importedBy')
            ->latest();

        if ($this->filterBatch) {
            $query->forBatch($this->filterBatch);
        }

        return [
            'failures' => $query->paginate(20),
            'batches' => ImportFailure::unresolved()
                ->select('batch_id')
                ->selectRaw('MIN(created_at) as imported_at')
                ->selectRaw('COUNT(*) as failure_count')
                ->groupBy('batch_id')
                ->orderByDesc('imported_at')
                ->get(),
        ];
    }

    public function openEdit(int $id): void
    {
        $failure = ImportFailure::findOrFail($id);
        $data = $failure->row_data;

        $this->editingId = $id;
        $this->editDateJoined = $data['date_joined'] ?? '';
        $this->editInitials = $data['initials'] ?? '';
        $this->editSurname = $data['surname'] ?? '';
        $this->editIdNumber = $data['id_number'] ?? '';
        $this->editPhone = $data['phone'] ?? '';
        $this->editEmail = $data['email'] ?? '';
        $this->editMembershipType = $data['membership_type'] ?? '';
        $this->editRenewalDate = $data['renewal_date'] ?? '';
        $this->editStatus = $data['status'] ?? '';

        $this->retryError = null;
        $this->retrySuccess = null;
        $this->refreshMatch();
        $this->showEditModal = true;
    }

    public function closeEdit(): void
    {
        $this->showEditModal = false;
        $this->editingId = null;
        $this->retryError = null;
        $this->retrySuccess = null;
        $this->matchedUserId = null;
        $this->matchedBy = null;
    }

    /**
     * Re-run the existing-user lookup whenever the admin edits identifying fields.
     */
    public function updatedEditIdNumber(): void { $this->refreshMatch(); }
    public function updatedEditEmail(): void    { $this->refreshMatch(); }
    public function updatedEditPhone(): void    { $this->refreshMatch(); }

    protected function refreshMatch(): void
    {
        $result = (new ExcelMemberImporter)->findExistingUser([
            'id_number' => $this->editIdNumber,
            'email' => $this->editEmail,
            'phone' => $this->editPhone,
        ]);

        $this->matchedUserId = $result['user']?->id;
        $this->matchedBy = $result['matched_by'];
    }

    #[Computed]
    public function matchedUser(): ?User
    {
        return $this->matchedUserId ? User::with(['activeMembership.type'])->find($this->matchedUserId) : null;
    }

    public function retryImport(): void
    {
        $failure = ImportFailure::findOrFail($this->editingId);

        $this->validate([
            'editInitials' => 'required|string|max:10',
            'editSurname' => 'required|string|max:255',
            'editEmail' => 'required|email',
        ]);

        $rowData = [
            'date_joined' => $this->editDateJoined,
            'initials' => $this->editInitials,
            'surname' => $this->editSurname,
            'id_number' => $this->editIdNumber,
            'phone' => $this->editPhone,
            'email' => $this->editEmail,
            'membership_type' => $this->editMembershipType,
            'renewal_date' => $this->editRenewalDate,
            'status' => $this->editStatus,
        ];

        $importer = new ExcelMemberImporter();
        $result = $importer->importSingleMember($rowData);

        if ($result['success']) {
            $failure->update(['row_data' => $rowData]);
            $failure->markResolved();

            try {
                app(\App\Services\NtfyService::class)->notifyAdmins(
                    'new_member',
                    'Import Retry Successful',
                    auth()->user()->name . " resolved import for {$this->editInitials} {$this->editSurname} ({$this->editEmail}).",
                );
            } catch (\Exception $e) {}

            $this->retrySuccess = "Successfully imported {$this->editInitials} {$this->editSurname}.";
            $this->retryError = null;

            // Close modal after short delay (handled in frontend)
            $this->showEditModal = false;
            $this->editingId = null;
            session()->flash('success', $this->retrySuccess);
        } else {
            $this->retryError = $result['error'];
            $this->retrySuccess = null;
        }
    }

    /**
     * Apply the row's data to the matched existing user instead of creating a new one.
     * Updates user details, membership type/expiry/status, and dedicated status.
     */
    public function updateExistingMember(): void
    {
        $this->validate([
            'editInitials' => 'required|string|max:10',
            'editSurname' => 'required|string|max:255',
            'editMembershipType' => 'required|string',
        ]);

        if (! $this->matchedUserId) {
            $this->retryError = 'No matched user to update. Enter a known ID, email, or phone first.';
            return;
        }

        $user = User::find($this->matchedUserId);
        if (! $user) {
            $this->retryError = 'Matched user no longer exists.';
            return;
        }

        $failure = ImportFailure::findOrFail($this->editingId);

        $rowData = [
            'date_joined' => $this->editDateJoined,
            'initials' => $this->editInitials,
            'surname' => $this->editSurname,
            'id_number' => $this->editIdNumber,
            'phone' => $this->editPhone,
            'email' => $this->editEmail,
            'membership_type' => $this->editMembershipType,
            'renewal_date' => $this->editRenewalDate,
            'status' => $this->editStatus,
        ];

        $result = (new ExcelMemberImporter)->updateExistingMember($user, $rowData);

        if ($result['success']) {
            $failure->update(['row_data' => $rowData]);
            $failure->markResolved();

            try {
                app(\App\Services\NtfyService::class)->notifyAdmins(
                    'new_member',
                    'Import: Existing Member Updated',
                    auth()->user()->name . " updated {$user->name} ({$user->email}) from import row #{$failure->row_number}.",
                );
            } catch (\Exception $e) {}

            session()->flash('success', "Updated existing member: {$user->name}.");
            $this->showEditModal = false;
            $this->editingId = null;
            $this->matchedUserId = null;
            $this->matchedBy = null;
        } else {
            $this->retryError = $result['error'];
            $this->retrySuccess = null;
        }
    }

    public function dismiss(int $id): void
    {
        $failure = ImportFailure::findOrFail($id);
        $failure->markResolved();
        session()->flash('success', "Row #{$failure->row_number} dismissed.");
    }

    /**
     * Bulk-resolve every unresolved failure (optionally filtered to the current batch)
     * that auto-matches an existing user by ID > email > phone. Unmatched rows and
     * rows whose update fails are left in place for manual handling.
     */
    public function resolveAllMatches(): void
    {
        $query = ImportFailure::unresolved();
        if ($this->filterBatch) {
            $query->forBatch($this->filterBatch);
        }

        $importer = new ExcelMemberImporter();
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $failedRows = [];

        foreach ($query->get() as $failure) {
            $data = $failure->row_data ?? [];

            $match = $importer->findExistingUser([
                'id_number' => $data['id_number'] ?? '',
                'email'     => $data['email'] ?? '',
                'phone'     => $data['phone'] ?? '',
            ]);

            if (! $match['user']) {
                $skipped++;
                continue;
            }

            $result = $importer->updateExistingMember($match['user'], $data);

            if ($result['success']) {
                $failure->markResolved();
                $updated++;
            } else {
                $failed++;
                $label = trim(($data['initials'] ?? '') . ' ' . ($data['surname'] ?? ''));
                $failedRows[] = $label ?: "Row #{$failure->row_number}";
            }
        }

        try {
            app(\App\Services\NtfyService::class)->notifyAdmins(
                'new_member',
                'Bulk Import Resolution',
                auth()->user()->name . " auto-resolved {$updated} import failure(s) by matching existing members.",
            );
        } catch (\Exception $e) {}

        $parts = ["{$updated} updated"];
        if ($skipped > 0) $parts[] = "{$skipped} with no match left";
        if ($failed > 0) $parts[] = "{$failed} failed" . ($failedRows ? ' (' . implode(', ', array_slice($failedRows, 0, 5)) . (count($failedRows) > 5 ? '…' : '') . ')' : '');

        session()->flash('success', 'Bulk resolve complete: ' . implode(', ', $parts) . '.');
    }

    public function dismissAll(): void
    {
        $query = ImportFailure::unresolved();
        if ($this->filterBatch) {
            $query->forBatch($this->filterBatch);
        }
        $count = $query->count();
        $query->update(['resolved' => true, 'resolved_at' => now()]);
        session()->flash('success', "{$count} failure(s) dismissed.");
    }

    #[Computed]
    public function membershipTypes()
    {
        return MembershipType::where('is_active', true)->orderBy('name')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Import Failures</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review member import failures</p>
    </x-slot>
    
    {{-- Header Actions --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.members.index') }}" wire:navigate class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <p class="text-zinc-600 dark:text-zinc-400">Review and fix members that failed to import. Edit the data and retry, or dismiss rows you don't need.</p>
            </div>
        </div>
        <div class="flex gap-2">
            @if($failures->total() > 0)
            <button wire:click="resolveAllMatches" wire:confirm="For every failure{{ $filterBatch ? ' in this batch' : '' }} that matches an existing member by ID, email, or phone, overwrite that member's details, membership, and dedicated status with the spreadsheet values. Continue?"
                class="px-4 py-2 text-sm font-medium text-amber-800 bg-amber-50 border border-amber-300 rounded-lg hover:bg-amber-100 dark:text-amber-200 dark:bg-amber-900/30 dark:border-amber-700 dark:hover:bg-amber-900/50 transition-colors">
                Resolve All Matches
            </button>
            <button wire:click="dismissAll" wire:confirm="Are you sure you want to dismiss all unresolved failures{{ $filterBatch ? ' in this batch' : '' }}?"
                class="px-4 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-300 rounded-lg hover:bg-red-100 dark:text-red-300 dark:bg-red-900/30 dark:border-red-700 dark:hover:bg-red-900/50 transition-colors">
                Dismiss All
            </button>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="p-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl">
        <p class="text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Batch Filter --}}
    @if($batches->count() > 1)
    <div class="flex items-center gap-3">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Filter by import batch:</label>
        <select wire:model.live="filterBatch" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
            <option value="">All batches ({{ $batches->sum('failure_count') }})</option>
            @foreach($batches as $batch)
            <option value="{{ $batch->batch_id }}">
                {{ \Carbon\Carbon::parse($batch->imported_at)->format('d M Y H:i') }} ({{ $batch->failure_count }} failures)
            </option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- Failures Table --}}
    @if($failures->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <svg class="w-16 h-16 text-emerald-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <h3 class="text-lg font-medium text-zinc-900 dark:text-white">No unresolved import failures</h3>
        <p class="mt-1 text-zinc-500 dark:text-zinc-400">All import issues have been resolved.</p>
        <a href="{{ route('admin.members.index') }}" wire:navigate class="mt-4 text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 transition-colors">
            Back to Members
        </a>
    </div>
    @else
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Row</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">ID Number</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Membership Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Error</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach($failures as $failure)
                    @php $data = $failure->row_data; @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 font-mono text-xs">{{ $failure->row_number }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ trim(($data['initials'] ?? '') . ' ' . ($data['surname'] ?? '')) ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $data['email'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 font-mono text-xs">{{ $data['id_number'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $data['membership_type'] ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs text-red-600 dark:text-red-400">{{ Str::limit($failure->error_message, 60) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openEdit({{ $failure->id }})"
                                    class="px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 rounded-lg hover:bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 transition-colors">
                                    Edit & Retry
                                </button>
                                <button wire:click="dismiss({{ $failure->id }})" wire:confirm="Dismiss this failure?"
                                    class="px-3 py-1.5 text-xs font-medium text-zinc-500 bg-zinc-100 rounded-lg hover:bg-zinc-200 dark:text-zinc-400 dark:bg-zinc-700 dark:hover:bg-zinc-600 transition-colors">
                                    Dismiss
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($failures->hasPages())
        <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800">
            {{ $failures->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- Edit & Retry Modal --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="closeEdit" class="fixed inset-0 bg-black/50 transition-opacity"></div>

            <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-xl dark:bg-zinc-800">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Edit & Retry Import</h2>
                    <button wire:click="closeEdit" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-6 py-4">
                    @if($retryError)
                    <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                        <p class="text-sm text-red-700 dark:text-red-300">{{ $retryError }}</p>
                    </div>
                    @endif

                    {{-- Existing member match banner --}}
                    @if($this->matchedUser)
                    @php
                        $mu = $this->matchedUser;
                        $activeMembership = $mu->activeMembership;
                    @endphp
                    <div class="mb-4 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-amber-900 dark:text-amber-200">
                                    Existing member found — matched by {{ $matchedBy }}
                                </p>
                                <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">
                                    <b>{{ $mu->name }}</b> &lt;{{ $mu->email }}&gt;
                                    @if($mu->phone) · {{ $mu->phone }}@endif
                                    @if($mu->id_number) · ID {{ $mu->id_number }}@endif
                                </p>
                                <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
                                    Current membership:
                                    @if($activeMembership)
                                        {{ $activeMembership->type->name ?? '—' }} ({{ $activeMembership->status }})@if($activeMembership->expires_at) — expires {{ $activeMembership->expires_at->format('d M Y') }}@endif
                                    @else
                                        none
                                    @endif
                                </p>
                                <p class="mt-2 text-xs text-amber-700 dark:text-amber-400">
                                    You can update this member's record with the spreadsheet values instead of creating a duplicate.
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <form wire:submit="retryImport" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date Joined</label>
                                <input type="text" wire:model="editDateJoined" placeholder="DD/MM/YYYY"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Initials <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="editInitials" placeholder="e.g. SP"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                @error('editInitials') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Surname <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="editSurname"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                @error('editSurname') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">ID Number</label>
                                <input type="text" wire:model.live.debounce.500ms="editIdNumber" placeholder="SA ID (13 digits)"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-mono dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Phone</label>
                                <input type="text" wire:model.live.debounce.500ms="editPhone"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Email <span class="text-red-500">*</span></label>
                                <input type="email" wire:model.live.debounce.500ms="editEmail"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                @error('editEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Membership Type</label>
                                <select wire:model="editMembershipType"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                    <option value="">— Select —</option>
                                    <option value="Regular Member">Regular Member</option>
                                    <option value="Dedicated Sport Shooter">Dedicated Sport Shooter</option>
                                    <option value="Dedicated Hunter">Dedicated Hunter</option>
                                    <option value="Dedicated Hunting & Sport">Dedicated Hunting & Sport</option>
                                    <option value="Dedicated Life Membership">Dedicated Life Membership</option>
                                    <option value="Junior">Junior</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Renewal Date</label>
                                <input type="text" wire:model="editRenewalDate" placeholder="DD/MM/YYYY or Life Member"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                            <select wire:model="editStatus"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <option value="">— blank —</option>
                                <option value="Active">Active</option>
                            </select>
                        </div>

                        <div class="flex flex-wrap justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                            <button type="button" wire:click="closeEdit"
                                class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-200 dark:border-zinc-600 dark:hover:bg-zinc-600 transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $this->matchedUser ? 'text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-200 dark:border-zinc-600 dark:hover:bg-zinc-600' : 'text-white bg-nrapa-blue hover:bg-nrapa-blue-dark' }}">
                                Retry as New Member
                            </button>
                            @if($this->matchedUser)
                            <button type="button" wire:click="updateExistingMember"
                                wire:confirm="Apply these spreadsheet values to the existing member {{ $this->matchedUser->name }}? This will overwrite their membership type, renewal date, and dedicated status."
                                class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition-colors">
                                Update Existing Member
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

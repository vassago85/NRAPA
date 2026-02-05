<?php

use App\Models\User;
use App\Models\MembershipType;
use App\Services\ExcelMemberImporter;
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
    public bool $autoApprove = false;
    public bool $autoActivate = false;
    public ?array $importResults = null;

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
    
    public function openImportModal(): void
    {
        $this->showImportModal = true;
        $this->reset(['excelFile', 'importResults']);
    }
    
    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->reset(['excelFile', 'importResults', 'defaultPassword', 'defaultMembershipType', 'skipDuplicates', 'autoApprove', 'autoActivate']);
    }
    
    public function downloadTemplate(): void
    {
        $importer = new ExcelMemberImporter();
        $tempPath = storage_path('app/temp/member_import_template.xlsx');
        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($tempPath));
        $importer->generateTemplate($tempPath);
        
        return response()->download($tempPath, 'member_import_template.xlsx')->deleteFileAfterSend();
    }
    
    public function importMembers(): void
    {
        $this->validate([
            'excelFile' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
            'defaultPassword' => 'required|string|min:6',
        ]);
        
        try {
            $importer = new ExcelMemberImporter();
            $filePath = $this->excelFile->storeAs('temp', 'import_' . time() . '.' . $this->excelFile->getClientOriginalExtension());
            $fullPath = storage_path('app/' . $filePath);
            
            $options = [
                'default_password' => $this->defaultPassword,
                'default_membership_type' => $this->defaultMembershipType,
                'skip_duplicates' => $this->skipDuplicates,
                'auto_approve' => $this->autoApprove,
                'auto_activate' => $this->autoActivate,
            ];
            
            $this->importResults = $importer->importFromExcel($fullPath, $options);
            
            // Cleanup temp file
            \Illuminate\Support\Facades\File::delete($fullPath);
            
            if ($this->importResults['success']) {
                session()->flash('success', "Import completed: {$this->importResults['imported']} members imported, {$this->importResults['skipped']} skipped.");
                $this->resetPage(); // Refresh the members list
            } else {
                session()->flash('error', 'Import failed. Please check the errors below.');
            }
        } catch (\Exception $e) {
            $this->importResults = [
                'success' => false,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Import error: ' . $e->getMessage()],
            ];
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        }
    }
    
    #[Computed]
    public function membershipTypes()
    {
        return MembershipType::where('is_active', true)->orderBy('name')->get();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Members</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Manage all registered members and their memberships.</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="downloadTemplate" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-600 dark:hover:bg-zinc-700">
                <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download Template
            </button>
            <button wire:click="openImportModal" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Import Members
            </button>
        </div>
    </div>
    
    @if(session('success'))
        <div class="p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif
    
    @if(session('error'))
        <div class="p-4 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

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
    
    {{-- Import Modal --}}
    @if($showImportModal)
    <div x-data="{ show: @entangle('showImportModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @click.away="show = false">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="show = false"></div>
            
            <div class="relative w-full max-w-3xl rounded-xl bg-white shadow-xl dark:bg-zinc-800">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Import Members from Excel</h2>
                    <button wire:click="closeImportModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="px-6 py-4">
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
                        <div class="space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
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
                            </div>
                        </div>
                        
                        {{-- Import Results --}}
                        @if($importResults)
                        <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <div class="rounded-lg p-4 {{ $importResults['success'] ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                                <div class="flex items-start gap-3">
                                    @if($importResults['success'])
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @else
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                    <div class="flex-1">
                                        <p class="font-medium {{ $importResults['success'] ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                            Import {{ $importResults['success'] ? 'Completed' : 'Failed' }}
                                        </p>
                                        <p class="mt-1 text-sm {{ $importResults['success'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                            Imported: {{ $importResults['imported'] }}, Skipped: {{ $importResults['skipped'] }}
                                        </p>
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
                        
                        <div class="flex justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <button type="button" wire:click="closeImportModal" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-200 dark:border-zinc-600 dark:hover:bg-zinc-600">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                                Import Members
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<?php

use App\Models\MembershipType;
use App\Services\ExcelMemberImporter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add Member - Admin')] class extends Component {
    public string $memberMode = 'import'; // 'import' or 'new'
    public string $initials = '';
    public string $surname = '';
    public string $email = '';
    public string $idNumber = '';
    public string $phone = '';
    public string $membershipType = '';
    public string $dateJoined = '';
    public string $renewalDate = '';
    public string $status = 'active';
    public string $defaultPassword = 'Nrapa2026!';
    public bool $sendWelcomeEmail = true;

    public ?string $error = null;

    public function updatedMemberMode(): void
    {
        if ($this->memberMode === 'new') {
            $this->status = 'applied';
        } else {
            $this->status = 'active';
        }
    }

    #[Computed]
    public function membershipTypes()
    {
        return MembershipType::where('is_active', true)->orderBy('name')->get();
    }

    public function addMember(): void
    {
        $this->validate([
            'memberMode' => 'required|in:import,new',
            'initials' => 'required|string|max:20',
            'surname' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'idNumber' => 'nullable|string|max:13',
            'phone' => 'nullable|string|max:20',
            'membershipType' => 'nullable|string',
            'dateJoined' => 'nullable|date',
            'renewalDate' => 'nullable|date',
            'status' => 'required|in:active,applied',
            'defaultPassword' => 'required|string|min:6',
        ]);

        $this->error = null;

        $isImport = $this->memberMode === 'import';

        $rowData = [
            'date_joined' => $this->dateJoined ?: now()->toDateString(),
            'initials' => $this->initials,
            'surname' => $this->surname,
            'id_number' => $this->idNumber,
            'phone' => $this->phone,
            'email' => $this->email,
            'membership_type' => $this->membershipType,
            'renewal_date' => $this->renewalDate,
            'status' => $isImport ? 'Active' : '',
        ];

        $options = [
            'default_password' => $this->defaultPassword,
            'auto_approve' => $isImport,
            'auto_activate' => $isImport,
            'send_welcome_email' => $this->sendWelcomeEmail,
            'source' => $isImport ? 'import' : 'admin',
        ];

        $importer = new ExcelMemberImporter();
        $result = $importer->importSingleMember($rowData, $options);

        if ($result['success']) {
            $emailNote = ($this->sendWelcomeEmail && !($result['email_sent'] ?? false))
                ? ' (Welcome email could not be sent — check mail configuration.)'
                : '';
            $msg = $isImport
                ? "Member {$this->initials} {$this->surname} imported successfully.{$emailNote}"
                : "Member {$this->initials} {$this->surname} created. They will be prompted to pay on first login.{$emailNote}";
            session()->flash('success', $msg);
            $this->redirectRoute('admin.members.show', ['user' => $result['user']->uuid], navigate: true);
        } else {
            $this->error = $result['error'];
        }
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.members.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Add Member</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manually add a single member to the system</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        @if($error)
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
            <div class="flex items-center gap-3">
                <svg class="size-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-red-700 dark:text-red-300">{{ $error }}</p>
            </div>
        </div>
        @endif

        <form wire:submit="addMember" class="space-y-6">
            {{-- Member Type Toggle --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Member Type</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative cursor-pointer">
                            <input type="radio" wire:model.live="memberMode" value="import" class="peer sr-only">
                            <div class="rounded-xl border-2 p-4 transition-colors peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/50">
                                        <svg class="size-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-zinc-900 dark:text-white">Import Existing</span>
                                </div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Existing member from another system. Account activated immediately, no payment required.</p>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" wire:model.live="memberMode" value="new" class="peer sr-only">
                            <div class="rounded-xl border-2 p-4 transition-colors peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="flex size-8 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/50">
                                        <svg class="size-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-zinc-900 dark:text-white">New Member</span>
                                </div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Brand-new member. They will be prompted to make payment on first login.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Personal Information --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Personal Information</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="initials" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Initials <span class="text-red-500">*</span></label>
                            <input type="text" id="initials" wire:model="initials" placeholder="e.g. J.P."
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('initials') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="surname" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Surname <span class="text-red-500">*</span></label>
                            <input type="text" id="surname" wire:model="surname" placeholder="e.g. Smith"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('surname') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" id="email" wire:model="email" placeholder="member@example.com"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        @error('email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="idNumber" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">ID Number</label>
                            <input type="text" id="idNumber" wire:model="idNumber" placeholder="13-digit SA ID" maxlength="13"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Date of birth is derived automatically</p>
                            @error('idNumber') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Phone Number</label>
                            <input type="text" id="phone" wire:model="phone" placeholder="e.g. 082 123 4567"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('phone') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Membership Details --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Membership Details</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label for="membershipType" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Membership Type</label>
                        <select id="membershipType" wire:model="membershipType"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <option value="">Select a membership type</option>
                            @foreach($this->membershipTypes as $type)
                                @if($memberMode === 'import')
                                <option value="{{ $type->slug }}">{{ $type->name }} — R{{ number_format($type->renewal_price ?? $type->initial_price, 2) }}/yr</option>
                                @else
                                <option value="{{ $type->slug }}">{{ $type->name }} — R{{ number_format($type->initial_price, 2) }}</option>
                                @endif
                            @endforeach
                        </select>
                        @if($memberMode === 'import')
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Showing annual renewal prices for existing members</p>
                        @else
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Showing sign-up prices — member will be prompted to pay</p>
                        @endif
                        @error('membershipType') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    @if($memberMode === 'import')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="dateJoined" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date Joined</label>
                            <input type="date" id="dateJoined" wire:model="dateJoined"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Defaults to today if left blank</p>
                            @error('dateJoined') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="renewalDate" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Renewal Date</label>
                            <input type="date" id="renewalDate" wire:model="renewalDate"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Leave blank for auto-calculation from membership type</p>
                            @error('renewalDate') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Account Settings --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Account Settings</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label for="defaultPassword" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Default Password <span class="text-red-500">*</span></label>
                        <input type="text" id="defaultPassword" wire:model="defaultPassword"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">The member will use this to sign in initially</p>
                        @error('defaultPassword') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="sendWelcomeEmail" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">Send welcome email with login details</span>
                    </label>
                </div>
            </div>

            {{-- Info Banner --}}
            @if($memberMode === 'new')
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                <div class="flex items-start gap-3">
                    <svg class="size-5 flex-shrink-0 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <p class="font-medium">New member payment flow</p>
                        <p class="mt-1 text-blue-600 dark:text-blue-400">The member will be created with status "Applied". When they sign in, they'll see payment instructions with a unique reference and the amount due for their membership type.</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.members.index') }}" wire:navigate
                    class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-600 dark:hover:bg-zinc-700 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-nrapa-blue rounded-lg hover:bg-nrapa-blue-dark transition-colors">
                    {{ $memberMode === 'import' ? 'Import Member' : 'Create Member' }}
                </button>
            </div>
        </form>
    </div>
</div>

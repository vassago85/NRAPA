<div>
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-2">
            <a href="{{ route('member.endorsements.index') }}" wire:navigate class="inline-flex items-center gap-1 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Back
            </a>
        </div>
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
            {{ $editingRequest ? 'Edit Endorsement Request' : 'Request Endorsement Letter' }}
        </h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Complete the form below to request a dedicated status endorsement letter.</p>
    </div>

    {{-- Progress Steps (3 steps) --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @php
                $steps = [
                    1 => 'Type',
                    2 => 'Firearm / Component',
                    3 => 'Review',
                ];
            @endphp
            @foreach($steps as $num => $label)
                <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                    <button 
                        wire:click="goToStep({{ $num }})"
                        @disabled($num > $this->getMaxAllowedStep())
                        class="flex items-center justify-center w-10 h-10 rounded-full text-sm font-semibold transition-colors
                            {{ $currentStep === $num 
                                ? 'bg-emerald-600 text-white' 
                                : ($currentStep > $num 
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' 
                                    : 'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400') }}
                            {{ $num <= $this->getMaxAllowedStep() ? 'cursor-pointer hover:ring-2 hover:ring-emerald-300' : 'cursor-not-allowed' }}"
                    >
                        @if($currentStep > $num)
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            {{ $num }}
                        @endif
                    </button>
                    <span class="ml-2 text-sm font-medium {{ $currentStep === $num ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400' }} hidden sm:inline">
                        {{ $label }}
                    </span>
                    @if(!$loop->last)
                        <div class="flex-1 h-0.5 mx-4 {{ $currentStep > $num ? 'bg-emerald-500' : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Step Content --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700">
        {{-- Step 1: Request Type --}}
        @if($currentStep === 1)
            <div class="p-6">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">Select Request Type</h2>
                
                <div class="grid gap-4 md:grid-cols-2">
                    {{-- New Endorsement --}}
                    <label class="relative cursor-pointer h-full">
                        <input type="radio" wire:model.live="requestType" value="new" class="peer sr-only">
                        <div class="h-full p-6 border-2 rounded-xl transition-all peer-checked:border-emerald-600 peer-checked:bg-emerald-100 peer-checked:shadow-lg peer-checked:shadow-emerald-200/50 dark:peer-checked:bg-emerald-900/30 dark:peer-checked:shadow-emerald-900/50 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600">
                            <div class="flex items-start gap-4 h-full">
                                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex-shrink-0">
                                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">New Endorsement</h3>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                        First-time endorsement letter for a Section 16 firearm application. Can include component requests (barrels, actions).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </label>

                    {{-- Renewal Endorsement --}}
                    <label class="relative cursor-pointer h-full">
                        <input type="radio" wire:model.live="requestType" value="renewal" class="peer sr-only">
                        <div class="h-full p-6 border-2 rounded-xl transition-all peer-checked:border-emerald-600 peer-checked:bg-emerald-100 peer-checked:shadow-lg peer-checked:shadow-emerald-200/50 dark:peer-checked:bg-emerald-900/30 dark:peer-checked:shadow-emerald-900/50 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600">
                            <div class="flex items-start gap-4 h-full">
                                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex-shrink-0">
                                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Renewal Endorsement</h3>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                        Endorsement renewal for existing firearms.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>

                @error('requestType')
                    <p class="mt-4 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Step 2: Type of firearm / component + details --}}
        @if($currentStep === 2)
            <div class="p-6" 
                 x-data="{ panelData: @entangle('firearmPanelData') }"
                 @firearm-data-updated.window="panelData = $event.detail.data; $wire.syncFirearmPanelData(panelData)">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">What are you requesting endorsement for?</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6">All endorsements are Section 16 for dedicated sport or hunting.</p>

                <div class="mb-8">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">
                        Type <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                        @foreach($entryTypeOptions as $value => $label)
                            <label class="relative cursor-pointer">
                                <input type="radio" wire:model.live="endorsementEntryType" value="{{ $value }}" class="peer sr-only">
                                <div class="p-4 text-center border-2 rounded-lg transition-all peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:shadow-lg peer-checked:shadow-emerald-200/50 dark:peer-checked:bg-emerald-600 dark:peer-checked:text-white dark:peer-checked:shadow-emerald-900/50 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 bg-white dark:bg-zinc-800">
                                    <span class="text-sm font-medium">{{ $label }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('endorsementEntryType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Firearm (Rifle / Shotgun / Handgun) --}}
                @if(in_array($endorsementEntryType, ['rifle', 'shotgun', 'handgun']))
                <div class="space-y-6">
                    @if($this->userFirearms->count() > 0)
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <label class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">Load from your Virtual Safe</label>
                            <div class="flex gap-3">
                                <select wire:model="existingFirearmId" class="flex-1 px-4 py-2 border border-blue-300 dark:border-blue-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white">
                                    <option value="">Select a firearm...</option>
                                    @foreach($this->userFirearms as $uf)
                                        <option value="{{ $uf->id }}">{{ $uf->make }} {{ $uf->model }} @if($uf->calibre_display) ({{ $uf->calibre_display }}) @endif</option>
                                    @endforeach
                                </select>
                                <button wire:click="loadExistingFirearm" type="button" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">Load</button>
                            </div>
                        </div>
                    @endif
                    @php $firearmPanelData = $this->firearmPanelData ?? []; @endphp
                    <livewire:firearm-search-panel 
                        wire:key="endorsement-firearm-panel-{{ $editingRequest?->id ?? 'new' }}"
                        :initial-data="$firearmPanelData"
                    />
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg mb-4">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Note:</strong> These fields align with SAPS Form 271 Section E. At least one serial number (barrel, frame, or receiver) is required.
                        </p>
                    </div>
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Action Type <span class="text-red-500">*</span></label>
                            <select wire:model.live="actionType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                <option value="">Select...</option>
                                @foreach($this->actionTypeOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('actionType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @if($actionType && $actionType !== 'other')
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ignition Type</label>
                                <select wire:model.live="ignitionType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="">Select...</option>
                                    @foreach($ignitionOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Names/Addresses Engraved in Metal</label>
                        <input type="text" wire:model="metalEngraving" placeholder="If any" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    </div>
                </div>
                @endif

                {{-- Barrel (component) --}}
                @if($endorsementEntryType === 'barrel')
                <div class="space-y-6 max-w-xl">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Barrel diameter <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="componentBarrelDiameter" placeholder="e.g. 6.5mm, .308" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        @error('componentBarrelDiameter') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="componentBarrelMake" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        @error('componentBarrelMake') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Serial number <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="componentBarrelSerial" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono">
                        @error('componentBarrelSerial') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                @endif

                {{-- Action (component) --}}
                @if($endorsementEntryType === 'action')
                <div class="space-y-6 max-w-xl">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Bolt face <span class="text-red-500">*</span></label>
                        <select wire:model.live="componentActionBoltFace" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            <option value="">Select...</option>
                            @foreach($boltFaceOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('componentActionBoltFace') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Action type <span class="text-red-500">*</span></label>
                        <select wire:model.live="componentActionType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            <option value="">Select...</option>
                            @foreach($componentActionTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('componentActionType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Serial number <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="componentActionSerial" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono">
                        @error('componentActionSerial') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                @endif
            </div>
        @endif

        {{-- Step 3: Review & Submit --}}
        @if($currentStep === 3)
            <div class="p-6">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">Review & Submit</h2>

                <div class="space-y-6">
                    {{-- Verified Prerequisites --}}
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Prerequisites Status</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- Knowledge Test --}}
                            <div class="p-4 rounded-lg border-2 transition-all {{ $eligibility['knowledge_test_passed'] ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }}">
                                <div class="flex items-center gap-2 mb-2">
                                    @if($eligibility['knowledge_test_passed'])
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                    <span class="font-semibold text-sm {{ $eligibility['knowledge_test_passed'] ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200' }}">
                                        Knowledge Test
                                    </span>
                                </div>
                                <p class="text-xs {{ $eligibility['knowledge_test_passed'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                    {{ $eligibility['knowledge_test_passed'] ? 'Passed' : 'Not Completed' }}
                                </p>
                            </div>

                            {{-- Required Documents --}}
                            <div class="p-4 rounded-lg border-2 transition-all {{ $eligibility['documents_complete'] ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }}">
                                <div class="flex items-center gap-2 mb-2">
                                    @if($eligibility['documents_complete'])
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                    <span class="font-semibold text-sm {{ $eligibility['documents_complete'] ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200' }}">
                                        Documents
                                    </span>
                                </div>
                                <p class="text-xs {{ $eligibility['documents_complete'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                    @if($eligibility['documents_complete'])
                                        All Required
                                    @else
                                        Missing {{ count($eligibility['missing_documents'] ?? []) }} doc(s)
                                    @endif
                                </p>
                            </div>

                            {{-- Activities --}}
                            <div class="p-4 rounded-lg border-2 transition-all {{ $eligibility['activities_met'] ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }}">
                                <div class="flex items-center gap-2 mb-2">
                                    @if($eligibility['activities_met'])
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                    <span class="font-semibold text-sm {{ $eligibility['activities_met'] ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200' }}">
                                        Activities
                                    </span>
                                </div>
                                <p class="text-xs {{ $eligibility['activities_met'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                    {{ $eligibility['activity_details']['approved_count'] ?? 0 }} / {{ $eligibility['activity_details']['required'] ?? 2 }} required
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Request Summary --}}
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Request Details</h3>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-zinc-500">Request Type</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $requestType === 'new' ? 'New Endorsement' : 'Renewal Endorsement' }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500">Type</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $entryTypeOptions[$endorsementEntryType] ?? $endorsementEntryType }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500">Licence</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">Section 16 – Dedicated sport or hunting</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Firearm Summary (when rifle/shotgun/handgun) --}}
                    @if(in_array($endorsementEntryType, ['rifle', 'shotgun', 'handgun']))
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Firearm Details</h3>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-zinc-500">Category</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $categoryOptions[$firearmCategory] ?? $firearmCategory }}</dd>
                            </div>
                            @if($calibreId || $calibreManual)
                                <div>
                                    <dt class="text-zinc-500">Calibre</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">
                                        {{ $calibreId ? $this->calibres->find($calibreId)?->name : $calibreManual }}
                                    </dd>
                                </div>
                            @endif
                            @if($make || $model)
                                <div>
                                    <dt class="text-zinc-500">Make/Model</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">{{ $make }} {{ $model }}</dd>
                                </div>
                            @endif
                            @if($barrelSerialNumber || $frameSerialNumber || $receiverSerialNumber || $serialNumber)
                                <div>
                                    <dt class="text-zinc-500">Serial(s)</dt>
                                    <dd class="font-medium font-mono text-zinc-900 dark:text-white">
                                        @if($barrelSerialNumber) Barrel: {{ $barrelSerialNumber }} @endif
                                        @if($frameSerialNumber) Frame: {{ $frameSerialNumber }} @endif
                                        @if($receiverSerialNumber) Receiver: {{ $receiverSerialNumber }} @endif
                                        @if($serialNumber && !$barrelSerialNumber && !$frameSerialNumber && !$receiverSerialNumber) {{ $serialNumber }} @endif
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                    @endif

                    {{-- Barrel component summary --}}
                    @if($endorsementEntryType === 'barrel')
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Barrel (component)</h3>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div><dt class="text-zinc-500">Diameter</dt><dd class="font-medium text-zinc-900 dark:text-white">{{ $componentBarrelDiameter }}</dd></div>
                            <div><dt class="text-zinc-500">Make</dt><dd class="font-medium text-zinc-900 dark:text-white">{{ $componentBarrelMake }}</dd></div>
                            <div><dt class="text-zinc-500">Serial</dt><dd class="font-medium font-mono text-zinc-900 dark:text-white">{{ $componentBarrelSerial }}</dd></div>
                        </dl>
                    </div>
                    @endif

                    {{-- Action component summary --}}
                    @if($endorsementEntryType === 'action')
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Action (component)</h3>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div><dt class="text-zinc-500">Bolt face</dt><dd class="font-medium text-zinc-900 dark:text-white">{{ $boltFaceOptions[$componentActionBoltFace] ?? $componentActionBoltFace }}</dd></div>
                            <div><dt class="text-zinc-500">Action type</dt><dd class="font-medium text-zinc-900 dark:text-white">{{ $componentActionTypeOptions[$componentActionType] ?? $componentActionType }}</dd></div>
                            <div><dt class="text-zinc-500">Serial</dt><dd class="font-medium font-mono text-zinc-900 dark:text-white">{{ $componentActionSerial }}</dd></div>
                        </dl>
                    </div>
                    @endif

                    {{-- Optional notes on review --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Additional notes (optional)</label>
                        <textarea wire:model="memberNotes" rows="2" placeholder="Any additional information..."
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white resize-none"></textarea>
                    </div>

                    {{-- Submission Errors --}}
                    @if(count($this->submissionErrors) > 0)
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <h4 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">Cannot Submit Yet</h4>
                            <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                                @foreach($this->submissionErrors as $error)
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ is_string($error) ? $error : ($error['message'] ?? json_encode($error)) }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    {{-- Debug: Show document status if trying to submit --}}
                    @php
                        // Check document status for debugging
                        if ($this->canSubmit && $this->declarationAccepted) {
                            try {
                                $testRequest = $this->editingRequest ?? new \App\Models\EndorsementRequest();
                                if ($testRequest->exists || $this->editingRequest) {
                                    $testRequest->request_type = $this->requestType;
                                    $missingDocs = $testRequest->getMissingRequestDocuments();
                                    if (count($missingDocs) > 0) {
                                        $docLabels = array_map(fn($doc) => \App\Models\EndorsementRequest::getDocumentTypeLabel($doc), $missingDocs);
                                    }
                                }
                            } catch (\Exception $e) {
                                // Ignore errors in debug display
                            }
                        }
                    @endphp

                    {{-- Declaration --}}
                    <div class="p-4 border-2 rounded-lg {{ $declarationAccepted ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-zinc-300 dark:border-zinc-600' }}">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" wire:model.live="declarationAccepted" 
                                class="mt-1 w-5 h-5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-white">I accept the declaration</span>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    I hereby declare that all information provided is true and correct. I am an active member in good standing with NRAPA and maintain my dedicated status requirements. I understand that providing false information may result in revocation of my endorsement and membership.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        @endif

        {{-- Navigation Footer --}}
        <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-700 flex justify-between">
            <div>
                @if($currentStep > 1)
                    <button wire:click="previousStep" type="button"
                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        Previous
                    </button>
                @endif
            </div>

            <div class="flex gap-3">
                <button wire:click="saveDraft" type="button"
                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    Save Draft
                </button>

                @if($currentStep < $totalSteps)
                    <button wire:click="nextStep" type="button"
                        @disabled(!$this->canProceedToNextStep)
                        wire:loading.attr="disabled"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-emerald-600">
                        <span wire:loading.remove wire:target="nextStep">Next</span>
                        <span wire:loading wire:target="nextStep">Processing...</span>
                    </button>
                @else
                    <button wire:click="submitRequest" type="button"
                        @disabled(!$this->canSubmit)
                        wire:loading.attr="disabled"
                        class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-emerald-600">
                        <span wire:loading.remove wire:target="submitRequest">Submit Request</span>
                        <span wire:loading wire:target="submitRequest">Submitting...</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Calibre Request Modal --}}
    @if($showCalibreRequestModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Background overlay --}}
            <div class="fixed inset-0 bg-zinc-500/75 dark:bg-zinc-900/75 transition-opacity" wire:click="closeCalibreRequestModal"></div>

            {{-- Modal panel --}}
            <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white" id="modal-title">
                        Request New Calibre
                    </h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Submit a request to add a calibre that's not in our list. An admin will review and approve it.
                    </p>
                </div>

                <div class="px-6 py-4 space-y-4">
                    {{-- Existing Calibres List --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Existing Calibres
                            <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                (Check if your calibre already exists)
                            </span>
                        </label>
                        
                        {{-- Search existing calibres --}}
                        <div class="mb-2">
                            <input type="text" 
                                wire:model.live.debounce.300ms="calibreSearchQuery"
                                placeholder="Search existing calibres..."
                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        </div>

                        {{-- List of existing calibres --}}
                        <div class="max-h-48 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-900/50">
                            @if($this->existingCalibres->count() > 0)
                                <div class="p-2 space-y-1">
                                    @foreach($this->existingCalibres as $calibre)
                                        <div class="px-3 py-2 text-sm rounded hover:bg-zinc-200 dark:hover:bg-zinc-800 cursor-pointer transition-colors"
                                            wire:click="$set('newCalibreName', '{{ $calibre->name }}')"
                                            title="Click to use this calibre name">
                                            <div class="flex items-center justify-between">
                                                <span class="font-medium text-zinc-900 dark:text-white">{{ $calibre->name }}</span>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $calibre->category_label }} • {{ $calibre->ignition_label }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-4 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($calibreSearchQuery)
                                        No calibres found matching "{{ $calibreSearchQuery }}"
                                    @elseif($newCalibreCategory || $newCalibreIgnition)
                                        No calibres found for selected filters. Try adjusting category or ignition type.
                                    @else
                                        Select a category and ignition type to see existing calibres.
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Calibre Name --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Calibre Name <span class="text-red-500">*</span>
                            <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                (Enter the calibre you want to request)
                            </span>
                        </label>
                        <input type="text" wire:model="newCalibreName" 
                            placeholder="e.g., 6.5 PRC, .300 PRC, 6mm ARC"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        @error('newCalibreName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @if($newCalibreName && $this->existingCalibres->contains('name', $newCalibreName))
                            <p class="mt-1 text-sm text-amber-600 dark:text-amber-400">
                                ⚠️ This calibre already exists in our database. Please check the list above.
                            </p>
                        @endif
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="newCalibreCategory" 
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            <option value="">Select category...</option>
                            <option value="handgun">Handgun</option>
                            <option value="rifle">Rifle</option>
                            <option value="shotgun">Shotgun</option>
                            <option value="muzzleloader">Muzzleloader</option>
                            <option value="historic">Historic</option>
                        </select>
                        @error('newCalibreCategory') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Ignition Type --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Ignition Type <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model.live="newCalibreIgnition" value="centerfire" 
                                    class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-zinc-700 dark:text-zinc-300">Centerfire</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model.live="newCalibreIgnition" value="rimfire"
                                    class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-zinc-700 dark:text-zinc-300">Rimfire</span>
                            </label>
                        </div>
                    </div>

                    {{-- SAPS Code (optional) --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            SAPS Code <span class="text-xs text-zinc-500">(Optional - if known)</span>
                        </label>
                        <input type="text" wire:model="newCalibreSapsCode" 
                            placeholder="e.g., 65PRC"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono uppercase">
                    </div>

                    {{-- Reason/Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Additional Notes <span class="text-xs text-zinc-500">(Optional)</span>
                        </label>
                        <textarea wire:model="newCalibreReason" rows="2"
                            placeholder="Any additional information about this calibre..."
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white resize-none"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3">
                    <button type="button" wire:click="closeCalibreRequestModal"
                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button type="button" wire:click="submitCalibreRequest"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        Submit Request
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
<?php

use App\Models\EndorsementRequest;
use App\Models\FirearmCalibre;
use App\Models\FirearmMake;
use App\Models\FirearmModel;
use App\Services\MembershipStandingService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] #[Title('Occasional (Section 15) Letter')] class extends Component {
    public string $firearmMake = '';
    public string $firearmModel = '';
    public string $firearmCalibre = '';
    public string $firearmType = '';
    public string $firearmActionType = '';
    public string $firearmIgnitionType = '';
    public string $firearmBarrelSerial = '';
    public string $firearmFrameSerial = '';
    public string $firearmReceiverSerial = '';
    public string $motivationNote = '';

    /**
     * Single declaration — not a long list of acknowledgements. The occasional
     * letter rests only on good standing; the member just has to confirm the
     * firearm details and that they understand this is not a dedicated letter.
     */
    public bool $declarationAccepted = false;

    public function mount(): void
    {
        if (! auth()->user()) {
            $this->redirect(route('login'), navigate: true);
        }
    }

    #[Computed]
    public function membership()
    {
        return auth()->user()->activeMembership;
    }

    #[Computed]
    public function standingReason(): ?string
    {
        return app(MembershipStandingService::class)->getStandingReason(auth()->user());
    }

    #[Computed]
    public function inGoodStanding(): bool
    {
        return app(MembershipStandingService::class)->isInGoodStanding(auth()->user());
    }

    /**
     * Resolve the typed make name to a reference make id (if it matches one).
     */
    protected function matchedMakeId(): ?int
    {
        $name = trim($this->firearmMake);
        if ($name === '') {
            return null;
        }

        return FirearmMake::active()
            ->where('normalized_name', FirearmMake::normalize($name))
            ->value('id');
    }

    #[Computed]
    public function makeSuggestions()
    {
        $term = trim($this->firearmMake);
        if (strlen($term) < 1) {
            return collect();
        }

        return FirearmMake::active()
            ->search($term)
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function modelSuggestions()
    {
        $term = trim($this->firearmModel);
        if (strlen($term) < 1) {
            return collect();
        }

        $query = FirearmModel::active()->search($term);

        $makeId = $this->matchedMakeId();
        if ($makeId) {
            $query->forMake($makeId);
        }

        return $query->orderBy('name')->limit(8)->get();
    }

    #[Computed]
    public function calibreSuggestions()
    {
        $term = trim($this->firearmCalibre);
        if (strlen($term) < 1) {
            return collect();
        }

        $query = FirearmCalibre::active()->notObsolete()->search($term);

        if (in_array($this->firearmType, ['handgun', 'rifle', 'shotgun'], true)) {
            $query->where('category', $this->firearmType);
        }

        return $query->orderBy('name')->limit(8)->get();
    }

    #[Computed]
    public function actionOptions(): array
    {
        return EndorsementRequest::getSelfDefenceActionTypeOptions($this->firearmType ?: null);
    }

    public function updatedFirearmType(): void
    {
        $available = EndorsementRequest::getSelfDefenceActionTypeOptions($this->firearmType ?: null);
        if (! array_key_exists($this->firearmActionType, $available)) {
            $this->firearmActionType = '';
        }
    }

    public function submit(): void
    {
        $user = auth()->user();

        // 1. Good standing is the only association requirement for this letter.
        if (! $this->inGoodStanding) {
            session()->flash('error', $this->standingReason ?: 'You must be a member in good standing to request this letter.');

            return;
        }

        // 2. Validate the firearm fields.
        $this->validate([
            'firearmMake' => 'required|string|max:255',
            'firearmModel' => 'required|string|max:255',
            'firearmCalibre' => 'required|string|max:255',
            'firearmType' => 'required|in:handgun,rifle,shotgun',
            'firearmActionType' => 'nullable|string|max:50',
            'firearmIgnitionType' => 'nullable|in:rimfire,centerfire',
            'firearmBarrelSerial' => 'nullable|string|max:255',
            'firearmFrameSerial' => 'nullable|string|max:255',
            'firearmReceiverSerial' => 'nullable|string|max:255',
            'motivationNote' => 'nullable|string|max:2000',
            'declarationAccepted' => 'accepted',
        ], [
            'declarationAccepted.accepted' => 'You must accept the declaration before submitting.',
        ], [
            'firearmMake' => 'firearm make',
            'firearmModel' => 'firearm model',
            'firearmCalibre' => 'firearm calibre',
            'firearmType' => 'firearm type',
        ]);

        $serialString = EndorsementRequest::formatSelfDefenceSerials([
            'barrel' => $this->firearmBarrelSerial,
            'frame' => $this->firearmFrameSerial,
            'receiver' => $this->firearmReceiverSerial,
        ]);
        if ($serialString === null) {
            $this->addError('firearmBarrelSerial', 'Enter at least one serial number (barrel, frame, or receiver).');

            return;
        }

        $declarationText = "I confirm that the firearm details above are true and correct. I understand that this NRAPA letter is a Section 15 (occasional sport shooting / hunting) letter only — it is not a dedicated status endorsement and is not issued under Section 16 of the Firearms Control Act, 2000.";
        $now = now();

        $request = new EndorsementRequest;
        $request->fill([
            'user_id' => $user->id,
            'request_type' => EndorsementRequest::TYPE_NEW,
            'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_OCCASIONAL,
            'status' => EndorsementRequest::STATUS_DRAFT,
            'firearm_make' => $this->firearmMake,
            'firearm_model' => $this->firearmModel,
            'firearm_calibre' => $this->firearmCalibre,
            'firearm_type' => $this->firearmType,
            'firearm_action_type' => $this->firearmActionType ?: null,
            'firearm_ignition_type' => $this->firearmIgnitionType ?: null,
            'firearm_serial' => $serialString,
            'motivation_note' => $this->motivationNote ?: null,
            'declaration_accepted_at' => $now,
            'declaration_text' => $declarationText,
        ]);
        $request->save();

        if (! $request->submit()) {
            session()->flash('error', 'Unable to submit your request. Please try again or contact NRAPA.');

            return;
        }

        session()->flash('success', 'Your occasional (Section 15) letter request has been submitted for review.');
        $this->redirect(route('member.endorsements.index'), navigate: true);
    }
}; ?>

<div class="max-w-3xl mx-auto">
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Occasional (Section 15) Letter</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">A letter confirming that a specific firearm is fit for occasional sport shooting or hunting under Section 15. Open to any member in good standing — no dedicated status required.</p>
        </div>
    </x-slot>

    {{-- Flash --}}
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Not in good standing --}}
    @if(! $this->inGoodStanding)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-amber-300 dark:border-amber-700 p-6 mb-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Good Standing Required</h3>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $this->standingReason ?: 'You must be a member in good standing to request this letter.' }}
            </p>
            <a href="{{ route('membership.index') }}" wire:navigate class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors text-sm font-medium">
                Manage Membership
            </a>
        </div>
    @else
        {{-- Nature & scope notice --}}
        <div class="mb-6 p-4 bg-nrapa-blue/5 dark:bg-nrapa-blue/10 border border-nrapa-blue/20 dark:border-nrapa-blue/30 rounded-xl text-sm text-zinc-700 dark:text-zinc-300">
            <p>
                This letter confirms that the firearm you name below is <strong>fit for occasional sport shooting or occasional hunting</strong> under <strong>Section 15</strong> of the Firearms Control Act. It is <strong>not</strong> a dedicated status endorsement, and the only association requirement is that you are a <strong>member in good standing</strong>.
            </p>
        </div>

        <form wire:submit="submit" class="space-y-6">
            {{-- Firearm details --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">Firearm Details</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">The specific firearm this letter should name.</p>

                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">Start typing to search the NRAPA reference list. If your firearm isn't listed, just type it in &mdash; your entry will still be used.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type <span class="text-red-500">*</span></label>
                        <select wire:model.live="firearmType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            <option value="">Select type…</option>
                            @foreach(EndorsementRequest::getSelfDefenceFirearmTypeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('firearmType') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Make (searchable) --}}
                    <div x-data="{ open: false }" @click.away="open = false" class="relative">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live.debounce.250ms="firearmMake" @focus="open = true" autocomplete="off" placeholder="Start typing…"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" />
                        <div x-show="open" x-cloak class="absolute z-30 mt-1 w-full max-h-56 overflow-auto rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 shadow-lg">
                            @forelse($this->makeSuggestions as $m)
                                <button type="button" @mousedown.prevent="$wire.set('firearmMake', @js($m->name)); open = false"
                                    class="block w-full text-left px-3 py-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-nrapa-blue/10">{{ $m->name }}</button>
                            @empty
                                <div class="px-3 py-2 text-xs text-zinc-500 dark:text-zinc-400">No matches — your typed value will be used.</div>
                            @endforelse
                        </div>
                        @error('firearmMake') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Model (searchable) --}}
                    <div x-data="{ open: false }" @click.away="open = false" class="relative">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Model <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live.debounce.250ms="firearmModel" @focus="open = true" autocomplete="off" placeholder="Start typing…"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" />
                        <div x-show="open" x-cloak class="absolute z-30 mt-1 w-full max-h-56 overflow-auto rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 shadow-lg">
                            @forelse($this->modelSuggestions as $m)
                                <button type="button" @mousedown.prevent="$wire.set('firearmModel', @js($m->name)); open = false"
                                    class="block w-full text-left px-3 py-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-nrapa-blue/10">{{ $m->name }}</button>
                            @empty
                                <div class="px-3 py-2 text-xs text-zinc-500 dark:text-zinc-400">No matches — your typed value will be used.</div>
                            @endforelse
                        </div>
                        @error('firearmModel') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Calibre (searchable) --}}
                    <div x-data="{ open: false }" @click.away="open = false" class="relative">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live.debounce.250ms="firearmCalibre" @focus="open = true" autocomplete="off" placeholder="Start typing…"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" />
                        <div x-show="open" x-cloak class="absolute z-30 mt-1 w-full max-h-56 overflow-auto rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 shadow-lg">
                            @forelse($this->calibreSuggestions as $c)
                                <button type="button" @mousedown.prevent="$wire.set('firearmCalibre', @js($c->name)); open = false"
                                    class="block w-full text-left px-3 py-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-nrapa-blue/10">{{ $c->name }}</button>
                            @empty
                                <div class="px-3 py-2 text-xs text-zinc-500 dark:text-zinc-400">No matches — your typed value will be used.</div>
                            @endforelse
                        </div>
                        @error('firearmCalibre') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Action</label>
                        <select wire:model="firearmActionType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            <option value="">Select action…</option>
                            @foreach($this->actionOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('firearmActionType') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ignition</label>
                        <select wire:model="firearmIgnitionType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            <option value="">Select ignition…</option>
                            @foreach(EndorsementRequest::getSelfDefenceIgnitionTypeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('firearmIgnitionType') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Serial Number(s) <span class="text-red-500">*</span></label>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Enter at least one serial number. A firearm may carry a serial on the barrel, frame and/or receiver &mdash; complete whichever apply.</p>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Barrel Serial</label>
                                <input type="text" wire:model="firearmBarrelSerial" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Frame Serial</label>
                                <input type="text" wire:model="firearmFrameSerial" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Receiver Serial</label>
                                <input type="text" wire:model="firearmReceiverSerial" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" />
                            </div>
                        </div>
                        @error('firearmBarrelSerial') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes <span class="text-zinc-400 font-normal">(optional)</span></label>
                        <textarea wire:model="motivationNote" rows="3" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"></textarea>
                        @error('motivationNote') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Declaration --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">Declaration</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">One tick is all this letter asks for — good standing does the rest.</p>

                <label class="flex items-start gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 cursor-pointer">
                    <input type="checkbox" wire:model.live="declarationAccepted" class="mt-1 h-4 w-4 rounded border-zinc-300 text-nrapa-blue focus:ring-nrapa-blue shrink-0" />
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                        I confirm that the firearm details above are true and correct. I understand that this NRAPA letter is a <strong>Section 15</strong> letter for <strong>occasional sport shooting or hunting</strong> only &mdash; it is <strong>not</strong> a dedicated status endorsement and is <strong>not</strong> issued under Section 16 of the Firearms Control Act, 2000.
                    </span>
                </label>
                @error('declarationAccepted') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col items-end gap-2">
                @unless($declarationAccepted)
                    <p class="text-xs text-amber-600 dark:text-amber-400">Tick the declaration above to enable submission.</p>
                @endunless
                <div class="flex items-center justify-between gap-4 w-full">
                    <a href="{{ route('member.endorsements.index') }}" wire:navigate class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                        @disabled(! $declarationAccepted)>
                        <span wire:loading.remove wire:target="submit">Submit Request</span>
                        <span wire:loading wire:target="submit">Submitting…</span>
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>

<?php

use App\Models\EndorsementAcknowledgement;
use App\Models\EndorsementRequest;
use App\Models\FirearmCalibre;
use App\Models\FirearmMake;
use App\Models\FirearmModel;
use App\Models\MembershipType;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] #[Title('Self-Defence Supporting Letter')] class extends Component {
    public string $applicationType = 'new'; // new | renewal
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

    /** Per-clause acceptance, keyed by clause_key. */
    public array $clauses = [];

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        // Default every clause to unticked so each must be deliberately accepted.
        foreach (array_keys(EndorsementRequest::selfDefenceClauses()) as $key) {
            $this->clauses[$key] = false;
        }
    }

    /**
     * A Section 13 self-defence letter is NOT a dedicated-status endorsement.
     * The only association requirement is that the member is in good standing.
     * We deliberately do NOT run the dedicated-status eligibility bundle here
     * (knowledge test, activities, dedicated documents) — none of those apply
     * to a Section 13 licence application.
     */
    #[Computed]
    public function memberInGoodStanding(): bool
    {
        return app(\App\Services\MembershipStandingService::class)
            ->isInGoodStanding(auth()->user());
    }

    #[Computed]
    public function membership()
    {
        return auth()->user()->activeMembership;
    }

    #[Computed]
    public function clauseTexts(): array
    {
        return EndorsementRequest::selfDefenceClauses();
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

        // Narrow to the selected make when the typed make matches a known one.
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

        // Filter by firearm type where it maps cleanly to a calibre category.
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

    #[Computed]
    public function allClausesAccepted(): bool
    {
        foreach (EndorsementRequest::selfDefenceClauses() as $key => $text) {
            if (empty($this->clauses[$key])) {
                return false;
            }
        }

        return true;
    }

    public function submit(): void
    {
        $user = auth()->user();

        // 1. Re-check good standing server-side (never trust page load).
        //    Section 13 has NO dedicated-status requirement — just good standing.
        if (! $this->memberInGoodStanding) {
            session()->flash('error', 'You are not currently in good standing with NRAPA, so a self-defence supporting letter cannot be issued. Please contact the office if you believe this is incorrect.');

            return;
        }

        // 2. Validate the firearm fields.
        $this->validate([
            'applicationType' => 'required|in:new,renewal',
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
        ], [], [
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

        // 3. Every clause must be individually accepted.
        if (! $this->allClausesAccepted) {
            session()->flash('error', 'You must tick every acknowledgement clause before submitting.');

            return;
        }

        // 4. Terms acceptance (same gate as the standard endorsement).
        $activeTerms = \App\Models\TermsVersion::active();
        if ($activeTerms && ! $user->hasAcceptedActiveTerms()) {
            session()->flash('error', 'You must accept the Terms & Conditions before submitting.');
            $this->redirect(route('terms.accept'), navigate: true);

            return;
        }

        $clauseTexts = EndorsementRequest::selfDefenceClauses();
        $now = now();

        $request = new EndorsementRequest;
        $request->fill([
            'user_id' => $user->id,
            'request_type' => $this->applicationType === 'renewal' ? EndorsementRequest::TYPE_RENEWAL : EndorsementRequest::TYPE_NEW,
            'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_SELF_DEFENCE,
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
            'declaration_text' => implode("\n\n", $clauseTexts),
        ]);
        $request->save();

        // Persist each clause as its own immutable acknowledgement (shared timestamp).
        $ip = request()->ip();
        $userAgent = request()->userAgent();
        foreach ($clauseTexts as $key => $text) {
            EndorsementAcknowledgement::create([
                'endorsement_request_id' => $request->id,
                'user_id' => $user->id,
                'clause_key' => $key,
                'clause_text' => $text,
                'accepted' => true,
                'accepted_at' => $now,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
        }

        if (! $request->submit()) {
            session()->flash('error', 'Unable to submit your request. Please try again or contact NRAPA.');

            return;
        }

        session()->flash('success', 'Your self-defence supporting letter request has been submitted for review.');
        $this->redirect(route('member.endorsements.index'), navigate: true);
    }
}; ?>

<div class="max-w-3xl mx-auto">
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Self-Defence Supporting Letter</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Request a voluntary NRAPA letter in support of a Section 13 self-defence licence application. The letter confirms you are a member in good standing and that the firearm named is, in NRAPA's view, fit for self-defence.</p>
        </div>
    </x-slot>

    {{-- Flash --}}
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Section 13 is not a dedicated-status endorsement, so any member in
         good standing can request it. The only association requirement
         re-checked on submit is good standing. --}}
    @if(! $this->memberInGoodStanding)
        <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-xl">
            <h3 class="font-semibold text-amber-800 dark:text-amber-200">Membership not in good standing</h3>
            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                Your NRAPA membership is not currently in good standing, so a self-defence supporting letter cannot be issued. Please contact the office if you believe this is incorrect.
            </p>
        </div>
    @endif

    {{-- Nature & scope notice --}}
    <div class="mb-6 p-4 bg-nrapa-blue/5 dark:bg-nrapa-blue/10 border border-nrapa-blue/20 dark:border-nrapa-blue/30 rounded-xl text-sm text-zinc-700 dark:text-zinc-300">
        <p>
            An association endorsement is <strong>not a legal requirement</strong> for a Section 13 (self-defence) licence application. NRAPA provides this letter voluntarily, at your request, to confirm your good standing with NRAPA and that the firearm named is fit for self-defence. <strong>This is not a dedicated-status endorsement</strong> and is not issued under Section 16 of the Act. It is not a motivation for the licence itself.
        </p>
    </div>

        <form wire:submit="submit" class="space-y-6">
            {{-- Application type --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">Application Type</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">Is this for a new Section 13 self-defence licence, or the renewal of an existing one?</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer {{ $applicationType === 'new' ? 'border-nrapa-blue bg-nrapa-blue/5' : 'border-zinc-200 dark:border-zinc-700' }}">
                        <input type="radio" wire:model.live="applicationType" value="new" class="mt-1 h-4 w-4 text-nrapa-blue focus:ring-nrapa-blue shrink-0" />
                        <span>
                            <span class="block text-sm font-medium text-zinc-900 dark:text-white">New Application</span>
                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">First-time Section 13 self-defence licence application.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer {{ $applicationType === 'renewal' ? 'border-nrapa-blue bg-nrapa-blue/5' : 'border-zinc-200 dark:border-zinc-700' }}">
                        <input type="radio" wire:model.live="applicationType" value="renewal" class="mt-1 h-4 w-4 text-nrapa-blue focus:ring-nrapa-blue shrink-0" />
                        <span>
                            <span class="block text-sm font-medium text-zinc-900 dark:text-white">Renewal</span>
                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">Renewal of an existing Section 13 self-defence licence.</span>
                        </span>
                    </label>
                </div>
                @error('applicationType') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Firearm details --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">Firearm Details</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">The firearm you are applying to license for self-defence.</p>

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
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Enter at least one serial number. A firearm may carry a serial on the barrel, frame and/or receiver &mdash; complete whichever apply. For a handgun, the serial is usually on the <strong>frame</strong>.</p>
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
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Motivation Note <span class="text-zinc-400 font-normal">(optional)</span></label>
                        <textarea wire:model="motivationNote" rows="3" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"></textarea>
                        @error('motivationNote') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Acknowledgements --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">Declarations &amp; Acknowledgements</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">Each clause must be ticked individually. Your acknowledgements are recorded immutably with a timestamp.</p>

                <div class="space-y-3">
                    @foreach($this->clauseTexts as $key => $text)
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 cursor-pointer">
                            <input type="checkbox" wire:model.live="clauses.{{ $key }}" class="mt-1 h-4 w-4 rounded border-zinc-300 text-nrapa-blue focus:ring-nrapa-blue shrink-0" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $text }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col items-end gap-2">
                @unless($this->allClausesAccepted)
                    <p class="text-xs text-amber-600 dark:text-amber-400">Tick all acknowledgement clauses above to enable submission.</p>
                @endunless
                <div class="flex items-center justify-between gap-4 w-full">
                    <a href="{{ route('member.endorsements.index') }}" wire:navigate class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                        @disabled(! $this->allClausesAccepted || ! $this->memberInGoodStanding)>
                        <span wire:loading.remove wire:target="submit">Submit Request</span>
                        <span wire:loading wire:target="submit">Submitting…</span>
                    </button>
                </div>
            </div>
        </form>
</div>

<?php

use App\Models\EndorsementAcknowledgement;
use App\Models\EndorsementRequest;
use App\Models\MembershipType;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] #[Title('Self-Defence Supporting Letter')] class extends Component {
    public string $firearmMake = '';
    public string $firearmModel = '';
    public string $firearmCalibre = '';
    public string $firearmType = '';
    public string $firearmSerial = '';
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

    #[Computed]
    public function eligibility(): array
    {
        return EndorsementRequest::getEligibilitySummary(auth()->user());
    }

    #[Computed]
    public function membership()
    {
        return auth()->user()->activeMembership;
    }

    #[Computed]
    public function dedicatedType()
    {
        return $this->membership?->type?->dedicated_type;
    }

    #[Computed]
    public function clauseTexts(): array
    {
        return EndorsementRequest::selfDefenceClauses();
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

        // 1. Re-validate eligibility server-side at submit time (never trust page load).
        $eligibility = EndorsementRequest::checkUserEligibility($user);
        if (! ($eligibility['eligible'] ?? false)) {
            session()->flash('error', 'You are not currently eligible to request a self-defence supporting letter. Please resolve the outstanding requirements shown below and try again.');

            return;
        }

        // 2. Validate the firearm fields.
        $validated = $this->validate([
            'firearmMake' => 'required|string|max:255',
            'firearmModel' => 'required|string|max:255',
            'firearmCalibre' => 'required|string|max:255',
            'firearmType' => 'required|in:handgun,rifle,shotgun',
            'firearmSerial' => 'nullable|string|max:255',
            'motivationNote' => 'nullable|string|max:2000',
        ], [], [
            'firearmMake' => 'firearm make',
            'firearmModel' => 'firearm model',
            'firearmCalibre' => 'firearm calibre',
            'firearmType' => 'firearm type',
        ]);

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
            'request_type' => EndorsementRequest::TYPE_NEW,
            'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_SELF_DEFENCE,
            'status' => EndorsementRequest::STATUS_DRAFT,
            'firearm_make' => $this->firearmMake,
            'firearm_model' => $this->firearmModel,
            'firearm_calibre' => $this->firearmCalibre,
            'firearm_type' => $this->firearmType,
            'firearm_serial' => $this->firearmSerial ?: null,
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
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Request a voluntary NRAPA letter confirming your dedicated status in support of a Section 13 self-defence licence application.</p>
        </div>
    </x-slot>

    {{-- Flash --}}
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Not dedicated --}}
    @if(! $this->dedicatedType)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-amber-300 dark:border-amber-700 p-6 mb-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Dedicated Membership Required</h3>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                A self-defence supporting letter is only available to active <strong>Dedicated Hunter</strong> or <strong>Dedicated Sport Shooter</strong> members in good standing.
            </p>
            <a href="{{ route('membership.apply') }}" wire:navigate class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors text-sm font-medium">
                Upgrade Membership
            </a>
        </div>
    @else
        {{-- Eligibility warning (non-blocking display; submit re-checks) --}}
        @if(! $this->eligibility['eligible'])
            <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-xl">
                <h3 class="font-semibold text-amber-800 dark:text-amber-200">Outstanding requirements</h3>
                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">You must resolve the following before this letter can be issued:</p>
                <ul class="mt-2 list-disc list-inside text-sm text-amber-700 dark:text-amber-300 space-y-1">
                    @foreach($this->eligibility['errors'] as $err)
                        <li>{{ is_array($err) ? ($err['message'] ?? '') : $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Nature & scope notice --}}
        <div class="mb-6 p-4 bg-nrapa-blue/5 dark:bg-nrapa-blue/10 border border-nrapa-blue/20 dark:border-nrapa-blue/30 rounded-xl text-sm text-zinc-700 dark:text-zinc-300">
            <p>
                An association endorsement is <strong>not a legal requirement</strong> for a Section 13 (self-defence) licence application. NRAPA provides this letter voluntarily, at your request, solely to confirm your dedicated status and activity. It is not a motivation for the licence itself.
            </p>
        </div>

        <form wire:submit="submit" class="space-y-6">
            {{-- Firearm details --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">Firearm Details</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">The firearm you are applying to license for self-defence.</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="firearmMake" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" />
                        @error('firearmMake') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Model <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="firearmModel" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" />
                        @error('firearmModel') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="firearmCalibre" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" />
                        @error('firearmCalibre') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type <span class="text-red-500">*</span></label>
                        <select wire:model="firearmType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            <option value="">Select type…</option>
                            @foreach(EndorsementRequest::getSelfDefenceFirearmTypeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('firearmType') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Serial Number <span class="text-zinc-400 font-normal">(optional — may be unknown at application stage)</span></label>
                        <input type="text" wire:model="firearmSerial" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" />
                        @error('firearmSerial') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
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
                            <input type="checkbox" wire:model="clauses.{{ $key }}" class="mt-1 h-4 w-4 rounded border-zinc-300 text-nrapa-blue focus:ring-nrapa-blue shrink-0" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $text }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between gap-4">
                <a href="{{ route('member.endorsements.index') }}" wire:navigate class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white">
                    Cancel
                </a>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                    @disabled(! $this->allClausesAccepted)>
                    <span wire:loading.remove wire:target="submit">Submit Request</span>
                    <span wire:loading wire:target="submit">Submitting…</span>
                </button>
            </div>
        </form>
    @endif
</div>

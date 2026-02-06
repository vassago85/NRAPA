<?php

use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestAttempt;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Knowledge Tests')] class extends Component {
    /**
     * Get the user's dedicated type from their active membership.
     */
    #[Computed]
    public function userDedicatedType(): ?string
    {
        return auth()->user()->activeMembership?->type?->dedicated_type;
    }

    #[Computed]
    public function availableTests()
    {
        return KnowledgeTest::active()
            ->forDedicatedType($this->userDedicatedType)
            ->whereHas('activeQuestions')
            ->withCount('activeQuestions')
            ->orderBy('dedicated_type')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function canApplyForDedicatedStatus(): bool
    {
        $membership = auth()->user()->activeMembership;
        return $membership && $membership->type->allows_dedicated_status;
    }

    #[Computed]
    public function myAttempts()
    {
        return KnowledgeTestAttempt::with('knowledgeTest')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function inProgressAttempt()
    {
        return KnowledgeTestAttempt::with('knowledgeTest')
            ->where('user_id', auth()->id())
            ->whereNull('submitted_at')
            ->first();
    }

    /**
     * Get the dedicated status requirements and completion status.
     */
    #[Computed]
    public function dedicatedStatusRequirements(): array
    {
        $passedAttempts = $this->myAttempts->where('passed', true);
        
        // Check which test types have been passed
        $passedHunter = $passedAttempts->contains(fn($a) => $a->knowledgeTest->dedicated_type === 'hunter');
        $passedSport = $passedAttempts->contains(fn($a) => in_array($a->knowledgeTest->dedicated_type, ['sport', 'sport_shooter']));
        $passedBoth = $passedAttempts->contains(fn($a) => $a->knowledgeTest->dedicated_type === 'both');

        return [
            'hunter' => [
                'label' => 'Dedicated Hunter',
                'passed' => $passedHunter || $passedBoth,
                'description' => 'Pass the Dedicated Hunter test OR the Combined test',
            ],
            'sport' => [
                'label' => 'Dedicated Sport Shooter', 
                'passed' => $passedSport || $passedBoth,
                'description' => 'Pass the Dedicated Sport Shooter test OR the Combined test',
            ],
            'both' => [
                'label' => 'Both (Hunter & Sport Shooter)',
                'passed' => $passedBoth || ($passedHunter && $passedSport),
                'description' => 'Pass the Combined test OR pass both individual tests',
            ],
        ];
    }

    /**
     * Determine which test type a test satisfies.
     */
    public function getTestSatisfies(KnowledgeTest $test): array
    {
        return match($test->dedicated_type) {
            'hunter' => ['hunter'],
            'sport', 'sport_shooter' => ['sport'],
            'both' => ['hunter', 'sport', 'both'],
            default => [],
        };
    }

    public function getTestStatus(KnowledgeTest $test): array
    {
        $attempts = $this->myAttempts->where('knowledge_test_id', $test->id);
        $passedAttempt = $attempts->firstWhere('passed', true);
        $remainingAttempts = $test->remainingAttempts(auth()->user());

        if ($passedAttempt) {
            return [
                'status' => 'passed',
                'label' => 'Passed',
                'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'canAttempt' => false,
            ];
        }

        if ($remainingAttempts <= 0) {
            return [
                'status' => 'exhausted',
                'label' => 'No attempts left',
                'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'canAttempt' => false,
            ];
        }

        $inProgress = $attempts->firstWhere(fn ($a) => $a->submitted_at === null);
        if ($inProgress) {
            return [
                'status' => 'in_progress',
                'label' => 'In Progress',
                'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'canAttempt' => true,
                'attemptId' => $inProgress->uuid,
            ];
        }

        return [
            'status' => 'available',
            'label' => "{$remainingAttempts} attempts left",
            'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'canAttempt' => true,
        ];
    }

    public function startTest(int $testId): void
    {
        $test = KnowledgeTest::findOrFail($testId);

        // Check if user can attempt
        if (!$test->canAttempt(auth()->user())) {
            session()->flash('error', 'You have exhausted all attempts for this test.');
            return;
        }

        // Check for existing in-progress attempt
        $existingAttempt = KnowledgeTestAttempt::where('user_id', auth()->id())
            ->where('knowledge_test_id', $testId)
            ->whereNull('submitted_at')
            ->first();

        if ($existingAttempt) {
            $this->redirect(route('knowledge-test.take', $test), navigate: true);
            return;
        }

        // Create new attempt
        $attempt = KnowledgeTestAttempt::create([
            'user_id' => auth()->id(),
            'knowledge_test_id' => $testId,
        ]);

        // Create answer placeholders for all questions
        foreach ($test->activeQuestions as $question) {
            $attempt->answers()->create([
                'question_id' => $question->id,
            ]);
        }

        $this->redirect(route('knowledge-test.take', $test), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Knowledge Tests</h1>
        <p class="text-zinc-600 dark:text-zinc-400">Complete required knowledge tests to apply for Dedicated Status.</p>
    </div>

    {{-- Requirements Status --}}
    @if($this->canApplyForDedicatedStatus)
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Your Dedicated Status Requirements</h2>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
            Pass the required tests to qualify for Dedicated Status. You can take the <strong>Combined test</strong> to qualify for both, 
            or take <strong>individual tests</strong> for each status.
        </p>
        
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach($this->dedicatedStatusRequirements as $key => $req)
            <div class="rounded-lg border p-4 {{ $req['passed'] ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900' }}">
                <div class="flex items-center gap-2 mb-2">
                    @if($req['passed'])
                    <svg class="size-5 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    @else
                    <svg class="size-5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    @endif
                    <span class="font-medium {{ $req['passed'] ? 'text-green-800 dark:text-green-200' : 'text-zinc-900 dark:text-white' }}">{{ $req['label'] }}</span>
                </div>
                <p class="text-xs {{ $req['passed'] ? 'text-green-700 dark:text-green-300' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $req['description'] }}</p>
                @if($req['passed'])
                <span class="mt-2 inline-block text-xs font-medium text-green-600 dark:text-green-400">✓ Qualified</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @else
    {{-- Dedicated Status Info for non-eligible members --}}
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 size-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <div>
                <p class="font-medium text-blue-800 dark:text-blue-200">About Dedicated Status</p>
                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    NRAPA offers two types of Dedicated Status: <strong>Dedicated Hunter</strong> and <strong>Dedicated Sport Shooter</strong>.
                    Each requires passing a specific knowledge test. Once passed, you can apply for Dedicated Status certification.
                </p>
            </div>
        </div>
    </div>
    @endif

    @if(!$this->canApplyForDedicatedStatus)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 size-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <div>
                <p class="font-medium text-amber-800 dark:text-amber-200">Membership Required</p>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    You need an active membership that allows Dedicated Status to take these tests.
                    @if(!auth()->user()->activeMembership)
                        Please <a href="{{ route('membership.apply') }}" wire:navigate class="underline">apply for membership</a> first.
                    @else
                        Your current membership type does not include Dedicated Status eligibility.
                    @endif
                </p>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
    </div>
    @endif

    {{-- In Progress Alert --}}
    @if($this->inProgressAttempt)
    <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <svg class="size-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <div>
                    <p class="font-medium text-yellow-800 dark:text-yellow-200">Test in progress</p>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">You have an unfinished attempt for "{{ $this->inProgressAttempt->knowledgeTest->name }}"</p>
                </div>
            </div>
            <a href="{{ route('knowledge-test.take', $this->inProgressAttempt->knowledgeTest) }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-yellow-600 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-700">
                Continue
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>
    </div>
    @endif

    {{-- Available Tests --}}
    <div class="grid gap-6 lg:grid-cols-2">
        @forelse($this->availableTests as $test)
        @php
            $status = $this->getTestStatus($test);
        @endphp
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            @if($test->dedicated_type === 'hunter')
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                🎯 Dedicated Hunter
                            </span>
                            @elseif($test->dedicated_type === 'sport' || $test->dedicated_type === 'sport_shooter')
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                🎯 Dedicated Sport Shooter
                            </span>
                            @elseif($test->dedicated_type === 'both')
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                🎯 Hunter & Sport Shooter Combined
                            </span>
                            @endif
                            
                            @if($test->dedicated_type === 'both')
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                Qualifies for both statuses
                            </span>
                            @endif
                            
                            @php
                                $satisfies = $this->getTestSatisfies($test);
                                $reqs = $this->dedicatedStatusRequirements;
                                $wouldHelp = false;
                                foreach ($satisfies as $s) {
                                    if (isset($reqs[$s]) && !$reqs[$s]['passed']) {
                                        $wouldHelp = true;
                                        break;
                                    }
                                }
                            @endphp
                            @if($wouldHelp && $status['status'] !== 'passed')
                            <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900 dark:text-orange-300">
                                ★ Recommended
                            </span>
                            @endif
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $test->name }}</h3>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $test->description }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $status['class'] }}">
                        {{ $status['label'] }}
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $test->active_questions_count }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Questions</p>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $test->passing_score }}%</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Pass Mark</p>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">
                            @if($test->time_limit_minutes)
                                {{ $test->time_limit_minutes }}m
                            @else
                                ∞
                            @endif
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Time Limit</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                @if($status['status'] === 'passed')
                <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="font-medium">You have passed this test</span>
                </div>
                @elseif($status['status'] === 'exhausted')
                <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span class="font-medium">Maximum attempts reached</span>
                </div>
                @elseif($status['status'] === 'in_progress')
                <a href="{{ route('knowledge-test.take', $test) }}" wire:navigate class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-yellow-600 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-700">
                    Continue Test
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                @else
                    @if($this->canApplyForDedicatedStatus)
                    <button wire:click="startTest({{ $test->id }})" wire:confirm="Are you sure you want to start this test? {{ $test->time_limit_minutes ? 'You will have ' . $test->time_limit_minutes . ' minutes to complete it.' : '' }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                        Start Test
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                    @else
                    <div class="text-center text-sm text-zinc-500 dark:text-zinc-400">
                        Membership with Dedicated Status eligibility required
                    </div>
                    @endif
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full rounded-xl border border-zinc-200 bg-white p-12 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No tests available</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">There are no knowledge tests available at this time.</p>
        </div>
        @endforelse
    </div>

    {{-- My Attempts History --}}
    @if($this->myAttempts->where('submitted_at', '!=', null)->count() > 0)
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">My Test History</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Test</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Result</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->myAttempts->where('submitted_at', '!=', null) as $attempt)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-900 dark:text-white">
                            {{ $attempt->knowledgeTest->name }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                            {{ $attempt->submitted_at->format('d M Y H:i') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-900 dark:text-white">
                            @if($attempt->total_score !== null)
                                {{ $attempt->total_score }} pts
                            @else
                                <span class="text-yellow-600 dark:text-yellow-400">Awaiting marking</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($attempt->passed === true)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Passed</span>
                            @elseif($attempt->passed === false)
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Failed</span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            @if($attempt->passed !== null)
                            <a href="{{ route('knowledge-test.results', $attempt) }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                View Results
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

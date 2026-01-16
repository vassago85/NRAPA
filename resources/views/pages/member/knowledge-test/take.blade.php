<?php

use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestAttempt;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Take Test')] class extends Component {
    public KnowledgeTest $test;
    public ?KnowledgeTestAttempt $attempt = null;
    public array $answers = [];
    public int $currentQuestionIndex = 0;

    public function mount(KnowledgeTest $test): void
    {
        $this->test = $test->load('activeQuestions');

        // Find or redirect
        $this->attempt = KnowledgeTestAttempt::with('answers.question')
            ->where('user_id', auth()->id())
            ->where('knowledge_test_id', $test->id)
            ->whereNull('submitted_at')
            ->first();

        if (!$this->attempt) {
            session()->flash('error', 'No active test attempt found.');
            $this->redirect(route('knowledge-test.index'), navigate: true);
            return;
        }

        // Check for timeout
        if ($this->attempt->hasTimedOut()) {
            $this->attempt->submit();
            session()->flash('info', 'Your test has been automatically submitted due to time expiry.');
            $this->redirect(route('knowledge-test.results', $this->attempt), navigate: true);
            return;
        }

        // Load saved answers
        foreach ($this->attempt->answers as $answer) {
            $this->answers[$answer->question_id] = $answer->answer_text ?? '';
        }
    }

    #[Computed]
    public function questions()
    {
        return $this->test->activeQuestions;
    }

    #[Computed]
    public function currentQuestion()
    {
        return $this->questions[$this->currentQuestionIndex] ?? null;
    }

    #[Computed]
    public function timeRemaining()
    {
        return $this->attempt?->getTimeRemainingInSeconds();
    }

    #[Computed]
    public function progress()
    {
        $answered = collect($this->answers)->filter(fn ($a) => !empty($a))->count();
        return [
            'answered' => $answered,
            'total' => $this->questions->count(),
            'percentage' => $this->questions->count() > 0 ? ($answered / $this->questions->count()) * 100 : 0,
        ];
    }

    public function saveAnswer(): void
    {
        if (!$this->currentQuestion) return;

        $questionId = $this->currentQuestion->id;
        $answerText = $this->answers[$questionId] ?? '';

        $this->attempt->answers()
            ->where('question_id', $questionId)
            ->update(['answer_text' => $answerText]);
    }

    public function previousQuestion(): void
    {
        $this->saveAnswer();
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        }
    }

    public function nextQuestion(): void
    {
        $this->saveAnswer();
        if ($this->currentQuestionIndex < $this->questions->count() - 1) {
            $this->currentQuestionIndex++;
        }
    }

    public function goToQuestion(int $index): void
    {
        $this->saveAnswer();
        $this->currentQuestionIndex = $index;
    }

    public function submitTest(): void
    {
        // Save current answer
        $this->saveAnswer();

        // Submit the attempt
        $this->attempt->submit();

        session()->flash('success', 'Your test has been submitted successfully.');
        $this->redirect(route('knowledge-test.results', $this->attempt), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col" x-data="{
    timeRemaining: {{ $this->timeRemaining ?? 'null' }},
    timerInterval: null,
    formatTime(seconds) {
        if (seconds === null) return 'Unlimited';
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    },
    init() {
        if (this.timeRemaining !== null) {
            this.timerInterval = setInterval(() => {
                this.timeRemaining--;
                if (this.timeRemaining <= 0) {
                    clearInterval(this.timerInterval);
                    $wire.submitTest();
                }
            }, 1000);
        }
    }
}" x-init="init()">
    {{-- Header --}}
    <div class="border-b border-zinc-200 bg-white px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $test->name }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Question {{ $currentQuestionIndex + 1 }} of {{ $this->questions->count() }}</p>
            </div>
            <div class="flex items-center gap-4">
                @if($this->timeRemaining !== null)
                <div class="flex items-center gap-2 rounded-lg px-4 py-2" :class="timeRemaining < 300 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-zinc-100 dark:bg-zinc-700'">
                    <svg class="size-5" :class="timeRemaining < 300 ? 'text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="font-mono text-lg font-bold" :class="timeRemaining < 300 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white'" x-text="formatTime(timeRemaining)"></span>
                </div>
                @endif
                <button wire:click="submitTest" wire:confirm="Are you sure you want to submit your test? You cannot change your answers after submitting." class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Submit Test
                </button>
            </div>
        </div>

        {{-- Progress --}}
        <div class="mt-4">
            <div class="flex items-center justify-between text-sm text-zinc-500 dark:text-zinc-400">
                <span>Progress: {{ $this->progress['answered'] }} of {{ $this->progress['total'] }} answered</span>
                <span>{{ number_format($this->progress['percentage'], 0) }}%</span>
            </div>
            <div class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" style="width: {{ $this->progress['percentage'] }}%"></div>
            </div>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
        {{-- Question Navigator Sidebar --}}
        <div class="hidden w-64 flex-shrink-0 overflow-y-auto border-r border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900 lg:block">
            <h3 class="mb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Questions</h3>
            <div class="grid grid-cols-5 gap-2">
                @foreach($this->questions as $index => $question)
                <button
                    wire:click="goToQuestion({{ $index }})"
                    class="flex size-10 items-center justify-center rounded-lg text-sm font-medium transition-colors
                        {{ $index === $currentQuestionIndex
                            ? 'bg-emerald-600 text-white'
                            : (!empty($this->answers[$question->id] ?? '')
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300'
                                : 'bg-white text-zinc-600 hover:bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700') }}"
                >
                    {{ $index + 1 }}
                </button>
                @endforeach
            </div>
            <div class="mt-4 space-y-2 text-xs text-zinc-500 dark:text-zinc-400">
                <div class="flex items-center gap-2">
                    <div class="size-4 rounded bg-emerald-600"></div>
                    <span>Current</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="size-4 rounded bg-emerald-100 dark:bg-emerald-900"></div>
                    <span>Answered</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="size-4 rounded bg-white dark:bg-zinc-800"></div>
                    <span>Not answered</span>
                </div>
            </div>
        </div>

        {{-- Question Content --}}
        <div class="flex-1 overflow-y-auto p-6">
            @if($this->currentQuestion)
            <div class="mx-auto max-w-3xl">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full {{ $this->currentQuestion->isMultipleChoice() ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }} px-2.5 py-0.5 text-xs font-medium">
                            {{ $this->currentQuestion->isMultipleChoice() ? 'Multiple Choice' : 'Written Answer' }}
                        </span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->currentQuestion->points }} {{ Str::plural('point', $this->currentQuestion->points) }}</span>
                    </div>

                    <h2 class="text-lg font-medium text-zinc-900 dark:text-white">{{ $this->currentQuestion->question_text }}</h2>

                    <div class="mt-6">
                        @if($this->currentQuestion->isMultipleChoice())
                        <div class="space-y-3">
                            @foreach($this->currentQuestion->options as $option)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition-colors {{ ($this->answers[$this->currentQuestion->id] ?? '') === $option ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-900/20' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                                <input
                                    type="radio"
                                    wire:model="answers.{{ $this->currentQuestion->id }}"
                                    value="{{ $option }}"
                                    class="size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700"
                                >
                                <span class="text-zinc-900 dark:text-white">{{ $option }}</span>
                            </label>
                            @endforeach
                        </div>
                        @else
                        <textarea
                            wire:model="answers.{{ $this->currentQuestion->id }}"
                            rows="6"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                            placeholder="Type your answer here..."
                        ></textarea>
                        @endif
                    </div>
                </div>

                {{-- Navigation --}}
                <div class="mt-6 flex items-center justify-between">
                    <button
                        wire:click="previousQuestion"
                        {{ $currentQuestionIndex === 0 ? 'disabled' : '' }}
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600"
                    >
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        Previous
                    </button>

                    {{-- Mobile question indicator --}}
                    <div class="flex items-center gap-1 lg:hidden">
                        @foreach($this->questions as $index => $question)
                        <button
                            wire:click="goToQuestion({{ $index }})"
                            class="size-2 rounded-full transition-colors {{ $index === $currentQuestionIndex ? 'bg-emerald-600' : (!empty($this->answers[$question->id] ?? '') ? 'bg-emerald-300' : 'bg-zinc-300 dark:bg-zinc-600') }}"
                        ></button>
                        @endforeach
                    </div>

                    @if($currentQuestionIndex < $this->questions->count() - 1)
                    <button
                        wire:click="nextQuestion"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                    >
                        Next
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                    @else
                    <button
                        wire:click="submitTest"
                        wire:confirm="Are you sure you want to submit your test? You cannot change your answers after submitting."
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                    >
                        Submit Test
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </button>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

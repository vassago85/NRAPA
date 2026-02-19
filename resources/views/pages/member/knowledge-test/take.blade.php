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

        // Backfill missing answer placeholders (e.g. after question conversion)
        $existingQuestionIds = $this->attempt->answers->pluck('question_id')->toArray();
        foreach ($this->test->activeQuestions as $question) {
            if (!in_array($question->id, $existingQuestionIds)) {
                $this->attempt->answers()->create(['question_id' => $question->id]);
            }
        }

        // Reload answers if any were created
        if (count($existingQuestionIds) < $this->test->activeQuestions->count()) {
            $this->attempt->load('answers.question');
        }

        // Load saved answers
        foreach ($this->attempt->answers as $answer) {
            $answerText = $answer->answer_text ?? '';
            if (empty($answerText)) {
                $this->answers[$answer->question_id] = '';
                continue;
            }
            if (str_starts_with($answerText, '[')) {
                $decoded = json_decode($answerText, true);
                $this->answers[$answer->question_id] = is_array($decoded) ? $decoded : $answerText;
            } elseif (str_starts_with($answerText, '{')) {
                $decoded = json_decode($answerText, true);
                $this->answers[$answer->question_id] = is_array($decoded) ? $decoded : $answerText;
            } else {
                $this->answers[$answer->question_id] = $answerText;
            }
        }
    }

    #[Computed]
    public function questions()
    {
        return $this->test->activeQuestions->values();
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
        $answered = collect($this->answers)->filter(function ($a) {
            if (is_array($a)) {
                return count($a) > 0;
            }
            return !empty($a);
        })->count();
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
        $answer = $this->answers[$questionId] ?? '';

        $answerText = is_array($answer) ? json_encode($answer) : $answer;

        $this->attempt->answers()->updateOrCreate(
            ['question_id' => $questionId],
            ['answer_text' => $answerText],
        );
    }

    // Toggle a multi-select option
    public function toggleMultiSelectOption(string $option): void
    {
        if (!$this->currentQuestion) return;

        $questionId = $this->currentQuestion->id;
        $currentAnswers = $this->answers[$questionId] ?? [];

        if (!is_array($currentAnswers)) {
            $currentAnswers = [];
        }

        if (in_array($option, $currentAnswers)) {
            $currentAnswers = array_values(array_diff($currentAnswers, [$option]));
        } else {
            $currentAnswers[] = $option;
        }

        $this->answers[$questionId] = $currentAnswers;
    }

    // Update priority order from drag-and-drop
    public function updatePriorityOrder(array $order): void
    {
        if (!$this->currentQuestion) return;

        $questionId = $this->currentQuestion->id;
        $this->answers[$questionId] = $order;
    }

    public function updateMatchingAnswer(array $matches): void
    {
        if (!$this->currentQuestion) return;

        $questionId = $this->currentQuestion->id;
        $this->answers[$questionId] = $matches;

        $this->attempt->answers()->updateOrCreate(
            ['question_id' => $questionId],
            ['answer_text' => json_encode($matches)],
        );
    }

    public function previousQuestion(): void
    {
        $this->saveAnswer();
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
            unset($this->currentQuestion);
        }
    }

    public function nextQuestion(): void
    {
        $this->saveAnswer();
        if ($this->currentQuestionIndex < $this->questions->count() - 1) {
            $this->currentQuestionIndex++;
            unset($this->currentQuestion);
        }
    }

    public function goToQuestion(int $index): void
    {
        $this->saveAnswer();
        $this->currentQuestionIndex = $index;
        unset($this->currentQuestion);
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
                <button wire:click="submitTest" wire:confirm="Are you sure you want to submit your test? You cannot change your answers after submitting." class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
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
                            ? 'bg-nrapa-blue text-white'
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
                    <div class="size-4 rounded bg-nrapa-blue"></div>
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
            <div class="mx-auto max-w-3xl" wire:key="question-{{ $this->currentQuestion->id }}">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    @php
                        $typeColors = [
                            'multiple_choice' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                            'multiple_select' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'priority_order' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                            'matching' => 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200',
                            'written' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                        ];
                        $typeLabels = [
                            'multiple_choice' => 'Select One Answer',
                            'multiple_select' => 'Select All That Apply',
                            'priority_order' => 'Drag to Order',
                            'matching' => 'Drag to Match',
                            'written' => 'Written Answer',
                        ];
                        $qType = $this->currentQuestion->question_type;
                    @endphp
                    <div class="mb-4 flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full {{ $typeColors[$qType] ?? 'bg-zinc-100 text-zinc-800' }} px-2.5 py-0.5 text-xs font-medium">
                            {{ $typeLabels[$qType] ?? $qType }}
                        </span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->currentQuestion->points }} {{ Str::plural('point', $this->currentQuestion->points) }}</span>
                    </div>

                    <h2 class="text-lg font-medium text-zinc-900 dark:text-white">{{ $this->currentQuestion->question_text }}</h2>

                    @if($this->currentQuestion->hasImage())
                    <div class="mt-4">
                        <img src="{{ $this->currentQuestion->image_url }}" alt="Question image" class="max-w-full rounded-lg border border-zinc-200 dark:border-zinc-700">
                    </div>
                    @endif

                    <div class="mt-6">
                        @if($this->currentQuestion->isMultipleChoice())
                        {{-- Single answer - Radio buttons --}}
                        <div class="space-y-3">
                            @foreach($this->currentQuestion->options as $optionKey => $optionText)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition-colors {{ ($this->answers[$this->currentQuestion->id] ?? '') === $optionKey ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-900/20' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                                <input
                                    type="radio"
                                    wire:model="answers.{{ $this->currentQuestion->id }}"
                                    value="{{ $optionKey }}"
                                    class="size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700"
                                >
                                <span class="text-zinc-900 dark:text-white">{{ $optionText }}</span>
                            </label>
                            @endforeach
                        </div>
                        @elseif($this->currentQuestion->isMultipleSelect())
                        {{-- Multiple answers - Checkboxes --}}
                        @php
                            $selectedAnswers = $this->answers[$this->currentQuestion->id] ?? [];
                            if (!is_array($selectedAnswers)) $selectedAnswers = [];
                        @endphp
                        <div class="space-y-3">
                            @foreach($this->currentQuestion->options as $optionKey => $optionText)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition-colors {{ in_array($optionKey, $selectedAnswers) ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-900/20' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                                <input
                                    type="checkbox"
                                    wire:click="toggleMultiSelectOption('{{ addslashes($optionKey) }}')"
                                    {{ in_array($optionKey, $selectedAnswers) ? 'checked' : '' }}
                                    class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700"
                                >
                                <span class="text-zinc-900 dark:text-white">{{ $optionText }}</span>
                            </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ count($selectedAnswers) }} option(s) selected
                        </p>
                        @elseif($this->currentQuestion->isPriorityOrder())
                        {{-- Priority order - Drag and drop --}}
                        @php
                            $options = $this->currentQuestion->options ?? [];
                            $savedOrder = $this->answers[$this->currentQuestion->id] ?? [];
                            // Initialize with keys in original order if no saved order
                            if (!is_array($savedOrder) || empty($savedOrder)) {
                                $orderedKeys = array_keys($options);
                            } else {
                                $orderedKeys = $savedOrder;
                            }
                        @endphp
                        <div class="rounded-lg border border-purple-200 bg-purple-50 p-3 mb-4 dark:border-purple-800 dark:bg-purple-900/20">
                            <p class="text-xs text-purple-700 dark:text-purple-300">Drag items to arrange them in the correct order. Position 1 is at the top.</p>
                        </div>
                        <div 
                            x-data="{
                                orderedKeys: @js($orderedKeys),
                                optionsMap: @js($options),
                                draggedIndex: null,
                                init() {
                                    // Initialize if empty
                                    if (this.orderedKeys.length === 0) {
                                        this.orderedKeys = Object.keys(this.optionsMap);
                                    }
                                },
                                startDrag(index) {
                                    this.draggedIndex = index;
                                },
                                dragOver(index) {
                                    if (this.draggedIndex === null || this.draggedIndex === index) return;
                                    
                                    const item = this.orderedKeys[this.draggedIndex];
                                    this.orderedKeys.splice(this.draggedIndex, 1);
                                    this.orderedKeys.splice(index, 0, item);
                                    this.draggedIndex = index;
                                },
                                endDrag() {
                                    this.draggedIndex = null;
                                    $wire.updatePriorityOrder(this.orderedKeys);
                                },
                                getOptionText(key) {
                                    return this.optionsMap[key] || key;
                                }
                            }"
                            class="space-y-2"
                        >
                            <template x-for="(key, index) in orderedKeys" :key="key">
                                <div 
                                    draggable="true"
                                    @dragstart="startDrag(index)"
                                    @dragover.prevent="dragOver(index)"
                                    @dragend="endDrag()"
                                    class="flex cursor-move items-center gap-3 rounded-lg border border-zinc-200 bg-white p-4 transition-colors hover:border-purple-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-purple-600"
                                    :class="{ 'opacity-50': draggedIndex === index }"
                                >
                                    <span class="flex size-6 items-center justify-center rounded-full bg-purple-100 text-xs font-bold text-purple-700 dark:bg-purple-900 dark:text-purple-300" x-text="index + 1"></span>
                                    <svg class="size-5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                    </svg>
                                    <span class="text-zinc-900 dark:text-white" x-text="getOptionText(key)"></span>
                                </div>
                            </template>
                        </div>
                        @elseif($this->currentQuestion->isMatching())
                        {{-- Matching - Drag answers to match with definitions --}}
                        @php
                            $options = $this->currentQuestion->options ?? []; // Left side (items)
                            $shuffledAnswers = $this->currentQuestion->getShuffledMatchingAnswers($this->attempt->id); // Correct answers + distractors, shuffled
                            $savedMatches = $this->answers[$this->currentQuestion->id] ?? [];
                            if (is_string($savedMatches) && str_starts_with($savedMatches, '{')) {
                                $savedMatches = json_decode($savedMatches, true) ?? [];
                            }
                            if (!is_array($savedMatches)) {
                                $savedMatches = [];
                            }
                        @endphp
                        <div 
                            x-data="{
                                options: @js($options),
                                shuffledAnswers: @js($shuffledAnswers),
                                matches: JSON.parse(JSON.stringify(@js($savedMatches) || {})),
                                availableAnswers: [],
                                draggedAnswer: null,
                                selectedAnswer: null,
                                init() {
                                    this.updateAvailableAnswers();
                                },
                                updateAvailableAnswers() {
                                    const matchedAnswers = Object.values(this.matches);
                                    this.availableAnswers = this.shuffledAnswers.filter(a => !matchedAnswers.includes(a));
                                },
                                startDrag(answer) {
                                    this.draggedAnswer = answer;
                                },
                                syncToWire() {
                                    $wire.updateMatchingAnswer(JSON.parse(JSON.stringify(this.matches)));
                                },
                                assignAnswer(key, answer) {
                                    let updated = {};
                                    for (let k in this.matches) {
                                        if (this.matches[k] !== answer) {
                                            updated[k] = this.matches[k];
                                        }
                                    }
                                    updated[key] = answer;
                                    this.matches = updated;
                                    this.selectedAnswer = null;
                                    this.draggedAnswer = null;
                                    this.updateAvailableAnswers();
                                    this.syncToWire();
                                },
                                dropOnSlot(key) {
                                    if (!this.draggedAnswer) return;
                                    this.assignAnswer(key, this.draggedAnswer);
                                },
                                tapAnswer(answer) {
                                    this.selectedAnswer = (this.selectedAnswer === answer) ? null : answer;
                                },
                                tapSlot(key) {
                                    if (this.selectedAnswer) {
                                        this.assignAnswer(key, this.selectedAnswer);
                                    }
                                },
                                removeMatch(key) {
                                    let updated = {};
                                    for (let k in this.matches) {
                                        if (k !== key) {
                                            updated[k] = this.matches[k];
                                        }
                                    }
                                    this.matches = updated;
                                    this.updateAvailableAnswers();
                                    this.syncToWire();
                                },
                                endDrag() {
                                    this.draggedAnswer = null;
                                }
                            }"
                        >
                            {{-- Answers panel: always visible at the top --}}
                            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50/50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">Answers <span class="normal-case">(tap an answer, then tap its slot — or drag on desktop)</span></p>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="(answer, index) in availableAnswers" :key="answer">
                                        <div 
                                            draggable="true"
                                            @dragstart="startDrag(answer)"
                                            @dragend="endDrag()"
                                            @click="tapAnswer(answer)"
                                            class="cursor-pointer select-none rounded-lg border px-3 py-2 text-sm font-medium transition-all"
                                            :class="selectedAnswer === answer
                                                ? 'border-blue-500 bg-blue-600 text-white shadow-md ring-2 ring-blue-300 dark:ring-blue-700'
                                                : 'border-blue-200 bg-white text-blue-800 hover:border-blue-400 hover:shadow-sm dark:border-blue-700 dark:bg-blue-900/40 dark:text-blue-200'"
                                            x-text="answer"
                                        ></div>
                                    </template>
                                    <template x-if="availableAnswers.length === 0 && Object.keys(matches).length > 0">
                                        <p class="text-xs text-emerald-600 dark:text-emerald-400 py-1">All answers matched!</p>
                                    </template>
                                </div>
                            </div>

                            {{-- Items with drop/tap slots --}}
                            <div class="space-y-3">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">Items — <span x-text="Object.keys(matches).length"></span> of {{ count($options) }} matched</p>
                                <template x-for="(text, key) in options" :key="key">
                                    <div class="flex items-start gap-3">
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-bold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300" x-text="key"></span>
                                        <div class="flex-1">
                                            <p class="text-sm text-zinc-900 dark:text-white mb-2" x-text="text"></p>
                                            <div 
                                                @dragover.prevent
                                                @drop="dropOnSlot(key)"
                                                @click="tapSlot(key)"
                                                class="min-h-[44px] rounded-lg border-2 border-dashed transition-colors cursor-pointer"
                                                :class="[
                                                    matches[key]
                                                        ? 'border-emerald-400 bg-emerald-50 dark:border-emerald-600 dark:bg-emerald-900/20'
                                                        : (selectedAnswer
                                                            ? 'border-blue-400 bg-blue-50/50 dark:border-blue-500 dark:bg-blue-900/10 hover:border-blue-500'
                                                            : 'border-zinc-300 dark:border-zinc-600')
                                                ]"
                                            >
                                                <template x-if="matches[key]">
                                                    <div class="flex items-center justify-between p-2 gap-2">
                                                        <span class="text-sm text-emerald-700 dark:text-emerald-300" x-text="matches[key]"></span>
                                                        <button @click.stop="removeMatch(key)" class="text-zinc-400 hover:text-red-500 dark:hover:text-red-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </template>
                                                <template x-if="!matches[key]">
                                                    <p class="p-2 text-xs" :class="selectedAnswer ? 'text-blue-500 dark:text-blue-400' : 'text-zinc-400 dark:text-zinc-500'" x-text="selectedAnswer ? 'Tap to place here' : 'Drop answer here'"></p>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        @elseif($this->currentQuestion->isWritten())
                        {{-- Written answer - Textarea --}}
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
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors"
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
                            class="size-2 rounded-full transition-colors {{ $index === $currentQuestionIndex ? 'bg-nrapa-blue' : (!empty($this->answers[$question->id] ?? '') ? 'bg-nrapa-blue/40' : 'bg-zinc-300 dark:bg-zinc-600') }}"
                        ></button>
                        @endforeach
                    </div>

                    @if($currentQuestionIndex < $this->questions->count() - 1)
                    <button
                        wire:click="nextQuestion"
                        class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors"
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
                        class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors"
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

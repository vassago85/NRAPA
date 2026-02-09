<?php

use App\Models\KnowledgeTestAttempt;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Test Results')] class extends Component {
    public KnowledgeTestAttempt $attempt;

    public function mount(KnowledgeTestAttempt $attempt): void
    {
        // Ensure the attempt belongs to the current user
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        // Ensure the attempt has been submitted
        if (!$attempt->isSubmitted()) {
            $this->redirect(route('knowledge-test.take', $attempt->knowledgeTest), navigate: true);
            return;
        }

        $this->attempt = $attempt->load(['knowledgeTest', 'answers.question', 'marker']);
    }

    #[Computed]
    public function totalPossiblePoints()
    {
        return $this->attempt->knowledgeTest->total_points;
    }

    #[Computed]
    public function percentage()
    {
        if ($this->totalPossiblePoints === 0) return 0;
        return ($this->attempt->total_score / $this->totalPossiblePoints) * 100;
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('knowledge-test.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Test Results</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $attempt->knowledgeTest->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">

    {{-- Pending Marking Notice --}}
    @if($attempt->passed === null)
    <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-6 dark:border-yellow-800 dark:bg-yellow-900/20">
        <div class="flex items-start gap-4">
            <svg class="mt-0.5 size-6 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div>
                <h3 class="font-semibold text-yellow-800 dark:text-yellow-200">Awaiting Marking</h3>
                <p class="mt-1 text-yellow-700 dark:text-yellow-300">
                    Your test contains written questions that require manual marking by an administrator.
                    You will be notified once your results are finalized.
                </p>
                <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">
                    Multiple choice answers have been auto-scored: {{ $attempt->auto_score ?? 0 }} points
                </p>
            </div>
        </div>
    </div>
    @else
    {{-- Result Card --}}
    <div class="rounded-xl border {{ $attempt->passed ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' }} p-8 text-center">
        @if($attempt->passed)
        <svg class="mx-auto size-16 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <h2 class="mt-4 text-2xl font-bold text-emerald-800 dark:text-emerald-200">Congratulations! You Passed!</h2>
        @else
        <svg class="mx-auto size-16 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <h2 class="mt-4 text-2xl font-bold text-red-800 dark:text-red-200">Unfortunately, You Did Not Pass</h2>
        @endif

        <div class="mt-6 flex items-center justify-center gap-8">
            <div>
                <p class="text-4xl font-bold {{ $attempt->passed ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                    {{ number_format($this->percentage, 1) }}%
                </p>
                <p class="text-sm {{ $attempt->passed ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">Your Score</p>
            </div>
            <div class="h-16 w-px bg-current opacity-20"></div>
            <div>
                <p class="text-4xl font-bold {{ $attempt->passed ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                    {{ $attempt->total_score }} / {{ $this->totalPossiblePoints }}
                </p>
                <p class="text-sm {{ $attempt->passed ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">Points</p>
            </div>
        </div>

        <p class="mt-4 text-sm {{ $attempt->passed ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
            Pass mark: {{ $attempt->knowledgeTest->passing_score }}%
        </p>

        @if(!$attempt->passed && $attempt->knowledgeTest->canAttempt(auth()->user()))
        <a href="{{ route('knowledge-test.index') }}" wire:navigate class="mt-6 inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-6 py-3 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
            Try Again
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
        </a>
        @endif
    </div>
    @endif

    {{-- Test Details --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="font-semibold text-zinc-900 dark:text-white">Test Information</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Test Name</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $attempt->knowledgeTest->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Pass Mark</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $attempt->knowledgeTest->passing_score }}%</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Total Questions</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $attempt->answers->count() }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="font-semibold text-zinc-900 dark:text-white">Your Attempt</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Started</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $attempt->started_at->format('d M Y H:i') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Submitted</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $attempt->submitted_at->format('d M Y H:i') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Duration</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $attempt->started_at->diffForHumans($attempt->submitted_at, true) }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="font-semibold text-zinc-900 dark:text-white">Score Breakdown</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Multiple Choice</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $attempt->auto_score ?? 0 }} pts</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Written</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $attempt->manual_score ?? 0 }} pts</dd>
                </div>
                <div class="flex justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                    <dt class="font-medium text-zinc-900 dark:text-white">Total</dt>
                    <dd class="font-bold text-zinc-900 dark:text-white">{{ $attempt->total_score ?? '—' }} pts</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Answer Review --}}
    @if($attempt->passed !== null)
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Answer Review</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Review your answers and see the correct responses.</p>
        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach($attempt->answers->sortBy('question.sort_order') as $index => $answer)
            @php
                $qType = $answer->question->question_type;
                $typeColors = [
                    'multiple_choice' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    'multiple_select' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                    'priority_order' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                    'matching' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
                    'written' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                ];
                $typeLabels = [
                    'multiple_choice' => 'Single Answer',
                    'multiple_select' => 'Multi-Select',
                    'priority_order' => 'Priority Order',
                    'matching' => 'Matching',
                    'written' => 'Written',
                ];
                
                                // Parse answer for multi-select and priority order (array)
                                $memberAnswer = $answer->answer_text;
                                $memberAnswerArray = [];
                                if (!empty($memberAnswer) && str_starts_with($memberAnswer, '[')) {
                                    $memberAnswerArray = json_decode($memberAnswer, true) ?? [];
                                }
                                // Parse answer for matching (object)
                                $memberMatchesObj = [];
                                if (!empty($memberAnswer) && str_starts_with($memberAnswer, '{')) {
                                    $memberMatchesObj = json_decode($memberAnswer, true) ?? [];
                                }
            @endphp
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex size-8 items-center justify-center rounded-full {{ $answer->points_awarded > 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' }} text-sm font-semibold">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full {{ $typeColors[$qType] ?? '' }} px-2 py-0.5 text-xs font-medium">
                                    {{ $typeLabels[$qType] ?? $qType }}
                                </span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $answer->points_awarded ?? 0 }} / {{ $answer->question->points }} pts
                                </span>
                            </div>
                            <p class="mt-2 font-medium text-zinc-900 dark:text-white">{{ $answer->question->question_text }}</p>

                            @if($answer->question->hasImage())
                            <div class="mt-3">
                                <img src="{{ $answer->question->image_url }}" alt="Question image" class="max-h-48 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            </div>
                            @endif

                            <div class="mt-3 space-y-3">
                                @if($qType === 'multiple_choice')
                                {{-- Multiple Choice: Show all options with single correct --}}
                                @if($answer->question->options && is_array($answer->question->options) && count($answer->question->options) > 0)
                                <div class="space-y-2">
                                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Options:</p>
                                    <div class="grid gap-2">
                                        @foreach($answer->question->options as $optionKey => $optionText)
                                        @php
                                            $isSelected = $answer->answer_text === $optionKey;
                                            $isCorrect = $answer->question->correct_answer === $optionKey;
                                        @endphp
                                        <div class="flex items-start gap-3 rounded-lg border p-3 text-sm
                                            {{ $isCorrect ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/30' : '' }}
                                            {{ $isSelected && !$isCorrect ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/30' : '' }}
                                            {{ !$isSelected && !$isCorrect ? 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800' : '' }}">
                                            <div class="flex-1">
                                                <span class="{{ $isCorrect ? 'text-emerald-800 dark:text-emerald-200' : ($isSelected ? 'text-red-800 dark:text-red-200' : 'text-zinc-700 dark:text-zinc-300') }}">
                                                    <span class="font-semibold">{{ $optionKey }}.</span> {{ $optionText }}
                                                </span>
                                                @if($isCorrect && $isSelected)
                                                <span class="ml-2 inline-flex items-center rounded-full bg-emerald-200 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200">
                                                    Your Answer - Correct!
                                                </span>
                                                @elseif($isCorrect)
                                                <span class="ml-2 inline-flex items-center rounded-full bg-emerald-200 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200">
                                                    Correct Answer
                                                </span>
                                                @elseif($isSelected)
                                                <span class="ml-2 inline-flex items-center rounded-full bg-red-200 px-2 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-800 dark:text-red-200">
                                                    Your Answer
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                @elseif($qType === 'multiple_select')
                                {{-- Multi-Select: Show all options with multiple correct --}}
                                @php
                                    $correctAnswers = $answer->question->correct_answers ?? [];
                                @endphp
                                <div class="space-y-2">
                                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Options (multiple correct answers):</p>
                                    <div class="grid gap-2">
                                        @foreach($answer->question->options as $optionKey => $optionText)
                                        @php
                                            $isSelected = in_array($optionKey, $memberAnswerArray);
                                            $isCorrect = in_array($optionKey, $correctAnswers);
                                        @endphp
                                        <div class="flex items-start gap-3 rounded-lg border p-3 text-sm
                                            {{ $isCorrect && $isSelected ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/30' : '' }}
                                            {{ $isCorrect && !$isSelected ? 'border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/30' : '' }}
                                            {{ $isSelected && !$isCorrect ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/30' : '' }}
                                            {{ !$isSelected && !$isCorrect ? 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800' : '' }}">
                                            <input type="checkbox" disabled {{ $isSelected ? 'checked' : '' }} class="mt-0.5 size-4 rounded border-zinc-300">
                                            <div class="flex-1">
                                                <span class="{{ $isCorrect ? 'text-emerald-800 dark:text-emerald-200' : ($isSelected ? 'text-red-800 dark:text-red-200' : 'text-zinc-700 dark:text-zinc-300') }}">
                                                    <span class="font-semibold">{{ $optionKey }}.</span> {{ $optionText }}
                                                </span>
                                                @if($isCorrect && $isSelected)
                                                <span class="ml-2 inline-flex items-center rounded-full bg-emerald-200 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200">
                                                    Correct!
                                                </span>
                                                @elseif($isCorrect)
                                                <span class="ml-2 inline-flex items-center rounded-full bg-yellow-200 px-2 py-0.5 text-xs font-semibold text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200">
                                                    Missed
                                                </span>
                                                @elseif($isSelected)
                                                <span class="ml-2 inline-flex items-center rounded-full bg-red-200 px-2 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-800 dark:text-red-200">
                                                    Wrong
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                @elseif($qType === 'priority_order')
                                {{-- Priority Order: Show member's order vs correct order --}}
                                @php
                                    $options = $answer->question->options ?? [];
                                    $correctOrder = $answer->question->correct_answers ?? array_keys($options);
                                @endphp
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Your Order:</p>
                                        @foreach($memberAnswerArray as $idx => $key)
                                        @php
                                            $correctPosition = array_search($key, $correctOrder);
                                            $isCorrectPosition = $correctPosition === $idx;
                                            $optionText = $options[$key] ?? $key;
                                        @endphp
                                        <div class="flex items-center gap-2 rounded-lg border p-2 text-sm
                                            {{ $isCorrectPosition ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/30' : 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/30' }}">
                                            <span class="flex size-5 items-center justify-center rounded-full {{ $isCorrectPosition ? 'bg-emerald-200 text-emerald-800' : 'bg-red-200 text-red-800' }} text-xs font-bold">{{ $idx + 1 }}</span>
                                            <span class="{{ $isCorrectPosition ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}"><span class="font-semibold">{{ $key }}.</span> {{ $optionText }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Correct Order:</p>
                                        @foreach($correctOrder as $idx => $key)
                                        @php $optionText = $options[$key] ?? $key; @endphp
                                        <div class="flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 p-2 text-sm dark:border-emerald-700 dark:bg-emerald-900/30">
                                            <span class="flex size-5 items-center justify-center rounded-full bg-emerald-200 text-xs font-bold text-emerald-800">{{ $idx + 1 }}</span>
                                            <span class="text-emerald-700 dark:text-emerald-300"><span class="font-semibold">{{ $key }}.</span> {{ $optionText }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                @elseif($qType === 'matching')
                                {{-- Matching: Show items with member's matches vs correct matches --}}
                                @php
                                    $matchOptions = $answer->question->options ?? []; // Left side items
                                    $correctMatches = $answer->question->correct_answers ?? []; // Correct pairs
                                @endphp
                                <div class="space-y-3">
                                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Your Matches:</p>
                                    @foreach($matchOptions as $key => $itemText)
                                    @php
                                        $memberMatch = $memberMatchesObj[$key] ?? null;
                                        $correctMatch = $correctMatches[$key] ?? null;
                                        $isCorrect = $memberMatch === $correctMatch;
                                    @endphp
                                    <div class="flex items-start gap-3 rounded-lg border p-3 text-sm
                                        {{ $isCorrect ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/30' : 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/30' }}">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="flex size-6 items-center justify-center rounded-full bg-zinc-200 text-xs font-bold text-zinc-700 dark:bg-zinc-600 dark:text-zinc-200">{{ $key }}</span>
                                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $itemText }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 pl-8">
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">Your answer:</span>
                                                @if($memberMatch)
                                                <span class="{{ $isCorrect ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">{{ $memberMatch }}</span>
                                                @else
                                                <span class="text-zinc-400 italic">Not matched</span>
                                                @endif
                                                @if(!$isCorrect && $correctMatch)
                                                <span class="text-xs text-zinc-400">→</span>
                                                <span class="text-xs text-emerald-600 dark:text-emerald-400">Correct: {{ $correctMatch }}</span>
                                                @endif
                                                @if($isCorrect)
                                                <span class="inline-flex items-center rounded-full bg-emerald-200 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200">✓</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                @elseif($qType === 'written')
                                {{-- Written answer --}}
                                <div class="rounded-lg border {{ $answer->is_correct ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' : 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800' }} p-3">
                                    <p class="text-xs font-medium {{ $answer->is_correct ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400' }}">Your Answer:</p>
                                    <p class="mt-1 text-sm {{ $answer->is_correct ? 'text-emerald-800 dark:text-emerald-200' : 'text-zinc-700 dark:text-zinc-300' }}">
                                        {{ $answer->answer_text ?: '(No answer provided)' }}
                                    </p>
                                </div>
                                @endif

                                @if($answer->marker_feedback)
                                <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                    <p class="text-xs font-medium text-blue-600 dark:text-blue-400">Marker Feedback:</p>
                                    <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">{{ $answer->marker_feedback }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Marked By --}}
    @if($attempt->marker)
    <div class="text-center text-sm text-zinc-500 dark:text-zinc-400">
        Marked by {{ $attempt->marker->name }} on {{ $attempt->marked_at->format('d M Y \a\t H:i') }}
    </div>
    @endif
    </div>
</div>

<?php

use App\Models\KnowledgeTestAttempt;
use App\Models\KnowledgeTestAnswer;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mark Test Attempt - Admin')] class extends Component {
    public KnowledgeTestAttempt $attempt;
    public array $scores = [];
    public array $feedback = [];
    public string $markerNotes = '';

    public function mount(KnowledgeTestAttempt $attempt): void
    {
        $this->attempt = $attempt->load(['user', 'knowledgeTest', 'answers.question']);

        // Initialize scores for written questions
        foreach ($this->attempt->answers as $answer) {
            if ($answer->question->isWritten()) {
                $this->scores[$answer->id] = $answer->points_awarded ?? 0;
                $this->feedback[$answer->id] = $answer->marker_feedback ?? '';
            }
        }
    }

    #[Computed]
    public function writtenAnswers()
    {
        return $this->attempt->answers()
            ->whereHas('question', fn ($q) => $q->where('question_type', 'written'))
            ->with('question')
            ->get();
    }

    #[Computed]
    public function autoMarkedAnswers()
    {
        return $this->attempt->answers()
            ->whereHas('question', fn ($q) => $q->whereIn('question_type', ['multiple_choice', 'multiple_select', 'priority_order']))
            ->with('question')
            ->get();
    }

    #[Computed]
    public function totalPossiblePoints()
    {
        return $this->attempt->knowledgeTest->total_points;
    }

    public function saveMarking(): void
    {
        // Validate scores
        foreach ($this->scores as $answerId => $score) {
            $answer = KnowledgeTestAnswer::find($answerId);
            if ($answer && $score > $answer->question->points) {
                session()->flash('error', "Score for question cannot exceed {$answer->question->points} points.");
                return;
            }
        }

        // Update written answer scores
        foreach ($this->writtenAnswers as $answer) {
            $answer->update([
                'points_awarded' => $this->scores[$answer->id] ?? 0,
                'marker_feedback' => $this->feedback[$answer->id] ?? null,
            ]);
        }

        // Mark the attempt as complete
        $this->attempt->markComplete(Auth::user(), $this->markerNotes ?: null);

        // Log the action
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'knowledge_test_marked',
            'auditable_type' => KnowledgeTestAttempt::class,
            'auditable_id' => $this->attempt->id,
            'old_values' => null,
            'new_values' => [
                'total_score' => $this->attempt->fresh()->total_score,
                'passed' => $this->attempt->fresh()->passed,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        session()->flash('success', 'Test marked successfully.');

        $this->redirect(route('admin.knowledge-tests.marking'), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.knowledge-tests.marking') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Mark Test Attempt</h1>
                <p class="text-zinc-600 dark:text-zinc-400">{{ $attempt->knowledgeTest->name }}</p>
            </div>
        </div>
    </div>

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
    </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Member Info --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Member</h2>
            <div class="flex items-center gap-4">
                <div class="flex size-14 items-center justify-center rounded-full bg-emerald-100 text-lg font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                    {{ $attempt->user->initials() }}
                </div>
                <div>
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $attempt->user->name }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $attempt->user->email }}</p>
                </div>
            </div>
            <dl class="mt-4 space-y-2 text-sm">
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
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Pass Mark</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $attempt->knowledgeTest->passing_score }}%</dd>
                </div>
            </dl>
        </div>

        {{-- Score Summary --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 lg:col-span-2">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Score Summary</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                    <p class="text-sm text-blue-600 dark:text-blue-400">Multiple Choice (Auto)</p>
                    <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $attempt->auto_score ?? 0 }} pts</p>
                </div>
                <div class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
                    <p class="text-sm text-purple-600 dark:text-purple-400">Written (Manual)</p>
                    <p class="mt-1 text-2xl font-bold text-purple-700 dark:text-purple-300">{{ collect($this->scores)->sum() }} pts</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-4 dark:bg-emerald-900/20">
                    <p class="text-sm text-emerald-600 dark:text-emerald-400">Total</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">
                        {{ ($attempt->auto_score ?? 0) + collect($this->scores)->sum() }} / {{ $this->totalPossiblePoints }} pts
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Auto-Marked Answers (Multiple Choice, Multiple Select, Priority Order) --}}
    @if($this->autoMarkedAnswers->count() > 0)
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Auto-Marked Answers</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Multiple choice, multi-select, and priority order questions</p>
        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach($this->autoMarkedAnswers as $answer)
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
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            @if($answer->is_correct)
                            <svg class="size-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            @else
                            <svg class="size-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            @endif
                            <span class="inline-flex items-center rounded-full {{ $typeColors[$qType] ?? '' }} px-2 py-0.5 text-xs font-medium">
                                {{ $typeLabels[$qType] ?? $qType }}
                            </span>
                            <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $answer->question->points }} {{ Str::plural('point', $answer->question->points) }}</span>
                        </div>
                        <p class="mt-2 text-zinc-900 dark:text-white">{{ $answer->question->question_text }}</p>
                        @if($answer->question->hasImage())
                        <div class="mt-3">
                            <img src="{{ $answer->question->image_url }}" alt="Question image" class="max-h-32 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        </div>
                        @endif

                        @if($qType === 'multiple_choice')
                        {{-- Single answer display --}}
                        @if($answer->question->options && is_array($answer->question->options) && count($answer->question->options) > 0)
                        <div class="mt-3 space-y-2">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Options:</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($answer->question->options as $optionKey => $optionText)
                                @php
                                    $isSelected = $answer->answer_text === $optionKey;
                                    $isCorrect = $answer->question->correct_answer === $optionKey;
                                @endphp
                                <div class="flex items-start gap-2 rounded-lg border p-2 text-sm
                                    {{ $isCorrect ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/40' : '' }}
                                    {{ $isSelected && !$isCorrect ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/30' : '' }}
                                    {{ !$isSelected && !$isCorrect ? 'border-zinc-200 dark:border-zinc-700' : '' }}">
                                    <span class="{{ $isCorrect ? 'text-emerald-700 dark:text-emerald-300' : ($isSelected ? 'text-red-700 dark:text-red-300' : 'text-zinc-600 dark:text-zinc-400') }}">
                                        <span class="font-semibold">{{ $optionKey }}.</span> {{ $optionText }}
                                        @if($isCorrect && $isSelected)
                                        <span class="ml-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">(Correct!)</span>
                                        @elseif($isCorrect)
                                        <span class="ml-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">(Correct)</span>
                                        @elseif($isSelected)
                                        <span class="ml-1 text-xs font-semibold text-red-600 dark:text-red-400">(Selected)</span>
                                        @endif
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @elseif($qType === 'multiple_select')
                        {{-- Multi-select display --}}
                        @php
                            $correctAnswers = $answer->question->correct_answers ?? [];
                        @endphp
                        <div class="mt-3 space-y-2">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Options (select all that apply):</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($answer->question->options as $optionKey => $optionText)
                                @php
                                    $isSelected = in_array($optionKey, $memberAnswerArray);
                                    $isCorrect = in_array($optionKey, $correctAnswers);
                                @endphp
                                <div class="flex items-start gap-2 rounded-lg border p-2 text-sm
                                    {{ $isCorrect && $isSelected ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/40' : '' }}
                                    {{ $isCorrect && !$isSelected ? 'border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/30' : '' }}
                                    {{ $isSelected && !$isCorrect ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/30' : '' }}
                                    {{ !$isSelected && !$isCorrect ? 'border-zinc-200 dark:border-zinc-700' : '' }}">
                                    <input type="checkbox" disabled {{ $isSelected ? 'checked' : '' }} class="mt-0.5 size-4 rounded border-zinc-300">
                                    <span class="{{ $isCorrect ? 'text-emerald-700 dark:text-emerald-300' : ($isSelected ? 'text-red-700 dark:text-red-300' : 'text-zinc-600 dark:text-zinc-400') }}">
                                        <span class="font-semibold">{{ $optionKey }}.</span> {{ $optionText }}
                                        @if($isCorrect && $isSelected)
                                        <span class="ml-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">(Correct!)</span>
                                        @elseif($isCorrect)
                                        <span class="ml-1 text-xs font-semibold text-yellow-600 dark:text-yellow-400">(Missed)</span>
                                        @elseif($isSelected)
                                        <span class="ml-1 text-xs font-semibold text-red-600 dark:text-red-400">(Wrong)</span>
                                        @endif
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        @elseif($qType === 'priority_order')
                        {{-- Priority order display --}}
                        @php
                            $options = $answer->question->options ?? [];
                            $correctOrder = $answer->question->correct_answers ?? array_keys($options);
                        @endphp
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Member's Order:</p>
                                @foreach($memberAnswerArray as $idx => $key)
                                @php
                                    $correctPosition = array_search($key, $correctOrder);
                                    $isCorrectPosition = $correctPosition === $idx;
                                    $optionText = $options[$key] ?? $key;
                                @endphp
                                <div class="flex items-center gap-2 rounded-lg border p-2 text-sm
                                    {{ $isCorrectPosition ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/40' : 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/30' }}">
                                    <span class="flex size-5 items-center justify-center rounded-full {{ $isCorrectPosition ? 'bg-emerald-200 text-emerald-800' : 'bg-red-200 text-red-800' }} text-xs font-bold">{{ $idx + 1 }}</span>
                                    <span class="{{ $isCorrectPosition ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}"><span class="font-semibold">{{ $key }}.</span> {{ $optionText }}</span>
                                </div>
                                @endforeach
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Correct Order:</p>
                                @foreach($correctOrder as $idx => $key)
                                @php $optionText = $options[$key] ?? $key; @endphp
                                <div class="flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 p-2 text-sm dark:border-emerald-700 dark:bg-emerald-900/40">
                                    <span class="flex size-5 items-center justify-center rounded-full bg-emerald-200 text-xs font-bold text-emerald-800">{{ $idx + 1 }}</span>
                                    <span class="text-emerald-700 dark:text-emerald-300"><span class="font-semibold">{{ $key }}.</span> {{ $optionText }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        @elseif($qType === 'matching')
                        {{-- Matching display --}}
                        @php
                            $matchOptions = $answer->question->options ?? [];
                            $correctMatches = $answer->question->correct_answers ?? [];
                        @endphp
                        <div class="mt-3 space-y-3">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Matching Results:</p>
                            @foreach($matchOptions as $key => $itemText)
                            @php
                                $memberMatch = $memberMatchesObj[$key] ?? null;
                                $correctMatch = $correctMatches[$key] ?? null;
                                $isCorrect = $memberMatch === $correctMatch;
                            @endphp
                            <div class="flex items-start gap-3 rounded-lg border p-3 text-sm
                                {{ $isCorrect ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/40' : 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/30' }}">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="flex size-5 items-center justify-center rounded-full bg-zinc-200 text-xs font-bold text-zinc-700 dark:bg-zinc-600 dark:text-zinc-200">{{ $key }}</span>
                                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $itemText }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 pl-7 text-xs">
                                        <span class="text-zinc-500 dark:text-zinc-400">Member:</span>
                                        @if($memberMatch)
                                        <span class="{{ $isCorrect ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">{{ $memberMatch }}</span>
                                        @else
                                        <span class="text-zinc-400 italic">Not matched</span>
                                        @endif
                                        @if(!$isCorrect && $correctMatch)
                                        <span class="text-zinc-400">→</span>
                                        <span class="text-emerald-600 dark:text-emerald-400">Correct: {{ $correctMatch }}</span>
                                        @endif
                                        @if($isCorrect)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">✓</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold {{ $answer->is_correct ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $answer->points_awarded ?? 0 }} / {{ $answer->question->points }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Written Answers --}}
    @if($this->writtenAnswers->count() > 0)
    <form wire:submit="saveMarking">
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Written Answers</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Review and score each written answer</p>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($this->writtenAnswers as $answer)
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">Written</span>
                                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Max {{ $answer->question->points }} {{ Str::plural('point', $answer->question->points) }}</span>
                            </div>
                            <p class="mt-2 font-medium text-zinc-900 dark:text-white">{{ $answer->question->question_text }}</p>
                            @if($answer->question->hasImage())
                            <div class="mt-3">
                                <img src="{{ $answer->question->image_url }}" alt="Question image" class="max-h-32 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            </div>
                            @endif
                            {{-- Member's Answer --}}
                            <div class="mt-3">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Member's Answer:</p>
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $answer->answer_text ?: '(No answer provided)' }}</p>
                                </div>
                            </div>
                            
                            {{-- Expected Answer (Model Answer) --}}
                            @if($answer->question->correct_answer)
                            <div class="mt-3">
                                <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400 mb-1">Expected Answer (Reference):</p>
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/40">
                                    <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ $answer->question->correct_answer }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Score (0-{{ $answer->question->points }})</label>
                            <input type="number" wire:model.live="scores.{{ $answer->id }}" min="0" max="{{ $answer->question->points }}" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Feedback (optional)</label>
                            <input type="text" wire:model="feedback.{{ $answer->id }}" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Feedback for member...">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="border-t border-zinc-200 p-6 dark:border-zinc-700">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Marker Notes (internal only)</label>
                    <textarea wire:model="markerNotes" rows="2" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Optional notes about this marking..."></textarea>
                </div>

                <div class="flex items-center justify-between">
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">
                        @php
                            $totalScore = ($attempt->auto_score ?? 0) + collect($this->scores)->sum();
                            $percentage = $this->totalPossiblePoints > 0 ? ($totalScore / $this->totalPossiblePoints) * 100 : 0;
                            $willPass = $percentage >= $attempt->knowledgeTest->passing_score;
                        @endphp
                        Projected result:
                        <span class="font-semibold {{ $willPass ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format($percentage, 1) }}% — {{ $willPass ? 'PASS' : 'FAIL' }}
                        </span>
                    </div>
                    <button type="submit" class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        Complete Marking
                    </button>
                </div>
            </div>
        </div>
    </form>
    @else
    {{-- No written questions, auto-finalize --}}
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-800 dark:bg-emerald-900/40">
        <div class="flex items-center gap-3">
            <svg class="size-6 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div>
                <p class="font-medium text-emerald-700 dark:text-emerald-300">This test has no written questions</p>
                <p class="text-sm text-emerald-600 dark:text-emerald-400">All answers were auto-marked. The result has been finalized.</p>
            </div>
        </div>
    </div>
    @endif
</div>

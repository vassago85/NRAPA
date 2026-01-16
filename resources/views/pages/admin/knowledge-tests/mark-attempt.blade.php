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
    public function multipleChoiceAnswers()
    {
        return $this->attempt->answers()
            ->whereHas('question', fn ($q) => $q->where('question_type', 'multiple_choice'))
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
            <a href="{{ route('admin.knowledge-tests.marking') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
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
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
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
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 lg:col-span-2">
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

    {{-- Multiple Choice Answers --}}
    @if($this->multipleChoiceAnswers->count() > 0)
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Multiple Choice Answers</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Auto-marked based on correct answers</p>
        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach($this->multipleChoiceAnswers as $answer)
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            @if($answer->is_correct)
                            <svg class="size-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            @else
                            <svg class="size-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            @endif
                            <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $answer->question->points }} {{ Str::plural('point', $answer->question->points) }}</span>
                        </div>
                        <p class="mt-2 text-zinc-900 dark:text-white">{{ $answer->question->question_text }}</p>
                        <div class="mt-3 flex items-center gap-4 text-sm">
                            <span class="{{ $answer->is_correct ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                <strong>Answer:</strong> {{ $answer->answer_text }}
                            </span>
                            @if(!$answer->is_correct)
                            <span class="text-green-600 dark:text-green-400">
                                <strong>Correct:</strong> {{ $answer->question->correct_answer }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold {{ $answer->is_correct ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
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
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
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
                            <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $answer->answer_text ?: '(No answer provided)' }}</p>
                            </div>
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
                        <span class="font-semibold {{ $willPass ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format($percentage, 1) }}% — {{ $willPass ? 'PASS' : 'FAIL' }}
                        </span>
                    </div>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                        Complete Marking
                    </button>
                </div>
            </div>
        </div>
    </form>
    @else
    {{-- No written questions, auto-finalize --}}
    <div class="rounded-xl border border-green-200 bg-green-50 p-6 dark:border-green-800 dark:bg-green-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div>
                <p class="font-medium text-green-700 dark:text-green-300">This test has no written questions</p>
                <p class="text-sm text-green-600 dark:text-green-400">All answers were auto-marked. The result has been finalized.</p>
            </div>
        </div>
    </div>
    @endif
</div>

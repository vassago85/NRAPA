<?php

use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestQuestion;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Manage Questions - Admin')] class extends Component {
    public KnowledgeTest $test;

    // Question form
    public ?int $editingQuestionId = null;
    public string $questionType = 'multiple_choice';
    public string $questionText = '';
    public array $options = ['', '', '', ''];
    public string $correctAnswer = '';
    public int $points = 1;

    public function mount(KnowledgeTest $test): void
    {
        $this->test = $test;
    }

    #[Computed]
    public function questions()
    {
        return $this->test->questions()->ordered()->get();
    }

    public function addOption(): void
    {
        $this->options[] = '';
    }

    public function removeOption(int $index): void
    {
        if (count($this->options) > 2) {
            unset($this->options[$index]);
            $this->options = array_values($this->options);
        }
    }

    public function editQuestion(int $id): void
    {
        $question = KnowledgeTestQuestion::findOrFail($id);
        $this->editingQuestionId = $id;
        $this->questionType = $question->question_type;
        $this->questionText = $question->question_text;
        $this->options = $question->options ?? ['', '', '', ''];
        $this->correctAnswer = $question->correct_answer ?? '';
        $this->points = $question->points;
    }

    public function cancelEdit(): void
    {
        $this->editingQuestionId = null;
        $this->resetForm();
    }

    public function saveQuestion(): void
    {
        $this->validate([
            'questionType' => ['required', 'in:multiple_choice,written'],
            'questionText' => ['required', 'string', 'max:2000'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        if ($this->questionType === 'multiple_choice') {
            $this->validate([
                'options' => ['required', 'array', 'min:2'],
                'options.*' => ['required', 'string', 'max:500'],
                'correctAnswer' => ['required', 'string'],
            ]);
        }

        $data = [
            'knowledge_test_id' => $this->test->id,
            'question_type' => $this->questionType,
            'question_text' => $this->questionText,
            'options' => $this->questionType === 'multiple_choice' ? array_values(array_filter($this->options)) : null,
            'correct_answer' => $this->questionType === 'multiple_choice' ? $this->correctAnswer : null,
            'points' => $this->points,
            'is_active' => true,
        ];

        if ($this->editingQuestionId) {
            $question = KnowledgeTestQuestion::findOrFail($this->editingQuestionId);
            $question->update($data);
            session()->flash('success', 'Question updated successfully.');
        } else {
            $data['sort_order'] = $this->test->questions()->max('sort_order') + 1;
            KnowledgeTestQuestion::create($data);
            session()->flash('success', 'Question added successfully.');
        }

        $this->cancelEdit();
    }

    public function toggleQuestionActive(int $id): void
    {
        $question = KnowledgeTestQuestion::findOrFail($id);
        $question->update(['is_active' => !$question->is_active]);
    }

    public function deleteQuestion(int $id): void
    {
        $question = KnowledgeTestQuestion::findOrFail($id);

        // Check if any answers exist for this question
        if ($question->answers()->count() > 0) {
            session()->flash('error', 'Cannot delete a question that has answers. Deactivate it instead.');
            return;
        }

        $question->delete();
        session()->flash('success', 'Question deleted successfully.');
    }

    public function moveUp(int $id): void
    {
        $question = KnowledgeTestQuestion::findOrFail($id);
        $previousQuestion = $this->test->questions()
            ->where('sort_order', '<', $question->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previousQuestion) {
            $tempOrder = $question->sort_order;
            $question->update(['sort_order' => $previousQuestion->sort_order]);
            $previousQuestion->update(['sort_order' => $tempOrder]);
        }
    }

    public function moveDown(int $id): void
    {
        $question = KnowledgeTestQuestion::findOrFail($id);
        $nextQuestion = $this->test->questions()
            ->where('sort_order', '>', $question->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($nextQuestion) {
            $tempOrder = $question->sort_order;
            $question->update(['sort_order' => $nextQuestion->sort_order]);
            $nextQuestion->update(['sort_order' => $tempOrder]);
        }
    }

    protected function resetForm(): void
    {
        $this->questionType = 'multiple_choice';
        $this->questionText = '';
        $this->options = ['', '', '', ''];
        $this->correctAnswer = '';
        $this->points = 1;
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.knowledge-tests.index') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Questions: {{ $test->name }}</h1>
                <p class="text-zinc-600 dark:text-zinc-400">{{ $this->questions->count() }} questions • {{ $this->questions->where('is_active', true)->sum('points') }} total points</p>
            </div>
        </div>
        @if($editingQuestionId === null)
        <button wire:click="$set('editingQuestionId', 0)" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Question
        </button>
        @endif
    </div>

    @if(session('success'))
    <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
    </div>
    @endif

    {{-- Question Form --}}
    @if($editingQuestionId !== null)
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
            {{ $editingQuestionId ? 'Edit Question' : 'Add Question' }}
        </h2>

        <form wire:submit="saveQuestion" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Question Type</label>
                    <select wire:model.live="questionType" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="written">Written Answer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Points</label>
                    <input type="number" wire:model="points" min="1" max="100" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Question</label>
                <textarea wire:model="questionText" rows="3" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Enter your question..."></textarea>
                @error('questionText') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            @if($questionType === 'multiple_choice')
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Answer Options</label>
                <div class="mt-2 space-y-2">
                    @foreach($options as $index => $option)
                    <div class="flex items-center gap-2">
                        <input type="radio" wire:model="correctAnswer" value="{{ $option }}" name="correct_answer" class="size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700" {{ empty($option) ? 'disabled' : '' }}>
                        <input type="text" wire:model.live="options.{{ $index }}" class="flex-1 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Option {{ $index + 1 }}">
                        @if(count($options) > 2)
                        <button type="button" wire:click="removeOption({{ $index }})" class="text-red-500 hover:text-red-600">
                            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                        @endif
                    </div>
                    @endforeach
                </div>
                <button type="button" wire:click="addOption" class="mt-2 text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                    + Add Option
                </button>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Select the radio button next to the correct answer</p>
                @error('correctAnswer') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
            @else
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                <p class="text-sm text-amber-700 dark:text-amber-300">
                    <strong>Written answers require manual marking.</strong> The member will provide a free-text response, and an administrator must review and score it.
                </p>
            </div>
            @endif

            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                    {{ $editingQuestionId ? 'Update Question' : 'Add Question' }}
                </button>
                <button type="button" wire:click="cancelEdit" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Questions List --}}
    <div class="space-y-4">
        @forelse($this->questions as $index => $question)
        <div class="rounded-xl border {{ $question->is_active ? 'border-zinc-200 dark:border-zinc-700' : 'border-zinc-100 dark:border-zinc-800 opacity-60' }} bg-white p-6 shadow-sm dark:bg-zinc-800">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="flex flex-col gap-1">
                        <button wire:click="moveUp({{ $question->id }})" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 {{ $index === 0 ? 'opacity-30 cursor-not-allowed' : '' }}" {{ $index === 0 ? 'disabled' : '' }}>
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                            </svg>
                        </button>
                        <button wire:click="moveDown({{ $question->id }})" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 {{ $index === $this->questions->count() - 1 ? 'opacity-30 cursor-not-allowed' : '' }}" {{ $index === $this->questions->count() - 1 ? 'disabled' : '' }}>
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full {{ $question->question_type === 'multiple_choice' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }} px-2 py-0.5 text-xs font-medium">
                                {{ $question->question_type === 'multiple_choice' ? 'Multiple Choice' : 'Written' }}
                            </span>
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $question->points }} {{ Str::plural('point', $question->points) }}</span>
                            @if(!$question->is_active)
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">Inactive</span>
                            @endif
                        </div>
                        <p class="mt-2 text-zinc-900 dark:text-white">{{ $question->question_text }}</p>

                        @if($question->question_type === 'multiple_choice' && $question->options)
                        <div class="mt-3 space-y-1">
                            @foreach($question->options as $option)
                            <div class="flex items-center gap-2 text-sm">
                                @if($option === $question->correct_answer)
                                <svg class="size-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <span class="font-medium text-green-700 dark:text-green-400">{{ $option }}</span>
                                @else
                                <svg class="size-4 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <span class="text-zinc-600 dark:text-zinc-400">{{ $option }}</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="toggleQuestionActive({{ $question->id }})" class="text-sm {{ $question->is_active ? 'text-amber-600 hover:text-amber-700 dark:text-amber-400' : 'text-green-600 hover:text-green-700 dark:text-green-400' }}">
                        {{ $question->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                    <button wire:click="editQuestion({{ $question->id }})" class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                        Edit
                    </button>
                    @if($question->answers()->count() === 0)
                    <button wire:click="deleteQuestion({{ $question->id }})" wire:confirm="Are you sure you want to delete this question?" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                        Delete
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No questions yet</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Add questions to your test to get started.</p>
            <button wire:click="$set('editingQuestionId', 0)" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Question
            </button>
        </div>
        @endforelse
    </div>
</div>

<?php

use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestQuestion;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Title('Manage Questions - Admin')] class extends Component {
    use WithFileUploads;

    public KnowledgeTest $test;

    // Question form
    public ?int $editingQuestionId = null;
    public string $questionType = 'multiple_choice';
    public string $questionText = '';
    public $questionImage = null;
    public ?string $existingImagePath = null;
    public bool $removeImage = false;
    public array $options = ['', '', '', ''];
    public string $correctAnswer = '';
    public array $correctAnswers = []; // For multi-select and priority order
    public array $matchingPairs = []; // For matching questions [{item: '', answer: ''}, ...]
    public array $matchingDistractors = []; // Extra wrong answers for matching questions
    public int $points = 1;

    // JSON Import
    public $jsonImportFile = null;
    public bool $showJsonImportModal = false;

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

    public function addMatchingPair(): void
    {
        $this->matchingPairs[] = ['item' => '', 'answer' => ''];
    }

    public function removeMatchingPair(int $index): void
    {
        if (count($this->matchingPairs) > 2) {
            unset($this->matchingPairs[$index]);
            $this->matchingPairs = array_values($this->matchingPairs);
            // Update points to match number of pairs
            $this->points = count($this->matchingPairs);
        }
    }

    public function addMatchingDistractor(): void
    {
        $this->matchingDistractors[] = '';
    }

    public function removeMatchingDistractor(int $index): void
    {
        unset($this->matchingDistractors[$index]);
        $this->matchingDistractors = array_values($this->matchingDistractors);
    }

    public function editQuestion(int $id): void
    {
        $question = KnowledgeTestQuestion::findOrFail($id);
        $this->editingQuestionId = $id;
        $this->questionType = $question->question_type;
        $this->questionText = $question->question_text;
        $this->existingImagePath = $question->image_path;
        $this->questionImage = null;
        $this->removeImage = false;
        
        // Handle matching questions separately
        if ($question->question_type === 'matching') {
            $options = $question->options ?? [];
            $correctAnswers = $question->correct_answers ?? [];
            $this->matchingPairs = [];
            foreach ($options as $key => $item) {
                if ($key !== '_distractors') {
                    $this->matchingPairs[] = [
                        'item' => $item,
                        'answer' => $correctAnswers[$key] ?? '',
                    ];
                }
            }
            if (empty($this->matchingPairs)) {
                $this->matchingPairs = [
                    ['item' => '', 'answer' => ''],
                    ['item' => '', 'answer' => ''],
                ];
            }
            // Load distractors
            $this->matchingDistractors = $correctAnswers['_distractors'] ?? [];
            $this->options = ['', '', '', ''];
            $this->correctAnswer = '';
            $this->correctAnswers = [];
        } else {
            // Convert letter-keyed options back to flat array for editing
            $options = $question->options ?? [];
            if (!empty($options)) {
                // Check if options use letter keys (associative array)
                $firstKey = array_key_first($options);
                if (is_string($firstKey) && preg_match('/^[A-Z]$/', $firstKey)) {
                    // Letter-keyed format: extract values and build key map
                    $this->options = array_values($options);
                    // Convert letter key to option text for correctAnswer
                    $this->correctAnswer = $options[$question->correct_answer] ?? $question->correct_answer ?? '';
                    // Convert letter keys to option texts for correctAnswers
                    $correctAnswers = $question->correct_answers ?? [];
                    $this->correctAnswers = array_map(fn($key) => $options[$key] ?? $key, $correctAnswers);
                } else {
                    // Numeric-keyed format (legacy): use as-is
                    $this->options = array_values($options);
                    $this->correctAnswer = $question->correct_answer ?? '';
                    $this->correctAnswers = $question->correct_answers ?? [];
                }
            } else {
                $this->options = ['', '', '', ''];
                $this->correctAnswer = '';
                $this->correctAnswers = [];
            }
            $this->matchingPairs = [
                ['item' => '', 'answer' => ''],
                ['item' => '', 'answer' => ''],
            ];
            $this->matchingDistractors = [];
        }

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
            'questionType' => ['required', 'in:multiple_choice,multiple_select,priority_order,matching,written'],
            'questionText' => ['required', 'string', 'max:2000'],
            'questionImage' => ['nullable', 'image', 'max:5120'], // 5MB max
            'points' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        // Validate multiple choice (single answer)
        if ($this->questionType === 'multiple_choice') {
            $this->validate([
                'options' => ['required', 'array', 'min:2'],
                'options.*' => ['required', 'string', 'max:500'],
                'correctAnswer' => ['required', 'string'],
            ]);
        }

        // Validate multiple select (multiple correct answers)
        if ($this->questionType === 'multiple_select') {
            $this->validate([
                'options' => ['required', 'array', 'min:2'],
                'options.*' => ['required', 'string', 'max:500'],
                'correctAnswers' => ['required', 'array', 'min:1'],
            ]);
        }

        // Validate priority order (all options, order matters)
        if ($this->questionType === 'priority_order') {
            $this->validate([
                'options' => ['required', 'array', 'min:2'],
                'options.*' => ['required', 'string', 'max:500'],
            ]);
            // For priority order, the correct order is stored in correctAnswers
            // which is set by dragging in the UI
        }

        // Validate matching (pairs of items and answers)
        if ($this->questionType === 'matching') {
            $this->validate([
                'matchingPairs' => ['required', 'array', 'min:2'],
                'matchingPairs.*.item' => ['required', 'string', 'max:500'],
                'matchingPairs.*.answer' => ['required', 'string', 'max:500'],
            ]);
            // Points should equal number of pairs (1 point per correct match)
            $this->points = count($this->matchingPairs);
        }

        // Handle image upload
        $imagePath = $this->existingImagePath;
        
        if ($this->removeImage && $this->existingImagePath) {
            Storage::disk('public')->delete($this->existingImagePath);
            $imagePath = null;
        }
        
        if ($this->questionImage) {
            // Delete old image if exists
            if ($this->existingImagePath) {
                Storage::disk('public')->delete($this->existingImagePath);
            }
            $imagePath = $this->questionImage->store('knowledge-test-images', 'public');
        }

        // Prepare options array with letter keys (A, B, C, D, ...)
        $filteredOptions = array_filter($this->options);
        $letterOptions = [];
        $optionKeyMap = []; // Map option text to letter key
        $letterIndex = 0;
        foreach ($filteredOptions as $option) {
            $letter = chr(65 + $letterIndex); // A, B, C, D, ...
            $letterOptions[$letter] = $option;
            $optionKeyMap[$option] = $letter;
            $letterIndex++;
        }

        // Determine correct answer(s) based on question type
        $correctAnswer = null;
        $correctAnswers = null;
        $optionsToStore = null;

        if ($this->questionType === 'multiple_choice') {
            // Convert option text to letter key
            $correctAnswer = $optionKeyMap[$this->correctAnswer] ?? $this->correctAnswer;
            $optionsToStore = $letterOptions;
        } elseif ($this->questionType === 'multiple_select') {
            // Convert option texts to letter keys
            $correctAnswers = array_map(fn($opt) => $optionKeyMap[$opt] ?? $opt, $this->correctAnswers);
            $optionsToStore = $letterOptions;
        } elseif ($this->questionType === 'priority_order') {
            // For priority order, store the correct order as letter keys
            if (!empty($this->correctAnswers)) {
                $correctAnswers = array_map(fn($opt) => $optionKeyMap[$opt] ?? $opt, $this->correctAnswers);
            } else {
                $correctAnswers = array_keys($letterOptions);
            }
            $optionsToStore = $letterOptions;
        } elseif ($this->questionType === 'matching') {
            // For matching, options = items (left side), correct_answers = matching answers (right side)
            $matchingOptions = [];
            $matchingCorrectAnswers = [];
            foreach ($this->matchingPairs as $index => $pair) {
                $letter = chr(65 + $index); // A, B, C, ...
                $matchingOptions[$letter] = $pair['item'];
                $matchingCorrectAnswers[$letter] = $pair['answer'];
            }
            // Add distractors (extra wrong answers)
            $filteredDistractors = array_filter($this->matchingDistractors, fn($d) => !empty(trim($d)));
            if (!empty($filteredDistractors)) {
                $matchingCorrectAnswers['_distractors'] = array_values($filteredDistractors);
            }
            $optionsToStore = $matchingOptions;
            $correctAnswers = $matchingCorrectAnswers;
        }

        $data = [
            'knowledge_test_id' => $this->test->id,
            'question_type' => $this->questionType,
            'question_text' => $this->questionText,
            'image_path' => $imagePath,
            'options' => $optionsToStore,
            'correct_answer' => $correctAnswer,
            'correct_answers' => $correctAnswers,
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

        // Delete image if exists
        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
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
        $this->editingQuestionId = null;
        $this->questionType = 'multiple_choice';
        $this->questionText = '';
        $this->questionImage = null;
        $this->existingImagePath = null;
        $this->removeImage = false;
        $this->options = ['', '', '', ''];
        $this->correctAnswer = '';
        $this->correctAnswers = [];
        $this->points = 1;
    }

    // Toggle correct answer for multi-select
    public function toggleCorrectAnswer(string $option): void
    {
        if (in_array($option, $this->correctAnswers)) {
            $this->correctAnswers = array_values(array_diff($this->correctAnswers, [$option]));
        } else {
            $this->correctAnswers[] = $option;
        }
    }

    // Update priority order from drag-and-drop
    public function updatePriorityOrder(array $order): void
    {
        $this->correctAnswers = $order;
    }

    // JSON Import/Export Methods
    public function openJsonImportModal(): void
    {
        $this->showJsonImportModal = true;
        $this->jsonImportFile = null;
    }

    public function closeJsonImportModal(): void
    {
        $this->showJsonImportModal = false;
        $this->jsonImportFile = null;
    }

    public function importQuestionsFromJson(): void
    {
        $this->validate([
            'jsonImportFile' => ['required', 'file', 'mimes:json,txt', 'max:5120'],
        ]);

        try {
            $content = file_get_contents($this->jsonImportFile->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                session()->flash('error', 'Invalid JSON file: ' . json_last_error_msg());
                return;
            }

            if (!isset($data['questions']) || !is_array($data['questions'])) {
                session()->flash('error', 'JSON file must contain a "questions" array.');
                return;
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];
            $maxSortOrder = $this->test->questions()->max('sort_order') ?? 0;

            foreach ($data['questions'] as $index => $questionData) {
                try {
                    // Validate required fields
                    if (empty($questionData['question_text']) || empty($questionData['question_type'])) {
                        $skipped++;
                        $errors[] = "Question #{$index}: Missing question_text or question_type";
                        continue;
                    }

                    $questionType = $questionData['question_type'];
                    if (!in_array($questionType, ['multiple_choice', 'multiple_select', 'priority_order', 'written'])) {
                        $skipped++;
                        $errors[] = "Question #{$index}: Invalid question_type (must be 'multiple_choice', 'multiple_select', 'priority_order', or 'written')";
                        continue;
                    }

                    // Validate multiple choice questions
                    if ($questionType === 'multiple_choice') {
                        if (empty($questionData['options']) || !is_array($questionData['options']) || count($questionData['options']) < 2) {
                            $skipped++;
                            $errors[] = "Question #{$index}: Multiple choice questions need at least 2 options";
                            continue;
                        }
                        if (empty($questionData['correct_answer'])) {
                            $skipped++;
                            $errors[] = "Question #{$index}: Missing correct_answer";
                            continue;
                        }
                    }

                    // Validate multiple select questions
                    if ($questionType === 'multiple_select') {
                        if (empty($questionData['options']) || !is_array($questionData['options']) || count($questionData['options']) < 2) {
                            $skipped++;
                            $errors[] = "Question #{$index}: Multiple select questions need at least 2 options";
                            continue;
                        }
                        if (empty($questionData['correct_answers']) || !is_array($questionData['correct_answers'])) {
                            $skipped++;
                            $errors[] = "Question #{$index}: Missing correct_answers array";
                            continue;
                        }
                    }

                    // Validate priority order questions
                    if ($questionType === 'priority_order') {
                        if (empty($questionData['options']) || !is_array($questionData['options']) || count($questionData['options']) < 2) {
                            $skipped++;
                            $errors[] = "Question #{$index}: Priority order questions need at least 2 options";
                            continue;
                        }
                    }

                    // Determine options and correct answers based on type
                    $hasOptions = in_array($questionType, ['multiple_choice', 'multiple_select', 'priority_order']);
                    $options = $hasOptions ? $questionData['options'] : null;
                    $correctAnswer = $questionType === 'multiple_choice' ? $questionData['correct_answer'] : null;
                    $correctAnswers = in_array($questionType, ['multiple_select', 'priority_order']) 
                        ? ($questionData['correct_answers'] ?? $questionData['options']) 
                        : null;

                    // Create question
                    $maxSortOrder++;
                    KnowledgeTestQuestion::create([
                        'knowledge_test_id' => $this->test->id,
                        'question_type' => $questionType,
                        'question_text' => $questionData['question_text'],
                        'options' => $options,
                        'correct_answer' => $correctAnswer,
                        'correct_answers' => $correctAnswers,
                        'points' => $questionData['points'] ?? 1,
                        'sort_order' => $questionData['sort_order'] ?? $maxSortOrder,
                        'is_active' => $questionData['is_active'] ?? true,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Question #{$index}: " . $e->getMessage();
                }
            }

            $message = "Imported {$imported} question(s)";
            if ($skipped > 0) {
                $message .= ", skipped {$skipped}";
            }
            if (!empty($errors) && count($errors) <= 5) {
                $message .= ". Errors: " . implode('; ', array_slice($errors, 0, 5));
            }

            session()->flash('success', $message);
            $this->closeJsonImportModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function exportQuestionsToJson()
    {
        $questions = $this->test->questions()->ordered()->get();

        $data = [
            'export_date' => now()->toIso8601String(),
            'version' => '1.1',
            'test_name' => $this->test->name,
            'questions' => $questions->map(function ($question) {
                return [
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'options' => $question->options,
                    'correct_answer' => $question->correct_answer,
                    'correct_answers' => $question->correct_answers,
                    'points' => $question->points,
                    'sort_order' => $question->sort_order,
                    'is_active' => $question->is_active,
                ];
            })->toArray(),
        ];

        $filename = 'knowledge-test-questions-' . \Illuminate\Support\Str::slug($this->test->name) . '-' . now()->format('Y-m-d-His') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
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
        <div class="flex gap-2">
            <button wire:click="openJsonImportModal" class="inline-flex items-center gap-2 rounded-lg border border-blue-300 bg-white px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-600 dark:bg-zinc-700 dark:text-blue-400 dark:hover:bg-zinc-600">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                </svg>
                Import JSON
            </button>
            <button wire:click="exportQuestionsToJson" class="inline-flex items-center gap-2 rounded-lg border border-green-300 bg-white px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50 dark:border-green-600 dark:bg-zinc-700 dark:text-green-400 dark:hover:bg-zinc-600">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export JSON
            </button>
            <button wire:click="$set('editingQuestionId', 0)" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Question
            </button>
        </div>
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
    <div id="question-form" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
            {{ $editingQuestionId ? 'Edit Question' : 'Add Question' }}
        </h2>

        <form wire:submit="saveQuestion" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Question Type</label>
                    <select wire:model.live="questionType" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        <option value="multiple_choice">Multiple Choice (Single Answer)</option>
                        <option value="multiple_select">Multiple Select (Multiple Answers)</option>
                        <option value="priority_order">Priority Order (Drag to Order)</option>
                        <option value="matching">Matching (Drag to Match Pairs)</option>
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

            {{-- Image Upload --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Question Image (Optional)</label>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Add an image to accompany the question. Max 5MB. Supports JPG, PNG, GIF.</p>
                
                <div class="mt-2">
                    @if($questionImage)
                        {{-- Preview of new upload --}}
                        <div class="relative inline-block">
                            <img src="{{ $questionImage->temporaryUrl() }}" alt="Preview" class="max-h-48 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <button type="button" wire:click="$set('questionImage', null)" class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @elseif($existingImagePath && !$removeImage)
                        {{-- Existing image --}}
                        <div class="relative inline-block">
                            <img src="{{ Storage::url($existingImagePath) }}" alt="Current image" class="max-h-48 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <button type="button" wire:click="$set('removeImage', true)" class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @else
                        {{-- Upload input --}}
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-zinc-300 border-dashed rounded-lg cursor-pointer bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-2 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">PNG, JPG, GIF up to 5MB</p>
                                </div>
                                <input type="file" wire:model="questionImage" class="hidden" accept="image/*">
                            </label>
                        </div>
                        <div wire:loading wire:target="questionImage" class="mt-2 text-sm text-zinc-500">Uploading...</div>
                    @endif
                </div>
                @error('questionImage') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
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
            @elseif($questionType === 'multiple_select')
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Answer Options (Select All Correct Answers)</label>
                <div class="mt-1 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                    <p class="text-xs text-blue-700 dark:text-blue-300">Check all options that are correct. Members will need to select all correct answers to get full points.</p>
                </div>
                <div class="mt-2 space-y-2">
                    @foreach($options as $index => $option)
                    <div class="flex items-center gap-2">
                        <input type="checkbox" 
                            wire:click="toggleCorrectAnswer('{{ $option }}')" 
                            {{ in_array($option, $correctAnswers) ? 'checked' : '' }}
                            class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700" 
                            {{ empty($option) ? 'disabled' : '' }}>
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
                @if(count($correctAnswers) > 0)
                <p class="mt-2 text-xs text-green-600 dark:text-green-400">{{ count($correctAnswers) }} correct answer(s) selected</p>
                @endif
                @error('correctAnswers') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
            @elseif($questionType === 'priority_order')
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Answer Options (Drag to Set Correct Order)</label>
                <div class="mt-1 rounded-lg border border-purple-200 bg-purple-50 p-3 dark:border-purple-800 dark:bg-purple-900/20">
                    <p class="text-xs text-purple-700 dark:text-purple-300">Drag items to set the correct order. Members will need to arrange these in the correct sequence.</p>
                </div>
                <div class="mt-2 space-y-2">
                    @foreach($options as $index => $option)
                    <div class="flex items-center gap-2">
                        <span class="flex size-6 items-center justify-center rounded bg-purple-100 text-xs font-bold text-purple-700 dark:bg-purple-900 dark:text-purple-300">{{ $index + 1 }}</span>
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
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">The order shown here (1, 2, 3...) is the correct order members must match.</p>
            </div>
            @elseif($questionType === 'matching')
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Matching Pairs</label>
                <div class="mt-1 rounded-lg border border-orange-200 bg-orange-50 p-3 dark:border-orange-800 dark:bg-orange-900/20">
                    <p class="text-xs text-orange-700 dark:text-orange-300">Enter items on the left and their matching answers on the right. The pairs are set in correct order here - answers will be shuffled when members take the test.</p>
                </div>
                <div class="mt-3 space-y-2">
                    <div class="grid grid-cols-2 gap-4 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                        <div>Items (Left side - shown in order)</div>
                        <div>Correct Matches (Right side - shuffled)</div>
                    </div>
                    @foreach($matchingPairs as $index => $pair)
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-2">
                            <span class="flex size-6 items-center justify-center rounded bg-orange-100 text-xs font-bold text-orange-700 dark:bg-orange-900 dark:text-orange-300">{{ chr(65 + $index) }}</span>
                            <input type="text" wire:model.live="matchingPairs.{{ $index }}.item" class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Item {{ chr(65 + $index) }}">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" wire:model.live="matchingPairs.{{ $index }}.answer" class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Matching answer for {{ chr(65 + $index) }}">
                            @if(count($matchingPairs) > 2)
                            <button type="button" wire:click="removeMatchingPair({{ $index }})" class="text-red-500 hover:text-red-600">
                                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="button" wire:click="addMatchingPair" class="mt-2 text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                    + Add Pair
                </button>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Points will be automatically set to the number of pairs (1 point per correct match).</p>

                {{-- Distractor Answers --}}
                <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Extra Wrong Answers (Optional)</label>
                    <div class="mt-1 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                        <p class="text-xs text-red-700 dark:text-red-300">Add extra answers that don't match any item. This makes the question harder by giving members more choices than correct matches.</p>
                    </div>
                    <div class="mt-3 space-y-2">
                        @foreach($matchingDistractors as $index => $distractor)
                        <div class="flex items-center gap-2">
                            <span class="flex size-6 items-center justify-center rounded bg-red-100 text-xs font-bold text-red-700 dark:bg-red-900 dark:text-red-300">✗</span>
                            <input type="text" wire:model.live="matchingDistractors.{{ $index }}" class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Wrong answer {{ $index + 1 }}">
                            <button type="button" wire:click="removeMatchingDistractor({{ $index }})" class="text-red-500 hover:text-red-600">
                                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" wire:click="addMatchingDistractor" class="mt-2 text-sm text-red-600 hover:text-red-700 dark:text-red-400">
                        + Add Wrong Answer
                    </button>
                </div>
            </div>
            @elseif($questionType === 'written')
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
                            @php
                                $typeColors = [
                                    'multiple_choice' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'multiple_select' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'priority_order' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                    'written' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                                ];
                                $typeLabels = [
                                    'multiple_choice' => 'Multiple Choice',
                                    'multiple_select' => 'Multiple Select',
                                    'priority_order' => 'Priority Order',
                                    'written' => 'Written',
                                ];
                            @endphp
                            <span class="inline-flex items-center rounded-full {{ $typeColors[$question->question_type] ?? 'bg-zinc-100 text-zinc-800' }} px-2 py-0.5 text-xs font-medium">
                                {{ $typeLabels[$question->question_type] ?? $question->question_type }}
                            </span>
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $question->points }} {{ Str::plural('point', $question->points) }}</span>
                            @if($question->hasImage())
                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                                Image
                            </span>
                            @endif
                            @if(!$question->is_active)
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">Inactive</span>
                            @endif
                        </div>
                        <p class="mt-2 text-zinc-900 dark:text-white">{{ $question->question_text }}</p>

                        @if($question->hasImage())
                        <div class="mt-3">
                            <img src="{{ $question->image_url }}" alt="Question image" class="max-h-32 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        </div>
                        @endif

                        @if(in_array($question->question_type, ['multiple_choice', 'multiple_select', 'priority_order']) && $question->options)
                        <div class="mt-3 space-y-1">
                            @foreach($question->options as $optionKey => $optionText)
                            <div class="flex items-center gap-2 text-sm">
                                @if($question->question_type === 'multiple_choice')
                                    {{-- Single correct answer - compare key to correct_answer --}}
                                    @if($optionKey === $question->correct_answer)
                                    <svg class="size-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span class="font-medium text-green-700 dark:text-green-400"><span class="font-bold">{{ $optionKey }}.</span> {{ $optionText }}</span>
                                    @else
                                    <svg class="size-4 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span class="text-zinc-600 dark:text-zinc-400"><span class="font-semibold">{{ $optionKey }}.</span> {{ $optionText }}</span>
                                    @endif
                                @elseif($question->question_type === 'multiple_select')
                                    {{-- Multiple correct answers - compare key to correct_answers array --}}
                                    @if(in_array($optionKey, $question->correct_answers ?? []))
                                    <svg class="size-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span class="font-medium text-green-700 dark:text-green-400"><span class="font-bold">{{ $optionKey }}.</span> {{ $optionText }}</span>
                                    @else
                                    <svg class="size-4 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span class="text-zinc-600 dark:text-zinc-400"><span class="font-semibold">{{ $optionKey }}.</span> {{ $optionText }}</span>
                                    @endif
                                @elseif($question->question_type === 'priority_order')
                                    {{-- Ordered options with position numbers --}}
                                    @php $position = array_search($optionKey, $question->correct_answers ?? array_keys($question->options)) + 1; @endphp
                                    <span class="flex size-5 items-center justify-center rounded bg-purple-100 text-xs font-bold text-purple-700 dark:bg-purple-900 dark:text-purple-300">{{ $position }}</span>
                                    <span class="text-zinc-600 dark:text-zinc-400"><span class="font-semibold">{{ $optionKey }}.</span> {{ $optionText }}</span>
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
                    <button wire:click="editQuestion({{ $question->id }})" x-on:click="setTimeout(() => document.getElementById('question-form')?.scrollIntoView({behavior: 'smooth', block: 'start'}), 100)" class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
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

    {{-- JSON Import Modal --}}
    @if($showJsonImportModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('showJsonImportModal') }" x-show="open" x-cloak>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="closeJsonImportModal" class="fixed inset-0 bg-black/50 transition-opacity"></div>
            <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Import Questions from JSON</h2>
                    <button wire:click="closeJsonImportModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>JSON Format:</strong> Your JSON file should contain a "questions" array. Each question should have:
                            <code class="block mt-2 p-2 bg-white dark:bg-zinc-800 rounded text-xs font-mono">question_type (multiple_choice/written), question_text, options (for MC), correct_answer (for MC), points (optional), sort_order (optional), is_active (optional)</code>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Select JSON File</label>
                        <div 
                            x-data="{ 
                                dragging: false,
                                handleDrop(e) {
                                    this.dragging = false;
                                    const files = e.dataTransfer.files;
                                    if (files.length > 0) {
                                        const input = this.$refs.jsonFileInput;
                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(files[0]);
                                        input.files = dataTransfer.files;
                                        input.dispatchEvent(new Event('change', { bubbles: true }));
                                    }
                                }
                            }"
                            x-on:dragover.prevent="dragging = true"
                            x-on:dragleave.prevent="dragging = false"
                            x-on:drop.prevent="handleDrop($event)"
                            :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/20': dragging }"
                            class="flex h-32 w-full cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:hover:bg-zinc-800 transition-colors"
                        >
                            <label class="cursor-pointer text-center">
                                <svg class="mx-auto size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="mt-2 text-sm text-zinc-500">Drop or click to upload JSON file</p>
                                <p class="text-xs text-zinc-400">JSON, TXT up to 5MB</p>
                                <input x-ref="jsonFileInput" type="file" wire:model="jsonImportFile" class="hidden" accept=".json,.txt">
                            </label>
                        </div>
                        @if($jsonImportFile)
                        <div class="mt-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <svg class="size-5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <span class="flex-1 text-sm text-zinc-900 dark:text-white">{{ $jsonImportFile->getClientOriginalName() }}</span>
                            <button type="button" wire:click="$set('jsonImportFile', null)" class="text-red-500 hover:text-red-700">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        @endif
                        @error('jsonImportFile') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="importQuestionsFromJson" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Import Questions
                        </button>
                        <button wire:click="closeJsonImportModal" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

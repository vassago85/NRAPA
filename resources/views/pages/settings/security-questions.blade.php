<?php

use App\Models\UserSecurityQuestion;
use Livewire\Component;

new class extends Component {
    public array $questions = [];
    public array $answers = [];
    public bool $isEditing = false;

    public function mount(): void
    {
        $existingQuestions = auth()->user()->securityQuestions;
        
        if ($existingQuestions->count() > 0) {
            foreach ($existingQuestions as $q) {
                $this->questions[] = $q->question;
                $this->answers[] = ''; // Don't show existing answers
            }
        } else {
            // Initialize empty questions
            for ($i = 0; $i < UserSecurityQuestion::REQUIRED_QUESTIONS; $i++) {
                $this->questions[] = '';
                $this->answers[] = '';
            }
        }
    }

    public function startEditing(): void
    {
        $this->isEditing = true;
        // Reset answers when editing
        $this->answers = array_fill(0, count($this->questions), '');
    }

    public function save(): void
    {
        // Validate that all questions are selected and answered
        $rules = [];
        $messages = [];
        
        for ($i = 0; $i < UserSecurityQuestion::REQUIRED_QUESTIONS; $i++) {
            $rules["questions.{$i}"] = 'required|string';
            $rules["answers.{$i}"] = 'required|string|min:2';
            $messages["questions.{$i}.required"] = 'Please select security question ' . ($i + 1);
            $messages["answers.{$i}.required"] = 'Please provide an answer for question ' . ($i + 1);
            $messages["answers.{$i}.min"] = 'Answer ' . ($i + 1) . ' must be at least 2 characters';
        }

        $this->validate($rules, $messages);

        // Check for duplicate questions
        $uniqueQuestions = array_unique($this->questions);
        if (count($uniqueQuestions) !== count($this->questions)) {
            $this->addError('questions', 'Each security question must be unique.');
            return;
        }

        // Delete existing questions and create new ones
        auth()->user()->securityQuestions()->delete();

        for ($i = 0; $i < UserSecurityQuestion::REQUIRED_QUESTIONS; $i++) {
            $question = auth()->user()->securityQuestions()->create([
                'question' => $this->questions[$i],
                'answer_hash' => '', // Will be set below
            ]);
            $question->setAnswer($this->answers[$i]);
        }

        $this->isEditing = false;
        $this->answers = array_fill(0, count($this->questions), ''); // Clear answers from memory
        
        session()->flash('success', 'Security questions saved successfully. These will be used to verify your identity if you need to reset your 2FA.');
    }

    public function cancel(): void
    {
        $this->isEditing = false;
        $this->mount(); // Reload from database
    }

    public function with(): array
    {
        return [
            'availableQuestions' => UserSecurityQuestion::getQuestionOptions(),
            'hasExistingQuestions' => auth()->user()->securityQuestions()->count() >= UserSecurityQuestion::REQUIRED_QUESTIONS,
        ];
    }
}; ?>

<section class="w-full">
    <x-slot name="header">
        @include('partials.settings-heading')
    </x-slot>

    <x-settings-layout heading="Security Questions" subheading="Set up questions to verify your identity for 2FA resets">

        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                <p class="text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
            </div>
        @endif

        <div class="space-y-6">
            {{-- Info Banner --}}
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                <div class="flex gap-3">
                    <svg class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <div>
                        <h4 class="font-medium text-blue-800 dark:text-blue-200">Why Security Questions?</h4>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                            If you lose access to your authenticator app and need to reset your 2FA, our support team will use these questions to verify your identity over the phone. Choose questions only you would know the answers to.
                        </p>
                    </div>
                </div>
            </div>

            @if($hasExistingQuestions && !$isEditing)
                {{-- Show current questions (without answers) --}}
                <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                    <div class="flex items-center gap-3 mb-3">
                        <svg class="size-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span class="font-medium text-emerald-800 dark:text-emerald-300">Security questions are set up</span>
                    </div>
                    <p class="text-sm text-emerald-700 dark:text-emerald-300 mb-4">
                        Your security questions are configured. For security reasons, we don't display your answers.
                    </p>
                    
                    <div class="space-y-2 mb-4">
                        @foreach($questions as $index => $question)
                            <div class="flex items-center gap-2 text-sm text-emerald-700 dark:text-emerald-300">
                                <span class="font-medium">{{ $index + 1 }}.</span>
                                <span>{{ $question }}</span>
                            </div>
                        @endforeach
                    </div>

                    <button wire:click="startEditing" 
                            class="px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white font-medium rounded-lg text-sm transition-colors">
                        Update Security Questions
                    </button>
                </div>
            @else
                {{-- Edit/Create Form --}}
                <form wire:submit="save" class="space-y-6">
                    @error('questions')
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-300 text-sm">
                            {{ $message }}
                        </div>
                    @enderror

                    @for($i = 0; $i < \App\Models\UserSecurityQuestion::REQUIRED_QUESTIONS; $i++)
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-3">Security Question {{ $i + 1 }}</h4>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Select a question</label>
                                    <select wire:model="questions.{{ $i }}"
                                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                        <option value="">Choose a question...</option>
                                        @foreach($availableQuestions as $key => $questionText)
                                            <option value="{{ $questionText }}">{{ $questionText }}</option>
                                        @endforeach
                                    </select>
                                    @error("questions.{$i}") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Your answer</label>
                                    <input type="text" wire:model="answers.{{ $i }}"
                                           placeholder="Enter your answer..."
                                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    @error("answers.{$i}") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Answers are case-insensitive and extra spaces are ignored.</p>
                                </div>
                            </div>
                        </div>
                    @endfor

                    <div class="flex items-center gap-3">
                        <button type="submit"
                                class="px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white font-medium rounded-lg text-sm transition-colors">
                            Save Security Questions
                        </button>

                        @if($isEditing)
                            <button type="button" wire:click="cancel"
                                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 font-medium rounded-lg text-sm transition-colors">
                                Cancel
                            </button>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    </x-settings-layout>
</section>

<?php

use Livewire\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    <x-slot name="header">
        @include('partials.settings-heading')
    </x-slot>

    <x-settings-layout :heading="__('Appearance')" :subheading="__('Choose your preferred theme')">
        <div class="space-y-4">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Select how you want the application to appear. You can choose between light mode, dark mode, or follow your system settings.
            </p>
            
            <div x-data="{ 
                     appearance: localStorage.getItem('theme') || 'system',
                     setTheme(theme) {
                         this.appearance = theme;
                         localStorage.setItem('theme', theme);
                         this.applyTheme();
                     },
                     applyTheme() {
                         if (this.appearance === 'dark' || (this.appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                             document.documentElement.classList.add('dark');
                         } else {
                             document.documentElement.classList.remove('dark');
                         }
                     }
                 }" 
                 x-init="applyTheme(); window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => { if(appearance === 'system') applyTheme(); })">
                
                <div class="grid grid-cols-3 gap-3">
                    {{-- Light Mode --}}
                    <button type="button" @click="setTheme('light')"
                            :class="appearance === 'light' ? 'ring-2 ring-emerald-500 border-emerald-500' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'"
                            class="flex flex-col items-center p-4 rounded-xl border bg-white dark:bg-zinc-800 transition-all">
                        <div class="w-full h-20 rounded-lg bg-zinc-100 border border-zinc-200 mb-3 flex items-center justify-center">
                            <svg class="w-8 h-8 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">Light</span>
                        <div x-show="appearance === 'light'" class="mt-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </button>

                    {{-- Dark Mode --}}
                    <button type="button" @click="setTheme('dark')"
                            :class="appearance === 'dark' ? 'ring-2 ring-emerald-500 border-emerald-500' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'"
                            class="flex flex-col items-center p-4 rounded-xl border bg-white dark:bg-zinc-800 transition-all">
                        <div class="w-full h-20 rounded-lg bg-zinc-800 border border-zinc-700 mb-3 flex items-center justify-center">
                            <svg class="w-8 h-8 text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">Dark</span>
                        <div x-show="appearance === 'dark'" class="mt-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </button>

                    {{-- System --}}
                    <button type="button" @click="setTheme('system')"
                            :class="appearance === 'system' ? 'ring-2 ring-emerald-500 border-emerald-500' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'"
                            class="flex flex-col items-center p-4 rounded-xl border bg-white dark:bg-zinc-800 transition-all">
                        <div class="w-full h-20 rounded-lg bg-gradient-to-r from-zinc-100 to-zinc-800 border border-zinc-300 mb-3 flex items-center justify-center">
                            <svg class="w-8 h-8 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">System</span>
                        <div x-show="appearance === 'system'" class="mt-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </button>
                </div>

                <p class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">
                    <span x-show="appearance === 'light'">Light mode is always on.</span>
                    <span x-show="appearance === 'dark'">Dark mode is always on.</span>
                    <span x-show="appearance === 'system'">Automatically matches your system's appearance settings.</span>
                </p>
            </div>
        </div>
    </x-settings-layout>
</section>

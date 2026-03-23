<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';
    public bool $showModal = false;

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mt-10 pt-6 border-t border-zinc-200 dark:border-zinc-700">
    <h3 class="text-lg font-semibold text-red-600 dark:text-red-400">{{ __('Danger Zone') }}</h3>
    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">{{ __('Permanently delete your account and all associated data from NRAPA.') }}</p>

    <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
        <div class="flex items-start gap-3">
            <svg class="size-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <div class="text-sm text-red-700 dark:text-red-300">
                <p class="font-medium">{{ __('The following will be permanently deleted:') }}</p>
                <ul class="mt-2 space-y-1 list-disc list-inside text-red-600 dark:text-red-400">
                    <li>{{ __('Your membership and membership certificates') }}</li>
                    <li>{{ __('All uploaded documents (ID, proof of address, competency certificates)') }}</li>
                    <li>{{ __('Endorsement requests and issued endorsement letters') }}</li>
                    <li>{{ __('Shooting activity records') }}</li>
                    <li>{{ __('All files stored on our servers') }}</li>
                </ul>
                <p class="mt-2 font-medium">{{ __('This action is irreversible and cannot be undone.') }}</p>
            </div>
        </div>
    </div>

    <button type="button" wire:click="$set('showModal', true)"
            class="mt-4 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
        {{ __('Delete Account') }}
    </button>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <form wire:submit="deleteUser" class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Are you sure?') }}</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('All your records — membership, certificates, documents, endorsements, and activities — will be permanently deleted from the system. This cannot be undone.') }}
                            </p>
                        </div>

                        <div>
                            <label for="delete_password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Enter your password to confirm') }}</label>
                            <input wire:model="password" type="password" id="delete_password"
                                   class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-red-500">
                            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" wire:click="$set('showModal', false)"
                                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
                                {{ __('Permanently Delete Account') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</section>

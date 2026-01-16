<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';
    public bool $showModal = false;

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Delete account') }}</h3>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <button type="button" wire:click="$set('showModal', true)" data-test="delete-user-button"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
        {{ __('Delete account') }}
    </button>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-lg p-6">
                    <form wire:submit="deleteUser" class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Are you sure you want to delete your account?') }}</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                            </p>
                        </div>

                        <div>
                            <label for="delete_password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Password') }}</label>
                            <input wire:model="password" type="password" id="delete_password"
                                   class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" wire:click="$set('showModal', false)"
                                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" data-test="confirm-delete-user-button"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
                                {{ __('Delete account') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</section>

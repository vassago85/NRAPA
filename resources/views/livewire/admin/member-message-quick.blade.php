<div>
    @if($open)
        <div class="fixed inset-0 z-[60] overflow-y-auto" wire:key="quick-message-modal-{{ $userId }}">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="close" class="fixed inset-0 bg-black/50"></div>
                <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-zinc-800">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Send Message</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                To {{ $userName ?: '—' }}
                                @if($userEmail)
                                    &lt;{{ $userEmail }}&gt;
                                @else
                                    <span class="text-amber-600 dark:text-amber-400">(no email on file)</span>
                                @endif
                            </p>
                        </div>
                        <button type="button" wire:click="close" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form wire:submit="send" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Subject <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="subject" maxlength="255" autofocus
                                placeholder="e.g. Please upload your ID document"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Message <span class="text-red-500">*</span></label>
                            <textarea wire:model="body" rows="6" maxlength="5000"
                                placeholder="The member will see this in their inbox and also receive a copy by email."
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                            @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                @if($userEmail)
                                    The member will see this in their inbox and get an email at <strong>{{ $userEmail }}</strong>. Plain text only; line breaks are preserved.
                                @else
                                    The member will see this in their inbox. Plain text only; line breaks are preserved.
                                @endif
                            </p>
                        </div>
                        <div class="flex justify-end gap-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                            <button type="button" wire:click="close"
                                class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-200 dark:border-zinc-600 transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

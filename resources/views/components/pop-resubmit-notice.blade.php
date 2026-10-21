@props(['membership'])

@if($membership->awaitsPopResubmit())
    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-950/30">
        <p class="text-sm font-medium text-red-800 dark:text-red-200">Please upload your proof of payment again</p>
        @if($membership->pop_resubmit_message)
            <p class="mt-1 whitespace-pre-line text-sm text-red-700 dark:text-red-300">{{ $membership->pop_resubmit_message }}</p>
        @endif
        <p class="mt-2 text-xs text-red-600 dark:text-red-400">The file you uploaded earlier stays on record until you send a replacement.</p>
    </div>
@endif

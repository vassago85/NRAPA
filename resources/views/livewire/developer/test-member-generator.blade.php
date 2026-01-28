<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
    <div class="flex items-center gap-3 mb-6">
        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Generate Test Members</h3>
    </div>

    <form wire:submit="generate" class="space-y-6">
        {{-- Stage Selection --}}
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                Member Stage
            </label>
            <select wire:model="stage" 
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-4 py-2 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                @foreach($stages as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                @if($stage === 'new')
                    Just a registered user, no membership.
                @elseif($stage === 'applied')
                    Membership application submitted, pending approval.
                @elseif($stage === 'approved')
                    Membership approved but not yet activated, no documents.
                @elseif($stage === 'active')
                    Active membership with verified ID and proof of address.
                @elseif($stage === 'dedicated')
                    Active membership with dedicated status, knowledge test passed, activities approved.
                @elseif($stage === 'full')
                    Fully qualified member with all requirements met. Can generate certificates and endorsements.
                @endif
            </p>
        </div>

        {{-- Count --}}
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                Number of Members to Generate
            </label>
            <input type="number" 
                   wire:model="count" 
                   min="1" 
                   max="10"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-4 py-2 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Generate 1-10 test members at once.</p>
        </div>

        {{-- Additional Options (only for 'full' stage) --}}
        @if($stage === 'full')
        <div class="space-y-3 p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Additional Options:</p>
            
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" 
                       wire:model="withCertificates"
                       class="w-4 h-4 text-emerald-600 border-zinc-300 rounded focus:ring-emerald-500">
                <span class="text-sm text-zinc-700 dark:text-zinc-300">Generate Certificates</span>
            </label>
            
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" 
                       wire:model="withFirearms"
                       class="w-4 h-4 text-emerald-600 border-zinc-300 rounded focus:ring-emerald-500">
                <span class="text-sm text-zinc-700 dark:text-zinc-300">Add Firearms to Virtual Safe</span>
            </label>
            
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" 
                       wire:model="withEndorsements"
                       class="w-4 h-4 text-emerald-600 border-zinc-300 rounded focus:ring-emerald-500">
                <span class="text-sm text-zinc-700 dark:text-zinc-300">Create Endorsement Requests</span>
            </label>
        </div>
        @endif

        {{-- Submit Button --}}
        <button type="submit" 
                class="w-full rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-6 py-3 transition-colors flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Generate Test Member(s)
        </button>
    </form>

    {{-- Info Box --}}
    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-800 dark:text-blue-200">
                <p class="font-medium mb-1">Test Member Credentials:</p>
                <p class="text-xs">Email: <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">testmember@nrapa.test</code> (or testmember2, testmember3, etc.)</p>
                <p class="text-xs">Password: <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">TestMember2026!</code></p>
            </div>
        </div>
    </div>
</div>

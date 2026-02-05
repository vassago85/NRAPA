<?php

use App\Models\Membership;
use App\Models\SystemSetting;
use App\Mail\PaymentInstructions;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

new class extends Component {
    public Membership $membership;
    public bool $emailSent = false;

    public function mount(Membership $membership): void
    {
        // Ensure user owns this membership
        if ($membership->user_id !== auth()->id()) {
            abort(403);
        }

        $this->membership = $membership;
    }

    public function with(): array
    {
        $bankAccount = SystemSetting::getBankAccount();
        $user = auth()->user();

        // Generate payment reference
        $prefix = $bankAccount['reference_prefix'] ?: 'NRAPA';
        $surname = strtoupper(explode(' ', $user->name)[count(explode(' ', $user->name)) - 1] ?? 'MEMBER');
        $idLastFour = substr($user->id_number ?? $user->id, -4);
        $reference = "{$prefix}-{$surname}-{$idLastFour}";

        return [
            'bankAccount' => $bankAccount,
            'reference' => $reference,
            'membershipType' => $this->membership->type,
        ];
    }

    public function resendEmail(): void
    {
        $user = auth()->user();
        $bankAccount = SystemSetting::getBankAccount();
        $prefix = $bankAccount['reference_prefix'] ?: 'NRAPA';
        $surname = strtoupper(explode(' ', $user->name)[count(explode(' ', $user->name)) - 1] ?? 'MEMBER');
        $idLastFour = substr($user->id_number ?? $user->id, -4);
        $reference = "{$prefix}-{$surname}-{$idLastFour}";

        try {
            Mail::to($user->email)->send(new PaymentInstructions(
                $this->membership,
                $bankAccount,
                $reference
            ));

            $this->emailSent = true;
            session()->flash('success', 'Payment instructions have been sent to your email.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to send email. Please use the details shown on this page.');
        }
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900 py-12 px-4">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <div class="flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg">
                    <svg class="size-9 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">Complete Your Payment</h1>
            <p class="text-zinc-600 dark:text-zinc-400">
                Please make an EFT payment using the details below to activate your membership.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Selected Package Summary -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Selected Membership</h2>
            <div class="flex items-center justify-between p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                <div>
                    <p class="font-semibold text-emerald-800 dark:text-emerald-200">{{ $membershipType->name }}</p>
                    <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ $membershipType->description }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">R{{ number_format($membershipType->price, 2) }}</p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400">
                        @if($membershipType->pricing_model === 'annual')
                            per year
                        @elseif($membershipType->pricing_model === 'once_off')
                            once-off
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Bank Account Details -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Bank Account Details</h2>

            @if(empty($bankAccount['bank_name']) && empty($bankAccount['account_number']))
                <div class="p-4 mb-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <p class="text-amber-700 dark:text-amber-300">
                        <strong>Note:</strong> Bank account details are being configured. Please contact NRAPA administration if you need immediate payment assistance.
                    </p>
                </div>
            @endif

            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-zinc-100 dark:border-zinc-700">
                    <span class="text-zinc-500 dark:text-zinc-400">Bank</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $bankAccount['bank_name'] ?: 'To be confirmed' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-zinc-100 dark:border-zinc-700">
                    <span class="text-zinc-500 dark:text-zinc-400">Account Name</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $bankAccount['account_name'] ?: 'To be confirmed' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-zinc-100 dark:border-zinc-700">
                    <span class="text-zinc-500 dark:text-zinc-400">Account Number</span>
                    <span class="font-mono font-medium text-zinc-900 dark:text-white">{{ $bankAccount['account_number'] ?: 'To be confirmed' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-zinc-100 dark:border-zinc-700">
                    <span class="text-zinc-500 dark:text-zinc-400">Branch Code</span>
                    <span class="font-mono font-medium text-zinc-900 dark:text-white">{{ $bankAccount['branch_code'] ?: 'To be confirmed' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-zinc-100 dark:border-zinc-700">
                    <span class="text-zinc-500 dark:text-zinc-400">Account Type</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $bankAccount['account_type'] ?: 'To be confirmed' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-zinc-100 dark:border-zinc-700">
                    <span class="text-zinc-500 dark:text-zinc-400">Amount</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400">R{{ number_format($membershipType->price, 2) }}</span>
                </div>
                <div class="flex justify-between py-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg px-3 -mx-3">
                    <span class="text-blue-700 dark:text-blue-300 font-medium">Payment Reference</span>
                    <span class="font-mono font-bold text-blue-800 dark:text-blue-200">{{ $reference }}</span>
                </div>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <h3 class="font-semibold text-amber-800 dark:text-amber-200 mb-2">Important</h3>
                    <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-1">
                        <li>• Please use the exact reference shown above when making payment</li>
                        <li>• Allow 1-3 business days for payment verification</li>
                        <li>• Your membership will be activated once payment is confirmed</li>
                        <li>• You will receive an email notification when your membership is active</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button wire:click="resendEmail"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Email Me These Details
            </button>
            <a href="{{ route('dashboard') }}" wire:navigate
               class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600 text-zinc-900 dark:text-white font-medium rounded-lg transition-colors">
                Go to Dashboard
            </a>
        </div>

        <!-- Status -->
        <div class="mt-8 text-center">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Your membership status: 
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">
                    Pending Payment
                </span>
            </p>
        </div>
    </div>
</div>

<?php

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalType;
use App\Enums\ChaseOutcome;
use App\Exceptions\Approvals\ApprovalAlreadyDecided;
use App\Exceptions\Approvals\ApprovalNotAllowed;
use App\Mail\PaymentInstructions;
use App\Models\Approval;
use App\Models\Membership;
use App\Models\SystemSetting;
use App\Queries\ApprovalQueue;
use App\Services\Approvals\ApprovalService;
use App\Services\Approvals\ChaseRunner;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('All Approvals - Admin')] class extends Component {

    #[Computed]
    public function pendingApprovals()
    {
        $queue = app(ApprovalQueue::class);

        return $queue->loadForDisplay($queue->waitingOnAdmin());
    }

    #[Computed]
    public function awaitingPayment()
    {
        $queue = app(ApprovalQueue::class);

        return $queue->loadForDisplay($queue->withChaseSummary($queue->waitingOnMember(ApprovalType::Payment)->with('approvable.membership.type')));
    }

    #[Computed]
    public function stats()
    {
        $queue = app(ApprovalQueue::class);
        $counts = $queue->countsByType();
        $awaiting = $queue->waitingOnMember(ApprovalType::Payment)->count();
        $pending = array_sum($counts);

        return [
            'pending_approvals' => $pending,
            'awaiting_payment' => $awaiting,
            'total' => $pending + $awaiting,
            'counts' => $counts,
        ];
    }

    public function confirmPayment(int $approvalId): void
    {
        $approval = Approval::findOrFail($approvalId);

        try {
            app(ApprovalService::class)->confirmPaymentWithoutProof($approval, auth()->user());
            session()->flash('success', 'Payment confirmed for '.($approval->user?->name ?? 'member').'.');
        } catch (ApprovalAlreadyDecided|ApprovalNotAllowed $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->refreshLists();
    }

    public function approveInline(int $approvalId): void
    {
        $approval = Approval::findOrFail($approvalId);

        try {
            app(ApprovalService::class)->approveInline($approval, auth()->user());
            $approval->refresh();
            session()->flash('success', $approval->awaitsSecondSignature()
                ? 'First signature recorded. A second admin needs to approve.'
                : 'Approved: '.($approval->approvable?->summaryLine() ?? 'item').'.');
        } catch (ApprovalAlreadyDecided|ApprovalNotAllowed $e) {
            session()->flash('error', $e->getMessage());
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first());
        }

        $this->refreshLists();
    }

    public function sendPaymentReminder(int $approvalId): void
    {
        $approval = Approval::with(['user', 'approvable.membership'])->find($approvalId);

        if (! $approval) {
            session()->flash('error', 'That payment is no longer awaiting approval.');
            return;
        }

        $outcome = app(ChaseRunner::class)->chase($approval, auth()->user(), source: 'manual');

        unset($this->awaitingPayment);
        session()->flash($outcome->sent() ? 'success' : 'error', $outcome->describe($approval, $approval->user));
    }

    public function resendPaymentInstructions(int $membershipId): void
    {
        $membership = Membership::with(['user', 'type', 'affiliatedClub'])->whereHas('user')->findOrFail($membershipId);

        if (! $membership->user?->email) {
            session()->flash('error', 'Member has no email address.');
            return;
        }

        try {
            Mail::to($membership->user->email)->send(new PaymentInstructions(
                $membership,
                SystemSetting::getBankAccount(),
                $membership->payment_reference,
            ));
            $membership->recordPaymentEmailSent();
            unset($this->awaitingPayment);
            session()->flash('success', 'Payment instructions sent to '.$membership->user->email);
        } catch (\Exception $e) {
            Log::warning('Failed to resend payment instructions', ['membership_id' => $membership->id, 'error' => $e->getMessage()]);
            session()->flash('error', 'Failed to send email: '.$e->getMessage());
        }
    }

    /**
     * Approve everything waiting on an admin, one audited decision at a time.
     * Items that need a second signature only get this admin's first one.
     */
    public function clearAllApprovals(): void
    {
        $admin = auth()->user();
        $service = app(ApprovalService::class);
        $cleared = 0;
        $skipped = 0;

        app(ApprovalQueue::class)->waitingOnAdmin()->get()->each(function (Approval $approval) use ($service, $admin, &$cleared, &$skipped) {
            try {
                $service->decide($approval, ApprovalDecision::Approve, $admin, 'Bulk approved from the approvals page');
                $cleared++;
            } catch (\Throwable $e) {
                $skipped++;
                Log::info('Bulk approval skipped an item', ['approval_id' => $approval->id, 'reason' => $e->getMessage()]);
            }
        });

        $this->refreshLists();

        session()->flash('success', "Approved {$cleared} item(s)".($skipped ? ", skipped {$skipped} that could not be approved by you" : '').'. Items awaiting payment were not affected.');
    }

    private function refreshLists(): void
    {
        unset($this->pendingApprovals, $this->awaitingPayment, $this->stats);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Approvals</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Everything waiting on an admin, oldest first</p>
    </x-slot>

    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
        <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total outstanding</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['total'] }}</p>
        </div>
        <div class="rounded-2xl shadow-sm border border-nrapa-blue/30 bg-nrapa-blue/5 p-4 dark:border-nrapa-blue/40 dark:bg-nrapa-blue/10">
            <p class="text-sm text-nrapa-blue dark:text-blue-300">Waiting on admin</p>
            <p class="mt-1 text-2xl font-bold text-nrapa-blue dark:text-blue-400">{{ $this->stats['pending_approvals'] }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                @foreach(\App\Enums\ApprovalType::cases() as $type)
                    {{ $this->stats['counts'][$type->value] }} {{ strtolower($type->pluralLabel()) }}@if(! $loop->last), @endif
                @endforeach
            </p>
        </div>
        <div class="rounded-2xl shadow-sm border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
            <p class="text-sm text-amber-700 dark:text-amber-300">Awaiting payment</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['awaiting_payment'] }}</p>
            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">No proof of payment yet</p>
        </div>
        <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 flex items-center justify-center">
            @if($this->stats['pending_approvals'] > 0)
            <button wire:click="clearAllApprovals"
                    wire:confirm="Approve every item waiting on an admin? Each one is recorded as your decision in the audit log. Items awaiting payment are not affected."
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                Approve all
            </button>
            @else
            <p class="text-sm text-zinc-400 dark:text-zinc-500">Nothing waiting</p>
            @endif
        </div>
    </div>

    {{-- Waiting on admin --}}
    <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-800 flex items-center gap-3">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Waiting on admin</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Oldest first</p>
            </div>
            <span class="ml-auto inline-flex items-center rounded-full bg-nrapa-blue/10 px-2.5 py-1 text-xs font-semibold text-nrapa-blue dark:bg-nrapa-blue/20 dark:text-blue-300">
                {{ $this->stats['pending_approvals'] }}
            </span>
            @if($this->stats['pending_approvals'] > 0)
                <a href="{{ route('admin.review.index') }}" wire:navigate class="px-4 py-2 text-sm font-medium rounded-lg bg-nrapa-blue text-white hover:bg-nrapa-blue-dark transition-colors">Start review queue</a>
            @endif
        </div>

        @forelse($this->pendingApprovals as $approval)
            <x-admin.approval-row :approval="$approval" />
        @empty
            <div class="p-12 text-center">
                <h3 class="font-semibold text-zinc-900 dark:text-white">All caught up</h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Nothing is waiting on an admin.</p>
            </div>
        @endforelse
    </div>

    {{-- Awaiting payment --}}
    <div class="rounded-2xl shadow-sm border border-amber-200 bg-white dark:border-amber-800/50 dark:bg-zinc-900">
        <div class="border-b border-amber-200 dark:border-amber-800/50 p-6 bg-amber-50/50 dark:bg-amber-900/10 flex items-center gap-3">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Awaiting payment</h2>
                <p class="text-sm text-amber-600 dark:text-amber-400">Waiting on the member to pay or upload proof of payment</p>
            </div>
            <span class="ml-auto inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                {{ $this->stats['awaiting_payment'] }}
            </span>
        </div>

        @forelse($this->awaitingPayment as $approval)
            @php $payment = $approval->approvable; $membership = $payment->membership; @endphp
            <div wire:key="awaiting-{{ $approval->id }}" class="flex flex-col gap-4 p-6 border-b border-zinc-100 last:border-b-0 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $approval->type->badgeClasses() }}">Payment</span>
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $payment->summaryLine() }}</h3>
                    </div>
                    <div class="flex items-center gap-3 mt-1 flex-wrap text-xs text-zinc-500 dark:text-zinc-400">
                        <span>{{ $approval->user?->email }}</span>
                        @if($ref = $payment->memberReference())
                            <span class="font-mono">Ref: {{ $ref }}</span>
                        @endif
                        <span>Waiting since {{ $approval->waitingSince()?->diffForHumans() }}</span>
                        <span>{{ $approval->chaseSummary() }}</span>
                    </div>
                    @if($membership)
                        <div class="flex items-center gap-2 mt-2 flex-wrap text-xs">
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-medium {{ $membership->payment_email_count > 0 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' }}">
                                Payment email: {{ $membership->payment_email_count > 0 ? 'sent '.$membership->payment_email_count.'×' : 'never sent' }}
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-medium {{ $membership->pop_reminder_count > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' }}">
                                Reminders: {{ $membership->pop_reminder_count > 0 ? 'sent '.$membership->pop_reminder_count.'×' : 'none sent' }}
                            </span>
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button wire:click="confirmPayment({{ $approval->id }})"
                        wire:confirm="Confirm the payment for {{ $approval->user?->name }} has been received? This is recorded as your decision."
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors">
                        Mark paid
                    </button>
                    @if($membership)
                        <button wire:click="resendPaymentInstructions({{ $membership->id }})"
                            wire:confirm="Send banking details and payment reference to {{ $approval->user?->email }}?"
                            class="px-4 py-2 text-sm font-medium rounded-lg border border-blue-300 bg-white text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:bg-zinc-800 dark:text-blue-400 transition-colors">
                            {{ $membership->payment_email_sent_at ? 'Resend payment details' : 'Send payment details' }}
                        </button>
                    @endif
                    @if($approval->user?->canReceiveNonEssentialEmail())
                        <button wire:click="sendPaymentReminder({{ $approval->id }})" data-chase
                            wire:confirm="Chase {{ $approval->user?->name }} now? Anyone chased in the last 24 hours is skipped."
                            class="px-4 py-2 text-sm font-medium rounded-lg border border-amber-300 bg-white text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:bg-zinc-800 dark:text-amber-400 transition-colors">
                            Chase
                        </button>
                    @else
                        <span class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">Cannot chase: email bounced</span>
                    @endif
                    @if($url = $approval->detailUrl())
                        <a href="{{ $url }}" wire:navigate class="px-4 py-2 text-sm font-medium rounded-lg border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 transition-colors">View</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center">
                <h3 class="font-semibold text-zinc-900 dark:text-white">No outstanding payments</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Every member with a payment due has uploaded proof.</p>
            </div>
        @endforelse
    </div>
</div>

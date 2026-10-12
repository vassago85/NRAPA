<?php

namespace App\Models;

use App\Actions\Memberships\ApproveMembership;
use App\Concerns\SyncsApproval;
use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use App\Enums\ApprovalState;
use App\Enums\ApprovalType;
use App\Services\Approvals\PaymentLedger;
use App\Support\Approvals\Checks\NoDisciplinaryHold;
use App\Support\Approvals\EvidenceBlock;
use App\Support\Approvals\PreflightCheck;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Payment extends Model implements Approvable
{
    use SyncsApproval;

    protected $fillable = [
        'uuid', 'user_id', 'membership_id', 'amount', 'currency', 'reference',
        'payment_method', 'proof_of_payment_path', 'submitted_at',
        'status', 'paid_at', 'processed_at', 'failed_at', 'metadata', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'paid_at' => 'datetime',
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payment $payment) {
            if (empty($payment->uuid)) {
                $payment->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class)->withTrashed();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * The reference the member was asked to pay with.
     */
    public function memberReference(): ?string
    {
        return $this->membership?->payment_reference ?? $this->reference;
    }

    public function referenceMatches(): bool
    {
        return PaymentLedger::referenceMatches($this->memberReference());
    }

    /**
     * Renewals and accepted type changes activate as soon as payment is
     * confirmed; a new application still needs its own review.
     */
    public function activatesMembershipOnApproval(): bool
    {
        $membership = $this->membership;

        if (! $membership) {
            return false;
        }

        return $membership->status === 'pending_payment'
            || ($membership->status === 'applied' && $membership->isRenewal());
    }

    // ===== Approvable =====

    public function approvalType(): ApprovalType
    {
        return ApprovalType::Payment;
    }

    public function approvalStateFromModel(): ?ApprovalState
    {
        if ($this->status !== 'pending') {
            return null;
        }

        return $this->proof_of_payment_path ? ApprovalState::WaitingOnAdmin : ApprovalState::WaitingOnMember;
    }

    public function approvalOutcomeFromModel(): ApprovalState
    {
        return match ($this->status) {
            'completed' => ApprovalState::Approved,
            'failed' => ApprovalState::Declined,
            default => ApprovalState::Withdrawn,
        };
    }

    public function approvalSubmittedAt(): ?CarbonInterface
    {
        return $this->submitted_at ?? $this->created_at;
    }

    public function summaryLine(): string
    {
        $membership = $this->membership;
        $kind = match (true) {
            ! $membership => 'payment',
            $membership->status === 'pending_payment' => 'upgrade to '.($membership->type?->name ?? 'new type'),
            $membership->isRenewal() => 'renewal',
            default => 'new membership',
        };

        return ($this->user?->name ?? 'Unknown member').' — '.$kind.', '.$this->formattedAmount();
    }

    public function detailLine(): string
    {
        $parts = [$this->proof_of_payment_path ? 'Proof of payment uploaded' : 'No proof of payment yet'];

        if ($reference = $this->memberReference()) {
            $parts[] = 'reference '.$reference.($this->referenceMatches() ? ' matches' : '');
        }

        return implode(' · ', $parts);
    }

    public function detailWarning(): ?string
    {
        return $this->referenceMatches() ? null : 'Reference does not match the expected pattern';
    }

    public function formattedAmount(): string
    {
        return 'R'.number_format((float) $this->amount, 0, '.', ',');
    }

    public function evidence(): EvidenceBlock
    {
        $membership = $this->membership;
        $mime = $this->proof_of_payment_path ? Str::lower(pathinfo($this->proof_of_payment_path, PATHINFO_EXTENSION)) : null;
        $fileAvailable = $this->proof_of_payment_path
            && $membership?->proof_of_payment_path === $this->proof_of_payment_path;

        return new EvidenceBlock(
            view: 'components.admin.evidence.payment',
            title: 'Proof of payment',
            data: [
                'payment' => $this,
                'membership' => $membership,
                'proofUrl' => $fileAvailable ? route('admin.approvals.proof-of-payment', $membership) : null,
                'isPdf' => $mime === 'pdf',
                'reference' => $this->memberReference(),
                'referenceMatches' => $this->referenceMatches(),
                'pattern' => PaymentLedger::referencePattern(),
            ],
            badge: $this->referenceMatches() ? 'Reference matches' : 'Check reference',
            badgeTone: $this->referenceMatches() ? 'emerald' : 'amber',
        );
    }

    public function preflightChecks(): array
    {
        return [
            $this->proof_of_payment_path
                ? PreflightCheck::pass('proof_uploaded', 'Proof of payment uploaded', 'Proof of payment uploaded '.($this->submitted_at?->format('d M Y') ?? ''))
                : PreflightCheck::fail('proof_uploaded', 'Proof of payment uploaded', 'No proof of payment uploaded'),
            $this->referenceMatches()
                ? PreflightCheck::pass('reference', 'Reference matches pattern', 'Reference '.$this->memberReference().' matches')
                : PreflightCheck::fail('reference', 'Reference matches pattern', 'Reference '.($this->memberReference() ?? 'missing').' does not match'),
            $this->amount > 0
                ? PreflightCheck::pass('amount', 'Amount due known', 'Amount due '.$this->formattedAmount())
                : PreflightCheck::fail('amount', 'Amount due known', 'No amount due recorded'),
            NoDisciplinaryHold::check($this->user),
        ];
    }

    public function canBeApprovedInline(): bool
    {
        return true;
    }

    public function approvalConsequence(): string
    {
        return $this->activatesMembershipOnApproval()
            ? 'Approving confirms the '.$this->formattedAmount().' payment, activates the membership, emails the member, and writes an audit entry against your name.'
            : 'Approving confirms the '.$this->formattedAmount().' payment and sends the application on for review. It writes an audit entry against your name.';
    }

    public function approveFromQueue(User $actor, ?string $note = null): void
    {
        $membership = $this->membership;

        if ($membership && $this->activatesMembershipOnApproval()) {
            app(ApproveMembership::class)->approve($membership, $actor);
        } elseif ($membership && ! $membership->payment_confirmed_at) {
            $membership->update([
                'payment_confirmed_at' => now(),
                'payment_confirmed_by' => $actor->id,
            ]);
        }

        $this->update([
            'status' => 'completed',
            'paid_at' => $membership?->payment_confirmed_at ?? now(),
            'processed_at' => now(),
            'notes' => $note ?: $this->notes,
        ]);
    }

    public function declineFromQueue(User $actor, string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'notes' => $reason,
        ]);

        $this->clearMembershipProof();
    }

    public function requestChangesFromQueue(User $actor, array $items, ?string $note = null): void
    {
        $this->update(['notes' => trim(implode("\n", $items)."\n".$note)]);
        $this->clearMembershipProof();
    }

    public function notifiesMemberOnDecision(ApprovalDecision $decision): bool
    {
        return $decision === ApprovalDecision::Approve && $this->activatesMembershipOnApproval();
    }

    private function clearMembershipProof(): void
    {
        $membership = $this->membership;

        if (! $membership?->proof_of_payment_path) {
            return;
        }

        try {
            Storage::disk(\App\Helpers\StorageHelper::getPrivateDisk())->delete($membership->proof_of_payment_path);
        } catch (\Throwable $e) {
        }

        $membership->update(['proof_of_payment_path' => null]);
    }
}

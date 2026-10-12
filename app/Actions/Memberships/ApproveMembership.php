<?php

namespace App\Actions\Memberships;

use App\Jobs\SyncMembershipToSage;
use App\Mail\MembershipApproved;
use App\Mail\PaymentInstructions;
use App\Mail\PaymentReceived;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestAttempt;
use App\Models\Membership;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CertificateIssueService;
use App\Services\NtfyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Activates a membership application or a paid type change. One
 * implementation shared by the approval queue and the legacy pages.
 */
class ApproveMembership
{
    public function __construct(private CertificateIssueService $certificates) {}

    /**
     * New application or renewal in status "applied".
     */
    public function approveApplication(Membership $membership, User $admin): void
    {
        if ($membership->status !== 'applied') {
            throw new \DomainException('This application has already been processed.');
        }

        if (! $membership->type) {
            throw new \DomainException('Cannot approve: membership type is missing.');
        }

        $membership->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'activated_at' => now(),
            'expires_at' => $membership->calculateRenewalAwareExpiry(),
            'payment_confirmed_at' => $membership->payment_confirmed_at ?? now(),
            'payment_confirmed_by' => $membership->payment_confirmed_by ?? $admin->id,
        ]);

        $membership->retireOtherActiveMemberships();
        $this->ensureMembershipNumber($membership);
        $this->issueDocuments($membership, $admin);
        $this->autoCompleteKnowledgeTests($membership, $admin);
        $this->sendApprovalEmail($membership);

        if ($membership->source !== 'import') {
            $this->sendPaymentInstructions($membership);
        }

        $this->notifyAdmins($membership, $admin, 'Membership Approved', "{$admin->name} approved {$membership->user?->name}'s {$membership->type->name} membership.");

        SyncMembershipToSage::dispatch($membership)->afterCommit();
    }

    /**
     * Paid type change (upgrade/downgrade) in status "pending_payment".
     */
    public function approveChange(Membership $membership, User $admin): void
    {
        if ($membership->status !== 'pending_payment') {
            throw new \DomainException('This change request is not ready for approval.');
        }

        $membership->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'activated_at' => now(),
            'expires_at' => $membership->type->calculateExpiryDate(now()),
            'payment_confirmed_at' => $membership->payment_confirmed_at ?? now(),
            'payment_confirmed_by' => $membership->payment_confirmed_by ?? $admin->id,
        ]);

        $membership->retireOtherActiveMemberships();
        $this->ensureMembershipNumber($membership);
        $this->issueDocuments($membership, $admin);
        $this->autoCompleteKnowledgeTests($membership, $admin);
        $this->purgeProofOfPayment($membership);

        try {
            if ($membership->user?->email) {
                Mail::to($membership->user->email)->send(new PaymentReceived(membership: $membership->load('type', 'user')));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send payment received email', ['membership_id' => $membership->id, 'error' => $e->getMessage()]);
        }

        $this->sendApprovalEmail($membership, force: true);
        $this->notifyAdmins($membership, $admin, 'Membership Upgrade Approved', "{$admin->name} approved {$membership->user?->name}'s upgrade to {$membership->type->name}.");

        SyncMembershipToSage::dispatch($membership)->afterCommit();
    }

    /**
     * Accept a type-change request: set the fee, move to pending_payment and
     * email payment instructions. Activation follows payment confirmation.
     */
    public function acceptChangeRequest(Membership $membership, User $admin, float $amount): void
    {
        if ($membership->status !== 'pending_change') {
            throw new \DomainException('This request is not awaiting an amount.');
        }

        $membership->update([
            'change_amount' => $amount,
            'status' => 'pending_payment',
        ]);

        if (! $membership->payment_reference) {
            $membership->update(['payment_reference' => Membership::generatePaymentReference($membership)]);
        }

        try {
            if ($membership->user?->email) {
                $membership->syncPendingUpgradePaymentAmount();
                $membership->refresh();

                Mail::to($membership->user->email)->send(new PaymentInstructions(
                    membership: $membership->load('type', 'user', 'previousMembership.type'),
                    bankAccount: SystemSetting::getBankAccount(),
                    reference: $membership->payment_reference,
                ));
                $membership->recordPaymentEmailSent();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send change request payment email', ['membership_id' => $membership->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Activate whichever flow applies to the membership's current status.
     */
    public function approve(Membership $membership, User $admin): void
    {
        match ($membership->status) {
            'pending_payment' => $this->approveChange($membership, $admin),
            default => $this->approveApplication($membership, $admin),
        };
    }

    private function ensureMembershipNumber(Membership $membership): void
    {
        if (! $membership->membership_number && $membership->user) {
            $membership->update(['membership_number' => $membership->user->formatted_member_number]);
        }
    }

    private function issueDocuments(Membership $membership, User $admin): void
    {
        $user = $membership->user;
        if (! $user) {
            return;
        }

        try {
            $this->certificates->issueMembershipCertificate($user, $admin, skipChecks: true);
        } catch (\Throwable $e) {
            Log::info('Membership certificate not issued at approval time', ['user_id' => $user->id, 'reason' => $e->getMessage()]);
        }

        try {
            $this->certificates->issueWelcomeLetter($user, $admin);
        } catch (\Throwable $e) {
            $this->fallbackCertificate($membership, $admin, 'welcome-letter', 'Welcome Letter', 'Welcome letter for new members', 'documents.welcome-letter', 14, 'WEL-');
        }

        try {
            $this->certificates->issueMembershipCard($user, $admin);
        } catch (\Throwable $e) {
            $this->fallbackCertificate($membership, $admin, 'membership-card', 'Membership Card', 'NRAPA membership identification card', 'documents.membership-card', 13, 'CARD-', $membership->expires_at);
        }
    }

    private function fallbackCertificate(Membership $membership, User $admin, string $slug, string $name, string $description, string $template, int $sortOrder, string $prefix, $validUntil = null): void
    {
        try {
            $certType = CertificateType::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'template' => $template,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);

            Certificate::create([
                'user_id' => $membership->user_id,
                'membership_id' => $membership->id,
                'certificate_type_id' => $certType->id,
                'certificate_number' => $prefix.strtoupper(substr(md5(uniqid()), 0, 8)),
                'qr_code' => bin2hex(random_bytes(16)),
                'issued_at' => now(),
                'valid_from' => now(),
                'valid_until' => $validUntil,
                'issued_by' => $admin->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to issue fallback certificate on approval', ['membership_id' => $membership->id, 'slug' => $slug, 'error' => $e->getMessage()]);
        }
    }

    private function autoCompleteKnowledgeTests(Membership $membership, User $admin): void
    {
        $user = $membership->user;
        $type = $membership->type;

        if (! $user || $user->hasPassedKnowledgeTest() || ! $type?->requires_knowledge_test) {
            return;
        }

        $required = $type->requiredKnowledgeTests;
        $tests = $required->isNotEmpty() ? $required : KnowledgeTest::active()->limit(1)->get();

        foreach ($tests as $test) {
            $alreadyPassed = KnowledgeTestAttempt::where('user_id', $user->id)
                ->where('knowledge_test_id', $test->id)
                ->where(fn ($q) => $q->where('passed', true)->orWhereNotNull('marked_by'))
                ->exists();

            if ($alreadyPassed) {
                continue;
            }

            $totalPoints = $test->total_points ?? 100;

            KnowledgeTestAttempt::create([
                'user_id' => $user->id,
                'knowledge_test_id' => $test->id,
                'started_at' => now(),
                'submitted_at' => now(),
                'auto_score' => $totalPoints,
                'manual_score' => 0,
                'total_score' => $totalPoints,
                'passed' => true,
                'marked_at' => now(),
                'marked_by' => $admin->id,
                'marker_notes' => 'Auto-completed on membership approval by admin',
            ]);
        }
    }

    private function sendApprovalEmail(Membership $membership, bool $force = false): void
    {
        if (! $membership->user?->email || (! $force && $membership->welcome_email_sent_at)) {
            return;
        }

        try {
            Mail::to($membership->user->email)->send(new MembershipApproved(membership: $membership, cardUrl: route('card')));
            $membership->update(['welcome_email_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Failed to send membership approval email', ['membership_id' => $membership->id, 'error' => $e->getMessage()]);
        }
    }

    private function sendPaymentInstructions(Membership $membership): void
    {
        if (! $membership->user?->email) {
            return;
        }

        try {
            Mail::to($membership->user->email)->send(new PaymentInstructions(
                $membership->load('type', 'user', 'affiliatedClub'),
                SystemSetting::getBankAccount(),
                $membership->payment_reference,
            ));
            $membership->recordPaymentEmailSent();
        } catch (\Throwable $e) {
            Log::warning('Failed to send payment instructions on approval', ['membership_id' => $membership->id, 'error' => $e->getMessage()]);
        }
    }

    private function purgeProofOfPayment(Membership $membership): void
    {
        if (! $membership->proof_of_payment_path) {
            return;
        }

        try {
            Storage::disk(\App\Helpers\StorageHelper::getPrivateDisk())->delete($membership->proof_of_payment_path);
        } catch (\Throwable $e) {
        }

        $membership->update(['proof_of_payment_path' => null]);
    }

    private function notifyAdmins(Membership $membership, User $admin, string $title, string $message): void
    {
        try {
            app(NtfyService::class)->notifyAdmins('new_member', $title, $message);
        } catch (\Throwable $e) {
        }
    }
}

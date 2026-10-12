<?php

use App\Models\Approval;
use App\Models\CalibreRequest;
use App\Models\EndorsementRequest;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\ShootingActivity;
use App\Services\Approvals\ApprovalSync;
use App\Services\Approvals\PaymentLedger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creates Payment rows for memberships waiting on payment, then an approval
 * row for every pending record of the six approvable types. Both are tagged
 * so down() removes only what this migration added.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ledger = app(PaymentLedger::class);
        $sync = app(ApprovalSync::class);
        $paymentsBefore = (int) Payment::max('id');
        $approvalsBefore = (int) Approval::max('id');

        Membership::query()
            ->whereIn('status', ['applied', 'pending_payment', 'pending_change'])
            ->chunkById(200, function ($memberships) use ($ledger, $sync) {
                foreach ($memberships as $membership) {
                    $ledger->syncForMembership($membership);
                    $sync->sync($membership);
                }
            });

        Payment::query()
            ->where('status', 'pending')
            ->chunkById(200, fn ($payments) => $payments->each(fn (Payment $p) => $sync->sync($p)));

        MemberDocument::query()
            ->where('status', 'pending')
            ->chunkById(200, fn ($docs) => $docs->each(fn (MemberDocument $d) => $sync->sync($d)));

        ShootingActivity::query()
            ->where('status', 'pending')
            ->chunkById(200, fn ($activities) => $activities->each(fn (ShootingActivity $a) => $sync->sync($a)));

        EndorsementRequest::query()
            ->whereIn('status', [
                EndorsementRequest::STATUS_SUBMITTED,
                EndorsementRequest::STATUS_UNDER_REVIEW,
                EndorsementRequest::STATUS_PENDING_DOCUMENTS,
            ])
            ->chunkById(200, fn ($requests) => $requests->each(fn (EndorsementRequest $r) => $sync->sync($r)));

        CalibreRequest::query()
            ->where('status', CalibreRequest::STATUS_PENDING)
            ->chunkById(200, fn ($requests) => $requests->each(fn (CalibreRequest $r) => $sync->sync($r)));

        Payment::query()->where('id', '>', $paymentsBefore)->each(function (Payment $payment) {
            $payment->metadata = array_merge($payment->metadata ?? [], ['backfilled' => true]);
            $payment->saveQuietly();
        });

        Approval::query()->where('id', '>', $approvalsBefore)->each(function (Approval $approval) {
            $approval->withMeta(['backfilled' => true])->saveQuietly();
        });
    }

    public function down(): void
    {
        DB::table('approvals')->where('meta->backfilled', true)->delete();
        DB::table('payments')->where('metadata->backfilled', true)->delete();
    }
};

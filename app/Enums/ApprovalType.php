<?php

namespace App\Enums;

use App\Models\CalibreRequest;
use App\Models\EndorsementRequest;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\ShootingActivity;

enum ApprovalType: string
{
    case Payment = 'payment';
    case Document = 'document';
    case Membership = 'membership';
    case Activity = 'activity';
    case Endorsement = 'endorsement';
    case Calibre = 'calibre';

    public function label(): string
    {
        return match ($this) {
            self::Payment => 'Payment',
            self::Document => 'Document',
            self::Membership => 'Membership',
            self::Activity => 'Activity',
            self::Endorsement => 'Endorsement',
            self::Calibre => 'Calibre',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Payment => 'Payments',
            self::Document => 'Documents',
            self::Membership => 'Memberships',
            self::Activity => 'Activities',
            self::Endorsement => 'Endorsements',
            self::Calibre => 'Calibres',
        };
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model&\App\Contracts\Approvable>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Payment => Payment::class,
            self::Document => MemberDocument::class,
            self::Membership => Membership::class,
            self::Activity => ShootingActivity::class,
            self::Endorsement => EndorsementRequest::class,
            self::Calibre => CalibreRequest::class,
        };
    }

    /**
     * Default for new approval rows. Stored per row so it can be changed
     * without a code branch on the type.
     */
    public function requiresSecondApprovalByDefault(): bool
    {
        // Everything is single-signature. Endorsements briefly required two
        // admins, but in practice that strands letters on the queue whenever
        // the second admin isn't around. The flag stays on the model so a
        // future rule could switch it back on per-type without a schema change.
        return false;
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Payment => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            self::Document => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
            self::Activity => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
            self::Membership => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            self::Endorsement => 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-200',
            self::Calibre => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
        };
    }

    public static function fromModel(object $model): ?self
    {
        foreach (self::cases() as $case) {
            if ($model instanceof ($case->modelClass())) {
                return $case;
            }
        }

        return null;
    }
}

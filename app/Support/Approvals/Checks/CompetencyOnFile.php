<?php

namespace App\Support\Approvals\Checks;

use App\Models\MemberDocument;
use App\Models\User;
use App\Support\Approvals\PreflightCheck;

class CompetencyOnFile
{
    public static function check(?User $member): PreflightCheck
    {
        $label = 'Competency certificate on file';

        if (! $member) {
            return PreflightCheck::fail('competency', $label, 'Member not found');
        }

        $document = MemberDocument::query()
            ->where('user_id', $member->id)
            ->where('status', 'verified')
            ->whereHas('documentType', fn ($q) => $q->whereIn('slug', MemberDocument::COMPETENCY_DOCUMENT_SLUGS))
            ->orderByDesc('expires_at')
            ->first();

        if (! $document) {
            return PreflightCheck::fail('competency', $label, 'No verified competency certificate');
        }

        if ($document->expires_at && $document->expires_at->isPast()) {
            return PreflightCheck::fail('competency', $label, 'Competency certificate expired '.$document->expires_at->format('d M Y'));
        }

        return PreflightCheck::pass('competency', $label, $document->expires_at
            ? 'Competency certificate valid to '.$document->expires_at->format('Y')
            : 'Competency certificate on file');
    }
}

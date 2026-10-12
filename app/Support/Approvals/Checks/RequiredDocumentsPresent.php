<?php

namespace App\Support\Approvals\Checks;

use App\Models\MemberDocument;
use App\Models\MembershipType;
use App\Models\User;
use App\Support\Approvals\PreflightCheck;
use Illuminate\Support\Collection;

class RequiredDocumentsPresent
{
    public static function check(?User $member, ?MembershipType $type): PreflightCheck
    {
        $label = 'Required documents present';

        if (! $member || ! $type) {
            return PreflightCheck::fail('required_documents', $label, 'Membership type unknown');
        }

        $checklist = self::checklist($member, $type);
        $required = $checklist->count();
        $missing = $checklist->reject(fn ($row) => $row['present']);

        if ($required === 0) {
            return PreflightCheck::pass('required_documents', $label, 'No documents required for this type');
        }

        if ($missing->isEmpty()) {
            return PreflightCheck::pass('required_documents', $label, "All {$required} required documents on file");
        }

        return PreflightCheck::fail('required_documents', $label, 'Missing: '.$missing->pluck('name')->implode(', '));
    }

    /**
     * @return Collection<int, array{name: string, present: bool, status: ?string}>
     */
    public static function checklist(User $member, MembershipType $type): Collection
    {
        $required = $type->documentTypes()->wherePivot('is_required', true)->get();

        if ($required->isEmpty()) {
            return collect();
        }

        $documents = MemberDocument::query()
            ->where('user_id', $member->id)
            ->whereIn('document_type_id', $required->pluck('id'))
            ->whereIn('status', ['pending', 'verified'])
            ->get()
            ->groupBy('document_type_id');

        return $required->map(function ($docType) use ($documents) {
            $docs = $documents->get($docType->id, collect());
            $status = $docs->contains('status', 'verified') ? 'verified' : ($docs->isNotEmpty() ? 'pending' : null);

            return [
                'name' => $docType->name,
                'present' => $status !== null,
                'status' => $status,
            ];
        })->values();
    }
}

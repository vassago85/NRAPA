<?php

declare(strict_types=1);

namespace App\Support\Members;

use App\Models\DocumentType;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\ShootingActivity;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;

/**
 * The one place member-side compliance is computed.
 *
 * Per the Phase 1 brief:
 *   "App\Support\Members\MemberCompliance::for(Member $member) returns a readonly
 *    DTO: a list of requirements and an overall result. ... Overall result is
 *    computed from the requirements using the rules Paul confirmed in Phase 0,
 *    and nowhere else."
 *
 * Paul confirmed the resolver should take an {@see User} (there is no `Member`
 * model — members are users with role=member). See Phase 0 Q13.
 *
 * Rules baked in (from Phase 0 answers):
 *
 * - Q1 (Lifetime + activities): lifetime → `not_applicable` with the reason
 *      "Lifetime members are always compliant". No ratio, no tick.
 * - Q2 (Activity count source): single source — `SystemSetting::get('endorsement_min_activities', 2)`.
 * - Q3 (Activity window): previous year Jan 1 – Oct 31 proves compliance for the
 *      current year; current year Jan 1 – Oct 31 banks for next year.
 * - Q4b (Dedicated status, hybrid): AND for eligibility, OR for display.
 *      {@see ComplianceResult::$isDedicatedEligible} vs ::$showsDedicatedSurfaces.
 * - Q6 (Honorary): new boolean `MembershipType.is_honorary`. Honorary members
 *      follow the same "always compliant" treatment as lifetime for activities.
 * - Q8 (ID slugs): the full {@see MemberDocument::ID_DOCUMENT_SLUGS} list,
 *      including `id-copy` — not the two-slug subset the old helpers used.
 * - Q13 (Member vs User): resolver type-hints `App\Models\User`.
 *
 * Caching: per-request static cache keyed by user id. Phase 1 does not cache
 * across requests. Call {@see self::flush()} after any write that could change
 * a member's compliance (document upload/verify, activity approve/reject,
 * membership state change, knowledge test pass, etc.) to invalidate.
 */
class MemberCompliance
{
    /** @var array<int, ComplianceResult> */
    private static array $cache = [];

    /**
     * Resolve the compliance DTO for a user. Repeated calls within the same
     * request return the cached result.
     */
    public static function for(User $user): ComplianceResult
    {
        $key = (int) $user->id;
        if (! isset(self::$cache[$key])) {
            self::$cache[$key] = self::build($user);
        }
        return self::$cache[$key];
    }

    /**
     * Flush the per-request cache. Pass a user id to flush only that user.
     *
     * Call this after any write that could change compliance so a subsequent
     * read within the same request sees the fresh result.
     */
    public static function flush(?int $userId = null): void
    {
        if ($userId === null) {
            self::$cache = [];
            return;
        }
        unset(self::$cache[$userId]);
    }

    private static function build(User $user): ComplianceResult
    {
        /** @var Membership|null $membership */
        $membership = $user->activeMembership;
        $type = $membership?->type;

        $isLifetime = (bool) ($type?->isLifetime() ?? false);
        $isHonorary = (bool) ($type?->is_honorary ?? false);
        $isActivityExempt = $isLifetime || $isHonorary;

        // Q4b (hybrid). isDedicatedEligible = AND; showsDedicatedSurfaces = OR.
        $allows = (bool) ($type?->allows_dedicated_status ?? false);
        $hasDedicatedType = in_array(
            $type?->dedicated_type,
            [MembershipType::DEDICATED_TYPE_HUNTER, MembershipType::DEDICATED_TYPE_SPORT, MembershipType::DEDICATED_TYPE_BOTH],
            true,
        );

        $requirements = [
            'membership' => self::buildMembershipRequirement($membership),
            'profile_phone' => self::buildPhoneRequirement($user),
            'id_document' => self::buildIdDocumentRequirement($user),
            'knowledge_test' => self::buildKnowledgeTestRequirement($user, $type),
            'activities' => self::buildActivitiesRequirement(
                $user,
                $isActivityExempt,
                $isLifetime,
                $isHonorary,
                hasActiveMembership: $membership !== null,
            ),
            'required_documents' => self::buildRequiredDocumentsRequirement($user, $type),
        ];

        return new ComplianceResult(
            requirements: $requirements,
            hasActiveMembership: $membership !== null,
            isLifetime: $isLifetime,
            isHonorary: $isHonorary,
            isDedicatedEligible: $allows && $hasDedicatedType,
            showsDedicatedSurfaces: $allows || $hasDedicatedType,
        );
    }

    private static function buildMembershipRequirement(?Membership $membership): Requirement
    {
        if (! $membership) {
            return new Requirement(
                key: 'membership',
                label: 'Active membership',
                state: RequirementState::NotMet,
                reason: 'You do not have an active NRAPA membership.',
                meta: ['status' => null],
            );
        }

        // The active-membership scope already filters out expired/revoked, but
        // a lifetime membership never expires. Treat anything the scope returns
        // as "met" — the Phase 2 next-step card is where expired/revoked copy
        // lives (Q11).
        return new Requirement(
            key: 'membership',
            label: 'Active membership',
            state: RequirementState::Met,
            reason: 'Your NRAPA membership is active.',
            meta: [
                'status' => $membership->status,
                'type' => $membership->type?->name,
                'expires_at' => $membership->expires_at?->toIso8601String(),
            ],
        );
    }

    private static function buildPhoneRequirement(User $user): Requirement
    {
        $phone = trim((string) $user->phone);
        if ($phone === '') {
            return new Requirement(
                key: 'profile_phone',
                label: 'Phone number',
                state: RequirementState::NotMet,
                reason: 'Add a phone number so we can reach you about your membership.',
                meta: ['field' => 'phone'],
            );
        }

        return new Requirement(
            key: 'profile_phone',
            label: 'Phone number',
            state: RequirementState::Met,
            reason: 'Your phone number is on file.',
            meta: ['field' => 'phone'],
        );
    }

    private static function buildIdDocumentRequirement(User $user): Requirement
    {
        // Q8: use the full slug list, including `id-copy`.
        $idSlugs = MemberDocument::ID_DOCUMENT_SLUGS;
        $idTypeIds = DocumentType::whereIn('slug', $idSlugs)->pluck('id');

        if ($idTypeIds->isEmpty()) {
            // No ID document type seeded — can't require what doesn't exist.
            return new Requirement(
                key: 'id_document',
                label: 'ID document',
                state: RequirementState::NotApplicable,
                reason: 'No ID document type is configured for this platform.',
            );
        }

        // CASE WHEN rather than MySQL's FIELD() so the resolver works under
        // SQLite (test suite) and PostgreSQL as well as MySQL (production).
        $doc = MemberDocument::where('user_id', $user->id)
            ->whereIn('document_type_id', $idTypeIds)
            ->orderByRaw("CASE status WHEN 'verified' THEN 0 WHEN 'pending' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END ASC")
            ->first();

        if (! $doc) {
            return new Requirement(
                key: 'id_document',
                label: 'ID document',
                state: RequirementState::NotMet,
                reason: 'Upload a copy of your ID document.',
                meta: ['status' => 'missing'],
            );
        }

        $status = $doc->status;

        if ($status === 'rejected') {
            return new Requirement(
                key: 'id_document',
                label: 'ID document',
                state: RequirementState::NotMet,
                reason: 'Your ID document was rejected. Please review the reason and upload a new copy.',
                meta: [
                    'status' => 'rejected',
                    'document_id' => $doc->id,
                    'rejection_reason' => $doc->rejection_reason,
                ],
            );
        }

        if ($status === 'pending') {
            return new Requirement(
                key: 'id_document',
                label: 'ID document',
                state: RequirementState::Pending,
                reason: 'Your ID document is uploaded and waiting for NRAPA to verify it.',
                meta: ['status' => 'pending', 'document_id' => $doc->id],
            );
        }

        // verified — but check expiry
        if ($doc->expires_at && $doc->expires_at->isPast()) {
            return new Requirement(
                key: 'id_document',
                label: 'ID document',
                state: RequirementState::NotMet,
                reason: 'Your ID document has expired. Please upload a current copy.',
                meta: [
                    'status' => 'expired',
                    'document_id' => $doc->id,
                    'expires_at' => $doc->expires_at->toIso8601String(),
                ],
            );
        }

        return new Requirement(
            key: 'id_document',
            label: 'ID document',
            state: RequirementState::Met,
            reason: 'Your ID document has been verified.',
            meta: [
                'status' => 'verified',
                'document_id' => $doc->id,
                'expires_at' => $doc->expires_at?->toIso8601String(),
            ],
        );
    }

    private static function buildKnowledgeTestRequirement(User $user, ?MembershipType $type): Requirement
    {
        if (! $type || ! $type->requires_knowledge_test) {
            return new Requirement(
                key: 'knowledge_test',
                label: 'Knowledge test',
                state: RequirementState::NotApplicable,
                reason: 'Your membership type does not require the knowledge test.',
            );
        }

        if ($user->hasPassedKnowledgeTest()) {
            return new Requirement(
                key: 'knowledge_test',
                label: 'Knowledge test',
                state: RequirementState::Met,
                reason: 'You have passed the dedicated-status knowledge test.',
            );
        }

        return new Requirement(
            key: 'knowledge_test',
            label: 'Knowledge test',
            state: RequirementState::NotMet,
            reason: 'Pass the dedicated-status knowledge test to continue.',
        );
    }

    /**
     * Compute the activities requirement.
     *
     * Rule (Paul Q1, Q2, Q3):
     *
     * 1. Lifetime or honorary members → `not_applicable`. No ratio shown.
     * 2. No active membership → `not_applicable`. You need a membership to
     *    log activities in the first place.
     * 3. Join-year member → `exempt`. The current year does not require
     *    activities, but current-year submissions still bank for next year.
     * 4. Else:
     *    - previous_year_approved_count >= required  → `met` (qualifies now)
     *    - OR current_year_approved_count >= required → `met` (already banked
     *      ahead of the deadline — treated as compliant immediately)
     *    - else → `not_met`
     *
     * Required count comes from `SystemSetting::get('endorsement_min_activities', 2)`.
     */
    private static function buildActivitiesRequirement(
        User $user,
        bool $isActivityExempt,
        bool $isLifetime,
        bool $isHonorary,
        bool $hasActiveMembership,
    ): Requirement {
        if (! $hasActiveMembership) {
            return new Requirement(
                key: 'activities',
                label: 'Range activities',
                state: RequirementState::NotApplicable,
                reason: 'Activity logging requires an active NRAPA membership.',
            );
        }

        if ($isActivityExempt) {
            $reason = $isLifetime
                ? 'Lifetime members are always compliant.'
                : 'Honorary members are always compliant.';

            return new Requirement(
                key: 'activities',
                label: 'Range activities',
                state: RequirementState::NotApplicable,
                reason: $reason,
                meta: [
                    'is_lifetime' => $isLifetime,
                    'is_honorary' => $isHonorary,
                ],
            );
        }

        $required = (int) SystemSetting::get('endorsement_min_activities', 2);
        $currentYear = (int) now()->year;
        $prevYear = $currentYear - 1;

        $countFor = static function (int $year) use ($user): int {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end = Carbon::create($year, 10, 31)->endOfDay();
            return (int) ShootingActivity::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('activity_date', [$start, $end])
                ->count();
        };

        $currentCount = $countFor($currentYear);
        $previousCount = $countFor($prevYear);
        $qualifyingCount = max($previousCount, $currentCount);
        $qualifyingYear = $previousCount >= $required
            ? $prevYear
            : ($currentCount >= $required ? $currentYear : $prevYear);

        $meta = [
            'required' => $required,
            'approved_count' => $qualifyingCount,
            'current_year_count' => $currentCount,
            'previous_year_count' => $previousCount,
            'qualifying_year' => $qualifyingYear,
            'banking_year' => $currentYear,
            'current_year' => $currentYear,
            'previous_year' => $prevYear,
        ];

        if ($user->isInJoinYear()) {
            return new Requirement(
                key: 'activities',
                label: 'Range activities',
                state: RequirementState::Exempt,
                reason: "You are exempt from the activity requirement during your first membership year. Submit {$required} activities before 31 October {$currentYear} to stay compliant in {$currentYear}'s following year.",
                meta: $meta + ['is_join_year_exempt' => true],
            );
        }

        if ($previousCount >= $required) {
            return new Requirement(
                key: 'activities',
                label: 'Range activities',
                state: RequirementState::Met,
                reason: "You are compliant for {$currentYear} on the strength of your {$prevYear} activities ({$previousCount} approved, need {$required}).",
                meta: $meta,
            );
        }

        if ($currentCount >= $required) {
            return new Requirement(
                key: 'activities',
                label: 'Range activities',
                state: RequirementState::Met,
                reason: "You have already submitted {$currentCount} approved activities for {$currentYear} (minimum {$required}). You are compliant.",
                meta: $meta,
            );
        }

        return new Requirement(
            key: 'activities',
            label: 'Range activities',
            state: RequirementState::NotMet,
            reason: "You need at least {$required} approved activities. You have {$previousCount} from {$prevYear} and {$currentCount} from {$currentYear} so far.",
            meta: $meta,
        );
    }

    private static function buildRequiredDocumentsRequirement(User $user, ?MembershipType $type): Requirement
    {
        if (! $type) {
            return new Requirement(
                key: 'required_documents',
                label: 'Required documents',
                state: RequirementState::NotApplicable,
                reason: 'Required documents are determined by your membership type.',
            );
        }

        $requiredTypes = $type->documentTypes()
            ->wherePivot('is_required', true)
            ->get();

        if ($requiredTypes->isEmpty()) {
            return new Requirement(
                key: 'required_documents',
                label: 'Required documents',
                state: RequirementState::NotApplicable,
                reason: 'Your membership type does not require any additional documents.',
            );
        }

        $missing = [];
        $pending = [];
        $rejected = [];

        // ID is handled by the id_document requirement — treat any slug in the
        // ID list as satisfied by that path to avoid double-counting it.
        $idSlugs = MemberDocument::ID_DOCUMENT_SLUGS;

        foreach ($requiredTypes as $docType) {
            if (in_array($docType->slug, $idSlugs, true)) {
                continue; // handled by id_document requirement
            }

            $doc = MemberDocument::where('user_id', $user->id)
                ->where('document_type_id', $docType->id)
                ->orderByRaw("CASE status WHEN 'verified' THEN 0 WHEN 'pending' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END ASC")
                ->first();

            if (! $doc) {
                $missing[] = ['slug' => $docType->slug, 'name' => $docType->name, 'document_type_id' => $docType->id];
                continue;
            }

            if ($doc->status === 'rejected') {
                $rejected[] = ['slug' => $docType->slug, 'name' => $docType->name, 'document_type_id' => $docType->id, 'rejection_reason' => $doc->rejection_reason];
                continue;
            }

            if ($doc->status === 'pending') {
                $pending[] = ['slug' => $docType->slug, 'name' => $docType->name, 'document_type_id' => $docType->id];
                continue;
            }

            // verified — check expiry
            if ($doc->expires_at && $doc->expires_at->isPast()) {
                $missing[] = ['slug' => $docType->slug, 'name' => $docType->name, 'document_type_id' => $docType->id, 'status' => 'expired'];
            }
        }

        $meta = [
            'required_count' => $requiredTypes->count(),
            'missing' => $missing,
            'pending' => $pending,
            'rejected' => $rejected,
        ];

        if (! empty($missing) || ! empty($rejected)) {
            $names = array_map(fn ($d) => $d['name'], array_merge($missing, $rejected));
            $list = implode(', ', $names);
            return new Requirement(
                key: 'required_documents',
                label: 'Required documents',
                state: RequirementState::NotMet,
                reason: "Please upload or re-upload: {$list}.",
                meta: $meta,
            );
        }

        if (! empty($pending)) {
            $names = array_map(fn ($d) => $d['name'], $pending);
            $list = implode(', ', $names);
            return new Requirement(
                key: 'required_documents',
                label: 'Required documents',
                state: RequirementState::Pending,
                reason: "Waiting on NRAPA to verify: {$list}.",
                meta: $meta,
            );
        }

        return new Requirement(
            key: 'required_documents',
            label: 'Required documents',
            state: RequirementState::Met,
            reason: 'All required documents are on file and verified.',
            meta: $meta,
        );
    }
}

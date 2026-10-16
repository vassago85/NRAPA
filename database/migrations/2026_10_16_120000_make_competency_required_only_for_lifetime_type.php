<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Make the firearm-competency document a lifetime-only up-front requirement.
 *
 * Business rule (per Paul, 2026-10-16): only lifetime members have to upload a
 * competency certificate as part of becoming a paid-up member. For every other
 * membership type the upload is optional at signup. (The endorsement-letter
 * preflight check still gates per-request issuance, which is unaffected.)
 *
 * Mechanism: this just rewrites the `is_required` flag on the
 * `document_type_membership_type` pivot — no new columns, no code paths added.
 * `RequiredDocumentsPresent::checklist()` and `MemberCompliance::
 * buildRequiredDocumentsRequirement()` already read from that flag, which is
 * why no PHP needs to change.
 */
return new class extends Migration
{
    public function up(): void
    {
        $competencyId = DB::table('document_types')->where('slug', 'firearm-competency')->value('id');

        if (! $competencyId) {
            // Nothing to migrate — competency document type isn't seeded yet.
            return;
        }

        $lifetimeId = DB::table('membership_types')->where('slug', 'lifetime')->value('id');

        // Flip every existing pivot row for competency to NOT required, except
        // the lifetime one. This is deliberately not keyed off a hand-maintained
        // list of membership slugs so legacy types (e.g. the pre-split
        // "dedicated-annual") get included too — the business rule is simply
        // "only lifetime requires competency up-front", so apply it uniformly.
        DB::table('document_type_membership_type')
            ->where('document_type_id', $competencyId)
            ->when(
                $lifetimeId !== null,
                fn ($q) => $q->where('membership_type_id', '!=', $lifetimeId),
            )
            ->update([
                'is_required' => false,
                'updated_at' => now(),
            ]);

        if (! $lifetimeId) {
            // No lifetime type in this DB — nothing more to do. (That would be
            // unusual, but we don't want the migration to fail on a bare DB.)
            return;
        }

        // Lifetime now requires competency. Upsert in case the pivot row is missing.
        $existing = DB::table('document_type_membership_type')
            ->where('membership_type_id', $lifetimeId)
            ->where('document_type_id', $competencyId)
            ->exists();

        if ($existing) {
            DB::table('document_type_membership_type')
                ->where('membership_type_id', $lifetimeId)
                ->where('document_type_id', $competencyId)
                ->update([
                    'is_required' => true,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('document_type_membership_type')->insert([
                'membership_type_id' => $lifetimeId,
                'document_type_id' => $competencyId,
                'is_required' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Best-effort restore of the previous rule: dedicated-* types required,
        // lifetime optional. We can only restore the slugs we knew about then —
        // any admin edit done between up() and down() will be overwritten.
        $competencyId = DB::table('document_types')->where('slug', 'firearm-competency')->value('id');

        if (! $competencyId) {
            return;
        }

        $restoreRequiredIds = DB::table('membership_types')
            ->where(function ($q) {
                $q->whereIn('slug', ['dedicated-sport', 'dedicated-hunter', 'dedicated-both', 'dedicated-annual'])
                    ->orWhere('allows_dedicated_status', true);
            })
            ->pluck('id');

        if ($restoreRequiredIds->isNotEmpty()) {
            DB::table('document_type_membership_type')
                ->whereIn('membership_type_id', $restoreRequiredIds)
                ->where('document_type_id', $competencyId)
                ->update([
                    'is_required' => true,
                    'updated_at' => now(),
                ]);
        }

        $lifetimeId = DB::table('membership_types')->where('slug', 'lifetime')->value('id');

        if ($lifetimeId) {
            DB::table('document_type_membership_type')
                ->where('membership_type_id', $lifetimeId)
                ->where('document_type_id', $competencyId)
                ->update([
                    'is_required' => false,
                    'updated_at' => now(),
                ]);
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove the duplicate "ID Document" (slug: id-document) document type.
     * The correct type is "ID" (slug: identity-document).
     * Migrate any orphan member documents before deleting.
     */
    public function up(): void
    {
        $old = DB::table('document_types')->where('slug', 'id-document')->first();
        $new = DB::table('document_types')->where('slug', 'identity-document')->first();

        if ($old && $new) {
            // Move any member documents from old type to new type
            DB::table('member_documents')
                ->where('document_type_id', $old->id)
                ->update(['document_type_id' => $new->id]);

            // Remove any pivot links for the old type
            DB::table('document_type_membership_type')
                ->where('document_type_id', $old->id)
                ->delete();

            // Delete the duplicate
            DB::table('document_types')->where('id', $old->id)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create the old document type if needed
        if (!DB::table('document_types')->where('slug', 'id-document')->exists()) {
            DB::table('document_types')->insert([
                'slug' => 'id-document',
                'name' => 'ID Document',
                'description' => null,
                'expiry_months' => null,
                'archive_months' => null,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed the new "Transfer Membership" type and fill in any membership prices
 * that are still at the placeholder R0. Production rows where an admin has
 * already set a real price are left untouched.
 *
 * Spec values:
 *   basic              700  / renewal 550
 *   dedicated-sport    1450 / renewal 550   (kept on initial_price for clarity)
 *   dedicated-hunter   1450 / renewal 550
 *   dedicated-both     1900 / renewal 550
 *   lifetime           4500 once-off (renewal 0)
 *   transfer           550  / renewal 550   (NEW)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 0. Ensure the document type for the previous-association certificate
        //    exists. Re-uses the existing firearm-competency document type for
        //    the competency certificate so SAPS competency uploads stay merged.
        $hasTransferCert = DB::table('document_types')->where('slug', 'transfer-membership-certificate')->exists();
        if (! $hasTransferCert) {
            DB::table('document_types')->insert([
                'slug' => 'transfer-membership-certificate',
                'name' => 'Transfer Membership Certificate',
                'description' => 'Current membership certificate from another SAPS-accredited association (used to verify a transfer application).',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 1. Insert the Transfer type if it does not exist yet.
        $exists = DB::table('membership_types')->where('slug', 'transfer')->exists();

        if (! $exists) {
            DB::table('membership_types')->insert([
                'slug' => 'transfer',
                'name' => 'Transfer Membership',
                'icon' => 'badge-check',
                'description' => 'Reduced-fee join-up for shooters transferring from another SAPS-accredited association. Upload your competency certificate and current membership certificate for review.',
                'duration_type' => 'annual',
                'duration_months' => 12,
                'requires_renewal' => true,
                'expiry_rule' => 'rolling',
                'pricing_model' => 'annual',
                'initial_price' => 550,
                'renewal_price' => 550,
                'upgrade_price' => null,
                'allows_dedicated_status' => true,
                'dedicated_type' => null,
                'requires_knowledge_test' => false,
                'requires_transfer_documents' => true,
                'discount_eligible' => false,
                'is_active' => true,
                'is_featured' => false,
                'display_on_landing' => true,
                'display_on_signup' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('membership_types')
                ->where('slug', 'transfer')
                ->update([
                    'requires_transfer_documents' => true,
                    'updated_at' => now(),
                ]);
        }

        // 2. Fill in placeholder prices ONLY where admins have not set them.
        $defaults = [
            'basic'             => ['initial_price' => 700,  'renewal_price' => 550],
            'dedicated-sport'   => ['initial_price' => 1450, 'renewal_price' => 550],
            'dedicated-hunter'  => ['initial_price' => 1450, 'renewal_price' => 550],
            'dedicated-both'    => ['initial_price' => 1900, 'renewal_price' => 550],
            'lifetime'          => ['initial_price' => 4500, 'renewal_price' => 0],
        ];

        foreach ($defaults as $slug => $prices) {
            $row = DB::table('membership_types')->where('slug', $slug)->first();
            if (! $row) {
                continue;
            }

            $update = [];
            foreach ($prices as $column => $value) {
                if ((float) $row->$column === 0.0) {
                    $update[$column] = $value;
                }
            }

            if (! empty($update)) {
                $update['updated_at'] = now();
                DB::table('membership_types')->where('id', $row->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        DB::table('membership_types')->where('slug', 'transfer')->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated renewals require the previous year's 2/2 activity record on file
 * before the member's new Dedicated Status Certificate can be issued.
 *
 * For imported members whose prior-year 2/2 was not carried across in the
 * import, and for one-off edge cases, an admin can manually attest to prior
 * compliance by stamping this column. The approval UI auto-stamps it on the
 * first renewal cycle of a member whose previous row has `source = 'import'`.
 *
 * Attribute-driven: this is a per-membership flag, not a per-type one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->timestamp('prior_year_compliance_verified_at')->nullable()->after('pop_resubmit_message');
            $table->foreignId('prior_year_compliance_verified_by')
                ->nullable()
                ->after('prior_year_compliance_verified_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('prior_year_compliance_verified_note')->nullable()->after('prior_year_compliance_verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropForeign(['prior_year_compliance_verified_by']);
            $table->dropColumn([
                'prior_year_compliance_verified_at',
                'prior_year_compliance_verified_by',
                'prior_year_compliance_verified_note',
            ]);
        });
    }
};

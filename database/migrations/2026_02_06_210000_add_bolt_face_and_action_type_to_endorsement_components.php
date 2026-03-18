<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds bolt_face and action_type for action components (Step 2 redesign).
     */
    public function up(): void
    {
        if (Schema::hasTable('endorsement_components')) {
            Schema::table('endorsement_components', function (Blueprint $table) {
                if (! Schema::hasColumn('endorsement_components', 'bolt_face')) {
                    $table->string('bolt_face')->nullable()->after('diameter')->comment('Bolt face type for action components');
                }
                if (! Schema::hasColumn('endorsement_components', 'action_type')) {
                    $table->string('action_type')->nullable()->after('bolt_face')->comment('Action type for action components: bolt_action, semi_auto, single_shot');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('endorsement_components')) {
            Schema::table('endorsement_components', function (Blueprint $table) {
                if (Schema::hasColumn('endorsement_components', 'bolt_face')) {
                    $table->dropColumn('bolt_face');
                }
                if (Schema::hasColumn('endorsement_components', 'action_type')) {
                    $table->dropColumn('action_type');
                }
            });
        }
    }
};

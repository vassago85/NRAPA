<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-defence endorsements store firearm details inline on the request
     * (they do not use the SAPS-271 EndorsementFirearm row). Action and
     * ignition were missing, so admins could not correct a handgun letter
     * that omitted them.
     */
    public function up(): void
    {
        Schema::table('endorsement_requests', function (Blueprint $table) {
            $table->string('firearm_action_type')->nullable()->after('firearm_type');
            $table->string('firearm_ignition_type')->nullable()->after('firearm_action_type');
        });
    }

    public function down(): void
    {
        Schema::table('endorsement_requests', function (Blueprint $table) {
            $table->dropColumn(['firearm_action_type', 'firearm_ignition_type']);
        });
    }
};

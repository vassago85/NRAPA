<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 (member platform) — Paul's Q6 answer.
 *
 * Honorary members get the "always compliant" activity treatment that lifetime
 * members already enjoy, but without having to be modelled on a lifetime
 * duration type. Add a boolean on `membership_types` so an admin can create a
 * new honorary type that is otherwise distinct from Lifetime.
 *
 * Defaults to false everywhere. Zero live members are affected until an admin
 * creates an honorary type and assigns someone to it.
 *
 * The resolver ({@see \App\Support\Members\MemberCompliance}) treats
 * `type.isLifetime() || type.is_honorary` as "exempt from activities".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            $table->boolean('is_honorary')->default(false)->after('allows_dedicated_status');
        });
    }

    public function down(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            $table->dropColumn('is_honorary');
        });
    }
};

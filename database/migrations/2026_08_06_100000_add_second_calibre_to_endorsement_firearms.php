<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add second-calibre columns to endorsement_firearms to support
     * "combination" firearms which have two calibres/cartridges
     * (rifle+shotgun, rifle+rifle, or shotgun+shotgun).
     */
    public function up(): void
    {
        Schema::table('endorsement_firearms', function (Blueprint $table) {
            $table->foreignId('firearm_calibre_id_2')
                ->nullable()
                ->after('calibre_text_override')
                ->constrained('firearm_calibres')
                ->nullOnDelete();

            $table->string('calibre_text_override_2')
                ->nullable()
                ->after('firearm_calibre_id_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('endorsement_firearms', function (Blueprint $table) {
            $table->dropForeign(['firearm_calibre_id_2']);
            $table->dropColumn([
                'firearm_calibre_id_2',
                'calibre_text_override_2',
            ]);
        });
    }
};

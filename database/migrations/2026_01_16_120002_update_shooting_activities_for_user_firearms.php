<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shooting_activities', function (Blueprint $table) {
            // Add reference to user's own firearm (optional - can still use generic firearm_type/calibre)
            if (!Schema::hasColumn('shooting_activities', 'user_firearm_id')) {
                $table->foreignId('user_firearm_id')->nullable()->after('calibre_id')
                    ->constrained('user_firearms')->nullOnDelete();
            }
            if (!Schema::hasColumn('shooting_activities', 'load_data_id')) {
                $table->foreignId('load_data_id')->nullable()->after('user_firearm_id')
                    ->constrained('load_data')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shooting_activities', function (Blueprint $table) {
            $table->dropForeign(['user_firearm_id']);
            $table->dropForeign(['load_data_id']);
            $table->dropColumn(['user_firearm_id', 'load_data_id']);
        });
    }
};

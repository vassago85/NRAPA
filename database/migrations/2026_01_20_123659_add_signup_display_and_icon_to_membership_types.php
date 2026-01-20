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
        Schema::table('membership_types', function (Blueprint $table) {
            // Separate flag for showing on signup/apply pages (different from landing page)
            $table->boolean('display_on_signup')->default(true)->after('display_on_landing');
            
            // Icon for visual identification (Heroicon name, e.g., 'shield-check', 'star', 'trophy')
            $table->string('icon', 50)->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            $table->dropColumn(['display_on_signup', 'icon']);
        });
    }
};

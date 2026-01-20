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
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->decimal('admin_fee', 10, 2)->default(0)->after('price');
            $table->boolean('display_on_landing')->default(false)->after('is_featured');
            $table->string('dedicated_type')->nullable()->after('allows_dedicated_status')
                ->comment('hunter, sport, both, or null for general');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'admin_fee', 'display_on_landing', 'dedicated_type']);
        });
    }
};

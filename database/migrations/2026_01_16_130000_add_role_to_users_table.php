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
        Schema::table('users', function (Blueprint $table) {
            // Add role column: developer > owner > admin > member
            $table->enum('role', ['developer', 'owner', 'admin', 'member'])->default('member')->after('is_admin');

            // Track who nominated owners/admins
            $table->foreignId('nominated_by')->nullable()->after('role')->constrained('users')->nullOnDelete();
            $table->timestamp('nominated_at')->nullable()->after('nominated_by');
        });

        // Migrate existing is_admin users to admin role
        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['nominated_by']);
            $table->dropColumn(['role', 'nominated_by', 'nominated_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend status enum to include change request statuses.
        // SQLite stores ENUM columns as TEXT and any pre-existing CHECK constraint
        // is recreated on Blueprint::enum(); for the test suite we skip the raw
        // MODIFY COLUMN and rely on Eloquent-side validation instead.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE memberships MODIFY COLUMN status ENUM('applied','approved','active','suspended','revoked','expired','pending_change','pending_payment') DEFAULT 'applied'");
        }

        Schema::table('memberships', function (Blueprint $table) {
            $table->decimal('change_amount', 10, 2)->nullable()->after('proof_of_payment_path');
        });
    }

    public function down(): void
    {
        // Revert any pending_change/pending_payment rows before shrinking the enum
        DB::table('memberships')
            ->whereIn('status', ['pending_change', 'pending_payment'])
            ->update(['status' => 'applied']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE memberships MODIFY COLUMN status ENUM('applied','approved','active','suspended','revoked','expired') DEFAULT 'applied'");
        }

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn('change_amount');
        });
    }
};

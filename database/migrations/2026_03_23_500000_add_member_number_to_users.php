<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add permanent member number to users
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('member_number')->nullable()->unique()->after('id');
        });

        // Drop the unique constraint on membership_number (same user keeps
        // the same number across renewals, so duplicates are expected)
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropUnique(['membership_number']);
            $table->index('membership_number');
        });

        // Assign sequential member numbers to all existing users.
        // Riaan Kunneke = 1 as founding member, then everyone else by creation date.
        $founderId = DB::table('users')
            ->where('name', 'like', '%Riaan%Kunneke%')
            ->value('id');

        $counter = 1;

        if ($founderId) {
            DB::table('users')
                ->where('id', $founderId)
                ->update(['member_number' => $counter]);
            $counter++;
        }

        $query = DB::table('users')->orderBy('created_at')->orderBy('id');
        if ($founderId) {
            $query->where('id', '!=', $founderId);
        }

        foreach ($query->pluck('id') as $userId) {
            DB::table('users')
                ->where('id', $userId)
                ->update(['member_number' => $counter]);
            $counter++;
        }

        // Update all existing memberships to use the new NRAPA-XXXXX format
        $memberships = DB::table('memberships')
            ->join('users', 'memberships.user_id', '=', 'users.id')
            ->select('memberships.id', 'users.member_number')
            ->get();

        foreach ($memberships as $membership) {
            DB::table('memberships')
                ->where('id', $membership->id)
                ->update([
                    'membership_number' => 'NRAPA-' . str_pad((string) $membership->member_number, 5, '0', STR_PAD_LEFT),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropIndex(['membership_number']);
            $table->unique('membership_number');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('member_number');
        });
    }
};

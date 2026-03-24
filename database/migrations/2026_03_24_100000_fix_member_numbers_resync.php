<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure member_number column exists on users
        if (! Schema::hasColumn('users', 'member_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('member_number')->nullable()->unique()->after('id');
            });
        }

        // Drop unique constraint on memberships.membership_number if it still exists.
        // Try both possible index names (Laravel convention and raw column name).
        try {
            Schema::table('memberships', function (Blueprint $table) {
                $table->dropUnique('memberships_membership_number_unique');
            });
        } catch (\Exception $e) {
            // Already dropped or doesn't exist — safe to ignore
        }

        // Add a regular index if not present
        $indexes = collect(DB::select("SHOW INDEX FROM memberships WHERE Column_name = 'membership_number'"));
        if ($indexes->isEmpty()) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->index('membership_number');
            });
        }

        // Assign sequential member numbers to all users who don't have one yet.
        // Riaan Kunneke = 1 as founding member.
        $maxExisting = (int) DB::table('users')->max('member_number');

        if ($maxExisting === 0) {
            // No numbers assigned yet — do the full initial assignment
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
        } else {
            // Some numbers exist — only fill in missing ones
            $counter = $maxExisting + 1;

            $missing = DB::table('users')
                ->whereNull('member_number')
                ->orderBy('created_at')
                ->orderBy('id')
                ->pluck('id');

            foreach ($missing as $userId) {
                DB::table('users')
                    ->where('id', $userId)
                    ->update(['member_number' => $counter]);
                $counter++;
            }
        }

        // Re-sync ALL memberships to use the NRAPA-XXXXX format
        $memberships = DB::table('memberships')
            ->join('users', 'memberships.user_id', '=', 'users.id')
            ->select('memberships.id', 'users.member_number')
            ->get();

        foreach ($memberships as $membership) {
            $formatted = 'NRAPA-' . str_pad((string) $membership->member_number, 5, '0', STR_PAD_LEFT);

            DB::table('memberships')
                ->where('id', $membership->id)
                ->update(['membership_number' => $formatted]);
        }
    }

    public function down(): void
    {
        // No-op — this is a data fix migration
    }
};

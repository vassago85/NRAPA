<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds expires_at field to endorsement_requests.
     * Endorsement letters expire 1 year after issue date.
     */
    public function up(): void
    {
        if (Schema::hasTable('endorsement_requests')) {
            Schema::table('endorsement_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('endorsement_requests', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->after('issued_at')->comment('Endorsement letter expires 1 year after issue date');
                }
            });

            // Set expires_at for existing issued letters (1 year from issued_at)
            if (DB::getDriverName() === 'mysql') {
                DB::table('endorsement_requests')
                    ->whereNotNull('issued_at')
                    ->whereNull('expires_at')
                    ->update([
                        'expires_at' => DB::raw('DATE_ADD(issued_at, INTERVAL 1 YEAR)'),
                    ]);
            } else {
                // SQLite or other databases - use Carbon to calculate
                $endorsements = DB::table('endorsement_requests')
                    ->whereNotNull('issued_at')
                    ->whereNull('expires_at')
                    ->get();

                foreach ($endorsements as $endorsement) {
                    $issuedAt = Carbon::parse($endorsement->issued_at);
                    $expiresAt = $issuedAt->copy()->addYear();

                    DB::table('endorsement_requests')
                        ->where('id', $endorsement->id)
                        ->update(['expires_at' => $expiresAt]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('endorsement_requests')) {
            Schema::table('endorsement_requests', function (Blueprint $table) {
                if (Schema::hasColumn('endorsement_requests', 'expires_at')) {
                    $table->dropColumn('expires_at');
                }
            });
        }
    }
};

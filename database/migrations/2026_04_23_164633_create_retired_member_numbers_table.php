<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retired_member_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('member_number')->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('retired_at')->useCurrent();
            $table->timestamps();
        });

        // Backfill: any gap between 1 and the current max member_number must be
        // treated as previously-used and reserved so it can never be re-issued.
        $maxExisting = (int) DB::table('users')->max('member_number');

        if ($maxExisting > 0) {
            $existing = DB::table('users')
                ->whereNotNull('member_number')
                ->pluck('member_number')
                ->map(fn ($n) => (int) $n)
                ->all();

            $existingSet = array_flip($existing);
            $now = now();
            $rows = [];

            for ($n = 1; $n <= $maxExisting; $n++) {
                if (! isset($existingSet[$n])) {
                    $rows[] = [
                        'member_number' => $n,
                        'user_id' => null,
                        'name' => null,
                        'email' => null,
                        'reason' => 'backfill: historical gap detected at migration',
                        'retired_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('retired_member_numbers')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('retired_member_numbers');
    }
};

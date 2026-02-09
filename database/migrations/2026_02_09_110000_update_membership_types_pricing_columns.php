<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Restructure pricing: single 'price' column becomes three columns:
     * - initial_price: sign-up fee (higher for basic, 0 for dedicated types)
     * - renewal_price: annual renewal fee
     * - upgrade_price: once-off dedicated upgrade fee (null for basic)
     */
    public function up(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            $table->decimal('initial_price', 10, 2)->default(0)->after('pricing_model')->comment('Sign-up fee for new members');
            $table->decimal('renewal_price', 10, 2)->default(0)->after('initial_price')->comment('Annual renewal fee');
            $table->decimal('upgrade_price', 10, 2)->nullable()->after('renewal_price')->comment('Once-off dedicated upgrade fee (null for basic)');
        });

        // Migrate existing price data to initial_price and renewal_price
        DB::table('membership_types')->get()->each(function ($type) {
            DB::table('membership_types')->where('id', $type->id)->update([
                'initial_price' => $type->price,
                'renewal_price' => $type->price,
            ]);
        });

        Schema::table('membership_types', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('pricing_model');
        });

        // Restore price from initial_price
        DB::table('membership_types')->get()->each(function ($type) {
            DB::table('membership_types')->where('id', $type->id)->update([
                'price' => $type->initial_price,
            ]);
        });

        Schema::table('membership_types', function (Blueprint $table) {
            $table->dropColumn(['initial_price', 'renewal_price', 'upgrade_price']);
        });
    }
};

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
            $table->unsignedTinyInteger('logins_without_2fa')->default(0)->after('two_factor_confirmed_at');
            $table->timestamp('last_2fa_reminder_at')->nullable()->after('logins_without_2fa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['logins_without_2fa', 'last_2fa_reminder_at']);
        });
    }
};

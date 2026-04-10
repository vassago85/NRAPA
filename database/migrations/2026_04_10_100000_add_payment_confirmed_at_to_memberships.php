<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->timestamp('payment_confirmed_at')->nullable()->after('pop_reminder_sent_at');
            $table->unsignedBigInteger('payment_confirmed_by')->nullable()->after('payment_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn(['payment_confirmed_at', 'payment_confirmed_by']);
        });
    }
};

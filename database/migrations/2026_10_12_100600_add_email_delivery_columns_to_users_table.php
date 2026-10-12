<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_status', 20)->nullable()->index();
            $table->timestamp('email_last_event_at')->nullable();
            $table->string('email_bounce_reason', 500)->nullable();
            $table->timestamp('last_signed_in_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email_status']);
            $table->dropIndex(['last_signed_in_at']);
            $table->dropColumn(['email_status', 'email_last_event_at', 'email_bounce_reason', 'last_signed_in_at']);
        });
    }
};

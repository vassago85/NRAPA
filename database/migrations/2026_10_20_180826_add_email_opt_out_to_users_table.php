<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add non-essential-email opt-out columns to users.
     *
     * email_opt_out_at — timestamp the member unsubscribed from non-essential
     * mail (reminders, chases, bulk admin messages). NULL means "will receive
     * all mail they qualify for". Transactional / account mail (approval
     * decisions, payments, password reset, membership status) is unaffected.
     *
     * email_opt_out_reason — short tag so we can tell where it came from
     * (e.g. 'footer_link', 'preferences_page', 'admin').
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_opt_out_at')->nullable()->after('email_status');
            $table->string('email_opt_out_reason', 64)->nullable()->after('email_opt_out_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_opt_out_at', 'email_opt_out_reason']);
        });
    }
};

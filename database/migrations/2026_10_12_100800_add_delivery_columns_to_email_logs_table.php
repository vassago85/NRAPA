<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('provider_message_id')->nullable()->unique()->after('status');
            $table->string('delivery_status', 20)->nullable()->index()->after('provider_message_id');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('opened_at')->nullable()->after('delivered_at');
            $table->timestamp('clicked_at')->nullable()->after('opened_at');
            $table->timestamp('bounced_at')->nullable()->after('clicked_at');
            $table->string('bounce_code', 20)->nullable()->after('bounced_at');
            $table->string('bounce_reason', 500)->nullable()->after('bounce_code');
            $table->json('events')->nullable()->after('bounce_reason');
        });

        Schema::create('mailgun_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event', 40)->index();
            $table->string('provider_message_id')->nullable()->index();
            $table->string('recipient')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailgun_events');

        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropUnique(['provider_message_id']);
            $table->dropIndex(['delivery_status']);
            $table->dropColumn([
                'provider_message_id',
                'delivery_status',
                'delivered_at',
                'opened_at',
                'clicked_at',
                'bounced_at',
                'bounce_code',
                'bounce_reason',
                'events',
            ]);
        });
    }
};

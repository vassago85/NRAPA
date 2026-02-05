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
        if (Schema::hasTable('notification_preferences')) {
            Schema::table('notification_preferences', function (Blueprint $table) {
                if (!Schema::hasColumn('notification_preferences', 'notify_document_rejected')) {
                    $table->boolean('notify_document_rejected')->default(true)->after('notify_document_uploaded');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notification_preferences')) {
            Schema::table('notification_preferences', function (Blueprint $table) {
                if (Schema::hasColumn('notification_preferences', 'notify_document_rejected')) {
                    $table->dropColumn('notify_document_rejected');
                }
            });
        }
    }
};

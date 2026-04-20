<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('member_messages', function (Blueprint $table) {
            // admin_to_member = admin sent this message to the member
            // member_to_admin = member sent this message to NRAPA admins
            $table->enum('direction', ['admin_to_member', 'member_to_admin'])
                ->default('admin_to_member')
                ->after('sent_by_user_id');

            // Thread root — null for the first message in a thread, otherwise the id of the parent
            $table->unsignedBigInteger('parent_id')->nullable()->after('direction');

            $table->foreign('parent_id')
                ->references('id')
                ->on('member_messages')
                ->nullOnDelete();

            $table->index(['user_id', 'direction']);
            $table->index('parent_id');
        });

        // Backfill: every existing message was admin -> member (the feature only existed that way)
        DB::table('member_messages')->update(['direction' => 'admin_to_member']);
    }

    public function down(): void
    {
        Schema::table('member_messages', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['user_id', 'direction']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn(['direction', 'parent_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_messages', function (Blueprint $table) {
            $table->index(['direction', 'read_at'], 'member_messages_direction_read_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('member_messages', function (Blueprint $table) {
            $table->dropIndex('member_messages_direction_read_at_index');
        });
    }
};

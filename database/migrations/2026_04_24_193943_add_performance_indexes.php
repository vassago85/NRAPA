<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });

        Schema::table('shooting_activities', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'activity_date']);
            $table->index('status');
        });

        Schema::table('member_documents', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->index('status');
        });

        if (Schema::hasTable('endorsement_requests')) {
            Schema::table('endorsement_requests', function (Blueprint $table) {
                $table->index('status');
            });
        }

        if (Schema::hasTable('calibre_requests')) {
            Schema::table('calibre_requests', function (Blueprint $table) {
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });

        Schema::table('shooting_activities', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status', 'activity_date']);
            $table->dropIndex(['status']);
        });

        Schema::table('member_documents', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        if (Schema::hasTable('endorsement_requests')) {
            Schema::table('endorsement_requests', function (Blueprint $table) {
                $table->dropIndex(['status']);
            });
        }

        if (Schema::hasTable('calibre_requests')) {
            Schema::table('calibre_requests', function (Blueprint $table) {
                $table->dropIndex(['status']);
            });
        }
    }
};

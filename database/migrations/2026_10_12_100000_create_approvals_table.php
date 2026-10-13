<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL auto-commits DDL, so if the container was killed between the
        // CREATE TABLE finishing and the migrations row being written, the
        // table exists on prod but the migration is unlogged. Every redeploy
        // then crashes here. Make the create idempotent so a replay just
        // records the migration and moves on.
        if (Schema::hasTable('approvals')) {
            return;
        }

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('state', 30);
            $table->timestamp('submitted_at');
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('requires_second_approval')->default(false);
            $table->foreignId('first_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_approved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['approvable_type', 'approvable_id']);
            $table->index(['state', 'submitted_at']);
            $table->index('type');
            $table->index('decided_at');
            $table->index(['user_id', 'state']);
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};

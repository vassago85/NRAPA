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
        Schema::create('dedicated_status_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_id')->constrained();

            // Status
            $table->enum('status', [
                'applied',
                'under_review',
                'approved',
                'rejected',
            ])->default('applied');

            // Application
            $table->timestamp('applied_at')->useCurrent();

            // Review
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');

            // Approval
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');

            // Rejection
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Validity
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            // Admin
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
        });

        Schema::create('shooting_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('activity_date');
            $table->string('activity_type');
            $table->string('venue')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('evidence_document_id')->nullable()->constrained('member_documents');

            // Verification
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'activity_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shooting_activities');
        Schema::dropIfExists('dedicated_status_applications');
    }
};

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
        Schema::create('endorsement_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Request type: new or renewal
            $table->enum('request_type', ['new', 'renewal'])->default('new');

            // Status workflow
            $table->enum('status', [
                'draft',
                'submitted',
                'under_review',
                'pending_documents',
                'issued',
                'rejected',
                'cancelled',
            ])->default('draft');

            // Purpose of endorsement
            $table->enum('purpose', [
                'section_16_application',
                'status_confirmation',
                'licence_renewal',
                'additional_firearm',
                'other',
            ])->nullable();
            $table->string('purpose_other_text')->nullable();

            // Declaration
            $table->timestamp('declaration_accepted_at')->nullable();
            $table->text('declaration_text')->nullable();

            // Timestamps for workflow
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Admin references
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();

            // Notes
            $table->text('member_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Generated letter reference
            $table->string('letter_reference')->nullable()->unique();
            $table->string('letter_file_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('request_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endorsement_requests');
    }
};

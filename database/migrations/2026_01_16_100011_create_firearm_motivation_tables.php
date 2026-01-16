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
        Schema::create('firearm_motivation_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->comment('Null for non-members');

            // Applicant details (for non-members or override)
            $table->string('applicant_name');
            $table->string('applicant_email');
            $table->string('applicant_id_number');
            $table->string('applicant_phone')->nullable();

            // Request details
            $table->string('firearm_type');
            $table->text('purpose');

            // Status
            $table->enum('status', [
                'submitted',
                'under_review',
                'approved',
                'rejected',
                'issued',
            ])->default('submitted');

            // Submission
            $table->timestamp('submitted_at')->useCurrent();

            // Review
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');

            // Approval
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');

            // Rejection
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Issuance (hard copy)
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users');

            // Admin
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('applicant_email');
        });

        Schema::create('firearm_motivation_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('firearm_motivation_requests')->cascadeOnDelete();
            $table->string('document_name');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedInteger('file_size');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firearm_motivation_documents');
        Schema::dropIfExists('firearm_motivation_requests');
    }
};

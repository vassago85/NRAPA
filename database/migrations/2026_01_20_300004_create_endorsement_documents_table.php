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
        Schema::create('endorsement_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('endorsement_request_id')->constrained()->cascadeOnDelete();

            // Document type for endorsement
            $table->enum('document_type', [
                'sa_id',
                'proof_of_address',
                'dedicated_status_certificate',
                'membership_proof',
                'activity_proof',
                'previous_endorsement_letter',
                'firearm_licence_card',
                'competency_certificate',
                'other',
            ]);

            // Status of this document requirement
            $table->enum('status', [
                'required',        // Document is required
                'pending_upload',  // Marked as "submit later"
                'uploaded',        // File has been uploaded
                'verified',        // Admin verified
                'rejected',        // Admin rejected
                'waived',          // Admin waived requirement
                'system_verified',  // System auto-verified (e.g., membership)
            ])->default('required');

            // File information (nullable if pending_upload)
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();

            // Upload tracking
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();

            // Verification
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            // Link to existing member document (optional)
            $table->foreignId('member_document_id')->nullable()->constrained()->nullOnDelete();

            // Document-specific metadata
            $table->json('metadata')->nullable();

            // Activity proof specific fields
            $table->string('activity_type')->nullable(); // match, training, hunt, practice
            $table->string('activity_discipline')->nullable(); // PRS, IPSC, etc.
            $table->date('activity_date')->nullable();
            $table->string('activity_venue')->nullable();
            $table->string('activity_organiser')->nullable();

            // Document validity
            $table->date('document_date')->nullable();
            $table->date('expires_at')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Whether this is a required document for the request type
            $table->boolean('is_required')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['endorsement_request_id', 'document_type']);
            $table->index(['endorsement_request_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endorsement_documents');
    }
};

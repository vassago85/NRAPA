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
        Schema::create('configuration_change_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->string('configuration_type'); // 'membership_type', 'document_type', 'document_requirements'
            $table->unsignedBigInteger('target_id')->nullable(); // ID of the record being changed (null for create)
            $table->string('action'); // 'create', 'update', 'delete'
            $table->json('old_values')->nullable(); // State before change
            $table->json('new_values'); // Proposed new state
            $table->text('reason')->nullable(); // Admin's reason for the change
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable(); // Owner's notes on approval/rejection
            $table->timestamps();
            
            $table->index(['configuration_type', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuration_change_requests');
    }
};

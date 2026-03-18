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
        Schema::create('member_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained();

            // Status change
            $table->enum('status', [
                'applied',
                'approved',
                'active',
                'suspended',
                'revoked',
                'expired',
                'lapsed',
            ]);

            $table->enum('previous_status', [
                'applied',
                'approved',
                'active',
                'suspended',
                'revoked',
                'expired',
                'lapsed',
            ])->nullable();

            // Change details
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->timestamp('changed_at')->useCurrent();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'changed_at']);
            $table->index(['membership_id', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_status_history');
    }
};

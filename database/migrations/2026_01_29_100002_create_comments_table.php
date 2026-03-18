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
        if (Schema::hasTable('comments')) {
            // Check if table has the required columns (indicates it was already migrated)
            if (Schema::hasColumn('comments', 'commentable_type') &&
                Schema::hasColumn('comments', 'commentable_id')) {
                // Table exists with correct structure, skip migration
                return;
            }
            // Table exists but missing columns, drop and recreate
            Schema::dropIfExists('comments');
        }

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Polymorphic relationship
            $table->morphs('commentable'); // commentable_type, commentable_id (morphs() creates index automatically)

            // Author
            $table->foreignId('author_id')->constrained('users');

            // Content
            $table->text('body');

            // Visibility
            $table->enum('visibility', [
                'internal',    // Only admins can see
                'applicant',   // Applicant can see
            ])->default('internal');

            // Notification
            $table->boolean('notify_applicant')->default(false);
            $table->timestamp('notified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            // Note: morphs() already creates an index on commentable_type and commentable_id
            $table->index('author_id');
            $table->index('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};

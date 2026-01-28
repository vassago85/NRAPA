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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Polymorphic relationship
            $table->morphs('commentable'); // commentable_type, commentable_id
            
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
            $table->index(['commentable_type', 'commentable_id']);
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

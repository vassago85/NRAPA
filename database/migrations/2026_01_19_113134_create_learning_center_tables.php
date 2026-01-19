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
        // Learning Categories
        Schema::create('learning_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // Icon class or SVG path
            $table->string('image_path')->nullable(); // Category cover image
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Learning Articles
        Schema::create('learning_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable(); // Short summary
            $table->longText('content'); // Rich text content with images
            $table->string('featured_image')->nullable();
            $table->integer('reading_time_minutes')->nullable(); // Estimated reading time
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['learning_category_id', 'is_published']);
            $table->index('is_featured');
        });

        // Article Images (for inline images in articles)
        Schema::create('learning_article_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_article_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->timestamps();
        });

        // Track which articles members have read (optional, for progress tracking)
        Schema::create('learning_article_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_article_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['user_id', 'learning_article_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_article_reads');
        Schema::dropIfExists('learning_article_images');
        Schema::dropIfExists('learning_articles');
        Schema::dropIfExists('learning_categories');
    }
};

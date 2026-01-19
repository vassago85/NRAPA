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
        // Create learning article pages table
        Schema::create('learning_article_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_article_id')->constrained()->cascadeOnDelete();
            $table->string('title'); // Page title / subheading
            $table->string('image_path')->nullable(); // Page header image
            $table->string('image_caption')->nullable(); // Image caption
            $table->longText('content'); // Page content
            $table->integer('page_number')->default(1);
            $table->timestamps();

            $table->index(['learning_article_id', 'page_number']);
        });

        // Track page reads (for progress tracking)
        Schema::create('learning_page_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_article_page_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['user_id', 'learning_article_page_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_page_reads');
        Schema::dropIfExists('learning_article_pages');
    }
};

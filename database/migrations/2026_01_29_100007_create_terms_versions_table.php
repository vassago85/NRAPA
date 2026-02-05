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
        Schema::create('terms_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique()->comment('Version identifier (e.g., "2026-01")');
            $table->string('title');
            $table->string('html_path')->nullable()->comment('Path to HTML file if stored on disk');
            $table->longText('html_content')->nullable()->comment('HTML content stored in database');
            $table->boolean('is_active')->default(false)->comment('Only one active version at a time');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Index for quick lookup of active version
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms_versions');
    }
};

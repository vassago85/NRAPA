<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bullet_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bullet_id')->constrained('bullets')->onDelete('cascade');
            $table->string('source_type', 32); // product_page | bc_table | catalog_pdf
            $table->string('source_url', 512);
            $table->dateTime('captured_at');
            $table->text('raw_excerpt')->nullable();
            $table->timestamps();

            $table->index('bullet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bullet_sources');
    }
};

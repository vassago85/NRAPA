<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('firearm_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firearm_make_id')->constrained('firearm_makes')->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized_name');
            $table->enum('category_hint', ['handgun', 'rifle', 'shotgun'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['firearm_make_id', 'normalized_name']);
            $table->index('firearm_make_id');
            $table->index('is_active');
            $table->index('category_hint');

            // Fulltext index for search (MySQL)
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['name', 'normalized_name']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firearm_models');
    }
};

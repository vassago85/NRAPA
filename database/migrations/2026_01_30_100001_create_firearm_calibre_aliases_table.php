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
        Schema::create('firearm_calibre_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firearm_calibre_id')->constrained('firearm_calibres')->cascadeOnDelete();
            $table->string('alias');
            $table->string('normalized_alias')->unique();
            $table->timestamps();

            $table->index('firearm_calibre_id');
            $table->index('normalized_alias');

            // Fulltext index for search (MySQL)
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['alias', 'normalized_alias']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firearm_calibre_aliases');
    }
};

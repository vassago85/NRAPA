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
        Schema::create('firearm_calibres', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('normalized_name')->unique();
            $table->enum('category', ['rimfire', 'handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic'])->default('rifle');
            $table->string('family')->nullable()->comment('Creedmoor|BR|CheyTac|Magnum|Mauser|etc');
            $table->decimal('bullet_diameter_mm', 4, 2)->nullable();
            $table->decimal('case_length_mm', 5, 2)->nullable();
            $table->string('parent')->nullable()->comment('Informational parent calibre, e.g. "6.5 Creedmoor"');
            $table->boolean('is_wildcat')->default(false);
            $table->boolean('is_obsolete')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable()->comment('PRS|ELR|Hunting|Service|Collector');
            $table->timestamps();

            $table->index('category');
            $table->index('family');
            $table->index('is_active');
            $table->index('is_wildcat');
            $table->index('normalized_name');

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
        Schema::dropIfExists('firearm_calibres');
    }
};

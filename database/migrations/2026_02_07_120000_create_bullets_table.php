<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bullets', function (Blueprint $table) {
            $table->id();
            $table->string('manufacturer', 64);
            $table->string('brand_line', 64);
            $table->string('bullet_label', 128);
            $table->string('caliber_label', 32);
            $table->unsignedSmallInteger('weight_gr');
            $table->decimal('diameter_in', 6, 3);
            $table->decimal('diameter_mm', 7, 3);
            $table->decimal('length_in', 7, 3)->nullable();
            $table->decimal('length_mm', 8, 3)->nullable();
            $table->decimal('bc_g1', 6, 3)->nullable();
            $table->decimal('bc_g7', 6, 3)->nullable();
            $table->string('bc_reference', 32)->nullable();
            $table->string('construction', 32);
            $table->string('intended_use', 16);
            $table->string('twist_note', 64)->nullable();
            $table->string('source_url', 255);
            $table->dateTime('last_verified_at');
            $table->timestamps();

            $table->unique(
                ['manufacturer', 'brand_line', 'caliber_label', 'weight_gr', 'twist_note', 'bc_reference'],
                'uniq_bullet'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bullets');
    }
};

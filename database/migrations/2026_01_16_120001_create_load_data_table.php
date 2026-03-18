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
        Schema::create('load_data', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_firearm_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calibre_id')->nullable()->constrained()->nullOnDelete();

            // Load name/identifier
            $table->string('name'); // e.g., "Match Load #1", "Hunting Load"

            // Projectile/Bullet details
            $table->string('bullet_make')->nullable(); // e.g., "Sierra", "Hornady", "Lapua"
            $table->string('bullet_model')->nullable(); // e.g., "MatchKing", "ELD-X"
            $table->decimal('bullet_weight', 6, 1)->nullable(); // in grains
            $table->decimal('bullet_bc', 5, 3)->nullable(); // Ballistic Coefficient
            $table->string('bullet_type')->nullable(); // e.g., "HPBT", "SP", "FMJ"

            // Powder details
            $table->string('powder_make')->nullable(); // e.g., "Hodgdon", "Vihtavuori", "Somchem"
            $table->string('powder_type')->nullable(); // e.g., "H4350", "N150", "S365"
            $table->decimal('powder_charge', 5, 1)->nullable(); // in grains

            // Primer details
            $table->string('primer_make')->nullable(); // e.g., "CCI", "Federal", "Murom"
            $table->string('primer_type')->nullable(); // e.g., "Large Rifle", "Small Rifle Magnum"

            // Brass/Case details
            $table->string('brass_make')->nullable(); // e.g., "Lapua", "Norma", "ADG"
            $table->integer('brass_firings')->nullable(); // Number of times fired
            $table->boolean('brass_annealed')->default(false);

            // Seating/OAL
            $table->decimal('coal', 5, 3)->nullable(); // Cartridge Overall Length in inches
            $table->decimal('cbto', 5, 3)->nullable(); // Cartridge Base to Ogive in inches
            $table->decimal('jump_to_lands', 5, 3)->nullable(); // Distance to lands in inches

            // Performance data
            $table->integer('muzzle_velocity')->nullable(); // fps
            $table->integer('velocity_es')->nullable(); // Extreme Spread
            $table->integer('velocity_sd')->nullable(); // Standard Deviation
            $table->decimal('group_size', 4, 2)->nullable(); // MOA or inches
            $table->string('group_size_unit')->default('moa'); // 'moa' or 'inches'

            // Testing conditions
            $table->date('tested_date')->nullable();
            $table->integer('tested_distance')->nullable(); // yards or meters
            $table->string('tested_distance_unit')->default('meters');
            $table->decimal('tested_temperature', 4, 1)->nullable(); // Celsius
            $table->integer('tested_altitude')->nullable(); // meters

            // Status
            $table->enum('status', ['development', 'tested', 'approved', 'retired'])->default('development');
            $table->boolean('is_favorite')->default(false);

            // Notes and safety
            $table->text('notes')->nullable();
            $table->boolean('is_max_load')->default(false); // Warning flag
            $table->text('safety_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'calibre_id']);
            $table->index(['user_id', 'user_firearm_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('load_data');
    }
};

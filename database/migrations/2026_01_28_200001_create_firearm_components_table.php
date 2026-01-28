<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates firearm_components table for SAPS 271 canonical component serials.
     * Hard-coded types: barrel, frame, receiver (as per SAPS 271 requirements).
     * 
     * At least ONE serial (barrel, frame, or receiver) must be provided per firearm.
     */
    public function up(): void
    {
        Schema::create('firearm_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firearm_id')->constrained('user_firearms')->cascadeOnDelete();
            
            // Hard-coded component types as per SAPS 271
            $table->enum('type', ['barrel', 'frame', 'receiver']);
            
            // Serial number (required for at least one component)
            $table->string('serial')->nullable();
            
            // Make (optional, for component-specific make if different from firearm)
            $table->string('make')->nullable();
            
            // Notes (optional)
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Index for quick lookup
            $table->index(['firearm_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firearm_components');
    }
};

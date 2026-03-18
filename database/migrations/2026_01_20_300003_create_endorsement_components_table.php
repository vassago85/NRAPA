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
        Schema::create('endorsement_components', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('endorsement_request_id')->constrained()->cascadeOnDelete();

            // Component type
            $table->enum('component_type', [
                'barrel',
                'action',
                'bolt',
                'receiver',
                'frame',
                'slide',
                'cylinder',
                'trigger_group',
                'other',
            ]);

            // Component details
            $table->string('component_description')->nullable();
            $table->string('component_serial')->nullable();
            $table->string('component_make')->nullable();
            $table->string('component_model')->nullable();

            // Calibre for barrels
            $table->foreignId('calibre_id')->nullable()->constrained()->nullOnDelete();
            $table->string('calibre_manual')->nullable();

            // Whether this component relates to the firearm in the request
            $table->boolean('relates_to_firearm')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            // Index
            $table->index('endorsement_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endorsement_components');
    }
};

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
        Schema::create('endorsement_firearms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('endorsement_request_id')->constrained()->cascadeOnDelete();

            // Firearm category aligned with SAPS categories
            $table->enum('firearm_category', [
                'handgun',
                'rifle_manual',
                'rifle_self_loading',
                'shotgun',
            ]);

            // Ignition type
            $table->enum('ignition_type', ['rimfire', 'centerfire'])->nullable();

            // Action type
            $table->enum('action_type', [
                'single_shot',
                'revolver',
                'semi_auto',
                'bolt_action',
                'lever_action',
                'pump_action',
                'break_action',
                'other',
            ])->nullable();

            // Calibre - can link to calibres table or manual entry
            $table->foreignId('calibre_id')->nullable()->constrained()->nullOnDelete();
            $table->string('calibre_manual')->nullable(); // For admin manual entries or other

            // Firearm details (optional, member may not know yet)
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();

            // Licence information
            $table->enum('licence_section', ['13', '15', '16', 'other'])->nullable();
            $table->string('saps_reference')->nullable();
            $table->date('licence_expiry_date')->nullable();

            // Link to user's existing firearm if applicable
            $table->foreignId('user_firearm_id')->nullable()->constrained()->nullOnDelete();

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
        Schema::dropIfExists('endorsement_firearms');
    }
};

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
        Schema::create('affiliated_clubs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Club type determines what dedicated status members receive
            $table->enum('dedicated_type', ['hunter', 'sport', 'both'])->default('both');

            // Custom fee schedule per club
            $table->decimal('initial_fee', 10, 2)->default(0)->comment('Custom sign-up fee for club members');
            $table->decimal('renewal_fee', 10, 2)->default(0)->comment('Custom annual renewal fee for club members');

            // Requirements
            $table->boolean('requires_competency')->default(true)->comment('Require SAPS firearm competency upload');
            $table->unsignedSmallInteger('required_activities_per_year')->default(2)->comment('Number of activities required per year');

            // Contact information
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliated_clubs');
    }
};

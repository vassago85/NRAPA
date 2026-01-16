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
        Schema::create('membership_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // Duration attributes
            $table->enum('duration_type', ['annual', 'lifetime', 'custom'])->default('annual');
            $table->unsignedSmallInteger('duration_months')->nullable()->comment('Null for lifetime');

            // Renewal attributes
            $table->boolean('requires_renewal')->default(true);
            $table->enum('expiry_rule', ['fixed_date', 'rolling', 'none'])->default('rolling');
            $table->unsignedTinyInteger('expiry_month')->nullable()->comment('1-12 for fixed_date');
            $table->unsignedTinyInteger('expiry_day')->nullable()->comment('1-31 for fixed_date');

            // Pricing attributes
            $table->enum('pricing_model', ['annual', 'once_off', 'none'])->default('annual');
            $table->decimal('price', 10, 2)->default(0);

            // Feature attributes
            $table->boolean('allows_dedicated_status')->default(false);
            $table->boolean('requires_knowledge_test')->default(false);
            $table->boolean('discount_eligible')->default(true);

            // Status
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_types');
    }
};

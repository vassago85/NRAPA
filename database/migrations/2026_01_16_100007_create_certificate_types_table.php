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
        Schema::create('certificate_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('template')->default('certificates.default');
            $table->unsignedSmallInteger('validity_months')->nullable()->comment('Null for indefinite');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Pivot table for certificate entitlements per membership type
        Schema::create('certificate_type_membership_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certificate_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('requires_dedicated_status')->default(false);
            $table->boolean('requires_active_membership')->default(true);
            $table->timestamps();

            $table->unique(['membership_type_id', 'certificate_type_id'], 'mt_ct_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_type_membership_type');
        Schema::dropIfExists('certificate_types');
    }
};

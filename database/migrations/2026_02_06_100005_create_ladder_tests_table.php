<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ladder_tests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('load_data_id')->nullable()->constrained('load_data')->nullOnDelete();
            $table->foreignId('user_firearm_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('calibre')->nullable();
            $table->string('bullet_make')->nullable();
            $table->decimal('bullet_weight', 6, 1)->nullable();
            $table->string('bullet_type')->nullable();
            $table->string('powder_type')->nullable();
            $table->string('primer_type')->nullable();
            $table->decimal('start_charge', 5, 1);
            $table->decimal('end_charge', 5, 1);
            $table->decimal('increment', 4, 2);
            $table->unsignedInteger('rounds_per_step')->default(3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('ladder_test_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ladder_test_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('step_number');
            $table->decimal('charge_weight', 5, 1);
            $table->json('velocities')->nullable();
            $table->decimal('group_size', 6, 2)->nullable();
            $table->integer('es')->nullable();
            $table->integer('sd')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('ladder_test_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ladder_test_steps');
        Schema::dropIfExists('ladder_tests');
    }
};

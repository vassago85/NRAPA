<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reloading_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['powder', 'primer', 'bullet', 'brass']);
            $table->string('make');
            $table->string('name');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit'); // grams, units, etc.
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reloading_inventories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reloading_inventory_id')->constrained('reloading_inventories')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['usage', 'restock', 'adjustment', 'ladder_test']);
            $table->decimal('quantity_change', 12, 4); // negative for usage, positive for restock
            $table->decimal('balance_after', 12, 2)->nullable();
            $table->unsignedInteger('rounds')->nullable(); // rounds loaded (if applicable)
            $table->string('source')->nullable(); // e.g., load recipe name, ladder test name
            $table->unsignedBigInteger('source_id')->nullable(); // load_data_id or ladder_test_id
            $table->string('source_type')->nullable(); // App\Models\LoadData, etc.
            $table->string('reason')->nullable(); // manual adjustment reason
            $table->date('logged_at');
            $table->timestamps();

            $table->index(['reloading_inventory_id', 'logged_at']);
            $table->index(['user_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};

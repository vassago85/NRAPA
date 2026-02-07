<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reloading_inventory_id')->constrained('reloading_inventories')->onDelete('cascade');
            $table->decimal('quantity_purchased', 10, 2);        // e.g., 1 (bottle), 2 (boxes)
            $table->decimal('purchase_unit_size', 10, 2);        // e.g., 453.592 (grams in 1lb), 100 (primers per box)
            $table->string('purchase_unit_label', 50);           // e.g., "1lb bottle", "box of 100"
            $table->decimal('quantity_added', 12, 2);            // actual base units added (grams or units)
            $table->decimal('price_paid', 10, 2);                // total Rand paid for this purchase
            $table->decimal('price_per_base_unit', 10, 4);       // calculated: R/gram or R/unit
            $table->date('purchased_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('reloading_inventory_id');
        });

        // Add inventory FK columns to load_data for linking components
        Schema::table('load_data', function (Blueprint $table) {
            $table->foreignId('powder_inventory_id')->nullable()->after('brass_price_per_unit')
                  ->constrained('reloading_inventories')->nullOnDelete();
            $table->foreignId('primer_inventory_id')->nullable()->after('powder_inventory_id')
                  ->constrained('reloading_inventories')->nullOnDelete();
            $table->foreignId('bullet_inventory_id')->nullable()->after('primer_inventory_id')
                  ->constrained('reloading_inventories')->nullOnDelete();
            $table->foreignId('brass_inventory_id')->nullable()->after('bullet_inventory_id')
                  ->constrained('reloading_inventories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('load_data', function (Blueprint $table) {
            $table->dropConstrainedForeignId('powder_inventory_id');
            $table->dropConstrainedForeignId('primer_inventory_id');
            $table->dropConstrainedForeignId('bullet_inventory_id');
            $table->dropConstrainedForeignId('brass_inventory_id');
        });

        Schema::dropIfExists('inventory_purchases');
    }
};

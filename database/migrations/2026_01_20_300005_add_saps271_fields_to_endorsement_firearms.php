<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds fields to align with SAPS 271 Form Section E - Description of Firearm
     */
    public function up(): void
    {
        Schema::table('endorsement_firearms', function (Blueprint $table) {
            // 1.2 - Names and addresses engraved in the metal
            $table->text('metal_engraving')->nullable()->after('action_type');
            
            // 1.4 - Calibre code (official SAPS code)
            $table->string('calibre_code')->nullable()->after('calibre_manual');
            
            // Serial numbers with their makes (SAPS requires at least one)
            // 1.7 - Barrel serial number + 1.8 Make
            $table->string('barrel_serial_number')->nullable()->after('serial_number');
            $table->string('barrel_make')->nullable()->after('barrel_serial_number');
            
            // 1.9 - Frame serial number + 1.10 Make
            $table->string('frame_serial_number')->nullable()->after('barrel_make');
            $table->string('frame_make')->nullable()->after('frame_serial_number');
            
            // 1.11 - Receiver serial number + 1.12 Make
            $table->string('receiver_serial_number')->nullable()->after('frame_make');
            $table->string('receiver_make')->nullable()->after('receiver_serial_number');
            
            // Other action specification (when action_type = 'other')
            $table->string('action_other_specify')->nullable()->after('action_type');
            
            // Hand Machine Carbine and Combination types
            // Update the category field to allow additional SAPS types
        });

        // Need to modify enum - safest way in MySQL is to use raw SQL
        // For now we'll handle these through the 'other' categories in code
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('endorsement_firearms', function (Blueprint $table) {
            $table->dropColumn([
                'metal_engraving',
                'calibre_code',
                'barrel_serial_number',
                'barrel_make',
                'frame_serial_number',
                'frame_make',
                'receiver_serial_number',
                'receiver_make',
                'action_other_specify',
            ]);
        });
    }
};

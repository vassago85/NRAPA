<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migrates existing serial_number from user_firearms to receiver component
     * in firearm_components table (non-destructive - keeps serial_number column).
     * 
     * This ensures backwards compatibility while moving to canonical structure.
     */
    public function up(): void
    {
        // Get all firearms with serial_number but no receiver component
        $firearms = DB::table('user_firearms')
            ->whereNotNull('serial_number')
            ->where('serial_number', '!=', '')
            ->get();
        
        foreach ($firearms as $firearm) {
            // Check if receiver component already exists
            $existingReceiver = DB::table('firearm_components')
                ->where('firearm_id', $firearm->id)
                ->where('type', 'receiver')
                ->first();
            
            // Only create if receiver doesn't exist
            if (!$existingReceiver) {
                DB::table('firearm_components')->insert([
                    'firearm_id' => $firearm->id,
                    'type' => 'receiver',
                    'serial' => $firearm->serial_number,
                    'make' => null, // Preserve original make in firearm.make
                    'notes' => 'Migrated from legacy serial_number field',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This does NOT delete the components, just documents the reverse.
     * In practice, you may want to keep the components even if rolling back.
     */
    public function down(): void
    {
        // Migration is data-only and non-destructive
        // Components remain in place for data safety
    }
};

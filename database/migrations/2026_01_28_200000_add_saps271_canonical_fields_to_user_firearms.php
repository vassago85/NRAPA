<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds SAPS 271 canonical firearm identity fields to user_firearms:
     * - firearm_type enum (rifle|shotgun|handgun|hand_machine_carbine|combination)
     * - action enum (semi_automatic|automatic|manual|other)
     * - other_action_text (nullable, for action='other')
     * - calibre_code (nullable, SAPS code)
     * 
     * Note: serial_number remains for backwards compatibility but will be migrated to components.
     */
    public function up(): void
    {
        // Check which columns already exist
        $hasFirearmType = Schema::hasColumn('user_firearms', 'firearm_type');
        $hasAction = Schema::hasColumn('user_firearms', 'action');
        $hasOtherActionText = Schema::hasColumn('user_firearms', 'other_action_text');
        $hasCalibreCode = Schema::hasColumn('user_firearms', 'calibre_code');
        
        if (!$hasFirearmType || !$hasAction || !$hasOtherActionText || !$hasCalibreCode) {
            Schema::table('user_firearms', function (Blueprint $table) use ($hasFirearmType, $hasAction, $hasOtherActionText, $hasCalibreCode) {
                // SAPS 271 canonical firearm type (replaces/extends firearm_type_id FK)
                if (!$hasFirearmType) {
                    $table->enum('firearm_type', [
                        'rifle',
                        'shotgun', 
                        'handgun',
                        'hand_machine_carbine',
                        'combination'
                    ])->nullable()->after('firearm_type_id');
                }
                
                // SAPS 271 action type
                if (!$hasAction) {
                    $table->enum('action', [
                        'semi_automatic',
                        'automatic',
                        'manual',
                        'other'
                    ])->nullable()->after('firearm_type');
                }
                
                // Other action specification (when action = 'other')
                if (!$hasOtherActionText) {
                    $table->string('other_action_text')->nullable()->after('action');
                }
                
                // SAPS calibre code (in addition to calibre_id FK)
                if (!$hasCalibreCode) {
                    $table->string('calibre_code')->nullable()->after('calibre_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_firearms', function (Blueprint $table) {
            $table->dropColumn([
                'firearm_type',
                'action',
                'other_action_text',
                'calibre_code',
            ]);
        });
    }
};

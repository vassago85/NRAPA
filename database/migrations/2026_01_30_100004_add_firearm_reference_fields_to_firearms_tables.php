<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds firearm reference FK fields to existing firearms tables:
     * - user_firearms
     * - endorsement_firearms
     *
     * Also adds SAPS 271 fields for serial numbers and makes.
     */
    public function up(): void
    {
        // Update user_firearms table
        Schema::table('user_firearms', function (Blueprint $table) {
            // Reference FKs
            $table->foreignId('firearm_calibre_id')->nullable()->after('calibre_id')->constrained('firearm_calibres')->nullOnDelete();
            $table->foreignId('firearm_make_id')->nullable()->after('make')->constrained('firearm_makes')->nullOnDelete();
            $table->foreignId('firearm_model_id')->nullable()->after('model')->constrained('firearm_models')->nullOnDelete();

            // Override fields (when reference not found)
            $table->string('calibre_text_override')->nullable()->after('firearm_calibre_id');
            $table->string('make_text_override')->nullable()->after('firearm_make_id');
            $table->string('model_text_override')->nullable()->after('firearm_model_id');

            // SAPS 271 serial number fields
            $table->string('barrel_serial_number')->nullable()->after('serial_number');
            $table->string('barrel_make_text')->nullable()->after('barrel_serial_number');
            $table->string('frame_serial_number')->nullable()->after('barrel_make_text');
            $table->string('frame_make_text')->nullable()->after('frame_serial_number');
            $table->string('receiver_serial_number')->nullable()->after('frame_make_text');
            $table->string('receiver_make_text')->nullable()->after('receiver_serial_number');

            // SAPS 271 engraved text
            $table->text('engraved_text')->nullable()->after('receiver_make_text')->comment('Names and addresses engraved in the metal');
        });

        // Update endorsement_firearms table
        Schema::table('endorsement_firearms', function (Blueprint $table) {
            // Reference FKs
            $table->foreignId('firearm_calibre_id')->nullable()->after('calibre_id')->constrained('firearm_calibres')->nullOnDelete();
            $table->foreignId('firearm_make_id')->nullable()->after('make')->constrained('firearm_makes')->nullOnDelete();
            $table->foreignId('firearm_model_id')->nullable()->after('model')->constrained('firearm_models')->nullOnDelete();

            // Override fields
            $table->string('calibre_text_override')->nullable()->after('firearm_calibre_id');
            $table->string('make_text_override')->nullable()->after('firearm_make_id');
            $table->string('model_text_override')->nullable()->after('firearm_model_id');

            // SAPS 271 serial number fields (if not already present)
            if (! Schema::hasColumn('endorsement_firearms', 'barrel_serial_number')) {
                $table->string('barrel_serial_number')->nullable()->after('serial_number');
                $table->string('barrel_make_text')->nullable()->after('barrel_serial_number');
                $table->string('frame_serial_number')->nullable()->after('barrel_make_text');
                $table->string('frame_make_text')->nullable()->after('frame_serial_number');
                $table->string('receiver_serial_number')->nullable()->after('frame_make_text');
                $table->string('receiver_make_text')->nullable()->after('receiver_serial_number');
            }

            // SAPS 271 engraved text
            if (! Schema::hasColumn('endorsement_firearms', 'metal_engraving')) {
                $table->text('metal_engraving')->nullable()->after('receiver_make_text')->comment('Names and addresses engraved in the metal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_firearms', function (Blueprint $table) {
            $table->dropForeign(['firearm_calibre_id']);
            $table->dropForeign(['firearm_make_id']);
            $table->dropForeign(['firearm_model_id']);
            $table->dropColumn([
                'firearm_calibre_id',
                'firearm_make_id',
                'firearm_model_id',
                'calibre_text_override',
                'make_text_override',
                'model_text_override',
                'barrel_serial_number',
                'barrel_make_text',
                'frame_serial_number',
                'frame_make_text',
                'receiver_serial_number',
                'receiver_make_text',
                'engraved_text',
            ]);
        });

        Schema::table('endorsement_firearms', function (Blueprint $table) {
            $table->dropForeign(['firearm_calibre_id']);
            $table->dropForeign(['firearm_make_id']);
            $table->dropForeign(['firearm_model_id']);
            $table->dropColumn([
                'firearm_calibre_id',
                'firearm_make_id',
                'firearm_model_id',
                'calibre_text_override',
                'make_text_override',
                'model_text_override',
            ]);
        });
    }
};

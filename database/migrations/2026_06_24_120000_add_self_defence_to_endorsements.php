<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a self-defence variant to the existing endorsement. The new
     * endorsement_type discriminator defaults to 'dedicated_status' so all
     * existing letters keep behaving exactly as before. The self-defence
     * firearm fields live inline (nullable) on the request because the
     * self-defence flow does not use the SAPS-271 EndorsementFirearm record.
     */
    public function up(): void
    {
        Schema::table('endorsement_requests', function (Blueprint $table) {
            $table->string('endorsement_type')->default('dedicated_status')->after('request_type');

            // Self-defence firearm details (only populated when endorsement_type = self_defence)
            $table->string('firearm_make')->nullable()->after('letter_file_path');
            $table->string('firearm_model')->nullable()->after('firearm_make');
            $table->string('firearm_calibre')->nullable()->after('firearm_model');
            $table->string('firearm_type')->nullable()->after('firearm_calibre'); // handgun | rifle | shotgun
            $table->string('firearm_serial')->nullable()->after('firearm_type');
            $table->text('motivation_note')->nullable()->after('firearm_serial');

            $table->index('endorsement_type');
        });

        // Belt-and-braces: make sure every existing row is explicitly marked as
        // the original variant (default already handles new rows).
        DB::table('endorsement_requests')->update(['endorsement_type' => 'dedicated_status']);

        // Per-clause acknowledgement audit trail. One row per ticked clause,
        // written once at submission time and never edited afterwards.
        Schema::create('endorsement_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endorsement_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('clause_key');
            $table->text('clause_text');
            $table->boolean('accepted')->default(true);
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('endorsement_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endorsement_acknowledgements');

        Schema::table('endorsement_requests', function (Blueprint $table) {
            $table->dropIndex(['endorsement_type']);
            $table->dropColumn([
                'endorsement_type',
                'firearm_make',
                'firearm_model',
                'firearm_calibre',
                'firearm_type',
                'firearm_serial',
                'motivation_note',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Simplifies the activity model by:
     * - Replacing dedicated_type with track (hunting|sport) in activity_types
     * - Adding group field to activity_types for UI grouping
     * - Adding track field to shooting_activities
     * - Creating activity_tags table (optional tags)
     * - Keeping event_categories and event_types for historical data mapping
     */
    public function up(): void
    {
        // Update activity_types table
        Schema::table('activity_types', function (Blueprint $table) {
            // Add new fields
            $table->enum('track', ['hunting', 'sport'])->nullable()->after('description');
            $table->string('group')->nullable()->after('track'); // UI-only grouping (e.g., "Training", "Competitions", "Hunting")
            
            // Keep dedicated_type for now (we'll migrate data then drop it)
        });

        // Migrate existing dedicated_type to track
        DB::statement("
            UPDATE activity_types 
            SET track = CASE 
                WHEN dedicated_type = 'hunter' THEN 'hunting'
                WHEN dedicated_type = 'sport_shooter' THEN 'sport'
                WHEN dedicated_type = 'both' THEN NULL  -- These will need manual review
                ELSE NULL
            END
        ");

        // Create activity_tags table (optional tags like PRS, IPSC, IDPA)
        Schema::create('activity_tags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'prs', 'ipsc', 'idpa'
            $table->string('label'); // e.g., 'PRS', 'IPSC', 'IDPA'
            $table->enum('track', ['hunting', 'sport'])->nullable(); // If set, only shown for that track
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Add track field to shooting_activities (for new records)
        Schema::table('shooting_activities', function (Blueprint $table) {
            $table->enum('track', ['hunting', 'sport'])->nullable()->after('activity_type_id');
            
            // Keep event_category_id and event_type_id for historical data
            // They will be nullable and used for data migration/mapping
        });

        // Create pivot table for activity_tags (many-to-many with shooting_activities)
        Schema::create('activity_tag_shooting_activity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shooting_activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['shooting_activity_id', 'activity_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_tag_shooting_activity');
        Schema::dropIfExists('activity_tags');
        
        Schema::table('shooting_activities', function (Blueprint $table) {
            $table->dropColumn('track');
        });
        
        Schema::table('activity_types', function (Blueprint $table) {
            $table->dropColumn(['track', 'group']);
        });
    }
};

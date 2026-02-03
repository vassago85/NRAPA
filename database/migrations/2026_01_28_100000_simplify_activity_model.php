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
        if (Schema::hasTable('activity_types')) {
            // Check columns before modifying table
            $hasTrack = Schema::hasColumn('activity_types', 'track');
            $hasGroup = Schema::hasColumn('activity_types', 'group');
            
            if (!$hasTrack || !$hasGroup) {
                Schema::table('activity_types', function (Blueprint $table) use ($hasTrack, $hasGroup) {
                    // Add new fields only if they don't exist
                    if (!$hasTrack) {
                        $table->enum('track', ['hunting', 'sport'])->nullable()->after('description');
                    }
                    if (!$hasGroup) {
                        $table->string('group')->nullable()->after('track'); // UI-only grouping (e.g., "Training", "Competitions", "Hunting")
                    }
                });
            }

            // Migrate existing dedicated_type to track (only if track column exists and has NULL values)
            if (Schema::hasColumn('activity_types', 'dedicated_type') && Schema::hasColumn('activity_types', 'track')) {
                DB::statement("
                    UPDATE activity_types 
                    SET track = CASE 
                        WHEN dedicated_type = 'hunter' AND track IS NULL THEN 'hunting'
                        WHEN dedicated_type = 'sport_shooter' AND track IS NULL THEN 'sport'
                        WHEN dedicated_type = 'both' AND track IS NULL THEN NULL  -- These will need manual review
                        ELSE track
                    END
                ");
            }
        }

        // Create activity_tags table (optional tags like PRS, IPSC, IDPA)
        if (!Schema::hasTable('activity_tags')) {
            Schema::create('activity_tags', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique(); // e.g., 'prs', 'ipsc', 'idpa'
                $table->string('label'); // e.g., 'PRS', 'IPSC', 'IDPA'
                $table->enum('track', ['hunting', 'sport'])->nullable(); // If set, only shown for that track
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Add track field to shooting_activities (for new records)
        if (Schema::hasTable('shooting_activities')) {
            Schema::table('shooting_activities', function (Blueprint $table) {
                if (!Schema::hasColumn('shooting_activities', 'track')) {
                    $table->enum('track', ['hunting', 'sport'])->nullable()->after('activity_type_id');
                }
            });
        }

        // Create pivot table for activity_tags (many-to-many with shooting_activities)
        if (!Schema::hasTable('activity_tag_shooting_activity')) {
            Schema::create('activity_tag_shooting_activity', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shooting_activity_id')->constrained()->cascadeOnDelete();
                $table->foreignId('activity_tag_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                
                // Use shorter name to comply with MySQL's 64-character identifier limit
                $table->unique(['shooting_activity_id', 'activity_tag_id'], 'at_sa_unique');
            });
        }
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

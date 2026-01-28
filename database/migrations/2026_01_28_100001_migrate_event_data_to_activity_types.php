<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Maps existing event_category and event_type data to activity_types.
     * This preserves historical data while moving to the simplified model.
     */
    public function up(): void
    {
        // Map event categories to activity types
        // Strategy: Create activity types from event categories that don't have a parent activity type
        
        // For event categories with activity_type_id, use that activity type
        // For event categories without activity_type_id, create new activity types
        
        $eventCategories = DB::table('event_categories')->get();
        
        foreach ($eventCategories as $eventCategory) {
            if ($eventCategory->activity_type_id) {
                // Update the parent activity type's track based on event category's dedicated_type
                $track = match($eventCategory->dedicated_type) {
                    'hunter' => 'hunting',
                    'sport' => 'sport',
                    'both' => null,
                    default => null,
                };
                
                if ($track) {
                    DB::table('activity_types')
                        ->where('id', $eventCategory->activity_type_id)
                        ->update(['track' => $track]);
                }
            } else {
                // Create a new activity type from this event category
                $track = match($eventCategory->dedicated_type) {
                    'hunter' => 'hunting',
                    'sport' => 'sport',
                    'both' => null,
                    default => null,
                };
                
                $activityTypeId = DB::table('activity_types')->insertGetId([
                    'slug' => $eventCategory->slug,
                    'name' => $eventCategory->name,
                    'description' => $eventCategory->description,
                    'track' => $track,
                    'group' => null, // Can be set manually later
                    'is_active' => $eventCategory->is_active,
                    'sort_order' => $eventCategory->sort_order,
                    'dedicated_type' => $eventCategory->dedicated_type, // Keep for now
                    'created_at' => $eventCategory->created_at,
                    'updated_at' => $eventCategory->updated_at,
                ]);
                
                // Update event_category to point to new activity type
                DB::table('event_categories')
                    ->where('id', $eventCategory->id)
                    ->update(['activity_type_id' => $activityTypeId]);
            }
        }
        
        // Map event types to activity tags
        // Strategy: Create activity tags from event types (these become optional tags)
        
        $eventTypes = DB::table('event_types')->get();
        
        foreach ($eventTypes as $eventType) {
            // Determine track from parent event category
            $eventCategory = DB::table('event_categories')
                ->where('id', $eventType->event_category_id)
                ->first();
            
            $track = null;
            if ($eventCategory) {
                $track = match($eventCategory->dedicated_type) {
                    'hunter' => 'hunting',
                    'sport' => 'sport',
                    default => null,
                };
            }
            
            // Create activity tag
            DB::table('activity_tags')->insert([
                'key' => $eventType->slug,
                'label' => $eventType->name,
                'track' => $track,
                'is_active' => $eventType->is_active,
                'sort_order' => $eventType->sort_order,
                'created_at' => $eventType->created_at,
                'updated_at' => $eventType->updated_at,
            ]);
        }
        
        // Update existing shooting_activities to set track based on activity_type
        DB::statement("
            UPDATE shooting_activities sa
            INNER JOIN activity_types at ON sa.activity_type_id = at.id
            SET sa.track = at.track
            WHERE sa.track IS NULL AND at.track IS NOT NULL
        ");
        
        // For shooting_activities with event_category_id but no activity_type_id,
        // try to infer track from event_category
        DB::statement("
            UPDATE shooting_activities sa
            INNER JOIN event_categories ec ON sa.event_category_id = ec.id
            INNER JOIN activity_types at ON ec.activity_type_id = at.id
            SET sa.track = at.track, sa.activity_type_id = at.id
            WHERE sa.activity_type_id IS NULL AND at.track IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is data-only, no schema changes to reverse
        // The data mapping is one-way for historical preservation
    }
};

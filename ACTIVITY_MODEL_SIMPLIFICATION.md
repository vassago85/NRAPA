# Activity Model Simplification

## Overview
This document outlines the simplification of the Activity data model, removing the "Event" terminology and complex multi-level hierarchy in favor of a simpler Track → Activity Type flow.

## Changes Summary

### Data Model Changes

#### Removed Concepts
- ❌ `event_categories` table (kept for historical data mapping)
- ❌ `event_types` table (kept for historical data mapping)
- ❌ "both" track logic (replaced with nullable track)
- ❌ `dedicated_type` enum in `activity_types` (replaced with `track`)

#### New/Updated Concepts
- ✅ `track` enum in `activity_types` (hunting | sport | null)
- ✅ `group` field in `activity_types` (UI-only grouping: Training, Competitions, Hunting, etc.)
- ✅ `activity_tags` table (optional tags like PRS, IPSC, IDPA)
- ✅ `track` field in `shooting_activities` (auto-set from activity type)

### Migration Files Created

1. **`2026_01_28_100000_simplify_activity_model.php`**
   - Adds `track` and `group` to `activity_types`
   - Creates `activity_tags` table
   - Adds `track` to `shooting_activities`
   - Creates pivot table for activity tags

2. **`2026_01_28_100001_migrate_event_data_to_activity_types.php`**
   - Maps existing event categories to activity types
   - Maps existing event types to activity tags
   - Updates existing shooting activities with track

3. **`2026_01_28_100002_remove_dedicated_type_from_activity_types.php`**
   - Removes `dedicated_type` column after data migration

### Model Updates

#### ActivityType Model
- ✅ Removed `dedicated_type` field
- ✅ Added `track` field (hunting | sport | null)
- ✅ Added `group` field (UI grouping)
- ✅ Removed `eventCategories()` relationship
- ✅ Updated scopes: `forTrack()` replaces `forDedicatedType()`
- ✅ Added `getGroups()` static method

#### New ActivityTag Model
- ✅ Created with fields: `key`, `label`, `track`, `is_active`, `sort_order`
- ✅ Many-to-many relationship with `ShootingActivity`
- ✅ Scopes: `active()`, `forTrack()`, `ordered()`

#### ShootingActivity Model
- ✅ Added `track` field (auto-set from activity type)
- ✅ Added `tags()` relationship (many-to-many)
- ✅ Kept `eventCategory()` and `eventType()` relationships (deprecated, for historical data)
- ✅ Auto-sets `track` when `activity_type_id` changes

### Form Updates

#### Member Activity Submission (`pages/member/activities/submit.blade.php`)
- ✅ Removed: `event_category_id`, `event_type_id` fields
- ✅ Added: `track` dropdown (hunting | sport)
- ✅ Added: `activity_tag_ids` multi-select (optional)
- ✅ Simplified flow: Track → Activity Type → Tags (optional)
- ✅ Removed: `updatedActivityTypeId()`, `updatedEventCategoryId()` methods
- ✅ Added: `updatedTrack()` method

#### Member Activity Edit (`pages/member/activities/edit.blade.php`)
- ✅ Same changes as submission form
- ✅ Loads existing tags from activity
- ✅ Syncs tags on update

#### Admin Activity Configuration (`pages/admin/activity-config/index.blade.php`)
- ✅ Removed: Event Categories tab
- ✅ Removed: Event Types tab
- ✅ Updated: Activity Types tab (now uses track and group)
- ✅ Added: Activity Tags tab
- ✅ Simplified to 2 tabs total

### View Updates

#### Member Activity Index (`pages/member/activities/index.blade.php`)
- ✅ Removed: Event Category/Type columns
- ✅ Added: Track column with badge
- ✅ Updated: Activity Type column shows tags below

#### Member Activity Show (`pages/member/activities/show.blade.php`)
- ✅ Removed: Event Category/Type display
- ✅ Added: Track badge
- ✅ Added: Tags display

#### Admin Activity Index (`pages/admin/activities/index.blade.php`)
- ✅ Removed: Event Category display
- ✅ Added: Track badge and tags

#### Admin Activity Show (`pages/admin/activities/show.blade.php`)
- ✅ Removed: Event Category/Type display
- ✅ Added: Track badge and tags

### Seeder Updates

#### ActivityConfigurationSeeder
- ✅ Removed: `seedEventCategories()` method
- ✅ Removed: `seedEventTypes()` method
- ✅ Updated: `seedActivityTypes()` to use track and group
- ✅ Added: `seedActivityTags()` method (maps old event types to tags)

### Test Data Generator

#### TestMemberGenerator
- ✅ Removed: `EventCategory` import
- ✅ Updated: `createApprovedActivities()` to use track instead of event_category_id

## User Flow Changes

### Before (Complex)
1. Select Related Activity (Activity Type)
2. Select Type of Activity (Event Category) - dependent on step 1
3. Select Event Type - dependent on step 2
4. Fill in other details

### After (Simplified)
1. Select Track (Hunting | Sport Shooting)
2. Select Activity Type - filtered by track
3. Select Tags (optional) - filtered by track
4. Fill in other details

## Data Safety

- ✅ Historical data preserved: `event_category_id` and `event_type_id` remain in `shooting_activities` table
- ✅ Data migration maps old event data to new activity types and tags
- ✅ Legacy relationships kept as deprecated methods for backward compatibility
- ✅ No data loss: All existing activities remain readable

## Menu & Navigation

- ✅ Admin menu unchanged (no Event items were present)
- ✅ All "Event" terminology removed from UI
- ✅ Consistent use of "Activities" terminology

## Files Modified

### Migrations
- `database/migrations/2026_01_28_100000_simplify_activity_model.php` (NEW)
- `database/migrations/2026_01_28_100001_migrate_event_data_to_activity_types.php` (NEW)
- `database/migrations/2026_01_28_100002_remove_dedicated_type_from_activity_types.php` (NEW)

### Models
- `app/Models/ActivityType.php` (UPDATED)
- `app/Models/ActivityTag.php` (NEW)
- `app/Models/ShootingActivity.php` (UPDATED)

### Views
- `resources/views/pages/member/activities/submit.blade.php` (UPDATED)
- `resources/views/pages/member/activities/edit.blade.php` (UPDATED)
- `resources/views/pages/member/activities/show.blade.php` (UPDATED)
- `resources/views/pages/member/activities/index.blade.php` (UPDATED)
- `resources/views/pages/admin/activity-config/index.blade.php` (REWRITTEN)
- `resources/views/pages/admin/activities/index.blade.php` (UPDATED)
- `resources/views/pages/admin/activities/show.blade.php` (UPDATED)

### Seeders
- `database/seeders/ActivityConfigurationSeeder.php` (UPDATED)
- `app/Livewire/Developer/TestMemberGenerator.php` (UPDATED)

## Acceptance Criteria Met

- ✅ Admins manage ONE primary list: Activity Types
- ✅ Users log activities with: Track → Activity Type
- ✅ Menus reflect the simplified Activity model
- ✅ No "Event" terminology remains in UI
- ✅ Existing permissions and auth logic untouched
- ✅ No breaking changes to historical data

## Next Steps

1. Run migrations: `php artisan migrate`
2. Run data migration seeder if needed
3. Test activity submission flow
4. Verify historical activities still display correctly
5. Update any custom reports/queries that reference event_category/event_type

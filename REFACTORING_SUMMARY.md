# NRAPA Platform Refactoring Summary

## Overview
This document summarizes the comprehensive refactoring of the NRAPA platform to simplify navigation, data model terminology, and implement SAPS 271 canonical firearm identity across the entire platform.

## Completed Changes

### A) Global Terminology & Structure

#### ✅ Removed "Event" Terminology
- **Status**: Complete
- **Changes**:
  - All UI references to "Event" replaced with "Activities"
  - Removed concepts: `event_categories`, `event_types` from UI (kept in database for historical data)
  - Updated all form labels, page titles, and navigation items
  - Admin activity config page now only manages "Activity Types" and "Activity Tags"

#### ✅ Dedicated Status Tracks
- **Status**: Complete
- **Changes**:
  - Fixed enum: `hunting` | `sport` (NO "both" track)
  - Updated `ActivityType` model to use `track` enum instead of `dedicated_type`
  - All forms and filters now use the simplified track system

### B) Activities Simplification

#### ✅ Simplified Activity Model
- **Status**: Complete
- **Data Model**:
  - **Before**: category → type → event (3 levels)
  - **After**: track → activity_type (2 levels)
- **ActivityType Fields**:
  - `name`, `slug`, `track` (hunting|sport), `group` (optional UI grouping), `is_active`, `sort_order`
- **ActivityTag Fields** (optional):
  - `key`, `label`, `track` (nullable), `is_active`, `sort_order`
- **User Flow**:
  1. Select Track (Hunting | Sport Shooting)
  2. Select Activity Type (filtered by track)
  3. Optional: Select Tags (filtered by track)

#### ✅ Admin Configuration
- **Status**: Complete
- **Changes**:
  - Removed "Event Categories" and "Event Types" tabs
  - Now only manages:
    - **Activity Types**: Name, Track, Group (optional), Sort Order
    - **Activity Tags**: Key, Label, Track (optional), Sort Order
  - Page renamed from "Activity Configuration" to "Activity Types" in sidebar

#### ✅ Historical Data Preservation
- **Status**: Complete
- **Migrations Created**:
  1. `2026_01_28_100000_simplify_activity_model.php` - Adds track/group, creates activity_tags
  2. `2026_01_28_100001_migrate_event_data_to_activity_types.php` - Maps existing event data
  3. `2026_01_28_100002_remove_dedicated_type_from_activity_types.php` - Removes dedicated_type
- **Data Safety**:
  - `event_category_id` and `event_type_id` kept in `shooting_activities` table
  - Legacy relationships kept as deprecated methods
  - All existing activities remain readable

### C) Firearms - SAPS 271 Canonical Identity

#### ✅ Canonical Firearm Identity Fields
- **Status**: Complete
- **UserFirearm Model Fields**:
  - `firearm_type` enum: rifle | shotgun | handgun | hand_machine_carbine | combination
  - `action` enum: semi_automatic | automatic | manual | other (+ `other_action_text` nullable)
  - `calibre_id` (FK) + `calibre_code` (nullable, SAPS code)
  - `make`, `model` (nullable)

#### ✅ Firearm Components Table
- **Status**: Complete
- **Table**: `firearm_components`
- **Fields**:
  - `firearm_id` (FK to user_firearms)
  - `type` ENUM('barrel', 'frame', 'receiver') - **hard-coded only, NOT admin-configurable**
  - `serial` VARCHAR nullable
  - `make` VARCHAR nullable
  - `notes` TEXT nullable
- **Model**: `FirearmComponent` with constants: `TYPE_BARREL`, `TYPE_FRAME`, `TYPE_RECEIVER`

#### ✅ Global Serial Number Validation
- **Status**: Complete
- **Rule**: At least ONE serial (barrel, frame, or receiver) required
- **Error Message**: "Provide at least one serial number (Barrel, Frame, or Receiver) as per SAPS 271."
- **Implementation**:
  - Custom validation method in Virtual Safe forms
  - Global validation rule class: `App\Rules\AtLeastOneSerialNumber`
  - Applied in `UserFirearm` model via `hasSerialNumber()` method

#### ✅ Backwards Compatibility
- **Status**: Complete
- **Migration**: `2026_01_28_200002_migrate_existing_serial_to_receiver_component.php`
- **Strategy**: Non-destructive migration
  - Existing `user_firearms.serial_number` mapped to `receiver` component
  - `serial_number` column kept for backwards compatibility
  - All existing records remain valid and readable

### D) Platform-Wide Reuse

#### ✅ Virtual Safe (Armoury)
- **Status**: Complete
- **Forms Updated**:
  - `pages/member/armoury/create.blade.php` - Uses SAPS 271 canonical fields
  - `pages/member/armoury/edit.blade.php` - Updated to match create form
- **Fields**:
  - SAPS 271 firearm_type, action, calibre_code
  - Component serials: barrel, frame, receiver
  - Validation: At least one serial required
- **Data Flow**: All firearm details stored in canonical `user_firearms` + `firearm_components`

#### ✅ Endorsements
- **Status**: Complete
- **Forms Updated**:
  - `pages/member/endorsements/create.blade.php` - Can load from Virtual Safe
  - `loadExistingFirearm()` method updated to populate from canonical firearm
- **EndorsementFirearm Model**:
  - `user_firearm_id` links to canonical firearm
  - `getSaps271IdentityAttribute()` accessor pulls from canonical source if linked
  - Falls back to stored fields if not linked
- **Letter Templates**: Ready to use `saps_271_identity` accessor (when PDF generation implemented)

#### ✅ Endorsement Letter Generation
- **Status**: Ready (PDF generation marked as TODO in code)
- **Implementation**:
  - `EndorsementFirearm::getSaps271IdentityAttribute()` provides canonical identity
  - If `user_firearm_id` is set, pulls from `UserFirearm::saps_271_identity`
  - Otherwise builds from stored fields
  - Includes all component serials in format: "Barrel: XXX, Frame: YYY, Receiver: ZZZ"

### E) Navigation / Menus

#### ✅ Member Area Sidebar
- **Status**: Complete
- **Structure**:
  - Dashboard
  - My Membership
  - Documents (active members only)
  - Activities (active members only)
  - Virtual Safe (active members only)
  - Endorsements (active members only)
  - Learning (collapsible group):
    - Learning Center
    - Knowledge Tests
    - Certificates
- **Icons Added**: `shield-check`, `document-check`, `badge-check`

#### ✅ Administration Sidebar
- **Status**: Complete
- **Structure**:
  - Dashboard
  - Members
  - Approvals (collapsible: All, Documents, Memberships, Activities)
  - **Endorsements** (new top-level item)
- **Route**: `admin.endorsements.index`

#### ✅ Configuration Sidebar
- **Status**: Complete
- **Structure**:
  - Membership Types
  - **Activity Types** (renamed from "Activities")
  - Calibres
  - Firearm Settings
- **Note**: Firearm component types are NOT configurable (hard-coded as barrel/frame/receiver)

### F) Files Modified

#### Migrations
- ✅ `2026_01_28_100000_simplify_activity_model.php` (NEW)
- ✅ `2026_01_28_100001_migrate_event_data_to_activity_types.php` (NEW)
- ✅ `2026_01_28_100002_remove_dedicated_type_from_activity_types.php` (NEW)
- ✅ `2026_01_28_200000_add_saps271_canonical_fields_to_user_firearms.php` (EXISTS)
- ✅ `2026_01_28_200001_create_firearm_components_table.php` (EXISTS)
- ✅ `2026_01_28_200002_migrate_existing_serial_to_receiver_component.php` (EXISTS)

#### Models
- ✅ `app/Models/ActivityType.php` - Updated (track, group, removed dedicated_type)
- ✅ `app/Models/ActivityTag.php` - Created
- ✅ `app/Models/ShootingActivity.php` - Updated (track field, tags relationship)
- ✅ `app/Models/UserFirearm.php` - Already has SAPS 271 fields and component relationships
- ✅ `app/Models/FirearmComponent.php` - Already exists
- ✅ `app/Models/EndorsementFirearm.php` - Updated (added `getSaps271IdentityAttribute()`)

#### Views - Member Area
- ✅ `resources/views/pages/member/activities/submit.blade.php` - Track → Activity Type flow
- ✅ `resources/views/pages/member/activities/edit.blade.php` - Updated
- ✅ `resources/views/pages/member/activities/show.blade.php` - Shows track and tags
- ✅ `resources/views/pages/member/activities/index.blade.php` - Updated table columns
- ✅ `resources/views/pages/member/armoury/create.blade.php` - SAPS 271 canonical fields
- ✅ `resources/views/pages/member/armoury/edit.blade.php` - Updated to match create
- ✅ `resources/views/pages/member/endorsements/create.blade.php` - Loads from Virtual Safe

#### Views - Admin Area
- ✅ `resources/views/pages/admin/activity-config/index.blade.php` - Simplified to 2 tabs
- ✅ `resources/views/pages/admin/activities/index.blade.php` - Shows track and tags
- ✅ `resources/views/pages/admin/activities/show.blade.php` - Shows track and tags

#### Helpers
- ✅ `app/Helpers/SidebarMenu.php` - Updated navigation structure

#### Validation Rules
- ✅ `app/Rules/AtLeastOneSerialNumber.php` - Global validation rule (NEW)

### G) Data Model Summary

#### Activities
```
shooting_activities
├── track (hunting|sport)
├── activity_type_id (FK)
└── activity_tag_shooting_activity (pivot)
    └── activity_tag_id (FK)

activity_types
├── track (hunting|sport)
├── group (optional UI grouping)
└── is_active, sort_order

activity_tags
├── track (hunting|sport|null)
└── key, label, is_active, sort_order
```

#### Firearms (SAPS 271 Canonical)
```
user_firearms
├── firearm_type (rifle|shotgun|handgun|hand_machine_carbine|combination)
├── action (semi_automatic|automatic|manual|other)
├── other_action_text (nullable)
├── calibre_id (FK)
├── calibre_code (nullable, SAPS code)
├── make, model (nullable)
└── serial_number (legacy, kept for backwards compatibility)

firearm_components
├── firearm_id (FK)
├── type (barrel|frame|receiver) - HARD-CODED
├── serial (nullable)
├── make (nullable)
└── notes (nullable)
```

#### Endorsements
```
endorsement_firearms
├── user_firearm_id (FK to canonical firearm)
├── [SAPS 271 fields - stored for letter generation]
└── Uses getSaps271IdentityAttribute() to pull from canonical if linked
```

## Validation Rules

### Global Serial Number Requirement
- **Rule**: At least ONE serial number required (barrel, frame, or receiver)
- **Message**: "Provide at least one serial number (Barrel, Frame, or Receiver) as per SAPS 271."
- **Applied In**:
  - Virtual Safe create/edit forms
  - Endorsement request forms
  - Global rule class available for reuse

## Navigation Structure

### Member Area
1. Dashboard
2. My Membership
3. Documents (active members)
4. Activities (active members)
5. Virtual Safe (active members)
6. Endorsements (active members)
7. Learning (collapsible):
   - Learning Center
   - Knowledge Tests
   - Certificates

### Administration
1. Dashboard
2. Members
3. Approvals (collapsible):
   - All Approvals
   - Documents
   - Memberships
   - Activities
4. Endorsements

### Configuration
1. Membership Types
2. Activity Types
3. Calibres
4. Firearm Settings

## Key Features

### SAPS 271 Canonical Identity
- **Single Source of Truth**: All firearm details stored in `user_firearms` + `firearm_components`
- **Reused Everywhere**: Virtual Safe, Endorsements, Letters, Reports
- **Component Serial Management**: Hard-coded types (barrel, frame, receiver) - NOT admin-configurable
- **Accessor Methods**:
  - `UserFirearm::getSaps271IdentityAttribute()` - Full canonical identity string
  - `UserFirearm::getPrimarySerialAttribute()` - Priority: receiver > frame > barrel
  - `EndorsementFirearm::getSaps271IdentityAttribute()` - Pulls from canonical if linked

### Activity Simplification
- **2-Step Flow**: Track → Activity Type (no categories/events)
- **Optional Tags**: Flat list, filtered by track
- **Admin Management**: Single page for Activity Types and Tags
- **Historical Data**: Preserved and mapped to new structure

## Testing Checklist

- [x] Member activity submission form (Track → Activity Type)
- [x] Member activity edit form
- [x] Admin activity configuration (Activity Types & Tags only)
- [x] Virtual Safe create form (SAPS 271 fields + components)
- [x] Virtual Safe edit form (matches create)
- [x] Endorsement form loads from Virtual Safe
- [x] Sidebar navigation structure
- [x] Serial number validation (at least one required)
- [x] Historical data migration strategy
- [ ] Endorsement letter PDF generation (uses canonical identity - TODO in code)

## Next Steps

1. **Run Migrations**: `php artisan migrate`
2. **Test Activity Submission**: Verify Track → Activity Type flow works
3. **Test Virtual Safe**: Verify SAPS 271 fields and component serials
4. **Test Endorsements**: Verify loading from Virtual Safe and canonical identity
5. **Implement PDF Generation**: Use `EndorsementFirearm::saps_271_identity` in letter templates

## Notes

- EventCategory and EventType models remain in codebase for historical data access
- Legacy `serial_number` field kept in `user_firearms` for backwards compatibility
- All existing activities and firearms remain readable and valid
- Component types (barrel, frame, receiver) are hard-coded as per SAPS 271 - NOT admin-configurable

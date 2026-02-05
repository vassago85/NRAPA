# SAPS 271 Firearm Types Compliance Update

## Summary
Updated all firearm type systems to conform exactly with **SAPS Form 271 Section E** requirements.

## SAPS 271 Form Section E - Firearm Types

According to SAPS Form 271, Section E requires the following firearm types:

1. **Rifle**
2. **Shotgun**
3. **Handgun**
4. **Combination**
5. **Other** (with specification field for armament/indeterminable design type)

**Action Type** is a separate field (Semi-automatic, Automatic, Manual, Other).

## Changes Made

### 1. Database Migration
- **File**: `database/migrations/2026_01_30_300000_update_firearm_types_to_saps271_compliant.php`
- **Changes**:
  - Updated `endorsement_firearms.firearm_category` enum from `['handgun', 'rifle_manual', 'rifle_self_loading', 'shotgun']` to `['rifle', 'shotgun', 'handgun', 'combination', 'other']`
  - Migrated existing `rifle_manual` and `rifle_self_loading` records to `rifle`
  - Updated `user_firearms.firearm_type` enum to add `'other'` and remove `'hand_machine_carbine'`
  - Migrated `hand_machine_carbine` to `'other'` with specification
  - Added `firearm_type_other` column to both tables for "other" specification

### 2. EndorsementFirearm Model
- **File**: `app/Models/EndorsementFirearm.php`
- **Changes**:
  - Updated constants:
    - Removed: `CATEGORY_RIFLE_MANUAL`, `CATEGORY_RIFLE_SELF_LOADING`
    - Added: `CATEGORY_RIFLE`, `CATEGORY_COMBINATION`, `CATEGORY_OTHER`
  - Updated `getCategoryOptions()` to return SAPS 271 compliant options
  - Updated `getActionTypeOptions()` to work with single `rifle` category (action type handles manual vs self-loading)
  - Updated `getCategoryLabelAttribute()` to include specification for "other" type
  - Added `firearm_type_other` to fillable array

### 3. UserFirearm Model
- **File**: `app/Models/UserFirearm.php`
- **Changes**:
  - Updated `firearm_type` comment to reflect SAPS 271 types
  - Added `firearm_type_other` to fillable array
  - Updated `getFirearmTypeLabelAttribute()` to handle "other" with specification
  - Removed `hand_machine_carbine` from match statement

### 4. FirearmSearchPanel Component
- **File**: `resources/views/livewire/firearm-search-panel.blade.php`
- **Changes**:
  - Updated firearm type options to match SAPS 271 exactly:
    - Rifle
    - Shotgun
    - Handgun
    - Combination
    - Other (specify)
  - Updated placeholder text for "other" specification: "e.g., Hand Machine Carbine, Armament, etc."
  - Added help text: "Specify armament or indeterminable design type"

### 5. Endorsement Create Form
- **File**: `resources/views/pages/member/endorsements/create.blade.php`
- **Changes**:
  - Added `firearmTypeOther` property
  - Updated all category mappings to use SAPS 271 compliant types
  - Removed references to `rifle_manual` and `rifle_self_loading`
  - Updated validation rules to accept: `rifle,shotgun,handgun,combination,other`
  - Updated `syncFirearmPanelData()` to handle `firearm_type_other`
  - Updated `loadExistingFirearm()` to map SAPS 271 types correctly
  - Updated `saveRequest()` to save `firearm_type_other` when category is "other"

### 6. SAPS 271 View
- **File**: `resources/views/documents/saps271-firearm.blade.php`
- **Status**: ✅ Already handles "other" type correctly with specification

## Migration Instructions

1. **Run the migration**:
   ```bash
   php artisan migrate
   ```

2. **Verify data migration**:
   ```bash
   php artisan tinker
   ```
   ```php
   // Check endorsement_firearms migration
   \App\Models\EndorsementFirearm::whereIn('firearm_category', ['rifle_manual', 'rifle_self_loading'])->count();
   // Should return 0 (all migrated to 'rifle')
   
   // Check user_firearms migration
   \App\Models\UserFirearm::where('firearm_type', 'hand_machine_carbine')->count();
   // Should return 0 (all migrated to 'other')
   ```

## Testing Checklist

- [ ] **Endorsement Form**: Create new endorsement, select each firearm type
- [ ] **Endorsement Form**: Select "Other" and enter specification
- [ ] **Endorsement Form**: Load existing firearm (verify category mapping)
- [ ] **FirearmSearchPanel**: Verify all 5 SAPS 271 types available
- [ ] **FirearmSearchPanel**: Verify "Other" shows specification field
- [ ] **UserFirearm**: Create firearm with each type
- [ ] **UserFirearm**: Create firearm with "Other" and specification
- [ ] **SAPS 271 View**: Verify "Other" displays with specification
- [ ] **EndorsementFirearm**: Verify category labels display correctly
- [ ] **Database**: Verify no `rifle_manual` or `rifle_self_loading` records exist

## Breaking Changes

⚠️ **Note**: This is a breaking change for existing data:
- `rifle_manual` and `rifle_self_loading` are consolidated into `rifle`
- `hand_machine_carbine` is migrated to `other` with specification
- Action type (manual vs self-loading) is now captured separately in the `action_type` field

## Backward Compatibility

- Migration automatically converts existing data
- Legacy `firearm_type_id` FK still exists for backward compatibility
- Display accessors handle both old and new data formats

## SAPS 271 Compliance

✅ **All firearm types now match SAPS Form 271 Section E exactly:**
- Rifle
- Shotgun  
- Handgun
- Combination
- Other (with specification)

✅ **Action Type is separate** (as per SAPS 271):
- Semi-automatic
- Automatic
- Manual
- Other (with specification)

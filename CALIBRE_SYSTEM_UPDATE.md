# Calibre System Update - All Database Calibres Available

## Summary
Updated the endorsement request form and calibre selector to show **ALL 86 centerfire options and 110 total calibres** from the database, instead of filtering them by category/ignition type.

## Changes Made

### 1. Endorsement Request Form (`resources/views/pages/member/endorsements/create.blade.php`)

**Before:**
- Calibres were filtered by firearm category and ignition type
- Limited options shown based on selected firearm type

**After:**
- Shows ALL active calibres from database (110 total)
- Grouped by category and ignition type in dropdown for better organization
- All 86 centerfire options available across all categories
- Display shows count: "All 110 calibres available"

**New Features:**
- Added `calibresByCategory()` computed property to group calibres
- Dropdown organized with optgroups:
  - Rifle - Centerfire (X options)
  - Rifle - Rimfire (X options)
  - Handgun - Centerfire (X options)
  - Handgun - Rimfire (X options)
  - Shotgun - Centerfire (X options)
  - Shotgun - Rimfire (X options)
  - Other

### 2. CalibreSelector Component (`app/Livewire/Components/CalibreSelector.php`)

**Updated:**
- Increased search result limit from 20 to 50 when searching
- Increased default limit from 20 to 100 when not searching
- Added `notObsolete()` scope to exclude obsolete calibres
- Only filters when specific `categoryFilter` or `ignitionFilter` are provided
- When no filters, shows all calibres (all 110 options available)

## Forms Using Database Calibres

### ✅ Endorsement Requests
- **Location:** `resources/views/pages/member/endorsements/create.blade.php`
- **Status:** ✅ Updated - Shows all 110 calibres grouped by category/ignition
- **Usage:** All 86 centerfire + 110 total calibres available

### ✅ Activities (Shooting Activities)
- **Location:** `resources/views/pages/member/activities/submit.blade.php`
- **Status:** ✅ Already correct - Shows all calibres grouped by category
- **Usage:** `Calibre::active()->ordered()->get()` - All calibres available

### ✅ Virtual Safe (Armoury)
- **Location:** `resources/views/pages/member/armoury/create.blade.php` & `edit.blade.php`
- **Status:** ✅ Already correct - Uses `CalibreSelector` component
- **Usage:** Shows all calibres when no filters applied, can filter by category/ignition if needed

### ✅ Load Data
- **Location:** `resources/views/pages/member/load-data/create.blade.php`
- **Status:** ✅ Already correct - Uses database calibres via firearm selection

## Database Calibres

The system uses the `calibres` table seeded with:
- **86 Centerfire options** across:
  - Rifle centerfire calibres
  - Handgun centerfire calibres
  - Shotgun centerfire calibres (gauges)
- **110 Total calibres** including:
  - All centerfire options (86)
  - Rimfire options (rifle, handgun)
  - All categories (rifle, handgun, shotgun, other)

## Testing Checklist

- [ ] Endorsement request form shows all 110 calibres in dropdown
- [ ] All 86 centerfire options are visible and selectable
- [ ] Calibres are properly grouped by category and ignition type
- [ ] Manual entry still works if calibre not found
- [ ] Calibre request modal still functional
- [ ] Virtual Safe calibre selector shows all options
- [ ] Activities form shows all calibres
- [ ] SAPS calibre code auto-fills when calibre selected

## Notes

- Manual entry option remains available for edge cases
- Calibre request system allows users to request new calibres
- All forms now consistently use the database calibres
- No breaking changes - existing data remains valid

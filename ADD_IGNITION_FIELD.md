# Add Ignition Field to Firearm Calibres

## Changes Made

### 1. Migration Created
- **File**: `database/migrations/2026_01_30_400000_add_ignition_to_firearm_calibres.php`
- Adds `ignition` enum field (`rimfire` | `centerfire`)
- Migrates existing data: `category=rimfire` → `ignition=rimfire, category=rifle`
- Updates category enum to remove `rimfire` (now only firearm types: handgun, rifle, shotgun, etc.)

### 2. Model Updated
- **File**: `app/Models/FirearmCalibre.php`
- Added `ignition` to `$fillable`
- Added `getIgnitionLabelAttribute()` accessor
- Updated `getCategoryLabelAttribute()` to remove rimfire handling
- Added `scopeForIgnition()` query scope

### 3. Admin UI Updated
- **File**: `resources/views/pages/admin/firearm-reference/index.blade.php`
- Added "Ignition" column to the calibres table
- Displays ignition type with color-coded badges (blue for rimfire, purple for centerfire)

### 4. Import Command Updated
- **File**: `app/Console/Commands/ImportFirearmReference.php`
- Automatically derives ignition from category during import
- Handles `category=rimfire` → `ignition=rimfire, category=rifle`
- Defaults to `centerfire` if not specified

## Migration Instructions

Run the migration to add the ignition field:

```powershell
cd c:\laragon\www\NRAPA
php artisan migrate
```

This will:
1. Add the `ignition` column
2. Migrate existing rimfire calibres (set ignition=rimfire, category=rifle)
3. Set all other calibres to ignition=centerfire
4. Update the category enum to remove 'rimfire'

## Handling Multi-Type Calibres

**Note**: Some calibres like `.22 LR` can be used in both pistols and rifles. Currently:
- The `category` field stores the **primary** firearm type (defaults to rifle for rimfire calibres)
- The `ignition` field stores the ignition type separately
- For calibres used in multiple firearm types, you can manually update the category or add notes

**Future Enhancement**: Consider adding a `also_used_in` JSON field or a pivot table for calibres that are used in multiple firearm types.

## Testing

After running the migration:
1. Check the Firearm Reference Data admin page
2. Verify the "Ignition" column appears
3. Verify rimfire calibres show "Rimfire" in the Ignition column
4. Verify other calibres show "Centerfire" in the Ignition column
5. Verify Category column now only shows firearm types (Rifle, Handgun, Shotgun, etc.)

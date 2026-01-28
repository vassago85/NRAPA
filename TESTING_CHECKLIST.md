# Local Testing Checklist

## Pre-Testing Setup

1. **Run Migrations**
   ```bash
   php artisan migrate
   ```
   - Verify all migrations run successfully
   - Check for any errors in migration output

2. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan cache:clear
   ```

## Testing Checklist

### ✅ Navigation & Sidebar

- [ ] **Member Area Sidebar**
  - [ ] Dashboard link works
  - [ ] My Membership link works
  - [ ] Documents link works (if active member)
  - [ ] Activities link works (if active member)
  - [ ] Virtual Safe link works (if active member)
  - [ ] Endorsements link works (if active member)
  - [ ] Learning group expands/collapses
  - [ ] Learning Center, Knowledge Tests, Certificates links work

- [ ] **Admin Area Sidebar**
  - [ ] Dashboard link works
  - [ ] Members link works
  - [ ] Approvals group expands/collapses
  - [ ] All Approvals, Documents, Memberships, Activities links work
  - [ ] Endorsements link works (new top-level item)

- [ ] **Configuration Sidebar**
  - [ ] Membership Types link works
  - [ ] Activity Types link works (renamed from "Activities")
  - [ ] Calibres link works
  - [ ] Firearm Settings link works

### ✅ Activities

- [ ] **Member Activity Submission**
  - [ ] Navigate to Activities → Submit Activity
  - [ ] Track dropdown shows: "Hunting" and "Sport Shooting" (NO "both")
  - [ ] Selecting track filters Activity Type dropdown
  - [ ] Activity Type dropdown only shows types for selected track
  - [ ] Activity Tags dropdown shows tags filtered by track
  - [ ] Form submits successfully
  - [ ] Activity appears in list with correct track badge

- [ ] **Member Activity Edit**
  - [ ] Edit existing activity
  - [ ] Track dropdown pre-populates correctly
  - [ ] Activity Type filtered by track
  - [ ] Tags pre-populate correctly
  - [ ] Save changes works

- [ ] **Admin Activity Configuration**
  - [ ] Navigate to Configuration → Activity Types
  - [ ] Page shows 2 tabs: "Activity Types" and "Activity Tags"
  - [ ] NO "Event Categories" or "Event Types" tabs visible
  - [ ] Add new Activity Type:
    - [ ] Name field works
    - [ ] Track dropdown: "Hunting" or "Sport Shooting" only
    - [ ] Group field (optional) works
    - [ ] Save works
  - [ ] Edit Activity Type works
  - [ ] Deactivate Activity Type works
  - [ ] Add new Activity Tag:
    - [ ] Key field works
    - [ ] Label field works
    - [ ] Track dropdown (optional) works
    - [ ] Save works

- [ ] **Admin Activity Review**
  - [ ] Navigate to Approvals → Activities
  - [ ] Activities list shows track badge
  - [ ] Activities list shows tags
  - [ ] View activity detail shows track and tags correctly

### ✅ Virtual Safe (Armoury)

- [ ] **Create Firearm**
  - [ ] Navigate to Virtual Safe → Add Firearm
  - [ ] SAPS 271 Fields:
    - [ ] Firearm Type dropdown: rifle, shotgun, handgun, hand_machine_carbine, combination
    - [ ] Action dropdown: semi_automatic, automatic, manual, other
    - [ ] "Other Action" text field appears when action = "other"
    - [ ] Calibre selector works
    - [ ] Calibre Code field works
    - [ ] Make and Model fields work
  - [ ] Component Serial Numbers:
    - [ ] Barrel serial and make fields visible
    - [ ] Frame serial and make fields visible
    - [ ] Receiver serial and make fields visible
    - [ ] Validation: Submit without any serial shows error
    - [ ] Validation: Submit with at least one serial works
  - [ ] Form submits successfully
  - [ ] Firearm appears in Virtual Safe list

- [ ] **Edit Firearm**
  - [ ] Edit existing firearm
  - [ ] SAPS 271 fields pre-populate correctly
  - [ ] Component serials load from firearm_components table
  - [ ] Can update component serials
  - [ ] Save changes works
  - [ ] Components update correctly in database

- [ ] **View Firearm**
  - [ ] View firearm detail page
  - [ ] Shows SAPS 271 canonical identity
  - [ ] Shows component serials (barrel, frame, receiver)

### ✅ Endorsements

- [ ] **Create Endorsement Request**
  - [ ] Navigate to Endorsements → Request Endorsement Letter
  - [ ] Step 1: Select request type (New/Renewal) works
  - [ ] Step 2: Firearm Details
    - [ ] "Load from Virtual Safe" section visible (if firearms exist)
    - [ ] Select firearm from dropdown
    - [ ] Click "Load" button
    - [ ] Form auto-populates:
      - [ ] Make, Model from canonical firearm
      - [ ] Calibre from canonical firearm
      - [ ] Calibre Code from canonical firearm
      - [ ] Firearm Category mapped from firearm_type
      - [ ] Action Type mapped from action
      - [ ] Component serials (barrel, frame, receiver) loaded
    - [ ] Can manually edit loaded fields
    - [ ] Can enter firearm details manually (without loading)
  - [ ] Serial number validation works (at least one required)
  - [ ] Form submits successfully

- [ ] **View Endorsement Request**
  - [ ] View submitted endorsement
  - [ ] Firearm details display correctly
  - [ ] Component serials display correctly
  - [ ] If linked to Virtual Safe firearm, shows canonical identity

### ✅ Data Integrity

- [ ] **Historical Data**
  - [ ] Existing activities still visible and readable
  - [ ] Existing firearms still visible and readable
  - [ ] Legacy serial_number migrated to receiver component (check database)

- [ ] **New Data Structure**
  - [ ] New activities use track field (not dedicated_type)
  - [ ] New firearms use SAPS 271 canonical fields
  - [ ] Component serials stored in firearm_components table

### ✅ Validation

- [ ] **Serial Number Validation**
  - [ ] Virtual Safe: Error when no serials provided
  - [ ] Endorsement: Error when no serials provided
  - [ ] Error message: "Provide at least one serial number (Barrel, Frame, or Receiver) as per SAPS 271."

### ✅ UI/UX

- [ ] **Terminology**
  - [ ] NO "Event" terminology visible anywhere
  - [ ] All references use "Activities"
  - [ ] Menu items use correct labels

- [ ] **Styling**
  - [ ] Forms use Tailwind CSS v4 utilities
  - [ ] Dark mode works correctly
  - [ ] Zinc palette consistent
  - [ ] Active route highlighting works in sidebar

## Database Verification

Run these queries to verify data structure:

```sql
-- Check activity_types has track field
DESCRIBE activity_types;

-- Check activity_tags table exists
SHOW TABLES LIKE 'activity_tags';

-- Check firearm_components table exists
SHOW TABLES LIKE 'firearm_components';

-- Check user_firearms has SAPS 271 fields
DESCRIBE user_firearms;

-- Verify components migrated
SELECT COUNT(*) FROM firearm_components WHERE type = 'receiver' AND notes LIKE '%Migrated%';
```

## Common Issues to Check

1. **Migration Errors**
   - If migrations fail, check for existing columns
   - Verify foreign key constraints

2. **Route Errors**
   - Check all routes are registered: `php artisan route:list`
   - Verify route names match sidebar menu

3. **Model Errors**
   - Check all relationships load correctly
   - Verify fillable arrays include new fields

4. **Form Errors**
   - Check wire:model bindings match component properties
   - Verify validation rules match form fields

## Post-Testing

After successful testing:

1. **Document Any Issues**
   - Note any bugs or unexpected behavior
   - Document workarounds if needed

2. **Prepare for Deployment**
   - All tests pass
   - No console errors
   - No database errors
   - All forms submit successfully

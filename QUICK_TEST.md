# Quick Local Test Guide

## Step 1: Run Migrations
```bash
cd c:\laragon\www\NRAPA
php artisan migrate
```

**What to check:**
- ✅ All migrations complete without errors
- ✅ Check output for any warnings about existing data

## Step 2: Clear Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## Step 3: Quick Smoke Tests

### Test 1: Navigation Sidebar
1. Login as member/admin
2. Check sidebar:
   - ✅ "Activity Types" in Configuration (not "Activities")
   - ✅ "Endorsements" in Administration
   - ✅ "Virtual Safe" in Member Area
   - ✅ Learning group expands/collapses

### Test 2: Activity Submission
1. Go to Activities → Submit Activity
2. Select Track (Hunting or Sport)
3. Verify Activity Type dropdown filters by track
4. Submit activity
5. ✅ Activity appears in list with track badge

### Test 3: Activity Configuration
1. Go to Configuration → Activity Types
2. ✅ See 2 tabs: "Activity Types" and "Activity Tags"
3. ✅ NO "Event Categories" or "Event Types" tabs
4. Try adding a new Activity Type:
   - Name: "Test Activity"
   - Track: "Hunting"
   - Save
5. ✅ New activity type appears in list

### Test 4: Virtual Safe
1. Go to Virtual Safe → Add Firearm
2. ✅ See SAPS 271 fields:
   - Firearm Type dropdown
   - Action dropdown
   - Component serials (Barrel, Frame, Receiver)
3. Fill required fields
4. Try submitting WITHOUT any serial → ✅ Should show error
5. Add at least one serial → ✅ Should submit successfully

### Test 5: Endorsement (if you have firearms in Virtual Safe)
1. Go to Endorsements → Request Endorsement Letter
2. Step 2: Firearm Details
3. ✅ See "Load from Virtual Safe" section
4. Select a firearm and click "Load"
5. ✅ Form auto-populates with firearm details
6. ✅ Component serials load correctly

## Step 4: Database Check
```sql
-- Quick verification queries
SELECT COUNT(*) FROM activity_types WHERE track IS NOT NULL;
SELECT COUNT(*) FROM activity_tags;
SELECT COUNT(*) FROM firearm_components;
SELECT COUNT(*) FROM user_firearms WHERE firearm_type IS NOT NULL;
```

## If Everything Works ✅

Proceed to deployment:
1. Review changes: `git status`
2. Stage files: `git add .`
3. Commit: `git commit -m "Refactor: Simplify Activities and implement SAPS 271 firearm identity"`
4. Push: `git push origin main`

## If Issues Found ❌

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console for JavaScript errors
3. Review migration output for errors
4. Verify database structure matches expected schema

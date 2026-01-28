# Deployment Steps

## Pre-Deployment Testing (Local)

### 1. Run Migrations
```bash
php artisan migrate
```

**Expected Output:**
- All migrations run successfully
- No errors about existing columns/tables
- Data migration completes (check for warnings about existing data)

### 2. Clear All Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan optimize:clear
```

### 3. Test Key Functionality

#### Navigation
- [ ] Sidebar loads correctly
- [ ] All menu items visible based on role
- [ ] "Activity Types" appears in Configuration (not "Activities")
- [ ] "Endorsements" appears in Administration

#### Activities
- [ ] Submit activity: Track → Activity Type flow works
- [ ] Activity Types admin page shows 2 tabs only
- [ ] No "Event" terminology visible

#### Virtual Safe
- [ ] Create firearm: SAPS 271 fields visible
- [ ] Component serials (barrel/frame/receiver) visible
- [ ] Validation: Error when no serial provided
- [ ] Edit firearm: Components load correctly

#### Endorsements
- [ ] Create endorsement: "Load from Virtual Safe" works
- [ ] Firearm details auto-populate from Virtual Safe
- [ ] Component serials load correctly

### 4. Database Verification
```sql
-- Check new columns exist
SHOW COLUMNS FROM activity_types LIKE 'track';
SHOW COLUMNS FROM activity_types LIKE 'group';
SHOW COLUMNS FROM shooting_activities LIKE 'track';
SHOW COLUMNS FROM user_firearms LIKE 'firearm_type';
SHOW COLUMNS FROM user_firearms LIKE 'action';

-- Check new tables exist
SHOW TABLES LIKE 'activity_tags';
SHOW TABLES LIKE 'firearm_components';
```

## Git Deployment

### 1. Review Changes
```bash
git status
git diff
```

### 2. Stage All Changes
```bash
git add .
```

### 3. Commit with Descriptive Message
```bash
git commit -m "Refactor: Simplify Activities model and implement SAPS 271 canonical firearm identity

- Remove Event terminology, replace with Activities throughout UI
- Simplify Activity model: Track → Activity Type (2-step flow)
- Add Activity Tags for optional categorization
- Implement SAPS 271 canonical firearm identity fields
- Add firearm_components table (barrel/frame/receiver serials)
- Update Virtual Safe forms to use SAPS 271 fields
- Update Endorsement forms to load from Virtual Safe
- Update sidebar navigation structure (Member + Admin areas)
- Add global validation rule for serial number requirement
- Preserve historical data with migration strategy
- Update all related views and models"
```

### 4. Push to Remote
```bash
git push origin main
# or
git push origin master
# or your branch name
```

## Post-Deployment (Server)

### 1. SSH to Server
```bash
ssh user@your-server
cd /opt/nrapa  # or your deployment path
```

### 2. Pull Latest Changes
```bash
git pull origin main
```

### 3. Run Migrations
```bash
docker exec nrapa-app php artisan migrate --force
```

**Note:** Use `--force` flag in production to skip confirmation prompts.

### 4. Clear Caches
```bash
docker exec nrapa-app php artisan config:cache
docker exec nrapa-app php artisan route:cache
docker exec nrapa-app php artisan view:cache
docker exec nrapa-app php artisan optimize
```

### 5. Verify Deployment
- [ ] Check application loads without errors
- [ ] Test navigation sidebar
- [ ] Test activity submission
- [ ] Test Virtual Safe create/edit
- [ ] Test endorsement creation
- [ ] Check logs for errors: `docker logs nrapa-app --tail 100`

### 6. Rollback Plan (if needed)
If issues occur:
```bash
# Revert to previous commit
git revert HEAD
git push origin main

# Or rollback specific migration
docker exec nrapa-app php artisan migrate:rollback --step=1
```

## Files Changed Summary

### New Files
- `app/Rules/AtLeastOneSerialNumber.php`
- `app/Models/ActivityTag.php`
- `database/migrations/2026_01_28_100000_simplify_activity_model.php`
- `database/migrations/2026_01_28_100001_migrate_event_data_to_activity_types.php`
- `database/migrations/2026_01_28_100002_remove_dedicated_type_from_activity_types.php`
- `TESTING_CHECKLIST.md`
- `DEPLOYMENT_STEPS.md`
- `REFACTORING_SUMMARY.md`

### Modified Files
- `app/Helpers/SidebarMenu.php`
- `app/Models/ActivityType.php`
- `app/Models/ShootingActivity.php`
- `app/Models/EndorsementFirearm.php`
- `resources/views/pages/member/activities/submit.blade.php`
- `resources/views/pages/member/activities/edit.blade.php`
- `resources/views/pages/member/activities/index.blade.php`
- `resources/views/pages/member/activities/show.blade.php`
- `resources/views/pages/member/armoury/edit.blade.php`
- `resources/views/pages/member/endorsements/create.blade.php`
- `resources/views/pages/admin/activity-config/index.blade.php`
- `resources/views/pages/admin/activities/index.blade.php`
- `resources/views/pages/admin/activities/show.blade.php`

## Important Notes

1. **Data Safety**: All migrations are non-destructive. Historical data is preserved.

2. **Backwards Compatibility**: 
   - Legacy `event_category_id` and `event_type_id` kept in `shooting_activities`
   - Legacy `serial_number` kept in `user_firearms`
   - Old relationships available as deprecated methods

3. **Component Types**: Firearm component types (barrel, frame, receiver) are hard-coded as per SAPS 271 - NOT admin-configurable.

4. **Testing**: Run full test suite locally before deploying to ensure all functionality works.

## Troubleshooting

### Migration Errors
- If "column already exists" errors: Check if migrations were partially run
- If foreign key errors: Verify related tables exist
- If data migration errors: Check existing data structure

### Route Errors
- Clear route cache: `php artisan route:clear`
- Verify routes: `php artisan route:list`

### View Errors
- Clear view cache: `php artisan view:clear`
- Check Blade syntax: Look for unclosed tags or syntax errors

### Model Errors
- Check relationships: Verify all `belongsTo` and `hasMany` relationships
- Check fillable arrays: Ensure new fields are in `$fillable`

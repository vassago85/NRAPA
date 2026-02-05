# Local Deployment Steps

## Quick Deploy Commands

### Option 1: Using Laragon Terminal (Recommended)
1. Open Laragon
2. Click "Terminal" button
3. Run these commands:

```bash
cd c:\laragon\www\NRAPA

# Run migrations
php artisan migrate

# Seed/update document types
php artisan db:seed --class=MembershipConfigurationSeeder

# Clear all caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart Laragon services (if needed)
# Or just refresh your browser
```

### Option 2: Using PowerShell with Full PHP Path
```powershell
# Find your PHP path first
Get-ChildItem "C:\laragon\bin\php" -Recurse -Filter "php.exe" | Select-Object -First 1 FullName

# Then use that path (example):
$php = "C:\laragon\bin\php\php-8.2\php.exe"
cd c:\laragon\www\NRAPA

& $php artisan migrate
& $php artisan db:seed --class=MembershipConfigurationSeeder
& $php artisan optimize:clear
& $php artisan config:cache
& $php artisan route:cache
& $php artisan view:cache
```

## Step-by-Step Deployment

### 1. Run Migrations
```bash
php artisan migrate
```
**Expected:** All migrations run successfully, including:
- `2026_01_30_500000_update_calibre_requests_to_firearm_calibres_framework.php`
- `2026_01_30_800000_add_document_rejected_notification_preference.php`

### 2. Update Document Types
```bash
php artisan db:seed --class=MembershipConfigurationSeeder
```
**Expected:** Document types updated to only 4 active types

### 3. Clear Caches
```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### 4. Rebuild Caches (Optional but recommended)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Verification Steps

### 1. Check Document Types
```bash
php artisan tinker
```
Then:
```php
use App\Models\DocumentType;

// Check active document types
DocumentType::active()->get(['name', 'slug', 'is_active']);

// Should show only:
// - ID
// - Proof of Address  
// - Competency
// - Firearm Licence
```

### 2. Check Calibre Requests Migration
```php
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Check category enum
$column = DB::select("DESCRIBE calibre_requests WHERE Field = 'category'");
print_r($column);

// Should show enum with: handgun, rifle, shotgun, muzzleloader, historic
```

### 3. Check Notification Preference
```php
use App\Models\NotificationPreference;

// Check if column exists
Schema::hasColumn('notification_preferences', 'notify_document_rejected');
// Should return: true
```

### 4. Test Document Upload Form
1. Visit: `http://nrapa.test/member/documents`
2. Click "Upload Document"
3. **Expected:** Dropdown shows only 4 options:
   - ID
   - Proof of Address
   - Competency
   - Firearm Licence

### 5. Test Calibre Request Form
1. Visit: `http://nrapa.test/member/endorsements/create`
2. Go to Step 2
3. Click "Request New Calibre"
4. **Expected:** 
   - Modal shows existing calibres list
   - Category dropdown shows: handgun, rifle, shotgun, muzzleloader, historic
   - Can search existing calibres

### 6. Test Document Rejection Notification
1. As admin, reject a document
2. **Expected:**
   - Member receives notification (if NTFY configured)
   - Rejected document appears on member dashboard
   - Red alert shows rejection reason

## Troubleshooting

### If migrations fail:
```bash
# Check migration status
php artisan migrate:status

# Rollback last migration if needed
php artisan migrate:rollback --step=1

# Try again
php artisan migrate
```

### If document types don't update:
```bash
# Force re-seed
php artisan db:seed --class=MembershipConfigurationSeeder --force
```

### If caches won't clear:
```bash
# Manual cache clear
php artisan cache:forget spatie.permission.cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### If document upload still shows old types:
1. Clear browser cache
2. Hard refresh (Ctrl+Shift+R)
3. Check database directly:
```php
php artisan tinker
DocumentType::where('is_active', true)->pluck('name');
```

## Success Criteria

✅ All migrations run successfully
✅ Document types reduced to 4 active types
✅ Document upload form shows only 4 options
✅ Calibre request form shows existing calibres
✅ Notification preference column added
✅ Member dashboard shows rejected documents
✅ No errors in Laravel logs

## Post-Deployment Testing

1. **Document Upload:**
   - [ ] Upload ID document
   - [ ] Upload Proof of Address
   - [ ] Upload Competency
   - [ ] Upload Firearm Licence
   - [ ] Verify only 4 types in dropdown

2. **Calibre Request:**
   - [ ] Open calibre request modal
   - [ ] See existing calibres list
   - [ ] Search for calibre
   - [ ] Submit new calibre request
   - [ ] Admin approves request
   - [ ] Verify FirearmCalibre created

3. **Document Rejection:**
   - [ ] Admin rejects document
   - [ ] Member sees notification
   - [ ] Rejected document appears on dashboard
   - [ ] Rejection reason displayed

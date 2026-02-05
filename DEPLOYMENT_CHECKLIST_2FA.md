# Deployment Checklist - 2FA Improvements & CI Fixes

## Changes Summary

### 1. 2FA Setup Improvements ✅
- Auto-show QR code modal when users are forced to set up 2FA
- Prevent modal from closing when 2FA is required
- Enhanced instructions about authenticator apps and next login requirement
- Files changed:
  - `resources/views/pages/settings/two-factor.blade.php`
  - `app/Http/Middleware/Enforce2FAForAdmins.php`

### 2. Migration Fixes ✅
- Fixed comments migration to prevent duplicate index errors
- File changed: `database/migrations/2026_01_29_100002_create_comments_table.php`

### 3. Test Fixes ✅
- Fixed 2FA test assertions
- Fixed profile deletion test for soft-deleted users
- Files changed:
  - `tests/Feature/Settings/TwoFactorAuthenticationTest.php`
  - `tests/Feature/Settings/ProfileUpdateTest.php`

## Deployment Steps

### On Server (SSH into your server):

```bash
# 1. Navigate to project directory
cd /path/to/nrapa  # Adjust to your server path

# 2. Pull latest changes
git pull origin main

# 3. Install/Update dependencies (if composer.json changed)
composer install --no-interaction --prefer-dist --optimize-autoloader

# 4. Run migrations (if any new migrations)
php artisan migrate --force

# 5. Clear all caches
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 6. Build assets (if package.json changed)
npm install
npm run build

# 7. Restart services (if using queue workers, etc.)
# php artisan queue:restart  # Uncomment if using queues
```

## Quick One-Liner

```bash
cd /path/to/nrapa && git pull origin main && composer install --no-interaction --prefer-dist --optimize-autoloader && php artisan migrate --force && php artisan optimize:clear && npm install && npm run build
```

## Verification Steps

After deployment, verify:

1. **2FA Setup Flow:**
   - Log in as admin with `logins_without_2fa >= 10`
   - Should be redirected to 2FA setup page
   - QR code modal should appear automatically
   - Instructions should be clear about apps and next login requirement

2. **Migrations:**
   - Check that `comments` table exists
   - Verify no duplicate index errors in logs

3. **Tests (if running on server):**
   - All tests should pass
   - No migration errors

## Important Notes

- **No database changes required** - Only code changes
- **No breaking changes** - All changes are backward compatible
- **2FA middleware** - Still bypasses in local/development/testing environments
- **Flux CSS** - Removed from build (commented out) - uncomment when Flux is installed

## Rollback (if needed)

```bash
# Revert to previous commit
git reset --hard HEAD~1  # Or specific commit hash
git pull origin main --force

# Clear caches
php artisan optimize:clear
```

## Files Changed (Summary)

- `resources/views/pages/settings/two-factor.blade.php` - 2FA UI improvements
- `app/Http/Middleware/Enforce2FAForAdmins.php` - Skip 2FA in local env
- `database/migrations/2026_01_29_100002_create_comments_table.php` - Migration fix
- `tests/Feature/Settings/TwoFactorAuthenticationTest.php` - Test fix
- `tests/Feature/Settings/ProfileUpdateTest.php` - Test fix
- `.github/workflows/tests.yml` - CI workflow improvements
- `resources/css/app.css` - Removed Flux imports (temporary)

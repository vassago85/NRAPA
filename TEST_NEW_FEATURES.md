# Testing New Features

This document describes how to run tests for the newly implemented features.

## New Features Tested

1. **Backup Service** - System backup functionality
2. **Excel Member Importer** - Bulk member import from Excel
3. **Daily Database Backup Command** - Scheduled backup automation

## Running Tests

### Option 1: Using Laragon Terminal

1. Open Laragon
2. Click "Terminal" button
3. Navigate to project:
   ```powershell
   cd c:\laragon\www\NRAPA
   ```
4. Run all tests:
   ```powershell
   php artisan test
   ```

### Option 2: Using Test Script

Run the PowerShell test script:
```powershell
.\run-tests.ps1
```

### Option 3: Run Specific Test Suites

Run only backup-related tests:
```powershell
php artisan test --filter Backup
```

Run only Excel import tests:
```powershell
php artisan test --filter ExcelMember
```

Run all new feature tests:
```powershell
php artisan test tests/Feature/BackupServiceTest.php tests/Feature/ExcelMemberImporterTest.php tests/Feature/BackupCommandTest.php
```

## Test Coverage

### BackupServiceTest
- ✅ Database backup creation (SQLite)
- ✅ CSV user export
- ✅ ZIP archive creation
- ✅ Byte formatting utility
- ✅ Old backup cleanup (30-day retention)

### ExcelMemberImporterTest
- ✅ Excel template generation
- ✅ User creation from member data
- ✅ Required field validation
- ✅ Email format validation
- ✅ Membership creation
- ✅ Auto-generated membership numbers
- ✅ Date parsing (various formats)
- ✅ Error handling for missing files

### BackupCommandTest
- ✅ Command requires password configuration
- ✅ Command runs with valid password

## Expected Test Results

All tests should pass when:
- Database is properly configured (SQLite for testing)
- PhpSpreadsheet is installed (`composer install`)
- ZipArchive PHP extension is available

## Troubleshooting

### "Class 'ZipArchive' not found"
Install php-zip extension in Laragon:
- Laragon → PHP → Extensions → Enable `php_zip`

### "Class 'PhpOffice\PhpSpreadsheet\IOFactory' not found"
Run composer install:
```powershell
composer install
```

### Tests fail with database errors
Ensure test database is set up:
```powershell
php artisan migrate --env=testing
```

## Manual Testing Checklist

After running automated tests, manually verify:

### Backup Feature
- [ ] Go to Owner Settings → System Backup
- [ ] Enter database password
- [ ] Click "Create Backup Now"
- [ ] Verify backup is created and downloadable
- [ ] Check automatic backup settings save correctly
- [ ] Verify password is encrypted in database

### Excel Import Feature
- [ ] Go to Admin → Members
- [ ] Click "Download Template"
- [ ] Open template in Excel
- [ ] Fill in sample member data
- [ ] Click "Import Members"
- [ ] Upload filled Excel file
- [ ] Verify members are created
- [ ] Check membership numbers are generated
- [ ] Verify duplicate detection works

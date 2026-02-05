# Calibre Request System - CLI Commands

## Local Testing Commands

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Check Migration Status
```bash
php artisan migrate:status
```

### 3. Rollback Migration (if needed)
```bash
php artisan migrate:rollback --step=1
```

### 4. Clear Application Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 5. Test Calibre Request Form
```bash
# Start development server
php artisan serve

# Then visit:
# http://localhost:8000/endorsements/create
# Click "Request New Calibre" button
```

### 6. Check Existing FirearmCalibres
```bash
php artisan tinker
```
Then in tinker:
```php
use App\Models\FirearmCalibre;

// Count total calibres
FirearmCalibre::count();

// List all calibres
FirearmCalibre::active()->notObsolete()->get(['id', 'name', 'category', 'ignition']);

// Search for a specific calibre
FirearmCalibre::where('name', 'like', '%6.5%')->get(['name', 'category', 'ignition']);

// Check by category
FirearmCalibre::where('category', 'rifle')->where('ignition', 'centerfire')->count();
```

### 7. Test Calibre Request Creation
```bash
php artisan tinker
```
Then in tinker:
```php
use App\Models\CalibreRequest;
use App\Models\User;

// Create a test calibre request
$user = User::first();
CalibreRequest::create([
    'user_id' => $user->id,
    'name' => 'Test Calibre 6.5 PRC',
    'category' => 'rifle',
    'ignition_type' => 'centerfire',
    'status' => 'pending',
]);

// Check pending requests
CalibreRequest::pending()->with('user')->get();
```

### 8. Test Admin Approval Process
```bash
php artisan tinker
```
Then in tinker:
```php
use App\Models\CalibreRequest;
use App\Models\FirearmCalibre;

// Get a pending request
$request = CalibreRequest::pending()->first();

// Manually approve (simulating admin action)
if ($request) {
    $calibre = FirearmCalibre::create([
        'name' => $request->name,
        'normalized_name' => FirearmCalibre::normalize($request->name),
        'category' => $request->category === 'other' ? 'rifle' : $request->category,
        'ignition' => $request->ignition_type,
        'is_active' => true,
        'is_obsolete' => false,
        'is_wildcat' => false,
    ]);
    
    $request->update([
        'status' => 'approved',
        'calibre_id' => $calibre->id,
        'reviewed_by' => 1, // Admin user ID
        'reviewed_at' => now(),
    ]);
    
    echo "Calibre created: {$calibre->name}\n";
    echo "Request approved: {$request->id}\n";
}
```

## Server Deployment Commands (Docker)

### 1. SSH into Server
```bash
ssh user@your-server
```

### 2. Navigate to Project Directory
```bash
cd /opt/nrapa
```

### 3. Pull Latest Changes
```bash
git pull origin main
```

### 4. Run Migration Inside Docker Container
```bash
docker exec nrapa-app php artisan migrate --force
```

### 5. Clear Caches
```bash
docker exec nrapa-app php artisan optimize:clear
docker exec nrapa-app php artisan config:cache
docker exec nrapa-app php artisan route:cache
docker exec nrapa-app php artisan view:cache
```

### 6. Restart Application Container
```bash
docker compose restart app
```

### 7. Check Application Logs
```bash
docker exec nrapa-app tail -f storage/logs/laravel.log
```

## Verification Commands

### 1. Verify Migration Ran Successfully
```bash
php artisan tinker
```
Then in tinker:
```php
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Check if calibre_requests table exists
Schema::hasTable('calibre_requests');

// Check column types
DB::select("SHOW COLUMNS FROM calibre_requests WHERE Field = 'category'");

// Check foreign key constraint
DB::select("
    SELECT 
        CONSTRAINT_NAME, 
        TABLE_NAME, 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_NAME = 'calibre_requests' 
    AND COLUMN_NAME = 'calibre_id'
");
```

### 2. Verify FirearmCalibre Integration
```bash
php artisan tinker
```
Then in tinker:
```php
use App\Models\CalibreRequest;
use App\Models\FirearmCalibre;

// Check if relationship works
$request = CalibreRequest::with('calibre')->first();
if ($request && $request->calibre) {
    echo "Relationship works! Calibre: {$request->calibre->name}\n";
}

// Check existing calibres count
echo "Total FirearmCalibres: " . FirearmCalibre::count() . "\n";
echo "Active FirearmCalibres: " . FirearmCalibre::active()->count() . "\n";
```

### 3. Test Category Enum Values
```bash
php artisan tinker
```
Then in tinker:
```php
use App\Models\CalibreRequest;

// Try creating with each valid category
$categories = ['handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic'];
foreach ($categories as $cat) {
    try {
        $test = new CalibreRequest();
        $test->category = $cat;
        echo "✓ Category '{$cat}' is valid\n";
    } catch (\Exception $e) {
        echo "✗ Category '{$cat}' failed: {$e->getMessage()}\n";
    }
}
```

## Database Inspection Commands

### 1. Check Calibre Requests Table Structure
```bash
php artisan tinker
```
Then in tinker:
```php
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Get table structure
$columns = DB::select("DESCRIBE calibre_requests");
foreach ($columns as $column) {
    echo "{$column->Field}: {$column->Type}\n";
}
```

### 2. Check Existing Calibre Requests
```bash
php artisan tinker
```
Then in tinker:
```php
use App\Models\CalibreRequest;

// List all requests
CalibreRequest::with(['user', 'calibre'])->get()->each(function($req) {
    echo "ID: {$req->id} | Name: {$req->name} | Category: {$req->category} | Status: {$req->status}\n";
    if ($req->calibre) {
        echo "  → Linked to FirearmCalibre: {$req->calibre->name}\n";
    }
});
```

### 3. Check for Orphaned Records
```bash
php artisan tinker
```
Then in tinker:
```php
use App\Models\CalibreRequest;

// Find requests with invalid calibre_id
$orphaned = CalibreRequest::whereNotNull('calibre_id')
    ->whereDoesntHave('calibre')
    ->get();

if ($orphaned->count() > 0) {
    echo "Found {$orphaned->count()} orphaned requests:\n";
    foreach ($orphaned as $req) {
        echo "  Request ID: {$req->id}, Calibre ID: {$req->calibre_id}\n";
    }
} else {
    echo "No orphaned records found.\n";
}
```

## Quick Test Script

Create a test file `test-calibre-request.php`:
```php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FirearmCalibre;
use App\Models\CalibreRequest;
use App\Models\User;

echo "=== Calibre Request System Test ===\n\n";

// 1. Check FirearmCalibre count
echo "1. FirearmCalibre count: " . FirearmCalibre::count() . "\n";

// 2. Check by category
echo "2. By category:\n";
foreach (['handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic'] as $cat) {
    $count = FirearmCalibre::where('category', $cat)->count();
    echo "   - {$cat}: {$count}\n";
}

// 3. Check pending requests
$pending = CalibreRequest::pending()->count();
echo "3. Pending calibre requests: {$pending}\n";

// 4. Check approved requests with calibres
$approved = CalibreRequest::approved()->whereNotNull('calibre_id')->count();
echo "4. Approved requests with linked calibres: {$approved}\n";

// 5. Test category validation
echo "5. Testing category validation:\n";
$testCategories = ['handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic', 'other'];
foreach ($testCategories as $cat) {
    try {
        $test = new CalibreRequest();
        $test->category = $cat;
        echo "   ✓ '{$cat}' is valid\n";
    } catch (\Exception $e) {
        echo "   ✗ '{$cat}' failed\n";
    }
}

echo "\n=== Test Complete ===\n";
```

Run it:
```bash
php test-calibre-request.php
```

## Troubleshooting Commands

### 1. If Migration Fails
```bash
# Check migration status
php artisan migrate:status

# See last migration error
php artisan migrate --pretend

# Rollback and retry
php artisan migrate:rollback --step=1
php artisan migrate
```

### 2. If Foreign Key Constraint Fails
```bash
php artisan tinker
```
Then in tinker:
```php
use Illuminate\Support\Facades\DB;

// Check if firearm_calibres table exists
DB::select("SHOW TABLES LIKE 'firearm_calibres'");

// Check foreign key constraints
DB::select("
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'calibre_requests'
");
```

### 3. Reset Calibre Requests (if needed)
```bash
php artisan tinker
```
Then in tinker:
```php
use App\Models\CalibreRequest;

// WARNING: This will delete all calibre requests!
// CalibreRequest::truncate();

// Or just reset pending ones
CalibreRequest::pending()->delete();
```

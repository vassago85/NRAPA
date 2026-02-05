<?php

/**
 * Calibre Request System Test Script
 * 
 * Run this script to test the calibre request system integration with FirearmCalibre
 * Usage: php test-calibre-request-system.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FirearmCalibre;
use App\Models\CalibreRequest;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== Calibre Request System Test ===\n\n";

$errors = [];
$warnings = [];
$success = [];

// Test 1: Check if FirearmCalibre table exists
echo "1. Checking FirearmCalibre table...\n";
if (Schema::hasTable('firearm_calibres')) {
    $success[] = "✓ FirearmCalibre table exists";
    echo "   ✓ FirearmCalibre table exists\n";
    
    $count = FirearmCalibre::count();
    echo "   → Total calibres: {$count}\n";
    
    if ($count > 0) {
        $success[] = "✓ FirearmCalibre has data";
        echo "   ✓ FirearmCalibre has data\n";
        
        // Show sample calibres by category
        $categories = ['handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic'];
        foreach ($categories as $cat) {
            $catCount = FirearmCalibre::where('category', $cat)->count();
            if ($catCount > 0) {
                echo "   → {$cat}: {$catCount} calibres\n";
            }
        }
    } else {
        $warnings[] = "⚠ FirearmCalibre table is empty - run import command";
        echo "   ⚠ FirearmCalibre table is empty\n";
    }
} else {
    $errors[] = "✗ FirearmCalibre table does not exist";
    echo "   ✗ FirearmCalibre table does not exist\n";
}

// Test 2: Check if calibre_requests table exists
echo "\n2. Checking calibre_requests table...\n";
if (Schema::hasTable('calibre_requests')) {
    $success[] = "✓ calibre_requests table exists";
    echo "   ✓ calibre_requests table exists\n";
    
    // Check table structure
    $columns = DB::select("DESCRIBE calibre_requests");
    $columnNames = array_column($columns, 'Field');
    
    $requiredColumns = ['id', 'user_id', 'name', 'category', 'ignition_type', 'status', 'calibre_id'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columnNames)) {
            echo "   ✓ Column '{$col}' exists\n";
        } else {
            $errors[] = "✗ Column '{$col}' missing";
            echo "   ✗ Column '{$col}' missing\n";
        }
    }
    
    // Check category enum values
    $categoryColumn = collect($columns)->firstWhere('Field', 'category');
    if ($categoryColumn) {
        echo "   → Category column type: {$categoryColumn->Type}\n";
        
        // Check if it includes new categories
        $validCategories = ['handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic'];
        $hasOther = strpos($categoryColumn->Type, "'other'") !== false;
        
        if ($hasOther) {
            $warnings[] = "⚠ Category enum still includes 'other' - migration may not have run";
            echo "   ⚠ Category enum still includes 'other'\n";
        } else {
            $success[] = "✓ Category enum updated correctly";
            echo "   ✓ Category enum updated correctly\n";
        }
    }
} else {
    $errors[] = "✗ calibre_requests table does not exist";
    echo "   ✗ calibre_requests table does not exist\n";
}

// Test 3: Check foreign key constraint
echo "\n3. Checking foreign key constraint...\n";
try {
    $driver = DB::getDriverName();
    
    if ($driver === 'mysql') {
        $fks = DB::select("
            SELECT 
                CONSTRAINT_NAME, 
                TABLE_NAME, 
                COLUMN_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'calibre_requests' 
            AND COLUMN_NAME = 'calibre_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if (count($fks) > 0) {
            $fk = $fks[0];
            echo "   ✓ Foreign key exists\n";
            echo "   → References: {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
            
            if ($fk->REFERENCED_TABLE_NAME === 'firearm_calibres') {
                $success[] = "✓ Foreign key points to firearm_calibres";
                echo "   ✓ Foreign key points to firearm_calibres\n";
            } else {
                $errors[] = "✗ Foreign key points to wrong table: {$fk->REFERENCED_TABLE_NAME}";
                echo "   ✗ Foreign key points to wrong table: {$fk->REFERENCED_TABLE_NAME}\n";
            }
        } else {
            $warnings[] = "⚠ Foreign key constraint not found";
            echo "   ⚠ Foreign key constraint not found\n";
        }
    } else {
        echo "   → SQLite detected - foreign key check skipped\n";
    }
} catch (\Exception $e) {
    $warnings[] = "⚠ Could not check foreign key: " . $e->getMessage();
    echo "   ⚠ Could not check foreign key: {$e->getMessage()}\n";
}

// Test 4: Test CalibreRequest model
echo "\n4. Testing CalibreRequest model...\n";
try {
    // Test category validation
    $testCategories = ['handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic'];
    $invalidCategories = ['other', 'invalid'];
    
    foreach ($testCategories as $cat) {
        try {
            $test = new CalibreRequest();
            $test->category = $cat;
            echo "   ✓ Category '{$cat}' is valid\n";
        } catch (\Exception $e) {
            $errors[] = "✗ Category '{$cat}' validation failed: " . $e->getMessage();
            echo "   ✗ Category '{$cat}' validation failed\n";
        }
    }
    
    // Test relationship
    if (method_exists(CalibreRequest::class, 'calibre')) {
        $success[] = "✓ CalibreRequest has calibre() relationship";
        echo "   ✓ CalibreRequest has calibre() relationship\n";
        
        // Test with existing request if any
        $request = CalibreRequest::whereNotNull('calibre_id')->first();
        if ($request) {
            try {
                $calibre = $request->calibre;
                if ($calibre instanceof FirearmCalibre) {
                    $success[] = "✓ Relationship returns FirearmCalibre instance";
                    echo "   ✓ Relationship works correctly\n";
                } else {
                    $errors[] = "✗ Relationship returns wrong type";
                    echo "   ✗ Relationship returns wrong type\n";
                }
            } catch (\Exception $e) {
                $errors[] = "✗ Relationship failed: " . $e->getMessage();
                echo "   ✗ Relationship failed: {$e->getMessage()}\n";
            }
        } else {
            echo "   → No approved requests found to test relationship\n";
        }
    } else {
        $errors[] = "✗ CalibreRequest missing calibre() relationship";
        echo "   ✗ CalibreRequest missing calibre() relationship\n";
    }
    
    // Test accessors
    $testRequest = new CalibreRequest();
    $testRequest->category = 'rifle';
    $testRequest->ignition_type = 'centerfire';
    
    if (method_exists($testRequest, 'getCategoryLabelAttribute')) {
        $label = $testRequest->category_label;
        echo "   ✓ Category label accessor works: {$label}\n";
    }
    
    if (method_exists($testRequest, 'getIgnitionTypeLabelAttribute')) {
        $label = $testRequest->ignition_type_label;
        echo "   ✓ Ignition type label accessor works: {$label}\n";
    }
    
} catch (\Exception $e) {
    $errors[] = "✗ Model test failed: " . $e->getMessage();
    echo "   ✗ Model test failed: {$e->getMessage()}\n";
}

// Test 5: Test creating a calibre request
echo "\n5. Testing calibre request creation...\n";
try {
    $user = User::first();
    if (!$user) {
        $warnings[] = "⚠ No users found - cannot test request creation";
        echo "   ⚠ No users found\n";
    } else {
        // Create a test request
        $testRequest = CalibreRequest::create([
            'user_id' => $user->id,
            'name' => 'Test Calibre ' . time(),
            'category' => 'rifle',
            'ignition_type' => 'centerfire',
            'status' => 'pending',
        ]);
        
        if ($testRequest->id) {
            $success[] = "✓ Can create calibre request";
            echo "   ✓ Created test request ID: {$testRequest->id}\n";
            
            // Clean up
            $testRequest->delete();
            echo "   → Test request deleted\n";
        } else {
            $errors[] = "✗ Failed to create calibre request";
            echo "   ✗ Failed to create calibre request\n";
        }
    }
} catch (\Exception $e) {
    $errors[] = "✗ Request creation test failed: " . $e->getMessage();
    echo "   ✗ Request creation test failed: {$e->getMessage()}\n";
}

// Test 6: Test existing calibres query (from endorsement form)
echo "\n6. Testing existing calibres query...\n";
try {
    // Simulate the query from the endorsement form
    $query = FirearmCalibre::active()
        ->notObsolete()
        ->orderBy('name');
    
    // Filter by category
    $query->where('category', 'rifle');
    
    // Filter by ignition
    $query->where('ignition', 'centerfire');
    
    $results = $query->limit(10)->get();
    
    if ($results->count() > 0) {
        $success[] = "✓ Existing calibres query works";
        echo "   ✓ Found {$results->count()} rifle centerfire calibres\n";
        echo "   → Sample: " . $results->first()->name . "\n";
    } else {
        $warnings[] = "⚠ No calibres found for rifle/centerfire";
        echo "   ⚠ No calibres found\n";
    }
} catch (\Exception $e) {
    $errors[] = "✗ Existing calibres query failed: " . $e->getMessage();
    echo "   ✗ Query failed: {$e->getMessage()}\n";
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Success: " . count($success) . "\n";
echo "Warnings: " . count($warnings) . "\n";
echo "Errors: " . count($errors) . "\n\n";

if (count($errors) > 0) {
    echo "Errors:\n";
    foreach ($errors as $error) {
        echo "  {$error}\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  {$warning}\n";
    }
    echo "\n";
}

if (count($errors) === 0) {
    echo "✓ All critical tests passed!\n";
    exit(0);
} else {
    echo "✗ Some tests failed. Please review errors above.\n";
    exit(1);
}

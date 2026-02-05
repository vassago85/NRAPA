<?php

use App\Models\User;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Services\ExcelMemberImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a membership type for testing
    $this->membershipType = MembershipType::create([
        'slug' => 'standard',
        'name' => 'Standard Membership',
        'description' => 'Standard membership type',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 500.00,
        'is_active' => true,
    ]);
});

test('excel importer can generate template', function () {
    // Skip if PhpSpreadsheet is not available
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $this->markTestSkipped('PhpSpreadsheet is not installed. Run: composer install');
    }
    
    $importer = new ExcelMemberImporter();
    $tempPath = storage_path('app/temp/test_template.xlsx');
    
    File::ensureDirectoryExists(dirname($tempPath));
    
    $importer->generateTemplate($tempPath);
    
    expect(File::exists($tempPath))->toBeTrue();
    
    // Cleanup
    File::delete($tempPath);
});

test('excel importer creates user from valid data', function () {
    $importer = new ExcelMemberImporter();
    
    // Create a simple Excel file manually (or use a test fixture)
    // For now, we'll test the createUser method directly using reflection
    $reflection = new ReflectionClass($importer);
    $method = $reflection->getMethod('createUser');
    $method->setAccessible(true);
    
    $memberData = [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'id_number' => '8001015800085',
        'phone' => '+27123456789',
        'date_of_birth' => '1980-01-01',
        'physical_address' => '123 Main St',
        'postal_address' => 'PO Box 123',
    ];
    
    $user = $method->invoke($importer, $memberData, 'password123');
    
    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('John Doe');
    expect($user->email)->toBe('john.doe@example.com');
    expect($user->role)->toBe(User::ROLE_MEMBER);
    expect($user->email_verified_at)->not->toBeNull();
});

test('excel importer validates required fields', function () {
    $importer = new ExcelMemberImporter();
    
    $reflection = new ReflectionClass($importer);
    $method = $reflection->getMethod('createUser');
    $method->setAccessible(true);
    
    // Missing name
    expect(fn() => $method->invoke($importer, ['email' => 'test@example.com'], 'password'))
        ->toThrow(Exception::class, 'Name and email are required');
    
    // Missing email
    expect(fn() => $method->invoke($importer, ['name' => 'Test'], 'password'))
        ->toThrow(Exception::class, 'Name and email are required');
    
    // Invalid email
    expect(fn() => $method->invoke($importer, ['name' => 'Test', 'email' => 'invalid-email'], 'password'))
        ->toThrow(Exception::class, 'Invalid email format');
});

test('excel importer creates membership for user', function () {
    $importer = new ExcelMemberImporter();
    $user = User::factory()->create();
    
    $reflection = new ReflectionClass($importer);
    $method = $reflection->getMethod('createMembership');
    $method->setAccessible(true);
    
    $memberData = [
        'membership_number' => 'STD-2026-0001',
        'membership_type' => 'standard',
        'status' => 'active',
    ];
    
    $method->invoke($importer, $user, $memberData, $this->membershipType, false, false);
    
    $membership = Membership::where('user_id', $user->id)->first();
    
    expect($membership)->not->toBeNull();
    expect($membership->membership_number)->toBe('STD-2026-0001');
    expect($membership->membership_type_id)->toBe($this->membershipType->id);
    expect($membership->status)->toBe('active');
});

test('excel importer generates membership number if not provided', function () {
    $importer = new ExcelMemberImporter();
    $user = User::factory()->create();
    
    $reflection = new ReflectionClass($importer);
    $method = $reflection->getMethod('createMembership');
    $method->setAccessible(true);
    
    $memberData = [
        'membership_number' => '', // Empty
        'membership_type' => 'standard',
        'status' => 'active',
    ];
    
    $method->invoke($importer, $user, $memberData, $this->membershipType, false, false);
    
    $membership = Membership::where('user_id', $user->id)->first();
    
    expect($membership)->not->toBeNull();
    expect($membership->membership_number)->not->toBeEmpty();
    // Membership type slug is "standard", so prefix is "STA" (first 3 letters)
    expect($membership->membership_number)->toMatch('/STA-\d{4}-\d{4}/');
});

test('excel importer handles missing file gracefully', function () {
    // Skip if PhpSpreadsheet is not available
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $this->markTestSkipped('PhpSpreadsheet is not installed. Run: composer install');
    }
    
    $importer = new ExcelMemberImporter();
    
    $result = $importer->importFromExcel(
        storage_path('app/temp/nonexistent.xlsx'),
        ['skip_duplicates' => true, 'default_password' => 'password123']
    );
    
    // Should fail because file doesn't exist
    expect($result['success'])->toBeFalse();
    expect($result)->toHaveKey('imported');
    expect($result)->toHaveKey('skipped');
    expect($result)->toHaveKey('errors');
    expect($result['errors'])->not->toBeEmpty();
});

test('excel importer parses date correctly', function () {
    $importer = new ExcelMemberImporter();
    
    $reflection = new ReflectionClass($importer);
    $method = $reflection->getMethod('parseDate');
    $method->setAccessible(true);
    
    // Test various date formats
    expect($method->invoke($importer, '1980-01-01'))->toBe('1980-01-01');
    expect($method->invoke($importer, '01/01/1980'))->not->toBeNull();
    expect($method->invoke($importer, null))->toBeNull();
    expect($method->invoke($importer, ''))->toBeNull();
});

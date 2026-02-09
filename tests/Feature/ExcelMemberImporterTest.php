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
    // Create membership types that mirror production
    $this->standardType = MembershipType::create([
        'slug' => 'standard-annual',
        'name' => 'Standard Annual Membership',
        'description' => 'Standard annual membership',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 500.00,
        'is_active' => true,
    ]);

    $this->lifetimeType = MembershipType::create([
        'slug' => 'lifetime',
        'name' => 'Lifetime Membership',
        'description' => 'Lifetime membership',
        'duration_type' => 'lifetime',
        'duration_months' => null,
        'requires_renewal' => false,
        'pricing_model' => 'once_off',
        'price' => 5000.00,
        'is_active' => true,
    ]);

    $this->dedicatedBothType = MembershipType::create([
        'slug' => 'dedicated-both',
        'name' => 'Dedicated Hunter & Sport Shooter',
        'description' => 'Dedicated both',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 800.00,
        'is_active' => true,
    ]);
});

test('excel importer can generate template', function () {
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $this->markTestSkipped('PhpSpreadsheet is not installed.');
    }

    $importer = new ExcelMemberImporter();
    $tempPath = storage_path('app/temp/test_template.xlsx');

    File::ensureDirectoryExists(dirname($tempPath));
    $importer->generateTemplate($tempPath);

    expect(File::exists($tempPath))->toBeTrue();

    // Verify template has correct headers
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempPath);
    $headers = $spreadsheet->getActiveSheet()->toArray()[0];

    expect($headers[0])->toContain('Date Joined');
    expect($headers[1])->toBe('Initials');
    expect($headers[2])->toBe('Surname');
    expect($headers[5])->toBe('Email');
    expect($headers[6])->toBe('Membership Type');

    File::delete($tempPath);
});

test('excel importer creates user from valid data', function () {
    $importer = new ExcelMemberImporter();

    $reflection = new ReflectionClass($importer);
    $method = $reflection->getMethod('createUser');
    $method->setAccessible(true);

    $memberData = [
        'name' => 'SP Basson',
        'email' => 'spbasson@example.com',
        'id_number' => '8001015800085',
        'phone' => '084 407 6112',
        'date_of_birth' => '1980-01-01',
        'physical_address' => null,
        'postal_address' => null,
    ];

    $user = $method->invoke($importer, $memberData, 'password123');

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('SP Basson');
    expect($user->email)->toBe('spbasson@example.com');
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
        'membership_number' => 'STA-2026-0001',
        'status' => 'active',
        'date_joined' => '2025-11-24',
        'renewal_date' => '2026-11-24',
        'is_life_member' => false,
    ];

    $method->invoke($importer, $user, $memberData, $this->standardType, false, false);

    $membership = Membership::where('user_id', $user->id)->first();

    expect($membership)->not->toBeNull();
    expect($membership->membership_number)->toBe('STA-2026-0001');
    expect($membership->membership_type_id)->toBe($this->standardType->id);
    expect($membership->status)->toBe('active');
    expect($membership->source)->toBe('import');
});

test('excel importer generates membership number if not provided', function () {
    $importer = new ExcelMemberImporter();
    $user = User::factory()->create();

    $reflection = new ReflectionClass($importer);
    $method = $reflection->getMethod('createMembership');
    $method->setAccessible(true);

    $memberData = [
        'membership_number' => '',
        'status' => 'active',
        'date_joined' => null,
        'renewal_date' => null,
        'is_life_member' => false,
    ];

    $method->invoke($importer, $user, $memberData, $this->standardType, false, false);

    $membership = Membership::where('user_id', $user->id)->first();

    expect($membership)->not->toBeNull();
    expect($membership->membership_number)->not->toBeEmpty();
    expect($membership->membership_number)->toMatch('/STA-\d{4}-\d{4}/');
});

test('excel importer handles missing file gracefully', function () {
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $this->markTestSkipped('PhpSpreadsheet is not installed.');
    }

    $importer = new ExcelMemberImporter();

    $result = $importer->importFromExcel(
        storage_path('app/temp/nonexistent.xlsx'),
        ['skip_duplicates' => true, 'default_password' => 'password123']
    );

    expect($result['success'])->toBeFalse();
    expect($result)->toHaveKey('imported');
    expect($result)->toHaveKey('skipped');
    expect($result)->toHaveKey('errors');
    expect($result['errors'])->not->toBeEmpty();
});

test('excel importer parses dates correctly', function () {
    $importer = new ExcelMemberImporter();

    $reflection = new ReflectionClass($importer);
    $method = $reflection->getMethod('parseDate');
    $method->setAccessible(true);

    // ISO format
    expect($method->invoke($importer, '1980-01-01'))->toBe('1980-01-01');

    // d/m/Y format (South African standard)
    expect($method->invoke($importer, '24/11/2025'))->toBe('2025-11-24');
    expect($method->invoke($importer, '17/12/2025'))->toBe('2025-12-17');

    // Text values should return null
    expect($method->invoke($importer, 'Life Member'))->toBeNull();

    // Empty values
    expect($method->invoke($importer, null))->toBeNull();
    expect($method->invoke($importer, ''))->toBeNull();
});

test('excel importer derives date of birth from SA ID number', function () {
    $importer = new ExcelMemberImporter();

    $reflection = new ReflectionClass($importer);
    $method = $reflection->getMethod('deriveDateOfBirthFromId');
    $method->setAccessible(true);

    expect($method->invoke($importer, '8705185113087'))->toBe('1987-05-18');
    expect($method->invoke($importer, '9907201326086'))->toBe('1999-07-20');
    expect($method->invoke($importer, '0010165037085'))->toBe('2000-10-16');

    // Edge cases
    expect($method->invoke($importer, null))->toBeNull();
    expect($method->invoke($importer, ''))->toBeNull();
    expect($method->invoke($importer, '12345'))->toBeNull(); // too short
});

test('excel importer resolves membership types from common names', function () {
    $importer = new ExcelMemberImporter();

    $reflection = new ReflectionClass($importer);
    $method = $reflection->getMethod('resolveMembershipType');
    $method->setAccessible(true);

    // Dedicated Life Membership → lifetime
    $result = $method->invoke($importer, 'Dedicated Life Membership', null);
    expect($result)->not->toBeNull();
    expect($result->slug)->toBe('lifetime');

    // Regular Member → standard-annual
    $result = $method->invoke($importer, 'Regular Member', null);
    expect($result)->not->toBeNull();
    expect($result->slug)->toBe('standard-annual');

    // Dedicated Hunting & Sport → dedicated-both
    $result = $method->invoke($importer, 'Dedicated Hunting & Sport', null);
    expect($result)->not->toBeNull();
    expect($result->slug)->toBe('dedicated-both');

    // Unknown type with default fallback
    $result = $method->invoke($importer, 'Unknown Type', $this->standardType);
    expect($result)->not->toBeNull();
    expect($result->slug)->toBe('standard-annual');

    // Unknown type with no default
    $result = $method->invoke($importer, 'Completely Unknown', null);
    expect($result)->toBeNull();
});

test('excel importer parses row in expected column format', function () {
    $importer = new ExcelMemberImporter();

    $reflection = new ReflectionClass($importer);
    $method = $reflection->getMethod('parseRow');
    $method->setAccessible(true);

    // Test row matching user's actual spreadsheet format
    $row = ['24/11/2025', 'SP', 'Basson', '0010165037085', '084 407 6112', 'spbasson123@example.com', 'Dedicated Life Membership', 'Life Member', 'Active'];

    $parsed = $method->invoke($importer, $row);

    expect($parsed['name'])->toBe('SP Basson');
    expect($parsed['email'])->toBe('spbasson123@example.com');
    expect($parsed['id_number'])->toBe('0010165037085');
    expect($parsed['phone'])->toBe('084 407 6112');
    expect($parsed['date_of_birth'])->toBe('2000-10-16');
    expect($parsed['membership_type_raw'])->toBe('Dedicated Life Membership');
    expect($parsed['status'])->toBe('active');
    expect($parsed['is_life_member'])->toBeTrue();
    expect($parsed['renewal_date'])->toBeNull(); // Life member has no renewal
});

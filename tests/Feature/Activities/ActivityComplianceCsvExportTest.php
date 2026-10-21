<?php

declare(strict_types=1);

use App\Models\DocumentType;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\ShootingActivity;
use App\Models\User;
use App\Support\Members\ActivityComplianceCsvExport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function csvSeedType(): MembershipType
{
    return MembershipType::create([
        'slug' => 'test-annual-dedicated',
        'name' => 'Annual Dedicated',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 900,
        'renewal_price' => 800,
        'allows_dedicated_status' => true,
        'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
        'is_active' => true,
    ]);
}

function csvSeedLifetimeType(): MembershipType
{
    return MembershipType::create([
        'slug' => 'test-lifetime',
        'name' => 'Lifetime Membership',
        'duration_type' => 'lifetime',
        'duration_months' => 0,
        'requires_renewal' => false,
        'pricing_model' => 'once_off',
        'initial_price' => 15000,
        'renewal_price' => 0,
        'allows_dedicated_status' => true,
        'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
        'is_active' => true,
    ]);
}

function csvMakeMember(MembershipType $type, array $overrides = [], array $membershipOverrides = []): User
{
    $user = User::factory()->create($overrides + ['role' => User::ROLE_MEMBER]);
    Membership::create(array_merge([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'applied_at' => now()->subYears(2),
        'approved_at' => now()->subYears(2),
        'activated_at' => now()->subYears(2),
        'expires_at' => now()->addMonths(6),
    ], $membershipOverrides));
    return $user;
}

function csvApproveActivities(User $user, int $year, int $count): void
{
    $date = \Carbon\Carbon::create($year, 5, 1);
    for ($i = 0; $i < $count; $i++) {
        ShootingActivity::create([
            'user_id' => $user->id,
            'activity_date' => $date->copy()->addDays($i),
            'activity_type' => 'range_session',
            'track' => 'sport',
            'status' => 'approved',
            'approved_at' => now(),
            'location' => 'Test range',
            'description' => 'test',
        ]);
    }
}

/** @return array<int, array<int, string>> */
function csvRead(string $out): array
{
    $lines = array_filter(explode("\n", trim($out)), fn ($l) => $l !== '');
    return array_map(fn ($line) => str_getcsv($line), $lines);
}

function csvRun(ActivityComplianceCsvExport $export): array
{
    $out = fopen('php://temp', 'r+');
    $export->write($out);
    rewind($out);
    $contents = stream_get_contents($out);
    fclose($out);
    return csvRead($contents);
}

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_OWNER]);
});

it('writes the ten configured header columns', function () {
    $export = new ActivityComplianceCsvExport($this->admin, 2026);
    $rows = csvRun($export);

    expect($rows[0])->toBe([
        'Name',
        'Surname',
        'ID',
        'NRAPA Number',
        'Phone',
        'Email',
        'Membership',
        'Status',
        'Good Standing (Payment)',
        'Required Activities Compliant (2026)',
    ]);
});

it('includes every current member, not only activity-compliant ones', function () {
    $type = csvSeedType();

    $compliant = csvMakeMember($type, ['name' => 'Jane Doe']);
    csvApproveActivities($compliant, now()->year, 2);

    $short = csvMakeMember($type, ['name' => 'John Smith']);
    csvApproveActivities($short, now()->year, 1);

    $rows = csvRun(new ActivityComplianceCsvExport($this->admin, now()->year));

    $surnames = array_map(fn ($r) => $r[1], array_slice($rows, 1));
    expect($surnames)->toContain('Doe')->toContain('Smith');
});

it('writes the full row shape for a compliant annual member', function () {
    $type = csvSeedType();

    $user = csvMakeMember($type, [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '0821234567',
        'id_number' => '8001015555088',
        'member_number' => 42,
    ]);
    csvApproveActivities($user, now()->year, 2);

    $rows = csvRun(new ActivityComplianceCsvExport($this->admin, now()->year));
    $row = $rows[1];

    expect($row[0])->toBe('Jane')
        ->and($row[1])->toBe('Doe')
        ->and($row[2])->toBe('8001015555088')
        ->and($row[3])->toBe('NRAPA-00042')
        ->and($row[4])->toBe('0821234567')
        ->and($row[5])->toBe('jane@example.com')
        ->and($row[6])->toBe('Annual Dedicated')
        ->and($row[7])->toBe('Active')
        ->and($row[8])->toBe('Yes')          // Good Standing (Payment)
        ->and($row[9])->toBe('Yes');         // Required Activities Compliant
});

it('marks a lifetime member as in good standing regardless of expiry', function () {
    $lifetime = csvSeedLifetimeType();
    // Deliberately set a past expires_at — a lifetime member should still
    // show "Good Standing: Yes" because they have no renewal to miss.
    $user = csvMakeMember($lifetime, ['name' => 'Lily Lifer'], ['expires_at' => now()->subYear()]);
    csvApproveActivities($user, now()->year, 2);

    $rows = csvRun(new ActivityComplianceCsvExport($this->admin, now()->year));
    $row = $rows[1];

    expect($row[6])->toBe('Lifetime Membership')
        ->and($row[8])->toBe('Yes')          // Good Standing — lifetime is always Yes
        ->and($row[9])->toBe('Yes');         // Required Activities Compliant
});

it('marks a lifetime member who has not banked activities as Good Standing Yes, Required No', function () {
    $lifetime = csvSeedLifetimeType();
    $user = csvMakeMember($lifetime, ['name' => 'Idle Lifer']);
    // No activities approved.

    $rows = csvRun(new ActivityComplianceCsvExport($this->admin, now()->year));
    $row = $rows[1];

    expect($row[8])->toBe('Yes')             // Lifetime -> Yes
        ->and($row[9])->toBe('No');          // No activities -> No
});

it('marks a non-dedicated-status member as Not applicable for required activities', function () {
    // The clarification letter feature: Basic / transfer-only types never
    // carry the activity requirement, so the CSV cell should read
    // "Not applicable" rather than Yes/No.
    $basic = MembershipType::create([
        'slug' => 'test-basic',
        'name' => 'Basic',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 300,
        'renewal_price' => 300,
        'allows_dedicated_status' => false,
        'dedicated_type' => null,
        'is_active' => true,
    ]);

    $user = csvMakeMember($basic, ['name' => 'Basil Basic']);
    // Zero activities on purpose — the cell must NOT read "No", it must
    // read "Not applicable" because the requirement does not apply.

    $rows = csvRun(new ActivityComplianceCsvExport($this->admin, now()->year));
    $row = $rows[1];

    expect($row[6])->toBe('Basic')
        ->and($row[8])->toBe('Yes')              // Still in good standing (payment)
        ->and($row[9])->toBe('Not applicable');  // Activity rule does not apply
});

it('marks an annual member whose membership lapses before the window end as Good Standing No', function () {
    $type = csvSeedType();
    // Membership expires 30 September — before the 31 October window end.
    $user = csvMakeMember($type, ['name' => 'Chris Lapsed'], [
        'expires_at' => \Carbon\Carbon::create(now()->year, 9, 30),
    ]);
    csvApproveActivities($user, now()->year, 2);

    $rows = csvRun(new ActivityComplianceCsvExport($this->admin, now()->year));
    $row = $rows[1];

    expect($row[8])->toBe('No')              // Expiry before window end
        ->and($row[9])->toBe('Yes');         // But activities ARE compliant
});

it('splits name using verified ID document metadata when present', function () {
    DocumentType::create([
        'slug' => 'identity-document',
        'name' => 'ID',
        'description' => 'Identity document',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $type = csvSeedType();

    $user = csvMakeMember($type, ['name' => 'Fallback Display']);
    csvApproveActivities($user, now()->year, 2);

    $idType = DocumentType::where('slug', 'identity-document')->firstOrFail();
    MemberDocument::create([
        'user_id' => $user->id,
        'document_type_id' => $idType->id,
        'file_path' => 'docs/x.pdf',
        'original_filename' => 'id.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'status' => 'verified',
        'uploaded_at' => now()->subDay(),
        'verified_at' => now(),
        'metadata' => [
            'surname' => 'van der Westhuizen',
            'names' => 'Pieter Johan',
            'identity_number' => '8001015555088',
        ],
    ]);

    $rows = csvRun(new ActivityComplianceCsvExport($this->admin, now()->year));
    expect($rows[1][0])->toBe('Pieter Johan')
        ->and($rows[1][1])->toBe('van der Westhuizen')
        ->and($rows[1][2])->toBe('8001015555088');
});

it('falls back to splitting users.name on the last whitespace', function () {
    $type = csvSeedType();

    $user = csvMakeMember($type, ['name' => 'Mary Ann Jones']);
    csvApproveActivities($user, now()->year, 2);

    $rows = csvRun(new ActivityComplianceCsvExport($this->admin, now()->year));
    expect($rows[1][0])->toBe('Mary Ann')->and($rows[1][1])->toBe('Jones');
});

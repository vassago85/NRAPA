<?php

declare(strict_types=1);

use App\Models\DocumentType;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\ShootingActivity;
use App\Models\User;
use App\Support\Members\ActivityComplianceCsvExport;
use App\Support\Members\MemberCompliance;
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

function csvMakeMember(MembershipType $type, array $overrides = []): User
{
    $user = User::factory()->create($overrides + ['role' => User::ROLE_MEMBER]);
    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'applied_at' => now()->subYears(2),
        'approved_at' => now()->subYears(2),
        'activated_at' => now()->subYears(2),
        'expires_at' => now()->addMonths(6),
    ]);
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

beforeEach(function () {
    MemberCompliance::flush();
    $this->admin = User::factory()->create(['role' => User::ROLE_OWNER]);
});

it('writes all nine header columns and only includes members at or above the minimum', function () {
    $type = csvSeedType();

    $atMinimum = csvMakeMember($type, ['name' => 'Jane Doe']);
    csvApproveActivities($atMinimum, now()->year, 2);

    $short = csvMakeMember($type, ['name' => 'John Smith']);
    csvApproveActivities($short, now()->year, 1);

    $export = new ActivityComplianceCsvExport($this->admin, now()->year);
    $out = fopen('php://temp', 'r+');
    $export->write($out);
    rewind($out);
    $contents = stream_get_contents($out);
    fclose($out);

    $rows = csvRead($contents);
    expect($rows[0])->toBe([
        'Name',
        'Surname',
        'ID',
        'NRAPA Number',
        'Phone',
        'Email',
        'Membership',
        'Status',
        'Paid Up & Active for ' . now()->year,
    ]);

    $dataRows = array_slice($rows, 1);
    $surnames = array_map(fn ($r) => $r[1], $dataRows);
    expect($surnames)->toContain('Doe')->not->toContain('Smith');
});

it('writes the full row shape for a compliant member — contact, membership, status, paid-up flag', function () {
    \App\Models\DocumentType::create([
        'slug' => 'identity-document',
        'name' => 'ID',
        'description' => 'Identity document',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $type = csvSeedType();

    $user = csvMakeMember($type, [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '0821234567',
        'id_number' => '8001015555088',
        'member_number' => 42,
    ]);
    csvApproveActivities($user, now()->year, 2);
    // Verified ID doc so the resolver clears id_document and compliant=Yes.
    $idType = \App\Models\DocumentType::where('slug', 'identity-document')->firstOrFail();
    \App\Models\MemberDocument::create([
        'user_id' => $user->id,
        'document_type_id' => $idType->id,
        'file_path' => 'docs/x.pdf',
        'original_filename' => 'id.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'status' => 'verified',
        'uploaded_at' => now()->subDay(),
        'verified_at' => now(),
    ]);

    $export = new ActivityComplianceCsvExport($this->admin, now()->year);
    $out = fopen('php://temp', 'r+');
    $export->write($out);
    rewind($out);
    $contents = stream_get_contents($out);
    fclose($out);

    $rows = csvRead($contents);
    $row = $rows[1];

    // ['Name','Surname','ID','NRAPA Number','Phone','Email','Membership','Status','Paid Up & Active for {year}']
    expect($row[0])->toBe('Jane')
        ->and($row[1])->toBe('Doe')
        ->and($row[2])->toBe('8001015555088')
        ->and($row[3])->toBe('NRAPA-00042')
        ->and($row[4])->toBe('0821234567')
        ->and($row[5])->toBe('jane@example.com')
        ->and($row[6])->toBe('Annual Dedicated')
        ->and($row[7])->toBe('Active')
        ->and($row[8])->toBe('Yes');
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

    $export = new ActivityComplianceCsvExport($this->admin, now()->year);
    $out = fopen('php://temp', 'r+');
    $export->write($out);
    rewind($out);
    $contents = stream_get_contents($out);
    fclose($out);

    $rows = csvRead($contents);
    // ['Name','Surname','ID',...]
    expect($rows[1][0])->toBe('Pieter Johan')
        ->and($rows[1][1])->toBe('van der Westhuizen')
        ->and($rows[1][2])->toBe('8001015555088');
});

it('falls back to splitting users.name on the last whitespace', function () {
    $type = csvSeedType();

    $user = csvMakeMember($type, ['name' => 'Mary Ann Jones']);
    csvApproveActivities($user, now()->year, 2);

    $export = new ActivityComplianceCsvExport($this->admin, now()->year);
    $out = fopen('php://temp', 'r+');
    $export->write($out);
    rewind($out);
    $contents = stream_get_contents($out);
    fclose($out);

    $rows = csvRead($contents);
    // ['Name','Surname',...]
    expect($rows[1][0])->toBe('Mary Ann')->and($rows[1][1])->toBe('Jones');
});

it('reports compliant=No when the member banked activities but is missing other requirements', function () {
    $type = csvSeedType();
    // No phone number — compliance resolver's profile_phone requirement returns
    // NotMet, so even with the activity minimum banked the overall compliant
    // flag is false. This exercises the "Yes/No" column independently of the
    // row-filter rule.
    $user = csvMakeMember($type, ['name' => 'Grace Hopper', 'phone' => null]);
    csvApproveActivities($user, now()->year, 2);

    $export = new ActivityComplianceCsvExport($this->admin, now()->year);
    $out = fopen('php://temp', 'r+');
    $export->write($out);
    rewind($out);
    $contents = stream_get_contents($out);
    fclose($out);

    $rows = csvRead($contents);
    // Paid Up & Active column is index 8 in the new 9-column shape.
    expect($rows[1][8])->toBe('No');
});

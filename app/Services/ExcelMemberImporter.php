<?php

namespace App\Services;

use App\Mail\ImportWelcome;
use App\Models\ActivityType;
use App\Models\EndorsementRequest;
use App\Models\ImportFailure;
use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestAttempt;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\ShootingActivity;
use App\Models\SystemSetting;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelMemberImporter
{
    /**
     * Map of common membership status names (from club spreadsheets) to system membership type slugs.
     */
    protected array $membershipTypeMap = [
        // Dedicated types
        'dedicated sport' => 'dedicated-sport',
        'dedicated sport shooter' => 'dedicated-sport',
        'dedicated hunter' => 'dedicated-hunter',
        'dedicated hunting' => 'dedicated-hunter',
        'dedicated hunting & sport' => 'dedicated-both',
        'dedicated hunter & sport' => 'dedicated-both',
        'dedicated hunter & sport shooter' => 'dedicated-both',
        'dedicated both' => 'dedicated-both',

        // Lifetime / life membership
        'dedicated life membership' => 'lifetime',
        'dedicated lifetime' => 'lifetime',
        'life member' => 'lifetime',
        'life membership' => 'lifetime',
        'lifetime' => 'lifetime',
        'lifetime membership' => 'lifetime',

        // Standard / regular / occasional
        'regular member' => 'standard-annual',
        'regular' => 'standard-annual',
        'standard' => 'standard-annual',
        'standard annual' => 'standard-annual',
        'standard annual membership' => 'standard-annual',
        'occasional' => 'standard-annual',
        'occasional member' => 'standard-annual',

        // Junior
        'junior' => 'junior-annual',
        'junior member' => 'junior-annual',
        'junior annual' => 'junior-annual',
    ];

    /**
     * Import members from Excel file.
     *
     * @param  string  $filePath  Path to Excel file
     * @param  array  $options  Import options
     * @return array ['success' => bool, 'imported' => int, 'skipped' => int, 'errors' => array]
     */
    public function importFromExcel(string $filePath, array $options = []): array
    {
        $defaultPassword = $options['default_password'] ?? 'password123';
        $defaultMembershipTypeSlug = $options['default_membership_type'] ?? null;
        $skipDuplicates = $options['skip_duplicates'] ?? true;
        $autoApprove = $options['auto_approve'] ?? false;
        $autoActivate = $options['auto_activate'] ?? false;
        $sendWelcomeEmail = $options['send_welcome_email'] ?? true;
        $autoPassTests = $options['auto_pass_knowledge_tests'] ?? true;
        $autoActivities = $options['auto_create_activities'] ?? false;

        $batchId = (string) Str::uuid();
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $emailsSent = 0;
        $errors = [];
        $importedMembers = [];

        try {
            // Load Excel file
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            array_shift($rows);

            // Get default membership type if specified
            $defaultMembershipType = null;
            if ($defaultMembershipTypeSlug) {
                $defaultMembershipType = MembershipType::where('slug', $defaultMembershipTypeSlug)->first();
            }

            DB::beginTransaction();

            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2; // +2 because we skipped header and array is 0-indexed

                try {
                    // Parse row data
                    $memberData = $this->parseRow($row);

                    // Skip empty rows (need at least surname and email)
                    if (empty($memberData['name']) || empty($memberData['email'])) {
                        continue;
                    }

                    // Check for duplicate email
                    if ($skipDuplicates && User::where('email', $memberData['email'])->exists()) {
                        $skipped++;
                        $this->recordFailure($batchId, $rowNumber, $row, "User with email '{$memberData['email']}' already exists");

                        continue;
                    }

                    // Check for duplicate ID number
                    if (! empty($memberData['id_number']) && $skipDuplicates && User::where('id_number', $memberData['id_number'])->exists()) {
                        $skipped++;
                        $this->recordFailure($batchId, $rowNumber, $row, "User with ID number '{$memberData['id_number']}' already exists");

                        continue;
                    }

                    // Create user
                    $user = $this->createUser($memberData, $defaultPassword);

                    // Create membership
                    $membershipType = $this->resolveMembershipType($memberData['membership_type_raw'], $defaultMembershipType);
                    $membership = null;

                    if ($membershipType) {
                        $membership = $this->createMembership($user, $memberData, $membershipType, $autoApprove, $autoActivate, 'import');

                        $rowPassTests = $memberData['knowledge_test'] ?? null;
                        $shouldPassTests = $rowPassTests !== null ? $rowPassTests : $autoPassTests;
                        if ($shouldPassTests) {
                            $this->autoPassKnowledgeTests($user, $membershipType);
                        }

                        $rowActivities = $memberData['activities'] ?? null;
                        $shouldCreateActivities = $rowActivities !== null ? $rowActivities : $autoActivities;
                        if ($shouldCreateActivities) {
                            $this->autoCreateActivities($user, $membershipType);
                        }
                    }

                    if ($sendWelcomeEmail && $membership) {
                        $importedMembers[] = ['user' => $user, 'membership' => $membership];
                    }

                    $imported++;

                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Row {$rowNumber}: ".$e->getMessage();
                    $this->recordFailure($batchId, $rowNumber, $row, $e->getMessage());

                    continue;
                }
            }

            DB::commit();

            // Queue welcome emails after successful commit
            foreach ($importedMembers as $member) {
                try {
                    Mail::to($member['user']->email)->queue(new ImportWelcome(
                        $member['user'],
                        $member['membership'],
                        $defaultPassword,
                    ));
                    $emailsSent++;
                } catch (Exception $e) {
                    Log::warning('Failed to queue import welcome email', [
                        'user_id' => $member['user']->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($imported > 0) {
                try {
                    app(NtfyService::class)->notifyAdmins(
                        'new_member',
                        "Bulk Import: {$imported} Members",
                        "{$imported} members imported. {$emailsSent} welcome emails queued. {$failed} failed.",
                    );
                } catch (Exception $e) {}
            }

            return [
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'failed' => $failed,
                'emails_sent' => $emailsSent,
                'errors' => $errors,
                'batch_id' => $batchId,
            ];

        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'imported' => $imported,
                'skipped' => $skipped,
                'failed' => $failed,
                'emails_sent' => 0,
                'errors' => array_merge($errors, ['General error: '.$e->getMessage()]),
                'batch_id' => $batchId,
            ];
        }
    }

    /**
     * Record a failed import row to the database for later review/retry.
     */
    protected function recordFailure(string $batchId, int $rowNumber, array $rawRow, string $error): void
    {
        ImportFailure::create([
            'batch_id' => $batchId,
            'row_number' => $rowNumber,
            'row_data' => [
                'date_joined' => trim($rawRow[0] ?? ''),
                'initials' => trim($rawRow[1] ?? ''),
                'surname' => trim($rawRow[2] ?? ''),
                'id_number' => trim($rawRow[3] ?? ''),
                'phone' => trim($rawRow[4] ?? ''),
                'email' => trim($rawRow[5] ?? ''),
                'membership_type' => trim($rawRow[6] ?? ''),
                'renewal_date' => trim($rawRow[7] ?? ''),
                'status' => trim($rawRow[8] ?? ''),
                'knowledge_test' => trim($rawRow[9] ?? ''),
                'activities' => trim($rawRow[10] ?? ''),
            ],
            'error_message' => Str::limit($error, 497),
            'imported_by' => auth()->id(),
        ]);
    }

    /**
     * Import a single member from edited row data (for retrying a failed import).
     *
     * @param  array  $rowData  The edited row data (keyed: initials, surname, email, etc.)
     * @param  array  $options  Import options (default_password, default_membership_type, auto_approve, auto_activate)
     * @return array ['success' => bool, 'error' => ?string]
     */
    public function importSingleMember(array $rowData, array $options = []): array
    {
        $defaultPassword = $options['default_password'] ?? 'Nrapa2026!';
        $defaultMembershipTypeSlug = $options['default_membership_type'] ?? null;
        $autoApprove = $options['auto_approve'] ?? true;
        $autoActivate = $options['auto_activate'] ?? true;
        $sendWelcomeEmail = $options['send_welcome_email'] ?? true;
        $source = $options['source'] ?? 'import';
        $autoPassTests = $options['auto_pass_knowledge_tests'] ?? ($source === 'import');
        $autoActivities = $options['auto_create_activities'] ?? false;

        try {
            // Rebuild into the array format parseRow expects (column-indexed)
            $row = [
                $rowData['date_joined'] ?? '',
                $rowData['initials'] ?? '',
                $rowData['surname'] ?? '',
                $rowData['id_number'] ?? '',
                $rowData['phone'] ?? '',
                $rowData['email'] ?? '',
                $rowData['membership_type'] ?? '',
                $rowData['renewal_date'] ?? '',
                $rowData['status'] ?? '',
            ];

            $memberData = $this->parseRow($row);

            if (empty($memberData['name']) || empty($memberData['email'])) {
                return ['success' => false, 'error' => 'Name (initials + surname) and email are required.'];
            }

            // Check for duplicate email
            if (User::where('email', $memberData['email'])->exists()) {
                return ['success' => false, 'error' => "User with email '{$memberData['email']}' already exists."];
            }

            // Check for duplicate ID number
            if (! empty($memberData['id_number']) && User::where('id_number', $memberData['id_number'])->exists()) {
                return ['success' => false, 'error' => "User with ID number '{$memberData['id_number']}' already exists."];
            }

            $defaultMembershipType = $defaultMembershipTypeSlug
                ? MembershipType::where('slug', $defaultMembershipTypeSlug)->first()
                : null;

            DB::beginTransaction();

            $user = $this->createUser($memberData, $defaultPassword);

            $membershipType = $this->resolveMembershipType($memberData['membership_type_raw'], $defaultMembershipType);
            $membership = null;
            if ($membershipType) {
                $membership = $this->createMembership($user, $memberData, $membershipType, $autoApprove, $autoActivate, $source);

                if ($autoPassTests) {
                    $this->autoPassKnowledgeTests($user, $membershipType);
                }
                if ($autoActivities) {
                    $this->autoCreateActivities($user, $membershipType);
                }
            }

            DB::commit();

            $emailSent = false;
            if ($sendWelcomeEmail && $membership) {
                try {
                    Mail::to($user->email)->send(new ImportWelcome($user, $membership, $defaultPassword));
                    $emailSent = true;
                } catch (Exception $e) {
                    Log::warning('Failed to send import welcome email', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return ['success' => true, 'error' => null, 'user' => $user, 'email_sent' => $emailSent];

        } catch (Exception $e) {
            DB::rollBack();

            return ['success' => false, 'error' => $e->getMessage(), 'user' => null];
        }
    }

    /**
     * Parse a row from Excel into member data array.
     *
     * Expected columns:
     *  A: Date Joined (d/m/Y)
     *  B: Initials
     *  C: Surname
     *  D: ID Number
     *  E: Tel Number
     *  F: Email
     *  G: Membership Type (e.g. "Dedicated Hunting & Sport", "Regular Member", "Dedicated Life Membership")
     *  H: Renewal Date (d/m/Y or "Life Member")
     *  I: Activities / Status (e.g. "Active" or blank)
     */
    protected function parseRow(array $row): array
    {
        $initials = trim($row[1] ?? '');
        $surname = trim($row[2] ?? '');
        $name = trim("{$initials} {$surname}");

        $idNumber = trim($row[3] ?? '');
        $dateOfBirth = $this->deriveDateOfBirthFromId($idNumber);

        $membershipTypeRaw = trim($row[6] ?? '');
        $renewalDateRaw = trim($row[7] ?? '');
        $activitiesRaw = trim($row[8] ?? '');

        $isLifeMember = stripos($renewalDateRaw, 'life') !== false
                     || stripos($membershipTypeRaw, 'life') !== false;

        // Determine renewal date (null for life members)
        $renewalDate = $isLifeMember ? null : $this->parseDate($renewalDateRaw);

        // Determine if member should be active:
        //  - "Active" in DS Activities column, OR
        //  - Life member, OR
        //  - Renewal date is in the future (still valid)
        $isActive = (! empty($activitiesRaw) && stripos($activitiesRaw, 'active') !== false)
                  || $isLifeMember
                  || ($renewalDate && $renewalDate >= date('Y-m-d'));

        $knowledgeTestRaw = strtolower(trim($row[9] ?? ''));
        $activitiesRaw2 = strtolower(trim($row[10] ?? ''));

        return [
            'name' => $name,
            'email' => trim(strtolower($row[5] ?? '')),
            'id_number' => $idNumber,
            'phone' => trim($row[4] ?? ''),
            'date_of_birth' => $dateOfBirth,
            'physical_address' => null,
            'postal_address' => null,
            'membership_number' => '', // auto-generated
            'membership_type_raw' => $membershipTypeRaw,
            'status' => $isActive ? 'active' : 'applied',
            'date_joined' => $this->parseDate($row[0] ?? null),
            'renewal_date' => $renewalDate,
            'is_life_member' => $isLifeMember,
            'knowledge_test' => in_array($knowledgeTestRaw, ['yes', 'y', '1', 'true']) ? true
                                : (in_array($knowledgeTestRaw, ['no', 'n', '0', 'false']) ? false : null),
            'activities' => in_array($activitiesRaw2, ['yes', 'y', '1', 'true']) ? true
                            : (in_array($activitiesRaw2, ['no', 'n', '0', 'false']) ? false : null),
        ];
    }

    /**
     * Derive date of birth from a South African ID number.
     * SA ID format: YYMMDD GSSS C A Z (13 digits)
     */
    protected function deriveDateOfBirthFromId(?string $idNumber): ?string
    {
        if (empty($idNumber) || strlen($idNumber) < 6) {
            return null;
        }

        // Extract first 6 digits: YYMMDD
        $yy = substr($idNumber, 0, 2);
        $mm = substr($idNumber, 2, 2);
        $dd = substr($idNumber, 4, 2);

        // Validate month and day ranges
        if ((int) $mm < 1 || (int) $mm > 12 || (int) $dd < 1 || (int) $dd > 31) {
            return null;
        }

        // Determine century: if YY > current 2-digit year, born in 1900s, else 2000s
        $currentYY = (int) date('y');
        $century = ((int) $yy > $currentYY) ? '19' : '20';

        $dateStr = "{$century}{$yy}-{$mm}-{$dd}";

        // Validate the resulting date
        try {
            $date = new \DateTime($dateStr);
            // Sanity check: not in the future
            if ($date > new \DateTime) {
                // Try 1900s instead
                $dateStr = "19{$yy}-{$mm}-{$dd}";
                $date = new \DateTime($dateStr);
            }

            return $date->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Resolve a membership type from the raw spreadsheet value.
     */
    protected function resolveMembershipType(?string $rawType, ?MembershipType $default): ?MembershipType
    {
        if (! empty($rawType)) {
            $normalised = strtolower(trim($rawType));

            // Check our mapping table first
            if (isset($this->membershipTypeMap[$normalised])) {
                $slug = $this->membershipTypeMap[$normalised];
                $type = MembershipType::where('slug', $slug)->first();
                if ($type) {
                    return $type;
                }
            }

            // Fallback: try to find by slug or name directly
            $type = MembershipType::where('slug', Str::slug($rawType))
                ->orWhere('name', $rawType)
                ->first();

            if ($type) {
                return $type;
            }
        }

        return $default;
    }

    /**
     * Parse date from various formats (d/m/Y, Y-m-d, Excel numeric).
     */
    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // "Life Member" or similar text — not a date
        if (is_string($value) && preg_match('/[a-zA-Z]/', $value)) {
            return null;
        }

        // Already ISO format (YYYY-MM-DD)
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return $value;
        }

        // d/m/Y or d-m-Y format
        if (is_string($value) && preg_match('#^(\d{1,2})[/\-](\d{1,2})[/\-](\d{4})$#', $value, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        // Try to parse Excel date (numeric value)
        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);

                return $date->format('Y-m-d');
            } catch (Exception $e) {
                // Fall through
            }
        }

        // Last resort: let PHP try
        try {
            $date = new \DateTime($value);

            return $date->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Create a user from member data.
     */
    protected function createUser(array $memberData, string $defaultPassword): User
    {
        // Validate required fields
        if (empty($memberData['name']) || empty($memberData['email'])) {
            throw new Exception('Name and email are required');
        }

        // Validate email format
        if (! filter_var($memberData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format: '.$memberData['email']);
        }

        $user = User::create([
            'uuid' => Str::uuid(),
            'name' => $memberData['name'],
            'email' => $memberData['email'],
            'id_number' => $memberData['id_number'] ?? null,
            'phone' => $memberData['phone'] ?? null,
            'date_of_birth' => $memberData['date_of_birth'],
            'physical_address' => $memberData['physical_address'] ?? null,
            'postal_address' => $memberData['postal_address'] ?? null,
            'password' => $defaultPassword,
            'role' => User::ROLE_MEMBER,
            'email_verified_at' => now(), // Auto-verify imported users
        ]);

        return $user->fresh();
    }

    /**
     * Create a membership for the user.
     */
    protected function createMembership(User $user, array $memberData, MembershipType $membershipType, bool $autoApprove, bool $autoActivate, string $source = 'import'): Membership
    {
        // Use the user's permanent member number
        $membershipNumber = $user->formatted_member_number;

        // Determine status
        $status = $memberData['status'] ?? 'applied';
        if (! in_array($status, ['applied', 'approved', 'active', 'suspended', 'revoked', 'expired'])) {
            $status = 'applied';
        }

        // Use date_joined for applied_at if available, otherwise now
        $appliedAt = ! empty($memberData['date_joined'])
            ? \Carbon\Carbon::parse($memberData['date_joined'])
            : now();

        $approvedAt = ($status === 'approved' || $status === 'active') ? $appliedAt : null;
        $activatedAt = ($status === 'active') ? $appliedAt : null;

        // Determine expiry date — 12 months from activation/join date unless specified
        $expiresAt = null;
        if (! empty($memberData['renewal_date'])) {
            $expiresAt = \Carbon\Carbon::parse($memberData['renewal_date']);
        } elseif ($membershipType->requires_renewal && $membershipType->duration_months) {
            $baseDate = $activatedAt ?? $appliedAt;
            $expiresAt = $baseDate->copy()->addMonths($membershipType->duration_months);
        }

        return Membership::create([
            'uuid' => Str::uuid(),
            'user_id' => $user->id,
            'membership_type_id' => $membershipType->id,
            'membership_number' => $membershipNumber,
            'status' => $status,
            'applied_at' => $appliedAt,
            'approved_at' => $autoApprove || $approvedAt ? ($approvedAt ?? now()) : null,
            'approved_by' => ($autoApprove || $approvedAt) ? auth()->id() : null,
            'activated_at' => $autoActivate || $activatedAt ? ($activatedAt ?? now()) : null,
            'expires_at' => $expiresAt,
            'source' => $source,
        ]);
    }

    /**
     * Auto-pass all required knowledge tests for an imported member's membership type.
     * Imported members from another system have already proven competency.
     */
    protected function autoPassKnowledgeTests(User $user, MembershipType $membershipType): void
    {
        if ($membershipType->isBasic()) {
            return;
        }

        $dedicatedType = $membershipType->dedicated_type;

        if ($membershipType->isLifetime() && !$dedicatedType) {
            $dedicatedType = 'both';
        }

        $applicableTests = KnowledgeTest::active()
            ->forDedicatedType($dedicatedType)
            ->get();

        foreach ($applicableTests as $test) {
            $alreadyPassed = KnowledgeTestAttempt::where('user_id', $user->id)
                ->where('knowledge_test_id', $test->id)
                ->where('passed', true)
                ->exists();

            if ($alreadyPassed) {
                continue;
            }

            $totalPoints = $test->total_points ?: 100;

            KnowledgeTestAttempt::create([
                'user_id' => $user->id,
                'knowledge_test_id' => $test->id,
                'started_at' => now(),
                'submitted_at' => now(),
                'auto_score' => $totalPoints,
                'total_score' => $totalPoints,
                'passed' => true,
                'marked_at' => now(),
                'marked_by' => auth()->id(),
                'marker_notes' => 'Auto-passed: imported existing member',
            ]);
        }
    }

    /**
     * Auto-create approved activities for an imported member so they meet endorsement requirements.
     * Creates the minimum required number of activities for the current activity year.
     */
    protected function autoCreateActivities(User $user, MembershipType $membershipType): void
    {
        if ($membershipType->isBasic()) {
            return;
        }

        $dedicatedType = $membershipType->dedicated_type;

        if ($membershipType->isLifetime() && !$dedicatedType) {
            $dedicatedType = 'both';
        }

        $tracks = match ($dedicatedType) {
            'hunter' => [['track' => 'hunting', 'setting' => 'endorsement_min_activities_hunter', 'default' => EndorsementRequest::DEFAULT_MIN_ACTIVITIES_HUNTER]],
            'sport', 'sport_shooter' => [['track' => 'sport', 'setting' => 'endorsement_min_activities_sport', 'default' => EndorsementRequest::DEFAULT_MIN_ACTIVITIES_SPORT]],
            'both' => [
                ['track' => 'hunting', 'setting' => 'endorsement_min_activities_hunter', 'default' => EndorsementRequest::DEFAULT_MIN_ACTIVITIES_HUNTER],
                ['track' => 'sport', 'setting' => 'endorsement_min_activities_sport', 'default' => EndorsementRequest::DEFAULT_MIN_ACTIVITIES_SPORT],
            ],
            default => [],
        };

        $currentYear = now()->year;

        foreach ($tracks as $trackConfig) {
            $required = SystemSetting::get($trackConfig['setting'], $trackConfig['default']);

            $existingCount = ShootingActivity::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('track', $trackConfig['track'])
                ->whereBetween('activity_date', [
                    \Carbon\Carbon::create($currentYear, 1, 1)->startOfDay(),
                    \Carbon\Carbon::create($currentYear, 10, 31)->endOfDay(),
                ])
                ->count();

            $needed = max(0, $required - $existingCount);
            if ($needed === 0) {
                continue;
            }

            $activityType = ActivityType::active()
                ->forTrack($trackConfig['track'])
                ->first() ?? ActivityType::active()->first();

            for ($i = 0; $i < $needed; $i++) {
                ShootingActivity::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $user->id,
                    'activity_type_id' => $activityType?->id,
                    'track' => $trackConfig['track'],
                    'activity_date' => now()->subDays($i + 1)->toDateString(),
                    'description' => 'Imported — prior activities from previous system',
                    'status' => 'approved',
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Generate a sample Excel template matching the expected import format.
     */
    public function generateTemplate(string $filePath): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Member Import');

        // Set headers — must match parseRow() column order
        $headers = [
            'Date Joined (DD/MM/YYYY)',
            'Initials',
            'Surname',
            'ID Number',
            'Tel Number',
            'Email',
            'Membership Type',
            'Renewal Date (DD/MM/YYYY)',
            'Status (Active / blank)',
            'Knowledge Test (Yes / No)',
            'Activities (Yes / No)',
        ];

        $sheet->fromArray([$headers], null, 'A1');

        // Add sample rows demonstrating the various membership types
        $sampleRows = [
            ['24/11/2025', 'SP', 'Basson', '0010165037085', '084 407 6112', 'spbasson123@example.com', 'Dedicated Life Membership', 'Life Member', 'Active', 'Yes', 'Yes'],
            ['28/11/2025', 'TA', 'Tilbury', '9907201326086', '072 119 8026', 'tilbury@example.com', 'Regular Member', '27/11/2025', '', 'No', 'No'],
            ['17/12/2025', 'PJ', 'Pretorius', '8705185113087', '071 586 7077', 'pretorius@example.com', 'Dedicated Hunting & Sport', '16/12/2026', 'Active', 'Yes', 'Yes'],
        ];

        $rowNum = 2;
        foreach ($sampleRows as $sampleRow) {
            $sheet->fromArray([$sampleRow], null, "A{$rowNum}");
            $rowNum++;
        }

        // Add a notes sheet explaining the membership types
        $notesSheet = $spreadsheet->createSheet();
        $notesSheet->setTitle('Notes');
        $notesSheet->fromArray([['Membership Type in Spreadsheet', 'Maps To']], null, 'A1');
        $notes = [
            ['Dedicated Sport / Dedicated Sport Shooter', 'Dedicated Sport Shooter'],
            ['Dedicated Hunter / Dedicated Hunting', 'Dedicated Hunter'],
            ['Dedicated Hunting & Sport / Dedicated Both', 'Dedicated Hunter & Sport Shooter'],
            ['Dedicated Life Membership / Lifetime / Life Member', 'Lifetime Membership'],
            ['Regular Member / Standard / Occasional', 'Standard Annual Membership'],
            ['Junior / Junior Member', 'Junior Annual Membership'],
            ['', ''],
            ['Knowledge Test column', 'Yes = auto-pass knowledge test for this member'],
            ['', 'No = skip knowledge test for this member'],
            ['', 'Blank = use the default setting from the import form'],
            ['', ''],
            ['Activities column', 'Yes = auto-create required activities for this member'],
            ['', 'No = skip activities for this member'],
            ['', 'Blank = use the default setting from the import form'],
        ];
        $r = 2;
        foreach ($notes as $note) {
            $notesSheet->fromArray([$note], null, "A{$r}");
            $r++;
        }
        $notesSheet->getStyle('A1:B1')->getFont()->setBold(true);
        $notesSheet->getColumnDimension('A')->setWidth(50);
        $notesSheet->getColumnDimension('B')->setWidth(40);

        // Style header row on main sheet
        $spreadsheet->setActiveSheetIndex(0);
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(35);
        $sheet->getColumnDimension('G')->setWidth(35);
        $sheet->getColumnDimension('H')->setWidth(25);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(24);
        $sheet->getColumnDimension('K')->setWidth(22);

        // Save file
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filePath);
    }
}

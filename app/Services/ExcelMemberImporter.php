<?php

namespace App\Services;

use App\Models\User;
use App\Models\Membership;
use App\Models\MembershipType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class ExcelMemberImporter
{
    /**
     * Import members from Excel file.
     *
     * @param string $filePath Path to Excel file
     * @param array $options Import options (default_password, default_membership_type, skip_duplicates, etc.)
     * @return array ['success' => bool, 'imported' => int, 'skipped' => int, 'errors' => array]
     */
    public function importFromExcel(string $filePath, array $options = []): array
    {
        $defaultPassword = $options['default_password'] ?? 'password123';
        $defaultMembershipTypeSlug = $options['default_membership_type'] ?? null;
        $skipDuplicates = $options['skip_duplicates'] ?? true;
        $autoApprove = $options['auto_approve'] ?? false;
        $autoActivate = $options['auto_activate'] ?? false;
        
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
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
                    // Parse row data (expecting: name, email, id_number, phone, date_of_birth, physical_address, postal_address, membership_number, membership_type, status)
                    $memberData = $this->parseRow($row);
                    
                    // Skip empty rows
                    if (empty($memberData['name']) || empty($memberData['email'])) {
                        continue;
                    }
                    
                    // Check for duplicate email
                    if ($skipDuplicates && User::where('email', $memberData['email'])->exists()) {
                        $skipped++;
                        $errors[] = "Row {$rowNumber}: User with email '{$memberData['email']}' already exists (skipped)";
                        continue;
                    }
                    
                    // Check for duplicate ID number
                    if (!empty($memberData['id_number']) && $skipDuplicates && User::where('id_number', $memberData['id_number'])->exists()) {
                        $skipped++;
                        $errors[] = "Row {$rowNumber}: User with ID number '{$memberData['id_number']}' already exists (skipped)";
                        continue;
                    }
                    
                    // Create user
                    $user = $this->createUser($memberData, $defaultPassword);
                    
                    // Create membership if membership data provided
                    if (!empty($memberData['membership_number']) || $defaultMembershipType) {
                        $this->createMembership($user, $memberData, $defaultMembershipType, $autoApprove, $autoActivate);
                    }
                    
                    $imported++;
                    
                } catch (Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    continue;
                }
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => array_merge($errors, ['General error: ' . $e->getMessage()]),
            ];
        }
    }
    
    /**
     * Parse a row from Excel into member data array.
     */
    protected function parseRow(array $row): array
    {
        // Expected columns: Name, Email, ID Number, Phone, Date of Birth, Physical Address, Postal Address, Membership Number, Membership Type, Status
        return [
            'name' => trim($row[0] ?? ''),
            'email' => trim(strtolower($row[1] ?? '')),
            'id_number' => trim($row[2] ?? ''),
            'phone' => trim($row[3] ?? ''),
            'date_of_birth' => $this->parseDate($row[4] ?? null),
            'physical_address' => trim($row[5] ?? ''),
            'postal_address' => trim($row[6] ?? ''),
            'membership_number' => trim($row[7] ?? ''),
            'membership_type' => trim($row[8] ?? ''),
            'status' => trim(strtolower($row[9] ?? 'active')),
        ];
    }
    
    /**
     * Parse date from various formats.
     */
    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        // If it's already a date string
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return $value;
        }
        
        // Try to parse Excel date (numeric value)
        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (Exception $e) {
                // Fall through to string parsing
            }
        }
        
        // Try to parse as date string
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
        if (!filter_var($memberData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format: ' . $memberData['email']);
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
            'password' => Hash::make($defaultPassword),
            'role' => User::ROLE_MEMBER,
            'email_verified_at' => now(), // Auto-verify imported users
        ]);
        
        // Refresh to ensure email_verified_at is loaded
        return $user->fresh();
    }
    
    /**
     * Create a membership for the user.
     */
    protected function createMembership(User $user, array $memberData, ?MembershipType $defaultType, bool $autoApprove, bool $autoActivate): void
    {
        // Determine membership type
        $membershipType = null;
        
        if (!empty($memberData['membership_type'])) {
            // Try to find by slug or name
            $membershipType = MembershipType::where('slug', Str::slug($memberData['membership_type']))
                ->orWhere('name', $memberData['membership_type'])
                ->first();
        }
        
        if (!$membershipType && $defaultType) {
            $membershipType = $defaultType;
        }
        
        if (!$membershipType) {
            throw new Exception('Membership type not found and no default specified');
        }
        
        // Generate membership number if not provided
        $membershipNumber = $memberData['membership_number'];
        if (empty($membershipNumber)) {
            $membershipNumber = $this->generateMembershipNumber($membershipType);
        }
        
        // Check if membership number already exists
        if (Membership::where('membership_number', $membershipNumber)->exists()) {
            throw new Exception("Membership number '{$membershipNumber}' already exists");
        }
        
        // Determine status
        $status = $memberData['status'] ?? 'applied';
        if (!in_array($status, ['applied', 'approved', 'active', 'suspended', 'revoked', 'expired'])) {
            $status = 'applied';
        }
        
        // Set timestamps based on status
        $now = now();
        $appliedAt = $now;
        $approvedAt = ($status === 'approved' || $status === 'active') ? $now : null;
        $activatedAt = ($status === 'active') ? $now : null;
        
        // Calculate expiry date if needed
        $expiresAt = null;
        if ($membershipType->requires_renewal && $membershipType->duration_months) {
            $expiresAt = $now->copy()->addMonths($membershipType->duration_months);
        }
        
        Membership::create([
            'uuid' => Str::uuid(),
            'user_id' => $user->id,
            'membership_type_id' => $membershipType->id,
            'membership_number' => $membershipNumber,
            'status' => $status,
            'applied_at' => $appliedAt,
            'approved_at' => $autoApprove || $approvedAt ? $approvedAt : null,
            'approved_by' => ($autoApprove || $approvedAt) ? auth()->id() : null,
            'activated_at' => $autoActivate || $activatedAt ? $activatedAt : null,
            'expires_at' => $expiresAt,
        ]);
    }
    
    /**
     * Generate a unique membership number.
     */
    protected function generateMembershipNumber(MembershipType $type): string
    {
        $prefix = strtoupper(substr($type->slug, 0, 3));
        $year = now()->format('Y');
        
        // Find the highest number for this prefix/year
        $lastMembership = Membership::where('membership_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('membership_number', 'desc')
            ->first();
        
        if ($lastMembership) {
            $parts = explode('-', $lastMembership->membership_number);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }
    
    /**
     * Generate a sample Excel template.
     */
    public function generateTemplate(string $filePath): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = [
            'Name',
            'Email',
            'ID Number',
            'Phone',
            'Date of Birth (YYYY-MM-DD)',
            'Physical Address',
            'Postal Address',
            'Membership Number (optional)',
            'Membership Type (slug or name)',
            'Status (applied/approved/active)',
        ];
        
        $sheet->fromArray([$headers], null, 'A1');
        
        // Add sample row
        $sampleRow = [
            'John Doe',
            'john.doe@example.com',
            '8001015800085',
            '+27123456789',
            '1980-01-01',
            '123 Main Street, City, 1234',
            'PO Box 123, City, 1234',
            '',
            'standard',
            'active',
        ];
        
        $sheet->fromArray([$sampleRow], null, 'A2');
        
        // Style header row
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(40);
        $sheet->getColumnDimension('G')->setWidth(40);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(20);
        $sheet->getColumnDimension('J')->setWidth(15);
        
        // Save file
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filePath);
    }
}

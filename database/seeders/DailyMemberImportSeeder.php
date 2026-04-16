<?php

namespace Database\Seeders;

use App\Services\ExcelMemberImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Reads public/dailyupload.csv and imports members as existing/imported members.
 * Skips rows marked as cancelled or deceased.
 *
 * Usage: php artisan db:seed --class=DailyMemberImportSeeder
 */
class DailyMemberImportSeeder extends Seeder
{
    protected array $skipKeywords = [
        'cancel',
        'cancle',
        'deceased',
        'dead',
        'passed away',
        'removed',
    ];

    protected array $excludedIdNumbers = [
        '4806065094183', // F. Lang — deceased
    ];

    public function run(): void
    {
        $path = public_path('dailyupload.csv');

        if (! file_exists($path)) {
            $this->command->error('File not found: public/dailyupload.csv');
            return;
        }

        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows);

        $importer = new ExcelMemberImporter();
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $failures = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if (count($row) < 8 || empty(trim($row[3] ?? ''))) {
                continue;
            }

            $idNumber = trim($row[4] ?? '');
            if (in_array($idNumber, $this->excludedIdNumbers)) {
                $skipped++;
                $this->command->warn("Row {$rowNumber}: Skipped (excluded) — {$row[2]} {$row[3]}");
                continue;
            }

            $membershipTypeRaw = trim($row[7] ?? '');
            $statusRaw = trim($row[9] ?? '');
            $email = trim($row[6] ?? '');

            // Take only the first email if multiple are separated by ; or ,
            if (str_contains($email, ';')) {
                $email = trim(explode(';', $email)[0]);
            } elseif (str_contains($email, ',')) {
                $email = trim(explode(',', $email)[0]);
            }

            $phone = trim($row[5] ?? '');
            if (strtolower($phone) === 'tbc' || $phone === '`') {
                $phone = '';
            }

            if ($this->shouldSkip($membershipTypeRaw) || $this->shouldSkip($statusRaw)) {
                $skipped++;
                $this->command->warn("Row {$rowNumber}: Skipped (cancelled/deceased) — {$row[2]} {$row[3]}");
                continue;
            }

            if (empty($email) || strtolower($email) === 'tbc' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if (empty($phone)) {
                    $skipped++;
                    $this->command->warn("Row {$rowNumber}: Skipped (no valid email or phone) — {$row[2]} {$row[3]}");
                    continue;
                }
                $email = '';
            }

            // Skip members already in the system (by email, phone, or ID number)
            $idNumber = \App\Models\User::normalizeIdNumber(trim($row[4] ?? ''));
            $normalizedPhone = \App\Models\User::normalizePhone($phone);
            if (
                (! empty($email) && \App\Models\User::where('email', strtolower($email))->exists()) ||
                (! empty($normalizedPhone) && \App\Models\User::where('phone', $normalizedPhone)->exists()) ||
                (! empty($idNumber) && strlen($idNumber) === 13 && \App\Models\User::where('id_number', $idNumber)->exists())
            ) {
                continue;
            }

            $knowledgeTest = strtolower(trim($row[10] ?? '')) === 'yes';
            $activities = strtolower(trim($row[11] ?? '')) === 'yes';

            $rowData = [
                'date_joined'     => trim($row[0] ?? ''),
                'initials'        => trim($row[2] ?? ''),
                'surname'         => trim($row[3] ?? ''),
                'id_number'       => trim($row[4] ?? ''),
                'phone'           => $phone,
                'email'           => $email,
                'membership_type' => $membershipTypeRaw,
                'renewal_date'    => trim($row[8] ?? ''),
                'status'          => strtolower(trim($statusRaw)) === 'active' ? 'Active' : '',
            ];

            $result = $importer->importSingleMember($rowData, [
                'default_password'          => 'Nrapa2026!',
                'auto_approve'              => true,
                'auto_activate'             => true,
                'send_welcome_email'        => true,
                'source'                    => 'import',
                'auto_pass_knowledge_tests' => $knowledgeTest,
            ]);

            if ($result['success']) {
                $imported++;
                $label = $knowledgeTest ? ' [tests passed]' : '';
                $label .= $activities ? ' [activities]' : '';
                $this->command->info("Row {$rowNumber}: Imported {$row[2]} {$row[3]} ({$email}){$label}");
            } else {
                $failed++;
                $failures[] = "Row {$rowNumber}: {$row[2]} {$row[3]} — {$result['error']}";
                $this->command->error("Row {$rowNumber}: FAILED {$row[2]} {$row[3]} — {$result['error']}");
            }
        }

        $this->command->newLine();
        $this->command->info("Import complete: {$imported} imported, {$skipped} skipped, {$failed} failed.");

        if (! empty($failures)) {
            $this->command->newLine();
            $this->command->warn('Failures:');
            foreach ($failures as $f) {
                $this->command->warn("  • {$f}");
            }
        }

        Log::info('DailyMemberImportSeeder completed', [
            'imported' => $imported,
            'skipped'  => $skipped,
            'failed'   => $failed,
            'failures' => $failures,
        ]);
    }

    protected function shouldSkip(string $value): bool
    {
        $lower = strtolower($value);

        foreach ($this->skipKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }
}

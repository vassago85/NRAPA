<?php

namespace Database\Seeders;

use App\Services\ExcelMemberImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Reads public/dailyupload.xlsx (preferred) or public/dailyupload.csv (fallback)
 * and imports members as existing/imported members.
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
        $rows = $this->loadRows();

        if ($rows === null) {
            return;
        }

        array_shift($rows); // header

        $importer = new ExcelMemberImporter();
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $failures = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if (count($row) < 8 || $this->cell($row, 3) === '') {
                continue;
            }

            $rawIdNumber = $this->cell($row, 4);
            if (in_array($rawIdNumber, $this->excludedIdNumbers, true)) {
                $skipped++;
                $this->command->warn("Row {$rowNumber}: Skipped (excluded) — {$this->cell($row, 2)} {$this->cell($row, 3)}");
                continue;
            }

            $membershipTypeRaw = $this->cell($row, 7);
            $statusRaw = $this->cell($row, 9);
            $email = strtolower($this->cell($row, 6));

            if (str_contains($email, ';')) {
                $email = trim(explode(';', $email)[0]);
            } elseif (str_contains($email, ',')) {
                $email = trim(explode(',', $email)[0]);
            }

            $phone = $this->cell($row, 5);
            if (strtolower($phone) === 'tbc' || $phone === '`') {
                $phone = '';
            }

            if ($this->shouldSkip($membershipTypeRaw) || $this->shouldSkip($statusRaw)) {
                $skipped++;
                $this->command->warn("Row {$rowNumber}: Skipped (cancelled/deceased) — {$this->cell($row, 2)} {$this->cell($row, 3)}");
                continue;
            }

            if (empty($email) || strtolower($email) === 'tbc' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if (empty($phone)) {
                    $skipped++;
                    $this->command->warn("Row {$rowNumber}: Skipped (no valid email or phone) — {$this->cell($row, 2)} {$this->cell($row, 3)}");
                    continue;
                }
                $email = '';
            }

            $idNumber = \App\Models\User::normalizeIdNumber($rawIdNumber);
            $normalizedPhone = \App\Models\User::normalizePhone($phone);
            if (
                (! empty($email) && \App\Models\User::where('email', $email)->exists()) ||
                (! empty($normalizedPhone) && \App\Models\User::where('phone', $normalizedPhone)->exists()) ||
                (! empty($idNumber) && strlen($idNumber) === 13 && \App\Models\User::where('id_number', $idNumber)->exists())
            ) {
                continue;
            }

            $knowledgeTest = strtolower($this->cell($row, 10)) === 'yes';

            $rowData = [
                'date_joined'     => $this->cell($row, 0),
                'initials'        => $this->cell($row, 2),
                'surname'         => $this->cell($row, 3),
                'id_number'       => $rawIdNumber,
                'phone'           => $phone,
                'email'           => $email,
                'membership_type' => $membershipTypeRaw,
                'renewal_date'    => $this->cell($row, 8),
                'status'          => strtolower($statusRaw) === 'active' ? 'Active' : '',
            ];

            $result = $importer->importSingleMember($rowData, [
                'default_password'          => 'Nrapa2026!',
                'auto_approve'              => true,
                'auto_activate'             => true,
                'send_welcome_email'        => true,
                'source'                    => 'import',
                'auto_pass_knowledge_tests' => $knowledgeTest,
            ]);

            $label = $this->cell($row, 2) . ' ' . $this->cell($row, 3);

            if ($result['success']) {
                $imported++;
                $status = ($result['phone_fallback'] ?? false) ? 'phone login' : 'ok';
                $this->command->info("✓ {$label} — {$status}");
            } else {
                $failed++;
                $reason = Str::before($result['error'], '(Connection:');
                $reason = Str::limit(trim($reason), 80, '…');
                $failures[] = "{$label} — {$reason}";
                $this->command->error("✗ {$label} — {$reason}");
            }
        }

        $this->command->newLine();
        $this->command->info("Done: {$imported} imported, {$skipped} skipped, {$failed} failed.");

        if (! empty($failures)) {
            $this->command->newLine();
            $this->command->warn('Failed:');
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

    /**
     * Load rows from public/dailyupload.xlsx (preferred) or .csv (fallback).
     *
     * XLSX is preferred because CSV exports re-format Excel date serials
     * according to the saving machine's locale (e.g. en-ZA produces yyyy/mm/dd
     * while en-GB produces dd/mm/yyyy). Reading XLSX directly lets
     * ExcelMemberImporter::parseDate() see the raw Excel serials unambiguously.
     */
    protected function loadRows(): ?array
    {
        $xlsx = public_path('dailyupload.xlsx');
        $csv = public_path('dailyupload.csv');

        if (file_exists($xlsx)) {
            $this->command->info('Reading: public/dailyupload.xlsx');
            $spreadsheet = IOFactory::load($xlsx);
            // toArray() preserves numeric Excel date serials so parseDate() can
            // convert them via PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject().
            return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        }

        if (file_exists($csv)) {
            $this->command->info('Reading: public/dailyupload.csv');
            return array_map('str_getcsv', file($csv));
        }

        $this->command->error('File not found: public/dailyupload.xlsx or public/dailyupload.csv');
        return null;
    }

    /**
     * Read a cell and coerce it to a trimmed string (handles numeric Excel
     * cells, nulls, and stray whitespace uniformly).
     */
    protected function cell(array $row, int $index): string
    {
        $value = $row[$index] ?? '';
        if ($value === null) {
            return '';
        }
        return trim((string) $value);
    }
}

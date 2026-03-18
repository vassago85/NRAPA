<?php

namespace Database\Seeders;

use App\Models\TermsVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TermsVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if terms version already exists
        if (TermsVersion::where('version', '2026-01')->exists()) {
            $this->command->info('Terms version 2026-01 already exists. Skipping.');

            return;
        }

        // Read the terms HTML template
        $termsHtmlPath = resource_path('views/documents/terms/nrapa-terms.blade.php');
        $htmlContent = '';

        if (File::exists($termsHtmlPath)) {
            // Read the Blade file and extract HTML (remove @php and @endphp blocks)
            $content = File::get($termsHtmlPath);
            // For now, just use the file path - admin can upload the actual HTML later
            $htmlContent = $content;
        }

        // Create initial terms version
        $termsVersion = TermsVersion::create([
            'version' => '2026-01',
            'title' => 'NRAPA Membership Terms & Conditions',
            'html_content' => $htmlContent,
            'html_path' => null,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $this->command->info('Created initial Terms & Conditions version: 2026-01');
    }
}

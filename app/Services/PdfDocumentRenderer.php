<?php

namespace App\Services;

use App\Contracts\DocumentRenderer;
use App\Models\Certificate;
use App\Models\EndorsementRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\Process\Process;

/**
 * PDF Document Renderer.
 *
 * Strategy order:
 * 1. wkhtmltopdf — lightweight Qt WebKit engine, reliable on any server
 * 2. DomPDF fallback — pure PHP, no external dependencies
 */
class PdfDocumentRenderer implements DocumentRenderer
{
    protected string $disk;

    protected string $pathPrefix = 'documents';

    public function __construct()
    {
        $this->disk = app()->environment(['local', 'development', 'testing'])
            ? 'local'
            : (config('filesystems.disks.r2.key') ? 'r2' : (config('filesystems.disks.s3.key') ? 's3' : 'local'));
    }

    protected function getWkhtmltopdfPath(): ?string
    {
        $candidates = [
            '/usr/bin/wkhtmltopdf',
            '/usr/local/bin/wkhtmltopdf',
        ];

        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Generate PDF via wkhtmltopdf (Qt WebKit engine — no Chrome/Puppeteer needed).
     */
    protected function generateWithWkhtmltopdf(string $html, string $outputPath, ?array $customSize = null): bool
    {
        $binary = $this->getWkhtmltopdfPath();
        if (! $binary) {
            Log::info('wkhtmltopdf binary not found, skipping');
            return false;
        }

        $tmpDir = sys_get_temp_dir();
        $htmlFile = $tmpDir . DIRECTORY_SEPARATOR . 'pdf_' . uniqid() . '.html';
        $pdfFile = $tmpDir . DIRECTORY_SEPARATOR . 'pdf_' . uniqid() . '.pdf';

        try {
            file_put_contents($htmlFile, $html);

            $args = [
                $binary,
                '--quiet',
                '--no-outline',
                '--print-media-type',
                '--enable-local-file-access',
                '--margin-top', '0',
                '--margin-bottom', '0',
                '--margin-left', '0',
                '--margin-right', '0',
                '--dpi', '150',
            ];

            if ($customSize) {
                $args[] = '--page-width';
                $args[] = $customSize['width'] . 'mm';
                $args[] = '--page-height';
                $args[] = $customSize['height'] . 'mm';
            } else {
                $args[] = '--page-size';
                $args[] = 'A4';
            }

            $args[] = $htmlFile;
            $args[] = $pdfFile;

            $process = new Process($args);
            $process->setTimeout(30);
            $process->run();

            if (! file_exists($pdfFile) || filesize($pdfFile) < 100) {
                Log::warning('wkhtmltopdf PDF generation failed', [
                    'exit_code' => $process->getExitCode(),
                    'stderr' => substr($process->getErrorOutput(), 0, 500),
                ]);
                return false;
            }

            Storage::disk($this->disk)->put($outputPath, file_get_contents($pdfFile));

            return true;
        } finally {
            @unlink($htmlFile);
            @unlink($pdfFile);
        }
    }

    /**
     * @param  array{width: float, height: float}|null  $customSize  Custom paper size in mm (overrides A4)
     */
    protected function generatePdf(string $template, array $data, string $filePath, ?array $customSize = null): void
    {
        // Strategy 1: wkhtmltopdf (Qt WebKit — lightweight and reliable)
        try {
            $html = view($template, $data)->render();

            if ($this->generateWithWkhtmltopdf($html, $filePath, $customSize)) {
                Log::info('PDF generated via wkhtmltopdf', [
                    'template' => $template,
                    'file' => $filePath,
                ]);
                return;
            }
        } catch (\Throwable $e) {
            Log::warning('wkhtmltopdf render failed', [
                'template' => $template,
                'error' => $e->getMessage(),
            ]);
        }

        // Strategy 2: DomPDF fallback
        try {
            $pdf = Pdf::view($template, $data)->driver('dompdf');

            if ($customSize) {
                $pdf->paperSize($customSize['width'], $customSize['height'], 'mm');
            } else {
                $pdf->format('a4');
            }

            $pdf->disk($this->disk)->save($filePath);

            Log::info('PDF generated via DomPDF fallback', [
                'template' => $template,
                'file' => $filePath,
            ]);
        } catch (\Throwable $e) {
            Log::error('All PDF generation methods failed', [
                'template' => $template,
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function renderCertificate(Certificate $certificate, string $template): string
    {
        // Ensure relationships are loaded
        $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

        // Map old template names to new templates
        $templateMap = [
            'documents.paid-up' => 'documents.certificates.good-standing',
            'documents.dedicated-hunter' => 'documents.certificates.dedicated-status',
            'documents.dedicated-sport' => 'documents.certificates.dedicated-status',
            'certificates.confirmation' => 'documents.certificates.good-standing',
        ];

        if (isset($templateMap[$template])) {
            $template = $templateMap[$template];
        }

        // Also check by slug
        $slug = $certificate->certificateType->slug ?? '';
        if ($slug === 'membership-certificate' || $slug === 'paid-up-certificate' || $slug === 'good-standing-certificate') {
            $template = 'documents.certificates.good-standing';
        } elseif ($slug === 'dedicated-hunter-certificate' || $slug === 'dedicated-hunter' ||
                  $slug === 'dedicated-sport-certificate' || $slug === 'dedicated-sport' ||
                  $slug === 'dedicated-both-certificate' ||
                  $slug === 'occasional-hunter-certificate' || $slug === 'occasional-sport-certificate') {
            $template = 'documents.certificates.dedicated-status';
        }

        // Generate file path
        $filename = "certificate-{$certificate->uuid}.pdf";
        $filePath = "{$this->pathPrefix}/{$filename}";

        // Use custom paper size for membership cards (portrait card format)
        $customSize = ($slug === 'membership-card')
            ? ['width' => 90, 'height' => 148]
            : null;

        $this->generatePdf($template, [
            'certificate' => $certificate,
            'user' => $certificate->user,
            'membership' => $certificate->membership,
            'certificateType' => $certificate->certificateType,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ], $filePath, $customSize);

        return $filePath;
    }

    public function renderWelcomeLetter(User $user, string $template): string
    {
        // Ensure membership is loaded
        $membership = $user->activeMembership;

        // Map old template names to new templates
        if ($template === 'documents.welcome-letter' || str_contains($template, 'welcome')) {
            $template = 'documents.letters.welcome';
        }

        // Try to get certificate if available (for QR code)
        $certificate = \App\Models\Certificate::where('user_id', $user->id)
            ->whereHas('certificateType', fn ($q) => $q->where('slug', 'welcome-letter'))
            ->latest('created_at')
            ->first();

        // Generate file path
        $filename = "welcome-letter-{$user->uuid}.pdf";
        $filePath = "{$this->pathPrefix}/{$filename}";

        $this->generatePdf($template, [
            'user' => $user,
            'membership' => $membership,
            'certificate' => $certificate,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ], $filePath);

        return $filePath;
    }

    public function renderEndorsementLetter(EndorsementRequest $request, string $template): string
    {
        // Ensure relationships are loaded (EndorsementRequest has no direct 'membership' relationship)
        $request->loadMissing(['user', 'firearm', 'firearm.firearmCalibre', 'firearm.firearmMake', 'firearm.firearmModel', 'components']);

        // Map template if needed
        if ($template === 'documents.endorsement-letter') {
            $template = 'documents.letters.endorsement';
        }

        // Generate file path
        $filename = "endorsement-letter-{$request->uuid}.pdf";
        $filePath = "{$this->pathPrefix}/{$filename}";

        $this->generatePdf($template, [
            'request' => $request,
            'user' => $request->user,
            'firearm' => $request->firearm,
            'membership' => $request->user->activeMembership,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ], $filePath);

        return $filePath;
    }
}

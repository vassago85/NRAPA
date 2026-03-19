<?php

namespace App\Services;

use App\Contracts\DocumentRenderer;
use App\Models\Certificate;
use App\Models\EndorsementRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * PDF Document Renderer using Spatie Laravel PDF.
 *
 * Tries Browsershot (Chrome) first for best rendering quality,
 * then falls back to DomPDF if Chrome/Browsershot is unavailable.
 */
class PdfDocumentRenderer implements DocumentRenderer
{
    protected string $disk;

    protected string $pathPrefix = 'documents';

    public function __construct()
    {
        // Use local storage for local/development/testing environments
        $this->disk = app()->environment(['local', 'development', 'testing'])
            ? 'local'
            : (config('filesystems.disks.r2.key') ? 'r2' : (config('filesystems.disks.s3.key') ? 's3' : 'local'));
    }

    /**
     * Generate and save a PDF, trying Browsershot first then DomPDF fallback.
     *
     * @param  array{width: float, height: float}|null  $customSize  Custom paper size in mm (overrides A4)
     */
    protected function generatePdf(string $template, array $data, string $filePath, ?array $customSize = null): void
    {
        // Try Browsershot (Chrome) first — best rendering quality
        try {
            $pdf = Pdf::view($template, $data)
                ->withBrowsershot(function (\Spatie\Browsershot\Browsershot $browsershot) {
                    $browsershot
                        ->noSandbox()
                        ->setOption('args', [
                            '--disable-setuid-sandbox',
                            '--disable-dev-shm-usage',
                            '--disable-gpu',
                            '--single-process',
                            '--no-zygote',
                            '--disable-software-rasterizer',
                            '--disable-extensions',
                        ]);
                });

            if ($customSize) {
                $pdf->paperSize($customSize['width'], $customSize['height'], 'mm');
            } else {
                $pdf->format('a4');
            }

            $pdf->margins(0, 0, 0, 0)
                ->disk($this->disk)
                ->save($filePath);

            Log::info('PDF generated via Browsershot (Chrome)', [
                'template' => $template,
                'file' => $filePath,
            ]);

            return;
        } catch (\Throwable $e) {
            Log::warning('Browsershot PDF generation failed, trying DomPDF fallback', [
                'template' => $template,
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to DomPDF — pure PHP, no external dependencies
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
            Log::error('Both Browsershot and DomPDF PDF generation failed', [
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

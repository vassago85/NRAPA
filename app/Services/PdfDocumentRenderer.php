<?php

namespace App\Services;

use App\Contracts\DocumentRenderer;
use App\Models\Certificate;
use App\Models\EndorsementRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * PDF Document Renderer using Spatie Laravel PDF (Browsershot/Chromium).
 * 
 * Generates PDF files from Blade templates for certificates and letters.
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
                  $slug === 'dedicated-sport-certificate' || $slug === 'dedicated-sport') {
            $template = 'documents.certificates.dedicated-status';
        }
        
        // Generate file path
        $filename = "certificate-{$certificate->uuid}.pdf";
        $filePath = "{$this->pathPrefix}/{$filename}";

        // Generate PDF using Spatie
        Pdf::view($template, [
                'certificate' => $certificate,
                'user' => $certificate->user,
                'membership' => $certificate->membership,
                'certificateType' => $certificate->certificateType,
                'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
            ])
            ->format('a4')
            ->disk($this->disk)
            ->save($filePath);

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
            ->whereHas('certificateType', fn($q) => $q->where('slug', 'welcome-letter'))
            ->latest('created_at')
            ->first();
        
        // Generate file path
        $filename = "welcome-letter-{$user->uuid}.pdf";
        $filePath = "{$this->pathPrefix}/{$filename}";

        // Generate PDF using Spatie
        Pdf::view($template, [
                'user' => $user,
                'membership' => $membership,
                'certificate' => $certificate,
                'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
            ])
            ->format('a4')
            ->disk($this->disk)
            ->save($filePath);

        return $filePath;
    }

    public function renderEndorsementLetter(EndorsementRequest $request, string $template): string
    {
        // Ensure relationships are loaded
        $request->loadMissing(['user', 'firearm', 'firearm.firearmCalibre', 'firearm.firearmMake', 'firearm.firearmModel', 'components', 'membership']);
        
        // Map template if needed
        if ($template === 'documents.endorsement-letter') {
            $template = 'documents.letters.endorsement';
        }
        
        // Generate file path
        $filename = "endorsement-letter-{$request->uuid}.pdf";
        $filePath = "{$this->pathPrefix}/{$filename}";

        // Generate PDF using Spatie
        Pdf::view($template, [
                'request' => $request,
                'user' => $request->user,
                'firearm' => $request->firearm,
                'membership' => $request->user->activeMembership,
                'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
            ])
            ->format('a4')
            ->disk($this->disk)
            ->save($filePath);

        return $filePath;
    }
}

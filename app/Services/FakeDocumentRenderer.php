<?php

namespace App\Services;

use App\Contracts\DocumentRenderer;
use App\Models\Certificate;
use App\Models\EndorsementRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/**
 * Fake document renderer that generates placeholder PDFs/HTML.
 * 
 * Fallback renderer when Spatie Laravel PDF (Browsershot/Chromium) is not available.
 * This implementation writes HTML files to storage as placeholders.
 */
class FakeDocumentRenderer implements DocumentRenderer
{
    protected string $disk;
    protected string $pathPrefix = 'documents';

    public function __construct()
    {
        // Use local storage for local/development/testing environments
        $this->disk = app()->environment(['local', 'development', 'testing']) 
            ? 'local' 
            : (config('filesystems.disks.r2.key') ? 'r2' : 'local');
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
        
        // Render the Blade template to HTML
        $html = View::make($template, [
            'certificate' => $certificate,
            'user' => $certificate->user,
            'membership' => $certificate->membership,
            'certificateType' => $certificate->certificateType,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ])->render();

        // Generate file path
        $filename = "certificate-{$certificate->uuid}.html";
        $filePath = "{$this->pathPrefix}/{$filename}";

        // Store the HTML (TODO: Convert to PDF)
        Storage::disk($this->disk)->put($filePath, $html);

        // In production, PdfDocumentRenderer with Spatie/Browsershot handles real PDFs
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
        
        // Render the Blade template to HTML
        // Try to get certificate if available (for QR code)
        $certificate = \App\Models\Certificate::where('user_id', $user->id)
            ->whereHas('certificateType', fn($q) => $q->where('slug', 'welcome-letter'))
            ->latest('created_at')
            ->first();
        
        $html = View::make($template, [
            'user' => $user,
            'membership' => $membership,
            'certificate' => $certificate,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ])->render();

        // Generate file path
        $filename = "welcome-letter-{$user->uuid}.html";
        $filePath = "{$this->pathPrefix}/{$filename}";

        // Store the HTML (TODO: Convert to PDF)
        Storage::disk($this->disk)->put($filePath, $html);

        // TODO: Convert HTML to PDF
        return $filePath;
    }

    public function renderEndorsementLetter(EndorsementRequest $request, string $template): string
    {
        // Ensure relationships are loaded
        $request->loadMissing(['user', 'firearm', 'firearm.firearmCalibre', 'firearm.firearmMake', 'firearm.firearmModel', 'components', 'membership']);
        
        // Render the Blade template to HTML
        $html = View::make($template, [
            'request' => $request,
            'user' => $request->user,
            'firearm' => $request->firearm,
            'membership' => $request->user->activeMembership,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ])->render();

        // Generate file path
        $filename = "endorsement-letter-{$request->uuid}.html";
        $filePath = "{$this->pathPrefix}/{$filename}";

        // Store the HTML (TODO: Convert to PDF)
        Storage::disk($this->disk)->put($filePath, $html);

        // In production, PdfDocumentRenderer with Spatie/Browsershot handles real PDFs
        return $filePath;
    }
}

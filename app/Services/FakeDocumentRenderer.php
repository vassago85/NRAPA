<?php

namespace App\Services;

use App\Contracts\DocumentRenderer;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/**
 * Fake document renderer that generates placeholder PDFs/HTML.
 * 
 * TODO: Replace with actual PDF generation (DomPDF, Snappy, etc.)
 * This implementation writes HTML files to storage as placeholders.
 */
class FakeDocumentRenderer implements DocumentRenderer
{
    protected string $disk = 'local';
    protected string $pathPrefix = 'documents';

    public function renderCertificate(Certificate $certificate, string $template): string
    {
        // Render the Blade template to HTML
        $html = View::make($template, [
            'certificate' => $certificate,
            'user' => $certificate->user,
            'membership' => $certificate->membership,
            'certificateType' => $certificate->certificateType,
        ])->render();

        // Generate file path
        $filename = "certificate-{$certificate->uuid}.html";
        $filePath = "{$this->pathPrefix}/{$filename}";

        // Store the HTML (TODO: Convert to PDF)
        Storage::disk($this->disk)->put($filePath, $html);

        // TODO: Convert HTML to PDF using DomPDF/Snappy/etc.
        // For now, return the HTML file path
        // Note: In production, this should return a PDF file path
        return $filePath;
    }

    public function renderWelcomeLetter(User $user, string $template): string
    {
        // Render the Blade template to HTML
        $html = View::make($template, [
            'user' => $user,
            'membership' => $user->activeMembership,
        ])->render();

        // Generate file path
        $filename = "welcome-letter-{$user->uuid}.html";
        $filePath = "{$this->pathPrefix}/{$filename}";

        // Store the HTML (TODO: Convert to PDF)
        Storage::disk($this->disk)->put($filePath, $html);

        // TODO: Convert HTML to PDF
        return $filePath;
    }
}

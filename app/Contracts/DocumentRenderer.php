<?php

namespace App\Contracts;

use App\Models\Certificate;
use App\Models\User;
use App\Models\EndorsementRequest;

/**
 * Interface for document rendering services.
 * 
 * This interface allows for interchangeable PDF generation engines
 * (DomPDF, Snappy, etc.) while maintaining a consistent API.
 */
interface DocumentRenderer
{
    /**
     * Render a certificate to a document file.
     * 
     * @param Certificate $certificate The certificate to render
     * @param string $template The Blade template path to use
     * @return string The file path where the document was saved
     */
    public function renderCertificate(Certificate $certificate, string $template): string;

    /**
     * Render a welcome letter to a document file.
     * 
     * @param User $user The user to generate the welcome letter for
     * @param string $template The Blade template path to use
     * @return string The file path where the document was saved
     */
    public function renderWelcomeLetter(User $user, string $template): string;

    /**
     * Render an endorsement letter to a document file.
     * 
     * @param EndorsementRequest $request The endorsement request to render
     * @param string $template The Blade template path to use
     * @return string The file path where the document was saved
     */
    public function renderEndorsementLetter(EndorsementRequest $request, string $template): string;
}

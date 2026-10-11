<?php

namespace App\Http\Controllers;

use App\Helpers\DocumentHelper;
use App\Helpers\StorageHelper;
use App\Models\Certificate;
use App\Services\CertificateIssueService;
use App\Services\WalletPassService;
use Illuminate\Support\Facades\Storage;

/**
 * Serves certificate files to the owning member and to staff (admin and above).
 */
class CertificateFileController extends Controller
{
    private const LEGACY_TEMPLATES = [
        'certificates.membership' => 'documents.membership-card',
        'certificates.dedicated' => 'documents.certificates.dedicated-status',
        'certificates.endorsement' => 'documents.letters.endorsement',
        'certificates.confirmation' => 'documents.certificates.good-standing',
        'documents.paid-up' => 'documents.certificates.good-standing',
        'documents.dedicated-hunter' => 'documents.certificates.dedicated-status',
        'documents.dedicated-sport' => 'documents.certificates.dedicated-status',
        'documents.welcome-letter' => 'documents.letters.welcome',
    ];

    public function download(Certificate $certificate, CertificateIssueService $issueService)
    {
        $this->authorizeAccess($certificate);

        $disk = Storage::disk(StorageHelper::getPrivateDisk());

        if (! $certificate->file_path || ! $disk->exists($certificate->file_path)) {
            if (! $issueService->regenerateDocument($certificate)) {
                abort(404, 'Certificate file could not be generated. Please contact support.');
            }
            $certificate->refresh();
        }

        $filename = 'nrapa-'.str_replace(' ', '-', strtolower($certificate->certificateType->name)).'-'.$certificate->certificate_number.'.pdf';

        return $disk->download($certificate->file_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function preview(Certificate $certificate)
    {
        $this->authorizeAccess($certificate);

        $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

        return view($this->previewTemplate($certificate), [
            'certificate' => $certificate,
            'user' => $certificate->user,
            'membership' => $certificate->membership,
            'certificateType' => $certificate->certificateType,
            'logo_url' => DocumentHelper::getLogoUrl(),
        ]);
    }

    public function appleWallet(Certificate $certificate, WalletPassService $walletService)
    {
        $this->authorizeWalletPass($certificate);

        $filePath = $walletService->generateAppleWalletPass($certificate);

        if (! $filePath) {
            abort(503, 'Apple Wallet pass generation is not yet configured. Please contact support.');
        }

        return Storage::disk(StorageHelper::getPrivateDisk())->download($filePath, 'nrapa-membership-card.pkpass', [
            'Content-Type' => 'application/vnd.apple.pkpass',
        ]);
    }

    public function googleWallet(Certificate $certificate, WalletPassService $walletService)
    {
        $this->authorizeWalletPass($certificate);

        $saveUrl = $walletService->generateGoogleWalletPass($certificate);

        if (! $saveUrl) {
            abort(503, 'Google Wallet pass generation is not yet configured. Please contact support.');
        }

        return redirect($saveUrl);
    }

    private function authorizeAccess(Certificate $certificate): void
    {
        $user = auth()->user();

        if ($certificate->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403);
        }
    }

    private function authorizeWalletPass(Certificate $certificate): void
    {
        $this->authorizeAccess($certificate);

        if ($certificate->certificateType->slug !== 'membership-card') {
            abort(404, 'Wallet passes are only available for membership cards.');
        }
    }

    private function previewTemplate(Certificate $certificate): string
    {
        $type = $certificate->certificateType;

        $bySlug = match ($type->slug ?? '') {
            'dedicated-hunter-certificate', 'dedicated-hunter',
            'dedicated-sport-certificate', 'dedicated-sport', 'dedicated-sport-shooter' => 'documents.certificates.dedicated-status',
            'membership-card' => 'documents.membership-card',
            'membership-certificate', 'paid-up-certificate', 'good-standing-certificate' => 'documents.certificates.good-standing',
            'welcome-letter' => 'documents.letters.welcome',
            default => null,
        };

        if ($bySlug) {
            return $bySlug;
        }

        $template = $type->template ?? 'documents.certificates.good-standing';

        return self::LEGACY_TEMPLATES[$template] ?? $template;
    }
}

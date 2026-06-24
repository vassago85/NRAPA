<?php

namespace App\Services;

use App\Contracts\DocumentRenderer;
use App\Models\AuditLog;
use App\Models\EndorsementRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Generates and issues an approved endorsement letter (PDF + DB update + member email).
 *
 * Intended to run on the queue so admin approve/issue actions return immediately
 * instead of blocking on Gotenberg PDF rendering and object-storage upload.
 */
class EndorsementLetterIssuer
{
    public function issueApprovedLetter(
        EndorsementRequest $request,
        User $admin,
        string $dedicatedCategory,
        bool $dedicatedStatusCompliant = true,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        bool $autoIssued = false,
        ?string $issuedVia = null,
    ): string
    {
        if ($request->status !== EndorsementRequest::STATUS_APPROVED) {
            throw new \Exception('Endorsement request must be approved before letter can be issued.');
        }

        if ($request->isIssued()) {
            return $request->letter_reference ?? '';
        }

        $request->loadMissing([
            'user',
            'firearm',
            'firearm.firearmCalibre',
            'firearm.firearmMake',
            'firearm.firearmModel',
            'components',
            'acknowledgements',
        ]);

        $letterReference = EndorsementRequest::generateLetterReference();
        $issuedAt = now();

        $request->applyLetterRenderSnapshot(
            $letterReference,
            $dedicatedStatusCompliant,
            $dedicatedCategory,
            $issuedAt,
        );

        $renderer = app(DocumentRenderer::class);
        $letterPath = $renderer->renderEndorsementLetter($request, 'documents.letters.endorsement');

        $request->issue(
            $admin,
            $letterReference,
            $letterPath,
            $dedicatedStatusCompliant,
            $dedicatedCategory,
        );

        $newValues = [
            'status' => 'issued',
            'letter_reference' => $letterReference,
            'dedicated_status_compliant' => $dedicatedStatusCompliant,
            'dedicated_category' => $dedicatedCategory,
        ];

        if ($autoIssued) {
            $newValues['auto_issued'] = true;
        }

        if ($issuedVia) {
            $newValues['issued_via'] = $issuedVia;
        }

        AuditLog::create([
            'user_id' => $admin->id,
            'event' => 'endorsement_issued',
            'auditable_type' => EndorsementRequest::class,
            'auditable_id' => $request->id,
            'old_values' => ['status' => 'approved'],
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        Log::info('Endorsement letter issued', [
            'endorsement_request_id' => $request->id,
            'letter_reference' => $letterReference,
            'auto_issued' => $autoIssued,
        ]);

        return $letterReference;
    }
}

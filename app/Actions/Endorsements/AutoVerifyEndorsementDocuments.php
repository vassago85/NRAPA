<?php

namespace App\Actions\Endorsements;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\DocumentType;
use App\Models\EndorsementDocument;
use App\Models\EndorsementRequest;
use App\Models\MemberDocument;
use App\Models\User;
use Illuminate\Support\Str;

class AutoVerifyEndorsementDocuments
{
    /**
     * Auto-create system-verified EndorsementDocument rows from existing
     * member documents, certificates, membership, and activities.
     */
    public function execute(EndorsementRequest $request, User $user): void
    {
        $requiredDocTypes = EndorsementRequest::getRequiredDocumentTypes($request->request_type);

        foreach ($requiredDocTypes as $docType) {
            if ($request->documents()->where('document_type', $docType)->exists()) {
                continue;
            }

            $memberDocSlug = match ($docType) {
                'sa_id' => 'id-document',
                'proof_of_address' => 'proof-of-address',
                'competency_certificate' => 'competency-certificate',
                default => null,
            };

            $memberDoc = null;
            if ($memberDocSlug) {
                $docTypeModel = DocumentType::where('slug', $memberDocSlug)->first();
                if ($docTypeModel) {
                    $memberDoc = MemberDocument::where('user_id', $user->id)
                        ->where('document_type_id', $docTypeModel->id)
                        ->where('status', 'verified')
                        ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                        ->latest('verified_at')
                        ->first();
                }
            }

            if ($docType === 'dedicated_status_certificate') {
                $certType = CertificateType::where('slug', 'dedicated-status-certificate')->first();
                if ($certType) {
                    $cert = Certificate::where('user_id', $user->id)
                        ->where('certificate_type_id', $certType->id)
                        ->whereNull('revoked_at')
                        ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', now()))
                        ->latest('issued_at')
                        ->first();

                    if ($cert) {
                        EndorsementDocument::create([
                            'uuid' => Str::uuid()->toString(),
                            'endorsement_request_id' => $request->id,
                            'document_type' => $docType,
                            'status' => 'system_verified',
                            'metadata' => ['certificate_id' => $cert->id],
                        ]);

                        continue;
                    }
                }
            }

            if ($docType === 'membership_proof' && $user->activeMembership) {
                EndorsementDocument::create([
                    'uuid' => Str::uuid()->toString(),
                    'endorsement_request_id' => $request->id,
                    'document_type' => $docType,
                    'status' => 'system_verified',
                    'metadata' => ['membership_id' => $user->activeMembership->id],
                ]);

                continue;
            }

            if ($docType === 'activity_proof') {
                $activityCheck = EndorsementRequest::checkActivityRequirements($user);
                if ($activityCheck['met']) {
                    EndorsementDocument::create([
                        'uuid' => Str::uuid()->toString(),
                        'endorsement_request_id' => $request->id,
                        'document_type' => $docType,
                        'status' => 'system_verified',
                        'metadata' => [
                            'approved_count' => $activityCheck['approved_count'],
                            'required' => $activityCheck['required'],
                        ],
                    ]);

                    continue;
                }
            }

            if ($memberDoc) {
                EndorsementDocument::create([
                    'uuid' => Str::uuid()->toString(),
                    'endorsement_request_id' => $request->id,
                    'document_type' => $docType,
                    'status' => 'system_verified',
                    'member_document_id' => $memberDoc->id,
                    'metadata' => ['auto_verified' => true],
                ]);
            }
        }
    }
}

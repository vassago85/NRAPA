<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\EndorsementComponent;
use App\Models\EndorsementRequest;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DocumentPreviewController extends Controller
{
    private const DOCUMENT_TYPES = [
        'endorsement' => [
            'label' => 'Endorsement Letter',
            'template' => 'documents.letters.endorsement',
            'description' => 'Firearm licence application endorsement — new white/orange design',
        ],
        'good-standing' => [
            'label' => 'Good Standing Certificate',
            'template' => 'documents.certificates.good-standing',
            'description' => 'Membership certificate confirming good standing',
        ],
        'dedicated-status' => [
            'label' => 'Dedicated Status Certificate',
            'template' => 'documents.certificates.dedicated-status',
            'description' => 'Dedicated Hunter/Sport Shooter status certificate',
        ],
        'welcome' => [
            'label' => 'Welcome Letter',
            'template' => 'documents.letters.welcome',
            'description' => 'New member welcome letter',
        ],
        'membership-card' => [
            'label' => 'Membership Card',
            'template' => 'documents.membership-card',
            'description' => 'Digital membership card (credit-card size)',
        ],
    ];

    public function index(): View
    {
        return view('pages.developer.document-preview', [
            'types' => self::DOCUMENT_TYPES,
        ]);
    }

    public function show(string $type)
    {
        if (! isset(self::DOCUMENT_TYPES[$type])) {
            abort(404, "Unknown document type: {$type}");
        }

        $template = self::DOCUMENT_TYPES[$type]['template'];

        return match ($type) {
            'endorsement' => view($template, $this->endorsementData()),
            'good-standing' => view($template, $this->goodStandingData()),
            'dedicated-status' => view($template, $this->dedicatedStatusData()),
            'welcome' => view($template, $this->welcomeData()),
            'membership-card' => view($template, $this->membershipCardData()),
        };
    }

    private function mockUser(): User
    {
        $user = new User([
            'name' => 'Paul Sheffield Charsley',
            'email' => 'paul@example.co.za',
            'id_number' => '8506015800087',
        ]);
        $user->id = 999999;
        $user->uuid = 'preview-user-00000000-0000-0000-0000-000000000000';

        return $user;
    }

    private function mockMembership(): Membership
    {
        $type = new MembershipType([
            'name' => 'Premium Sport & Hunter',
            'slug' => 'premium-sport-hunter',
        ]);

        $membership = new Membership([
            'membership_number' => 'NRAPA-2026-00001',
            'status' => 'active',
            'activated_at' => Carbon::parse('2024-03-15'),
            'applied_at' => Carbon::parse('2024-03-10'),
            'expires_at' => Carbon::parse('2027-03-15'),
        ]);
        $membership->setRelation('type', $type);

        return $membership;
    }

    private function mockCertificate(string $slug, string $label): Certificate
    {
        $user = $this->mockUser();
        $membership = $this->mockMembership();

        $certType = new CertificateType([
            'name' => $label,
            'slug' => $slug,
        ]);

        $certificate = new Certificate([
            'certificate_number' => 'NRAPA-CERT-2026-PREVIEW',
            'qr_code' => 'preview-qr-'.$slug,
            'issued_at' => now(),
            'valid_until' => now()->addYear(),
            'signatory_name' => 'Paul Charsley',
            'signatory_title' => 'NRAPA Administration',
            'signatory_signature_path' => null,
            'commissioner_oaths_scan_path' => null,
        ]);
        $certificate->id = 999999;
        $certificate->uuid = 'preview-cert-'.$slug;
        $certificate->setRelation('user', $user);
        $certificate->setRelation('membership', $membership);
        $certificate->setRelation('certificateType', $certType);

        return $certificate;
    }

    private function endorsementData(): array
    {
        $user = $this->mockUser();
        $membership = $this->mockMembership();
        $user->setRelation('activeMembership', $membership);

        $components = new Collection([
            new EndorsementComponent([
                'component_type' => 'barrel',
                'component_make' => 'Bartlein',
                'component_model' => 'Carbon',
                'component_serial' => 'BRT-2026-001',
                'diameter' => '6.5mm',
            ]),
        ]);

        $request = new EndorsementRequest([
            'letter_reference' => 'END-2026-PREVIEW',
            'issued_at' => now(),
            'purpose' => 'section_16_application',
            'dedicated_status_compliant' => true,
            'dedicated_category' => 'Dedicated Sport Shooter',
            'status' => 'issued',
        ]);
        $request->uuid = 'preview-endorsement-00000000';
        $request->setRelation('user', $user);
        $request->setRelation('firearm', null);
        $request->setRelation('components', $components);

        return [
            'request' => $request,
        ];
    }

    private function goodStandingData(): array
    {
        $certificate = $this->mockCertificate('good-standing-certificate', 'Good Standing Certificate');

        return [
            'certificate' => $certificate,
            'user' => $certificate->user,
            'membership' => $certificate->membership,
            'certificateType' => $certificate->certificateType,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ];
    }

    private function dedicatedStatusData(): array
    {
        $certificate = $this->mockCertificate('dedicated-hunter-certificate', 'Dedicated Hunter Certificate');

        return [
            'certificate' => $certificate,
            'user' => $certificate->user,
            'membership' => $certificate->membership,
            'certificateType' => $certificate->certificateType,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ];
    }

    private function welcomeData(): array
    {
        $user = $this->mockUser();
        $membership = $this->mockMembership();
        $user->physical_address = '702 Esther Street, Garsfontein, Pretoria, 0042';
        $user->postal_address = 'PO Box 12345, Garsfontein, 0042';

        $certificate = $this->mockCertificate('welcome-letter', 'Welcome Letter');
        $certificate->setRelation('user', $user);
        $certificate->setRelation('membership', $membership);

        return [
            'certificate' => $certificate,
            'user' => $user,
            'membership' => $membership,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ];
    }

    private function membershipCardData(): array
    {
        $certificate = $this->mockCertificate('membership-card', 'Membership Card');

        return [
            'certificate' => $certificate,
            'user' => $certificate->user,
            'membership' => $certificate->membership,
            'certificateType' => $certificate->certificateType,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ];
    }
}

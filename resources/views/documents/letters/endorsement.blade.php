@extends('documents.layouts.nrapa-official')

@php
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getEndorsementQrCodeUrl($request, 200);
    $verifyUrl = $request->letter_reference
        ? url('/verify/endorsement/' . $request->letter_reference)
        : ($request->uuid ? url('/verify/endorsement/' . $request->uuid) : '#');
    $signatory = \App\Helpers\DocumentDataHelper::getEndorsementSignatoryInfo($request);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml(\App\Helpers\DocumentDataHelper::getDefaultSignaturePath());

    $user = $request->user;
    $membership = $user->activeMembership;
    $firearm = $request->firearm;

    $purposeText = match($request->purpose) {
        'section_16_application' => 'Section 16 firearm licence application',
        'status_confirmation' => 'Status confirmation for regulatory purposes',
        'licence_renewal' => 'Firearm licence renewal application',
        'additional_firearm' => 'Application for additional firearm',
        'other' => $request->purpose_other_text ?? 'Other purpose',
        default => 'Firearm licence application',
    };

    $title = 'Endorsement Letter — NRAPA';
@endphp

@section('document-banner')
<div class="doc-banner">
    <div class="doc-banner-title">Endorsement Letter</div>
    <div class="doc-banner-subtitle">{{ $request->isSelfDefence() ? 'Self-defence supporting letter — issued voluntarily at the member\'s request' : 'Issued for firearm licence application purposes' }}</div>
</div>
@endsection

@section('content')
    {{-- Info grid: Member + Letter details (table layout) --}}
    <table class="layout-table">
        <tr>
            <td class="half">
                <div class="card">
                    <div class="card-title">Applicant / Member</div>
                    <table class="kv-table">
                        <tr>
                            <td class="kv-label">Full Name</td>
                            <td class="kv-value">{{ $user->getIdName() }}</td>
                        </tr>
                        <tr>
                            <td class="kv-label">ID / Passport</td>
                            <td class="kv-value">{{ $user->getIdNumber() ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="kv-label">Membership Number</td>
                            <td class="kv-value">{{ $membership->membership_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="kv-label">Membership Status</td>
                            <td class="kv-value" style="color:#1f6b3a; font-weight:600;">Member in Good Standing</td>
                        </tr>
                        <tr>
                            <td class="kv-label">Dedicated Status</td>
                            <td class="kv-value">{{ $request->dedicated_status_label }}</td>
                        </tr>
                        <tr>
                            <td class="kv-label">Dedicated Category</td>
                            <td class="kv-value">{{ $request->dedicated_category_label }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="half">
                <div class="card">
                    <div class="card-title">Letter Details</div>
                    <table class="kv-table">
                        <tr>
                            <td class="kv-label">Endorsement Ref</td>
                            <td class="kv-value">{{ $request->letter_reference ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="kv-label">Issued Date</td>
                            <td class="kv-value">{{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    @if($request->isSelfDefence())
    {{-- ===== Self-defence variant ===== --}}
    @php
        $sdType = $request->firearm_type_label;
        $sdMakeModel = trim(($request->firearm_make ?? '') . ' ' . ($request->firearm_model ?? ''));
        $sdCalibre = $request->firearm_calibre;
        $sdSerial = $request->firearm_serial ?: 'Serial to be confirmed';
    @endphp
    <div class="card components-card">
        <div class="card-title">Firearm This Letter Relates To</div>
        <table class="fg-table">
            <tr>
                <td>
                    <span class="fg-label">Type</span>
                    <span class="fg-value">{{ $sdType }}</span>
                </td>
                @if($sdMakeModel)
                <td>
                    <span class="fg-label">Make / Model</span>
                    <span class="fg-value">{{ $sdMakeModel }}</span>
                </td>
                @endif
                @if($sdCalibre)
                <td>
                    <span class="fg-label">Calibre</span>
                    <span class="fg-value">{{ $sdCalibre }}</span>
                </td>
                @endif
                <td>
                    <span class="fg-label">Serial</span>
                    <span class="fg-value">{{ $sdSerial }}</span>
                </td>
            </tr>
        </table>
    </div>

    @php
        $sdApplicationPhrase = $request->isRenewal()
            ? 'the renewal of a firearm licence for self-defence'
            : 'an application for a firearm for self-defence';
    @endphp
    <div class="letter-body">
        To whom it may concern,<br/><br/>
        This letter is issued by NRAPA at the written request of the above member in support of {{ $sdApplicationPhrase }} in terms of Section 13 of the Firearms Control Act, 2000 (Act 60 of 2000).
        <br/><br/>
        <div style="background:#f5f7fb; border-left:3px solid #0B4EA2; padding:6px 10px; margin:2px 0 8px 0; font-size:11px;">
            <b>Firearm to which this letter relates:</b> {{ $sdType }}@if($sdMakeModel) &mdash; {{ $sdMakeModel }}@endif@if($sdCalibre), {{ $sdCalibre }}@endif ({{ $sdSerial }}).
        </div>
        <div style="font-size:10.5px; line-height:1.45; color:#333;">
            <div style="font-weight:700; color:#0B4EA2; margin-bottom:3px;">Please note the nature and scope of this endorsement:</div>
            <p style="margin:0 0 6px 0;">Based on the information available to NRAPA and as declared by the member, this is the only Section 13 (self-defence) firearm of which NRAPA is aware; the member has declared they hold no other firearm licensed under Section 13. This endorsement relies on information provided by the member, does not warrant their complete firearm holdings, and does not relieve the SAPS of its own verification obligations. It speaks only as at the date of issue, and the licensing decision remains within the sole discretion of the Registrar of Firearms.</p>
            <p style="margin:0;">An association endorsement is not a legal requirement for a Section 13 (self-defence) licence application. NRAPA provides this letter voluntarily, at the member's request, for the limited purpose of confirming that the member is a registered dedicated hunter and/or dedicated sports person, in good standing and active in their dedicated activities as at the date of issue. It is a confirmation of association status only, and does not constitute a motivation for the self-defence licence. The motivation rests with the member; the SAPS assesses it and the Registrar decides, and NRAPA expresses no view on its merits.</p>
        </div>
    </div>
    @else
    {{-- ===== Dedicated status variant (unchanged) ===== --}}
    {{-- Endorsed items --}}
    @php
        $hasComponents = $request->components && $request->components->isNotEmpty();
        $endorsedHeading = $firearm && $hasComponents
            ? 'Endorsed Firearm & Components'
            : ($firearm ? 'Endorsed Firearm' : ($hasComponents && $request->components->count() > 1 ? 'Endorsed Components' : 'Endorsed Component'));
    @endphp
    @if ($firearm || $hasComponents)
    <div class="card components-card">
        <div class="card-title">{{ $endorsedHeading }}</div>
        @if($firearm)
        @php
            $makeName = $firearm->make_display ?? '';
            $modelName = $firearm->model_display ?? '';
            $calibreName = $firearm->calibre_display ?? '';
            $actionLabel = $firearm->action_type_label;
            $serialNumbers = $firearm->serial_numbers;
        @endphp
        <table class="fg-table">
            <tr>
                <td>
                    <span class="fg-label">Type</span>
                    <span class="fg-value">{{ $firearm->category_label ?? 'Firearm' }}</span>
                </td>
                @if(trim($makeName . ' ' . $modelName))
                <td>
                    <span class="fg-label">Make / Model</span>
                    <span class="fg-value">{{ trim($makeName . ' ' . $modelName) }}</span>
                </td>
                @endif
                @if($calibreName)
                <td>
                    <span class="fg-label">Calibre</span>
                    <span class="fg-value">{{ $calibreName }}</span>
                </td>
                @endif
            </tr>
            @if($firearm->component_diameter || $actionLabel || !empty($serialNumbers))
            <tr>
                @if($firearm->component_diameter)
                <td>
                    <span class="fg-label">Diameter</span>
                    <span class="fg-value">{{ $firearm->component_diameter }}</span>
                </td>
                @endif
                @if($actionLabel)
                <td>
                    <span class="fg-label">Action</span>
                    <span class="fg-value">{{ $actionLabel }}</span>
                </td>
                @endif
                @foreach($serialNumbers as $type => $info)
                <td>
                    <span class="fg-label">{{ ucfirst($type) }} Serial</span>
                    <span class="fg-value">{{ !empty($info['serial']) ? strtoupper($info['serial']) : '—' }}@if($info['make']) <span style="font-weight:400; font-size:10px; color:#6a6a6a;">({{ $info['make'] }})</span>@endif</span>
                </td>
                @endforeach
            </tr>
            @endif
        </table>
        @endif
        @if($hasComponents)
        <table class="component-table">
            @foreach ($request->components as $component)
            <tr>
                <td>
                    <span class="component-type">{{ $component->component_type_label }}</span>
                    @if ($component->component_make || $component->component_model)
                        &mdash; {{ trim(($component->component_make ?? '') . ' ' . ($component->component_model ?? '')) }}
                    @endif
                </td>
                <td class="component-detail">
                    @if ($component->component_type === 'barrel' && $component->diameter)
                        Diameter: {{ $component->diameter }}
                    @elseif ($component->calibre_display)
                        Calibre: {{ $component->calibre_display }}
                    @endif
                    @if ($component->component_serial)
                        &nbsp;| Serial: {{ strtoupper($component->component_serial) }}
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
        @endif
    </div>
    @endif

    {{-- Letter body --}}
    <div class="letter-body">
        To whom it may concern,<br/><br/>
        This letter serves to confirm that <b>{{ $user->getIdName() }}</b>
        (ID/Passport: <b>{{ $user->getIdNumber() ?? 'N/A' }}</b>) is a
        <b>member in good standing</b> of the National Rifle &amp; Pistol Association of South Africa (NRAPA).
        <br/><br/>
        This endorsement is issued for the following purpose(s):
        <span class="purpose-line">{{ $purposeText }}</span>
        Issued under the member's <b>{{ $request->dedicated_category_label }}</b> status.
        <br/><br/>
        The Association confirms that the firearm or component(s) described above is suitable for the stated purpose in accordance with the Firearms Control Act (Act 60 of 2000, as amended) and relevant Regulations.
    </div>
    @endif

    {{-- Bottom: Signatory + Commissioner of Oaths (table layout) --}}
    <table class="layout-table">
        <tr>
            <td class="half">
                <div class="card signatory-card">
                    <div class="card-title">Authorised NRAPA Signatory</div>
                    <div class="sig-box">{!! $signatureHtml !!}</div>
                    <div style="height:2px;"></div>
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $signatory['name'] }}</div>
                    <div class="sig-title">{{ $signatory['title'] }}</div>
                    <div class="sig-date">Issued at {{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</div>
                </div>
            </td>
            <td class="half">
                @include('documents.partials.commissioner-oaths-inline')
            </td>
        </tr>
    </table>

    {{-- Verification strip (full width, horizontal) --}}
    @include('documents.partials.verify-strip', [
        'qrCodeUrl' => $qrCodeUrl,
        'verifyUrl' => $verifyUrl,
        'verifyStripTitle' => 'Verify This Endorsement',
        'verifyStripBlurb' => 'Scan the QR code or visit the link below to confirm this is a genuine NRAPA endorsement letter.',
    ])
@endsection

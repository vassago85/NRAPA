@extends('layouts.info')

@php
    use App\Models\MembershipType;
    $transferType = MembershipType::where('slug', 'transfer')->first();
    $basicType = MembershipType::where('slug', 'basic')->first();
    $bothType = MembershipType::where('slug', 'dedicated-both')->first();
    $transferFee = (float) ($transferType?->initial_price ?? 550);
    $standardSignup = (float) ($basicType?->initial_price ?? 700);
    $bothUpgrade = $bothType ? (float) ($basicType?->initial_price ?? 0) + (float) ($bothType->upgrade_price ?? 0) : 1900.0;
@endphp

@section('title', 'Transfer Dedicated Status to NRAPA | Change Shooting Association')
@section('description', 'Transfer your dedicated status to NRAPA from another SAPS-accredited association. Recognition of prior learning, document review and only the renewal-equivalent fee.')
@section('heading', 'Transfer your dedicated status membership to NRAPA')
@section('subheading', 'Already a member of another SAPS-accredited association? Move across without paying a full sign-up fee.')
@section('breadcrumb', 'Transfer your membership')

@push('structured_data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => 'Transfer dedicated status membership to NRAPA',
    'serviceType' => 'Membership transfer',
    'provider' => [
        '@type' => 'Organization',
        'name' => 'NRAPA',
        'url' => config('app.url'),
    ],
    'areaServed' => 'ZA',
    'description' => 'Transfer your existing dedicated status from another SAPS-accredited association to NRAPA. Document review and reduced fee.',
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'ZAR',
        'price' => \App\Support\Money::schema($transferFee),
        'availability' => 'https://schema.org/InStock',
    ],
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endpush

@section('content')
    <div class="info-card">
        <h4>The short version</h4>
        <p>If you already hold a competency certificate and are a paid-up member of another SAPS-accredited association, you can transfer to NRAPA by uploading two documents and paying only the renewal-equivalent fee of <x-money :amount="$transferFee" /> &mdash; instead of a full sign-up.</p>
    </div>

    <h2>Who can transfer their membership to NRAPA?</h2>
    <p>You can use the transfer route if all of the following are true:</p>
    <ul class="checklist">
        <li>You hold a valid SAPS firearm competency certificate.</li>
        <li>You are currently a paid-up member of another SAPS-accredited association (any association &mdash; we don't single anyone out).</li>
        <li>You are tax resident in South Africa and 18 years or older (junior memberships have a separate process).</li>
    </ul>
    <p>If any of those isn't true, no problem &mdash; the standard <a href="{{ route('info.dedicated-procedure') }}">dedicated status procedure</a> still applies and we will guide you through it.</p>

    <h2>What documents do I need to upload?</h2>
    <p>During the application you will be asked to attach two PDFs or photos (each up to 10MB):</p>
    <ol class="step-list">
        <li>
            <strong>Your competency certificate.</strong>
            <p>The SAPS competency certificate (or proficiency certificates plus Statement of Results) issued in your name. We use this to confirm your training is current.</p>
        </li>
        <li>
            <strong>Your current membership certificate</strong> from the SAPS-accredited association you are leaving.
            <p>The certificate must show your member number, the association's name and FAR accreditation, and that the membership is currently active.</p>
        </li>
    </ol>
    <p>Both documents are stored on private, encrypted storage and are visible only to NRAPA admin reviewers. See our <a href="{{ route('privacy-policy') }}">privacy policy</a> for the POPIA detail.</p>

    <h2>What happens to my existing dedicated status?</h2>
    <p>Your dedicated status is granted to you under <a href="{{ route('info.firearm-licence-process') }}">Section 16 of the Firearms Control Act</a> and travels with you between SAPS-accredited associations. When NRAPA verifies your transfer, your dedicated standing is recognised under our FAR 1300122 (sport shooting) and FAR 1300127 (hunting) accreditations &mdash; you don't have to redo your training or your competency.</p>
    <p>You will, however, follow NRAPA's annual activity rules from the date of activation. See <a href="{{ route('info.shooting-exercises') }}">shooting activity records</a> for what counts.</p>

    <h2>How the review process works</h2>
    <ol class="step-list">
        <li>
            <strong>Apply &amp; upload.</strong>
            <p>Sign up for an NRAPA account, choose <em>Transfer Membership</em>, and upload your two documents.</p>
        </li>
        <li>
            <strong>Pay the transfer fee.</strong>
            <p>You will be emailed banking details with a unique payment reference. The fee is <x-money :amount="$transferFee" />.</p>
        </li>
        <li>
            <strong>Admin review.</strong>
            <p>An NRAPA admin verifies that your competency and current-association membership are valid. Most reviews are completed inside 5 working days.</p>
        </li>
        <li>
            <strong>Activation.</strong>
            <p>Once approved you receive a welcome email, your member number, and your NRAPA membership and dedicated status certificates appear in your portal.</p>
        </li>
        <li>
            <strong>If something is unclear.</strong>
            <p>If a document is hard to read or doesn't match SAPS-accredited records, we email you with what to fix and you can re-upload &mdash; you don't have to restart the application.</p>
        </li>
    </ol>

    <h2>What does the transfer route cost?</h2>
    <p>Transfer applicants pay only <strong><x-money :amount="$transferFee" /></strong> as a once-off transfer fee, and then the standard annual renewal each year thereafter. By comparison, a standard new dedicated hunter &amp; sport shooter sign-up is <x-money :amount="$bothUpgrade" /> in the first year. Pricing is set in the admin panel and shown live on the apply page so you always see the current amount before submitting.</p>

    <div class="info-card">
        <h4>Ready to move across?</h4>
        <p>Become an NRAPA <strong>dedicated status membership</strong> holder with the documents you already have. <a href="{{ route('register') }}">Start your transfer application</a>.</p>
    </div>

    <h2>Frequently asked questions</h2>
    <h3>Will I lose any of my activity history?</h3>
    <p>Activities you logged with another association stay with that association's records. Going forward, log your activities in the NRAPA portal so we can confirm your annual compliance.</p>

    <h3>Do I have to give my reason for transferring?</h3>
    <p>No. You only need to confirm you are currently a member of another SAPS-accredited association and upload the two supporting documents.</p>

    <h3>What if my current membership has lapsed?</h3>
    <p>If your existing association membership has expired, transfer isn't the right route. Apply via the standard <a href="{{ route('info.dedicated-procedure') }}">dedicated status procedure</a> instead.</p>

    <h2>Related guides</h2>
    <ul class="checklist">
        <li><a href="{{ route('info.dedicated-procedure') }}">How to get dedicated status in South Africa</a></li>
        <li><a href="{{ route('info.dedicated-hunter-vs-sport-shooter') }}">Dedicated hunter vs dedicated sport shooter &mdash; what's the difference?</a></li>
        <li><a href="{{ route('info.dedicated-status-faq') }}">Dedicated Status FAQ</a></li>
    </ul>
@endsection

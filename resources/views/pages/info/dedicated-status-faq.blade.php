@extends('layouts.info')

@section('title', 'Dedicated Status FAQ | South Africa Sport Shooter & Hunter Questions')
@section('description', 'Dedicated status FAQ for South African sport shooters and hunters: how long it takes, ammunition limits, firearm allowances, transferring associations, and annual activity rules.')
@section('heading', 'Dedicated Status FAQ')
@section('subheading', 'Long-tail questions about dedicated sport shooter and dedicated hunter status, answered.')
@section('breadcrumb', 'Dedicated Status FAQ')

@php
    $faqEntries = [
        [
            'q' => 'How long does dedicated status take in South Africa?',
            'a' => 'For a fresh applicant, allow about 5 to 10 working days from a complete NRAPA application to activation: that includes admin review of your documents, knowledge-test confirmation, and certificate issue. If you are transferring from another SAPS-accredited association, the same window usually applies.',
        ],
        [
            'q' => 'Can I lose my dedicated status?',
            'a' => 'Yes. Dedicated status is renewed annually and depends on logged participation. If you stop logging activities, fall behind on renewals, or your association loses or suspends you, your dedicated standing can lapse and SAPS must be informed. The fix is usually to bring your activities up to date and renew on time.',
        ],
        [
            'q' => 'What are the ammunition limits with dedicated status?',
            'a' => 'The Firearms Control Act sets ammunition possession in relation to your licensed firearms and intended use, not as a single national number. Sport shooters may keep a reasonable quantity for practice and competition; hunters may keep a reasonable quantity for hunting use. The exact figure depends on your licences and motivation. [VERIFY] We recommend checking the latest SAPS regulation or asking NRAPA support before stocking up.',
        ],
        [
            'q' => 'How many firearms can a dedicated sport shooter own?',
            'a' => 'There is no fixed national cap. SAPS evaluates each Section 16 application on its merits: the discipline you compete in, your activity record, and your motivation. A dedicated sport shooter who actively competes across handgun, rifle, and shotgun disciplines can motivate for several firearms; a casual competitor in one discipline cannot. [VERIFY] for any specific category before applying.',
        ],
        [
            'q' => 'Can I transfer my dedicated status from another association?',
            'a' => 'Yes. NRAPA accepts transfers from any other SAPS-accredited association. You upload your competency certificate and your current membership certificate, pay the reduced transfer fee, and your dedicated status is recognised. See our transfer-your-membership guide for the full process.',
        ],
        [
            'q' => 'What are the annual activity requirements for dedicated status?',
            'a' => 'NRAPA requires logged, verifiable participation each year: hunts, range sessions, league entries or competitions appropriate to your designation. The exact minimum depends on your category &mdash; see the dedicated-procedure guide for the current rules and the shooting activity record for what counts as evidence.',
        ],
        [
            'q' => 'Do I need to redo my SAPS competency when I transfer to NRAPA?',
            'a' => 'No. Your SAPS competency is granted to you, not to your association, and travels with you. We use your existing competency certificate as part of the transfer review.',
        ],
        [
            'q' => 'Can I hold both dedicated hunter and dedicated sport shooter status?',
            'a' => 'Yes. Many shooters do. NRAPA holds both accreditations (FAR 1300122 sport shooting and FAR 1300127 hunting), so a single membership can carry both designations. We have a combined package that covers both at a lower combined price.',
        ],
    ];

    $faqStructured = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f['q'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $f['a'],
            ],
        ], $faqEntries),
    ];
@endphp

@push('structured_data')
<script type="application/ld+json">
{!! json_encode($faqStructured, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endpush

@section('content')
    <p>This FAQ answers the questions we hear most often from members and prospective members about dedicated status in South Africa. Where the law is uncertain or recently changed we mark the value <code>[VERIFY]</code> rather than guess. If you need a definitive answer for a SAPS application, ask us.</p>

    @foreach($faqEntries as $faq)
        <h2>{{ $faq['q'] }}</h2>
        <p>{{ $faq['a'] }}</p>
    @endforeach

    <div class="info-card">
        <h4>Need help with a specific case?</h4>
        <p>Our admin team can confirm specifics for your discipline or licence application. <a href="mailto:info@nrapa.co.za">Email info@nrapa.co.za</a> or call +27 87 151 0987.</p>
    </div>

    <h2>Related guides</h2>
    <ul class="checklist">
        <li><a href="{{ route('info.dedicated-procedure') }}">How to get dedicated status in South Africa &mdash; step by step</a></li>
        <li><a href="{{ route('info.dedicated-hunter-vs-sport-shooter') }}">Dedicated hunter vs dedicated sport shooter</a></li>
        <li><a href="{{ route('info.transfer-your-membership') }}">Transfer your dedicated status to NRAPA</a></li>
        <li><a href="{{ route('info.shooting-exercises') }}">Shooting activity record for dedicated status</a></li>
        <li><a href="{{ route('register') }}">Register for your dedicated status membership</a></li>
    </ul>
@endsection

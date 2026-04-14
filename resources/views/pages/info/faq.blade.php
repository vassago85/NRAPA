@extends('layouts.info')

@section('title', 'FAQ | Membership, Dedicated Status & Common Questions')
@section('description', 'Answers to common questions about NRAPA membership, dedicated sport shooter and hunter status, endorsements, activity requirements, and how the association works in South Africa.')
@section('heading', 'Frequently asked questions')
@section('breadcrumb', 'FAQ')

@push('structured_data')
@php
    $faqN = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'Is NRAPA part of Ranyati Group?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes. NRAPA operates as the SAPS-accredited association division within the Ranyati Group ecosystem, alongside Ranyati Motivations and Ranyati Storage.'],
            ],
            [
                '@type' => 'Question',
                'name' => 'Does NRAPA guarantee SAPS will approve my licence?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No. SAPS decides every application. NRAPA provides accredited membership, documentation, and compliance tools; outcomes depend on SAPS and the facts of each case.'],
            ],
            [
                '@type' => 'Question',
                'name' => 'Where do I get a firearm licence motivation?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Ranyati Motivations (motivations.ranyati.co.za) is the group division focused on professional motivation documents. NRAPA handles association membership and dedicated status administration.'],
            ],
            [
                '@type' => 'Question',
                'name' => 'How do I contact NRAPA?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Use info@nrapa.co.za or +27 87 151 0987, or work through your member dashboard after registration.'],
            ],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($faqN, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <h2>Is NRAPA part of Ranyati Group?</h2>
    <p>Yes. NRAPA is the association and membership portal within the same group as <a href="https://motivations.ranyati.co.za">Ranyati Motivations</a> and <a href="https://storage.ranyati.co.za">Ranyati Storage</a>. The parent brand is <a href="https://ranyati.co.za">Ranyati Group</a>.</p>

    <h2>Will NRAPA guarantee my licence is approved?</h2>
    <p>No. We support compliance and documentation; SAPS makes final decisions on every application.</p>

    <h2>Who writes my motivation?</h2>
    <p><a href="https://motivations.ranyati.co.za">Ranyati Motivations</a> handles motivation drafting. NRAPA handles membership, activities, and dedicated status administration.</p>

    <h2>How do I contact support?</h2>
    <p>Email <a href="mailto:info@nrapa.co.za">info@nrapa.co.za</a> or call <a href="tel:+27871510987">+27 87 151 0987</a>. Registered members should also check the dashboard for notices.</p>

    <h2>More reading</h2>
    <ul class="link-list">
        <li><a href="{{ route('info.index') }}">Info hub</a></li>
        <li><a href="{{ route('info.how-to-get-dedicated-status-south-africa') }}">How to get dedicated status</a></li>
        <li><a href="{{ route('info.about') }}">About NRAPA</a></li>
        <li><a href="{{ route('register') }}">Register now</a></li>
    </ul>
@endsection

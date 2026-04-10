@extends('layouts.info')

@section('title', 'Dedicated Hunter South Africa | NRAPA')
@section('description', 'Dedicated hunter status in South Africa: SAPS-accredited association membership through NRAPA, lawful hunting activity, and compliance with the Firearms Control Act.')
@section('heading', 'Dedicated hunter in South Africa')
@section('breadcrumb', 'Dedicated hunter')

@section('content')
    <p>Dedicated hunter status links lawful hunting activity to membership in a SAPS-accredited hunting or combined association. NRAPA supports members who pursue this pathway with structured administration and proof-of-activity tools.</p>

    <h2>Responsibilities of the hunter</h2>
    <p>You must hunt lawfully, hold valid licences for your firearms, respect provincial seasons and property rights, and maintain the evidence of activity that SAPS may expect at renewal or inspection.</p>

    <h2>How NRAPA helps</h2>
    <p>We provide accredited association membership, guidance on internal procedures, and the member portal for submissions and certificates—without replacing SAPS decisions on any individual licence.</p>

    <h2>Related pages</h2>
    <p><a href="{{ route('info.how-to-get-dedicated-status-south-africa') }}">How to get dedicated status</a> · <a href="{{ route('info.dedicated-procedure') }}">Dedicated procedure</a> · <a href="https://motivations.ranyati.co.za/firearm-licence-motivation-hunting">Hunting motivations (Ranyati Motivations)</a></p>
@endsection

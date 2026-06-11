@extends('layouts.info')

@section('title', 'Firearm Licence Process South Africa | Section 16 & Competency')
@section('description', 'The firearm licence process in South Africa: SAPS competency, Section 13/15/16 firearm licence categories, supporting motivation, dedicated status and how NRAPA helps you stay compliant.')
@section('heading', 'Firearm Licence Process in South Africa')
@section('breadcrumb', 'Firearm Licence Process')

@section('content')
    <h2>Overview</h2>
    <p>The Firearms Control Act, 2000 (Act No 60 of 2000) aims at ensuring a person is competent to possess a firearm. Before any person may possess a firearm, one must obtain a firearm licence from the SAPS. No generic type licences exist — a firearm licence is firearm and serial number specific.</p>

    <h2>Competency Process</h2>
    <ol class="step-list">
        <li>
            <strong>Complete prescribed tests</strong>
            <p>Knowledge of the FCA (Unit Standard 117705), plus practicals (Handgun 119649, Rifle 119651, Self-loading 119650, Shotgun 119652) at an accredited training provider.</p>
        </li>
        <li>
            <strong>Obtain proficiency certificates</strong>
            <p>Collect your proficiency certificates and Statement of Results.</p>
        </li>
        <li>
            <strong>Complete SAPS 517 form</strong>
            <p>Application for Competency (black pen only).</p>
        </li>
        <li>
            <strong>Complete reference forms</strong>
            <p>3× SAPS Annexure 86 forms (references).</p>
        </li>
    </ol>

    <h3>Supporting documents for competency</h3>
    <ul class="checklist">
        <li>3 certified ID copies</li>
        <li>2 certified copies of competency certificates plus Statement of Results</li>
        <li>2 passport photos (neutral background, not older than 3 months)</li>
    </ul>

    <h2>Licensing Process</h2>
    <p>After obtaining your SAPS Competency Certificate:</p>
    <ol class="step-list">
        <li>
            <strong>Complete SAPS 271 form</strong>
            <p>The firearm licence application form.</p>
        </li>
        <li>
            <strong>Submit to DFO with supporting documents</strong>
            <p>Submit in person at your nearest SAPS station with all required documents.</p>
        </li>
    </ol>

    <h3>Required documents for licensing</h3>
    <ul class="checklist">
        <li>2 certified ID copies</li>
        <li>Certified copies of existing firearm licences (front and back for white card, front only for green card)</li>
        <li>Dealer stock return (if from dealer)</li>
        <li>Certified proof of residence (water &amp; lights bill, or rental agreement)</li>
        <li>Pictures of safe confirming installation</li>
        <li>Certified competency certificate</li>
        <li>Detailed motivation with references, membership, dedicated status, shooting activities, endorsements, calendars, hunt confirmations</li>
    </ul>

    <h2>Section 13, 15 and 16 firearm licences</h2>
    <p>Under the Firearms Control Act, the section under which you apply determines what you may use the firearm for and how many you may own. The most common categories for sport shooters and hunters are:</p>
    <ul class="checklist">
        <li><strong>Section 13</strong> — for self-defence (one handgun).</li>
        <li><strong>Section 15</strong> — occasional sport shooting or occasional hunting (limited firearms; needs association membership).</li>
        <li><strong>Section 16</strong> — dedicated sport shooting or dedicated hunting. With Section 16 status NRAPA confirms your participation each year and you may motivate for additional firearms appropriate to your discipline. <a href="{{ route('info.dedicated-procedure') }}">See how to get dedicated status.</a></li>
    </ul>
    <p>Section 16 applications must be supported by a SAPS-accredited association &mdash; that is the standing NRAPA confirms with its FAR 1300122 (sport shooting) and FAR 1300127 (hunting) accreditations.</p>
    <p>The SAPS application form is the <a href="https://www.saps.gov.za/services/flash/firearms/forms/english/e271.pdf" target="_blank" rel="noopener nofollow">SAPS 271 firearm licence application (PDF)</a>. Download it directly from the SAPS website, complete it in black ink, and submit it in person at your nearest Designated Firearms Officer (DFO) together with the supporting documents listed above.</p>

    <h2>Estate Firearms</h2>
    <ul class="checklist">
        <li>Certified Letter of Executorship</li>
        <li>Certified ID of Executor and Executor's letter</li>
        <li>Transfer letter from Executor</li>
        <li>Certified death certificate and ID of deceased</li>
        <li>Copy of deceased's licence or affidavit</li>
    </ul>

    <div class="info-card">
        <h4>Important Notes</h4>
        <ul>
            <li>Must submit at nearest SAPS in person</li>
            <li>Contact police station to confirm days/times</li>
            <li>Initial each page and sign last page of motivation</li>
            <li>Certify all documents before submission</li>
            <li>Must apply for Competency Certificate before licence applications</li>
        </ul>
    </div>
@endsection

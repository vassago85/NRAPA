@extends('layouts.info')

@section('title', 'Minimum Membership Requirements')
@section('description', 'Minimum requirements and conditions for NRAPA membership registration including dedicated, ordinary, senior and junior membership categories.')
@section('heading', 'Minimum Requirements')
@section('subheading', 'Conditions for registering as an NRAPA member')
@section('breadcrumb', 'Minimum Requirements')

@section('content')
    <h2>General Requirements</h2>
    <ol class="step-list">
        <li>
            <strong>Eligibility</strong>
            <p>Applicant must not be unfit to possess a firearm in terms of the Firearms Control Act (60 of 2000) and must be 18 years of age (except Junior membership).</p>
        </li>
        <li>
            <strong>Application</strong>
            <p>Complete the membership application form and submit a signed copy to the office.</p>
        </li>
        <li>
            <strong>Duration &amp; payment</strong>
            <p>Membership is for 12 months from date of enrolment. Once-off entry fee plus annual subscription required at enrolment, payable in month of first enrolment thereafter.</p>
        </li>
    </ol>

    <h2>Ordinary Membership</h2>
    <p>Any natural persons between <strong>18 and 60 years</strong> of age may apply, accompanied by proof of payment. Membership may be refused if disciplinary measures have been instituted by other accredited associations.</p>

    <h2>Dedicated Membership</h2>
    <ul class="checklist">
        <li>Must be a fully paid-up member in good standing</li>
        <li>No disciplinary actions pending</li>
        <li>Must indicate written adherence to Association's Code of Conduct at enrolment for the dedicated course</li>
        <li>In event of special merit, exemption may be granted with evidence of high-level provincial, national or international participation</li>
    </ul>

    <h2>Senior Membership</h2>
    <p>Any natural persons from age <strong>61</strong>, accompanied by proof of payment. May be refused if disciplinary measures exist.</p>

    <h2>Junior Membership</h2>
    <p>Any person <strong>under 18 years</strong>, accompanied by proof of payment.</p>

    <h2>Ready to join?</h2>
    <ul class="link-list">
        <li><a href="{{ route('register') }}">Register for NRAPA</a></li>
        <li><a href="{{ route('info.membership-benefits') }}">Membership benefits</a></li>
        <li><a href="{{ route('info.how-to-get-dedicated-status-south-africa') }}">How to get dedicated status</a></li>
    </ul>
@endsection

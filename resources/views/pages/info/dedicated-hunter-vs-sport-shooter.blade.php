@extends('layouts.info')

@php
    use App\Models\MembershipType;
    $sport = MembershipType::where('slug', 'dedicated-sport')->first();
    $hunter = MembershipType::where('slug', 'dedicated-hunter')->first();
    $both = MembershipType::where('slug', 'dedicated-both')->first();
    $basic = MembershipType::where('slug', 'basic')->first();
    $basicSignup = (float) ($basic?->initial_price ?? 0);
    $sportSignup = $basicSignup + (float) ($sport?->upgrade_price ?? 0);
    $hunterSignup = $basicSignup + (float) ($hunter?->upgrade_price ?? 0);
    $bothSignup = $basicSignup + (float) ($both?->upgrade_price ?? 0);
@endphp

@section('title', 'Dedicated Hunter vs Dedicated Sport Shooter | Differences Explained')
@section('description', 'Difference between dedicated hunter and dedicated sport shooter in South Africa: legal definitions under the Firearms Control Act, firearm and ammunition limits, and which NRAPA package fits.')
@section('heading', 'Dedicated hunter vs dedicated sport shooter')
@section('subheading', 'How the two designations differ &mdash; and how to choose the right NRAPA package')
@section('breadcrumb', 'Hunter vs Sport Shooter')

@section('content')
    <div class="info-card">
        <h4>The short version</h4>
        <p>Dedicated <strong>hunter</strong> status supports legal hunting and the firearms appropriate to it; dedicated <strong>sport shooter</strong> status supports formal target disciplines. Many shooters qualify for both &mdash; and NRAPA offers a combined package for that case.</p>
    </div>

    <h2>Legal definitions under the Firearms Control Act</h2>
    <p>Both statuses are granted via a SAPS-accredited association and are referenced on Section 16 firearm licence applications. The Firearms Control Act 60 of 2000 defines the two as follows:</p>
    <ul class="checklist">
        <li><strong>Dedicated hunter</strong> &mdash; a member of an accredited hunting association whose participation in hunting activities is verified by that association annually.</li>
        <li><strong>Dedicated sport shooter</strong> &mdash; a member of an accredited sport shooting association whose participation in formal sport shooting is verified by that association annually.</li>
    </ul>
    <p>NRAPA holds <strong>two</strong> accreditations: <strong>FAR 1300122</strong> for sport shooting and <strong>FAR 1300127</strong> for hunting, so a single NRAPA membership can carry either status, or both at once.</p>

    <h2>Firearm and ammunition limits at a glance</h2>
    <p>The Firearms Control Act and its regulations set distinct allowances for each designation. Where regulations have changed or the figure depends on motivation, we mark the value <code>[VERIFY]</code> rather than guessing:</p>

    <table class="info-table">
        <thead>
            <tr>
                <th>Topic</th>
                <th>Dedicated hunter</th>
                <th>Dedicated sport shooter</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Section under which most firearms are licensed</td>
                <td>Section 16 (dedicated hunting)</td>
                <td>Section 16 (dedicated sport shooting)</td>
            </tr>
            <tr>
                <td>Number of firearms allowed</td>
                <td>Reasonable number based on motivation and discipline <code>[VERIFY]</code></td>
                <td>Reasonable number based on motivation and discipline <code>[VERIFY]</code></td>
            </tr>
            <tr>
                <td>Ammunition possession (per calibre, per licensed firearm)</td>
                <td>Reasonable quantity for hunting use <code>[VERIFY]</code></td>
                <td>Reasonable quantity for sport-shooting practice and competition <code>[VERIFY]</code></td>
            </tr>
            <tr>
                <td>Annual activity requirement (NRAPA)</td>
                <td>Logged hunts and/or hunting-related range work that the association can verify</td>
                <td>Logged formal range or competition activity that the association can verify</td>
            </tr>
            <tr>
                <td>Typical disciplines</td>
                <td>Plains-game hunts, varmint, big-game, hunting-style range training</td>
                <td>IPSC, IDPA, NRL SA, Practical Precision, postal/long-range leagues</td>
            </tr>
        </tbody>
    </table>

    <p>The exact firearm and ammunition limits are set by SAPS and depend on your individual motivation, the discipline, and the firearm category. If you need a definitive number for an application, ask NRAPA support and we will verify it before you submit.</p>

    <h2>Which NRAPA package applies?</h2>
    <ul class="checklist">
        <li><strong>{{ $sport?->name ?? 'Dedicated Sport Shooter' }}</strong> &mdash; sport-shooting status only. Sign-up <x-money :amount="$sportSignup" />, annual renewal <x-money :amount="$sport?->renewal_price ?? 550" />.</li>
        <li><strong>{{ $hunter?->name ?? 'Dedicated Hunter' }}</strong> &mdash; hunting status only. Sign-up <x-money :amount="$hunterSignup" />, annual renewal <x-money :amount="$hunter?->renewal_price ?? 550" />.</li>
        <li><strong>{{ $both?->name ?? 'Dedicated Hunter & Sport Shooter' }}</strong> &mdash; both designations on a single membership. Sign-up <x-money :amount="$bothSignup" />, annual renewal <x-money :amount="$both?->renewal_price ?? 550" />.</li>
    </ul>
    <p>If you are coming from another SAPS-accredited association, you may qualify for our reduced <a href="{{ route('info.transfer-your-membership') }}">transfer route</a> instead of paying a full sign-up.</p>

    <h2>How do I decide?</h2>
    <ol class="step-list">
        <li>
            <strong>Look at how you actually use your firearms.</strong>
            <p>Is most of your range time formal competition or club leagues? You need sport shooter. Is most of it hunting trips and hunt-prep? You need hunter. If both, take the combined option from the start.</p>
        </li>
        <li>
            <strong>Plan two years ahead.</strong>
            <p>Section 16 is renewed annually and SAPS expects current evidence. Picking the wrong status and never logging the matching activity makes renewals harder.</p>
        </li>
        <li>
            <strong>You can upgrade later.</strong>
            <p>You can move from hunter or sport to the combined package at any time &mdash; you only pay the difference on the upgrade.</p>
        </li>
    </ol>

    <div class="info-card">
        <h4>Pick your dedicated status membership</h4>
        <p>Compare both packages side-by-side on the <a href="{{ route('home') }}#pricing">pricing section of the homepage</a>, then <a href="{{ route('register') }}">register for your dedicated status membership</a>. Already a member elsewhere? Use the <a href="{{ route('info.transfer-your-membership') }}">transfer route</a>.</p>
    </div>

    <h2>Related guides</h2>
    <ul class="checklist">
        <li><a href="{{ route('info.minimum-requirements') }}">Dedicated sport shooter &amp; hunter requirements</a></li>
        <li><a href="{{ route('info.dedicated-procedure') }}">How to get dedicated status in South Africa &mdash; step by step</a></li>
        <li><a href="{{ route('info.dedicated-status-faq') }}">Dedicated Status FAQ</a></li>
        <li><a href="{{ route('info.shooting-exercises') }}">Shooting activity record for dedicated status</a></li>
    </ul>
@endsection

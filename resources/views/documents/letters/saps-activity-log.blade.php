@extends('documents.layouts.nrapa-official')

@php
    /** @var \App\Models\User $user */
    /** @var \Illuminate\Support\Collection $activities */
    /** @var \Carbon\Carbon $from */
    /** @var \Carbon\Carbon $to */

    $issueDate = now();
    $totalActivities = $activities->count();
    $totalRounds = (int) $activities->sum('rounds_fired');
    $sportCount = $activities->where('track', 'sport')->count();
    $huntingCount = $activities->where('track', 'hunting')->count();
    $title = 'Activity Log — NRAPA';
@endphp

@section('document-banner')
<div class="doc-banner">
    <div class="doc-banner-title">Activity Letter</div>
    <div class="doc-banner-subtitle">For SAPS Firearm Licence Application or Motivation</div>
</div>
@endsection

@section('content')
    {{-- Top info grid: member + period summary --}}
    <table class="layout-table">
        <tr>
            <td class="half">
                <div class="card">
                    <div class="card-title">Member Details</div>
                    <table class="kv-table">
                        <tr><td class="kv-label">Full Name</td><td class="kv-value">{{ $user->getIdName() }}</td></tr>
                        <tr><td class="kv-label">ID / Passport</td><td class="kv-value">{{ $user->getIdNumber() ?? 'N/A' }}</td></tr>
                        <tr><td class="kv-label">Membership No.</td><td class="kv-value">{{ $user->activeMembership?->membership_number ?? 'N/A' }}</td></tr>
                        <tr><td class="kv-label">Membership Type</td><td class="kv-value">{{ $user->activeMembership?->type?->name ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </td>
            <td class="half">
                <div class="card">
                    <div class="card-title">Period &amp; Summary</div>
                    <table class="kv-table">
                        <tr><td class="kv-label">From</td><td class="kv-value">{{ $from->format('d F Y') }}</td></tr>
                        <tr><td class="kv-label">To</td><td class="kv-value">{{ $to->format('d F Y') }}</td></tr>
                        <tr><td class="kv-label">Verified Activities</td><td class="kv-value">{{ number_format($totalActivities) }}</td></tr>
                        <tr><td class="kv-label">Total Rounds</td><td class="kv-value">{{ number_format($totalRounds) }}</td></tr>
                        @if ($sportCount > 0 || $huntingCount > 0)
                            <tr><td class="kv-label">Breakdown</td><td class="kv-value">
                                @if ($sportCount > 0){{ $sportCount }} Sport @endif
                                @if ($sportCount > 0 && $huntingCount > 0) / @endif
                                @if ($huntingCount > 0){{ $huntingCount }} Hunting @endif
                            </td></tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- Letter body --}}
    <div class="letter-body">
        To Whom It May Concern (SAPS Designated Firearms Officer),
        <br/><br/>
        This document is issued by the National Rifle &amp; Pistol Association of South Africa (NRAPA), a SAPS-accredited
        association under the Firearms Control Act (Act 60 of 2000), holding accreditation FAR Sport Shooting <strong>1300122</strong>
        and FAR Hunting <strong>1300127</strong>. It serves as a record of the verified shooting activities of
        <strong>{{ $user->getIdName() }}</strong> between <strong>{{ $from->format('d F Y') }}</strong> and
        <strong>{{ $to->format('d F Y') }}</strong>, and is intended to accompany a firearm licence application or
        renewal motivation submitted to SAPS. Only activities verified and approved by NRAPA administration are listed below.
    </div>

    {{-- Activities table --}}
    @if ($activities->count() > 0)
        <table class="kv-table" style="width:100%; border-collapse:collapse; margin-top:8px;">
            <thead>
                <tr style="background:#0B4EA2; color:#ffffff;">
                    <th style="padding:6px 8px; text-align:left; font-size:9px; font-weight:600;">Date</th>
                    <th style="padding:6px 8px; text-align:left; font-size:9px; font-weight:600;">Activity / Discipline</th>
                    <th style="padding:6px 8px; text-align:left; font-size:9px; font-weight:600;">Location</th>
                    <th style="padding:6px 8px; text-align:left; font-size:9px; font-weight:600;">Firearm / Calibre</th>
                    <th style="padding:6px 8px; text-align:right; font-size:9px; font-weight:600;">Rounds</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($activities as $activity)
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:5px 8px; font-size:9px; vertical-align:top; white-space:nowrap;">
                            {{ $activity->activity_date?->format('d M Y') ?? 'N/A' }}
                        </td>
                        <td style="padding:5px 8px; font-size:9px; vertical-align:top;">
                            {{ $activity->activityType?->name ?? 'Activity' }}
                            @if ($activity->track)
                                <br/><span style="font-size:8px; color:#6a6a6a;">{{ ucfirst($activity->track) }}</span>
                            @endif
                        </td>
                        <td style="padding:5px 8px; font-size:9px; vertical-align:top;">
                            {{ $activity->location ?: ($activity->closest_town_city ?: 'N/A') }}
                            @if ($activity->province_name || $activity->country_name)
                                <br/><span style="font-size:8px; color:#6a6a6a;">
                                    {{ collect([$activity->province_name, $activity->country_name])->filter()->implode(', ') }}
                                </span>
                            @endif
                        </td>
                        <td style="padding:5px 8px; font-size:9px; vertical-align:top;">
                            {{ $activity->firearmType?->name ?? '—' }}
                            @if ($activity->userFirearm?->firearmCalibre?->name)
                                <br/><span style="font-size:8px; color:#6a6a6a;">{{ $activity->userFirearm->firearmCalibre->name }}</span>
                            @endif
                        </td>
                        <td style="padding:5px 8px; font-size:9px; vertical-align:top; text-align:right; white-space:nowrap;">
                            {{ number_format((int) ($activity->rounds_fired ?? 0)) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f3f4f6; font-weight:700;">
                    <td colspan="4" style="padding:6px 8px; font-size:9px; text-align:right;">Total verified activities: {{ number_format($totalActivities) }}</td>
                    <td style="padding:6px 8px; font-size:9px; text-align:right;">{{ number_format($totalRounds) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="letter-body" style="font-style:italic; color:#6a6a6a; margin-top:8px;">
            No verified shooting activities are on record for this member within the selected period.
        </div>
    @endif

    {{-- Signatory + notes --}}
    <table class="layout-table">
        <tr>
            <td class="half">
                <div class="card signatory-card">
                    <div class="card-title">Issued By</div>
                    <div class="sig-name">NRAPA Administration</div>
                    <div class="sig-title">National Rifle &amp; Pistol Association of South Africa</div>
                    <div class="sig-date">Generated {{ $issueDate->format('d F Y') }}</div>
                </div>
            </td>
            <td class="half">
                <div class="card">
                    <div class="card-title">About This Document</div>
                    <div style="font-size:9px; line-height:1.5; padding:4px 0;">
                        This document was generated automatically by the NRAPA member portal on
                        <strong>{{ $issueDate->format('d F Y') }}</strong> from records held by NRAPA. For an officially
                        signed copy on NRAPA letterhead, please contact administration via the member portal.
                    </div>
                </div>
            </td>
        </tr>
    </table>
@endsection

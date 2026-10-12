@php
    use App\Enums\ApprovalType;
    $label = $approval->type->label();
    $member = $approval->user;
    $item = $approval->approvable;
    $items = (array) $approval->metaValue('changes_requested', []);
    $reference = $approval->type === ApprovalType::Payment ? $item?->memberReference() : null;
@endphp
@extends('emails.layout')

@section('title', 'Reminder: your '.$label.' — NRAPA')
@section('heading', 'Still waiting on you')
@section('subtitle', $item?->summaryLine() ?? '')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $member?->name ?? 'member' }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        @if($approval->type === ApprovalType::Payment)
            We have not yet received proof of payment for your membership. Please upload it from your NRAPA account@if($reference) and use the reference <strong>{{ $reference }}</strong>@endif.
        @elseif($items !== [])
            Your <strong>{{ $label }}</strong> is on hold until the following is sorted out:
        @else
            Your <strong>{{ $label }}</strong> is waiting for something from you before we can review it.
        @endif
    </p>

    @if($items !== [])
        <div style="background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
            <ul class="tx" style="margin: 0; padding-left: 18px; color: #111827; font-size: 14px; line-height: 1.6;">
                @foreach($items as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ config('app.url') }}/dashboard" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                    <span style="color: #ffffff;">Open your NRAPA account</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; margin: 0 0 6px 0; font-size: 13px; text-align: center;">
        Already sorted? You can ignore this email. Need help? Contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Kind regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This reminder was sent to {{ $member?->email }} because your NRAPA {{ $label }} has been waiting for {{ $stage }} days.
@endsection

@php
    use App\Enums\ApprovalDecision;
    $label = $approval->type->label();
    $member = $approval->user;
@endphp
@extends('emails.layout')

@section('title', 'Update on your '.$label.' — NRAPA')
@section('heading', match ($decision) {
    ApprovalDecision::Approve => ucfirst($label).' approved',
    ApprovalDecision::Decline => ucfirst($label).' not approved',
    ApprovalDecision::RequestChanges => 'Changes needed',
})
@section('subtitle', $approval->approvable?->summaryLine() ?? '')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $member?->name ?? 'member' }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        @switch($decision)
            @case(ApprovalDecision::Approve)
                Your <strong>{{ $label }}</strong> has been reviewed and approved.
                @break
            @case(ApprovalDecision::Decline)
                Your <strong>{{ $label }}</strong> has been reviewed and could not be approved.
                @break
            @default
                Your <strong>{{ $label }}</strong> has been reviewed and needs a few changes before it can be approved.
        @endswitch
    </p>

    @if($items !== [] || $note)
        <div style="background-color: {{ $decision === ApprovalDecision::Approve ? '#ecfdf5' : '#fef2f2' }}; border: 1px solid {{ $decision === ApprovalDecision::Approve ? '#6ee7b7' : '#fca5a5' }}; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
            @if($items !== [])
                <ul class="tx" style="margin: 0 0 {{ $note ? '12px' : '0' }} 0; padding-left: 18px; color: #111827; font-size: 14px; line-height: 1.6;">
                    @foreach($items as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
            @if($note)
                <p class="tx" style="color: #374151; margin: 0; font-size: 14px; line-height: 1.6;">{{ $note }}</p>
            @endif
        </div>
    @endif

    @if($decision !== ApprovalDecision::Approve)
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
    @endif

    <p class="tx" style="color: #6b7280; margin: 0 0 6px 0; font-size: 13px; text-align: center;">
        Need help? Contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Kind regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This email was sent to {{ $member?->email }} regarding your NRAPA {{ $label }}.
@endsection

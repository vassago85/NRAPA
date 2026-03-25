@extends('emails.layout')

@section('title', 'Document Details Updated — NRAPA')
@section('heading', 'Document Details Updated')
@section('subtitle', 'An administrator has corrected details on your document')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $document->user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        An administrator has reviewed your <strong>{{ $document->documentType?->name ?? 'document' }}</strong> and made the following corrections to ensure your details are accurate:
    </p>

    {{-- Changes table --}}
    <div style="background-color: #eff6ff; border: 1px solid #93c5fd; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;" colspan="3">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #1e40af;">Changes Made</span>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #bfdbfe;">
                <td class="tx" style="padding: 8px 8px 8px 0; color: #1e40af; font-size: 13px; font-weight: 700;">Field</td>
                <td class="tx" style="padding: 8px 8px; color: #1e40af; font-size: 13px; font-weight: 700;">Was</td>
                <td class="tx" style="padding: 8px 0 8px 8px; color: #1e40af; font-size: 13px; font-weight: 700;">Now</td>
            </tr>
            @foreach($changes as $change)
            <tr style="border-bottom: 1px solid #dbeafe;">
                <td class="tx" style="padding: 8px 8px 8px 0; color: #1e3a8a; font-size: 14px; font-weight: 600;">{{ $change['label'] }}</td>
                <td class="tx" style="padding: 8px 8px; color: #991b1b; font-size: 14px; text-decoration: line-through;">{{ $change['old'] ?: '—' }}</td>
                <td class="tx" style="padding: 8px 0 8px 8px; color: #166534; font-size: 14px; font-weight: 700;">{{ $change['new'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        If you believe any of these changes are incorrect, please contact us so we can resolve it.
    </p>

    {{-- CTA --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ config('app.url') }}/member/documents" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                    <span style="color: #ffffff;">View My Documents</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; margin: 0 0 6px 0; font-size: 13px; text-align: center;">
        Need help? Contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Kind regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This email was sent to {{ $document->user->email }} regarding corrections to your NRAPA document.
@endsection

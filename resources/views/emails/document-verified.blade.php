@extends('emails.layout')

@section('title', 'NRAPA Document Approved')
@section('heading', 'Document Approved')
@section('subtitle', 'Your document has been verified')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">Dear {{ $document->user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0;">We're pleased to let you know that your document has been reviewed and approved.</p>

    {{-- Document details --}}
    <div class="bx-success" style="background-color: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #166534; margin: 0 0 12px 0; font-size: 16px;">Document Details</h3>
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="tx" style="padding: 6px 0; color: #166534; font-size: 14px;">Document Type:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #166534; font-size: 14px;">{{ $document->documentType?->name ?? 'Document' }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 6px 0; color: #166534; font-size: 14px;">Status:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #166534; font-size: 14px;">Verified</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 6px 0; color: #166534; font-size: 14px;">Verified On:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #166534; font-size: 14px;">{{ now()->format('d F Y') }}</td>
            </tr>
            @if($document->expires_at)
            <tr>
                <td class="tx" style="padding: 6px 0; color: #166534; font-size: 14px;">Valid Until:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #166534; font-size: 14px;">{{ $document->expires_at->format('d F Y') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">No further action is required from you regarding this document.</p>

    <p class="tx" style="color: #374151; margin: 0;">Kind regards,<br><strong>NRAPA Team</strong></p>
@endsection

@section('footer')
    This email was sent to {{ $document->user->email }} regarding your NRAPA document submission.
@endsection

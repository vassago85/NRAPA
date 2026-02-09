@extends('emails.layout')

@section('title', 'NRAPA Document Requires Attention')
@section('heading', 'Document Requires Attention')
@section('subtitle', 'Your document could not be verified')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">Dear {{ $document->user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0;">Unfortunately, your <strong>{{ $document->documentType?->name ?? 'document' }}</strong> could not be verified. Please see the reason below and upload a corrected document.</p>

    {{-- Rejection reason --}}
    <div class="bx-danger" style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #991b1b; margin: 0 0 8px 0; font-size: 16px;">Reason for Rejection</h3>
        <p style="color: #991b1b; margin: 0;">{{ $reason }}</p>
    </div>

    {{-- Next steps --}}
    <div class="bx-info" style="background-color: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #1e40af; margin: 0 0 8px 0; font-size: 16px;">What To Do Next</h3>
        <ol style="color: #1e40af; margin: 0; padding-left: 20px;">
            <li style="margin-bottom: 6px;">Review the reason above</li>
            <li style="margin-bottom: 6px;">Prepare a corrected document that addresses the issue</li>
            <li>Log in to your NRAPA account and upload the new document</li>
        </ol>
    </div>

    {{-- CTA button --}}
    <div style="text-align: center; margin: 0 0 20px 0;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto;" class="btn-primary">
            <tr>
                <td bgcolor="#0B4EA2" style="border-radius: 8px; text-align: center;">
                    <a href="{{ config('app.url') }}/member/documents" target="_blank"
                       style="display: inline-block; padding: 12px 30px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff !important; text-decoration: none; font-weight: bold; font-family: Arial, sans-serif; font-size: 15px;">
                        Upload New Document
                    </a>
                </td>
            </tr>
        </table>
    </div>

    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">If you have any questions, please don't hesitate to contact us.</p>

    <p class="tx" style="color: #374151; margin: 0;">Kind regards,<br><strong>NRAPA Team</strong></p>
@endsection

@section('footer')
    This email was sent to {{ $document->user->email }} regarding your NRAPA document submission.
@endsection

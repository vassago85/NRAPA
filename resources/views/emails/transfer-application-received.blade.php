@extends('emails.layout')

@section('title', 'NRAPA Transfer Application Received')
@section('heading', 'Transfer application received')
@section('subtitle', 'Thanks for choosing to move your dedicated status to NRAPA.')

@section('content')
    <p>Hi {{ $membership->user->name }},</p>

    <p>
        We have received your transfer application for the <strong>{{ $membership->type?->name ?? 'NRAPA Transfer Membership' }}</strong>
        @if($membership->previous_association_name)
            from <strong>{{ $membership->previous_association_name }}</strong>
        @endif
        and your supporting documents have been queued for admin review.
    </p>

    <table role="presentation" class="bx-info" style="width: 100%; border-radius: 8px; padding: 16px; margin: 16px 0; border: 1px solid #6366f1; background-color: #eef2ff;">
        <tr>
            <td>
                <h3 style="margin: 0 0 8px 0; font-size: 16px;">What happens next</h3>
                <ol style="margin: 0; padding-left: 20px;">
                    <li>An admin will verify your competency certificate and current membership certificate.</li>
                    <li>Once approved you will receive a welcome email and your transfer fee invoice (<x-money :amount="$membership->type?->initial_price ?? 550" />).</li>
                    <li>If anything is unclear we will email you with what to fix and you can re-upload.</li>
                </ol>
            </td>
        </tr>
    </table>

    <p>
        You can sign in to your dashboard at any time to track the status of your application.
    </p>

    <p>
        <a href="{{ config('app.url') }}/dashboard" style="display: inline-block; padding: 10px 16px; background-color: #1a5cb8; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">View Application</a>
    </p>

    <p style="font-size: 13px; color: #6b7280;">
        Your documents are stored privately and only NRAPA admin reviewers can access them.
    </p>
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>NRAPA Message</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f4f5; margin: 0; padding: 24px; color: #18181b; }
    .wrap { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
    .header { background: #0B4EA2; color: #ffffff; padding: 20px 24px; }
    .header h1 { margin: 0; font-size: 18px; font-weight: 600; }
    .body { padding: 24px; line-height: 1.55; }
    .body h2 { font-size: 16px; margin: 0 0 16px; color: #0B4EA2; }
    .body .message { white-space: pre-wrap; background: #fafafa; border-left: 3px solid #0B4EA2; padding: 16px; border-radius: 6px; }
    .meta { font-size: 12px; color: #71717a; margin-top: 20px; }
    .btn { display: inline-block; margin-top: 20px; background: #0B4EA2; color: #ffffff !important; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; font-size: 14px; }
    .footer { font-size: 11px; color: #a1a1aa; padding: 16px 24px; background: #fafafa; text-align: center; }
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1>NRAPA — Member Message</h1>
    </div>
    <div class="body">
        <p>Hi {{ $user->name }},</p>
        <h2>{{ $memberMessage->subject }}</h2>
        <div class="message">{{ $memberMessage->body }}</div>
        @if($sender)
            <p class="meta">From: {{ $sender->name }} ({{ $sender->role === 'admin' || $sender->role === 'owner' || $sender->role === 'developer' ? 'NRAPA Admin' : $sender->name }})</p>
        @endif
        <a href="{{ $inboxUrl }}" class="btn">View in your Inbox</a>
        <p class="meta">You can reply to this message by contacting NRAPA directly &mdash; replies to this email address are not monitored.</p>
    </div>
    <div class="footer">
        National Rifle Association of South Africa &middot; nrapa.ranyati.co.za
    </div>
</div>
</body>
</html>

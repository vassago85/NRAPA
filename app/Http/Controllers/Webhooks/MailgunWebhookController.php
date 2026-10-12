<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMailgunEvent;
use App\Models\EmailLog;
use App\Models\MailgunEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MailgunWebhookController extends Controller
{
    public const MAX_AGE_SECONDS = 15 * 60;

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->hasValidSignature($request)) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $data = (array) $request->input('event-data', []);
        $eventId = (string) ($data['id'] ?? '');
        $event = (string) ($data['event'] ?? '');

        if ($eventId === '' || $event === '') {
            return response()->json(['message' => 'Missing event data.'], 422);
        }

        try {
            $record = MailgunEvent::create([
                'event_id' => $eventId,
                'event' => $event,
                'provider_message_id' => EmailLog::normalizeMessageId(data_get($data, 'message.headers.message-id')),
                'recipient' => isset($data['recipient']) ? strtolower((string) $data['recipient']) : null,
                'occurred_at' => isset($data['timestamp']) ? Carbon::createFromTimestamp((float) $data['timestamp']) : now(),
                'payload' => $data,
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'Already received.']);
        }

        ProcessMailgunEvent::dispatch($record->id);

        return response()->json(['message' => 'Received.']);
    }

    private function hasValidSignature(Request $request): bool
    {
        $key = (string) config('services.mailgun.webhook_signing_key');
        $timestamp = (string) $request->input('signature.timestamp', '');
        $token = (string) $request->input('signature.token', '');
        $signature = (string) $request->input('signature.signature', '');

        if ($key === '' || $timestamp === '' || $token === '' || $signature === '' || ! ctype_digit($timestamp)) {
            return false;
        }

        if (abs(now()->getTimestamp() - (int) $timestamp) > self::MAX_AGE_SECONDS) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp.$token, $key), $signature);
    }
}

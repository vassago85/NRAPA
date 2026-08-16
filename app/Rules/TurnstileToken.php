<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Validate a Cloudflare Turnstile response token by calling the siteverify
 * endpoint. If no secret is configured (local, CI, or before Turnstile is
 * provisioned), the rule short-circuits to "valid" so environments without
 * bot protection still work.
 */
class TurnstileToken implements ValidationRule
{
    public function __construct(protected ?string $remoteIp = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = (string) config('services.turnstile.secret', '');

        if ($secret === '') {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Please complete the anti-bot challenge before submitting.');

            return;
        }

        try {
            $verifyUrl = (string) config(
                'services.turnstile.verify_url',
                'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            );

            $response = Http::asForm()
                ->timeout(5)
                ->post($verifyUrl, array_filter([
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => $this->remoteIp,
                ]));

            if (! $response->successful()) {
                Log::warning('[TURNSTILE] siteverify HTTP error', [
                    'status' => $response->status(),
                ]);
                $fail('Anti-bot verification is temporarily unavailable. Please try again in a moment.');

                return;
            }

            $body = $response->json();
            if (($body['success'] ?? false) === true) {
                return;
            }

            Log::info('[TURNSTILE] token rejected', [
                'error-codes' => $body['error-codes'] ?? [],
            ]);
            $fail('The anti-bot challenge failed. Please refresh the page and try again.');
        } catch (\Throwable $e) {
            Log::warning('[TURNSTILE] siteverify exception: '.$e->getMessage());
            $fail('Anti-bot verification is temporarily unavailable. Please try again in a moment.');
        }
    }
}

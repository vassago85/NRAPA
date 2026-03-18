<?php

namespace App\Helpers;

use App\Mail\TermsAcceptanceRequiredMail;
use App\Models\TermsVersion;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TermsHelper
{
    /**
     * Check if user needs to accept terms and send email if needed.
     */
    public static function checkAndNotify(User $user): void
    {
        try {
            // Check if there's an active terms version
            $activeTerms = TermsVersion::active();

            if (! $activeTerms) {
                // No active terms - nothing to accept
                return;
            }

            // Check if user has already accepted the active terms
            if ($user->hasAcceptedActiveTerms()) {
                // Already accepted - no need to send email
                return;
            }

            // User needs to accept terms - send email
            self::sendTermsAcceptanceEmail($user);
        } catch (\Exception $e) {
            // Log error but don't break the flow
            Log::error('TermsHelper::checkAndNotify failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send terms acceptance required email to user.
     */
    public static function sendTermsAcceptanceEmail(User $user): void
    {
        try {
            $activeTerms = TermsVersion::active();

            if (! $activeTerms) {
                Log::warning('TermsHelper::sendTermsAcceptanceEmail - No active terms version found', [
                    'user_id' => $user->id,
                ]);

                return;
            }

            // Check if user has already accepted
            if ($user->hasAcceptedActiveTerms()) {
                Log::info('TermsHelper::sendTermsAcceptanceEmail - User already accepted terms', [
                    'user_id' => $user->id,
                ]);

                return;
            }

            // Send the email
            Mail::to($user->email)->send(new TermsAcceptanceRequiredMail($user));

            Log::info('TermsHelper::sendTermsAcceptanceEmail - Email sent successfully', [
                'user_id' => $user->id,
                'terms_version_id' => $activeTerms->id,
            ]);
        } catch (\Exception $e) {
            Log::error('TermsHelper::sendTermsAcceptanceEmail failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Models\User;
use App\Models\Membership;

class MembershipStandingService
{
    /**
     * Determine if a member is in good standing.
     * 
     * Good standing requires:
     * - Active membership status
     * - Not expired (or lifetime)
     * - Not suspended
     * - Not revoked
     * - good_standing flag is true (if column exists)
     * - Recent payment if required (for non-lifetime memberships)
     * - Terms & Conditions accepted (if active terms exist)
     */
    public function isInGoodStanding(User $user, ?Membership $membership = null): bool
    {
        $membership = $membership ?? $user->activeMembership;
        
        if (!$membership) {
            return false;
        }

        // Check status
        if (!$membership->isActive()) {
            return false;
        }

        // Check good_standing flag (if column exists)
        if (isset($membership->good_standing) && !$membership->good_standing) {
            return false;
        }

        // Check if expired (unless lifetime)
        if ($membership->isExpired()) {
            return false;
        }

        // Check if suspended
        if ($membership->status === 'suspended') {
            return false;
        }

        // Check if revoked
        if ($membership->status === 'revoked') {
            return false;
        }

        // Check if Terms & Conditions have been accepted
        $activeTerms = \App\Models\TermsVersion::active();
        if ($activeTerms && !$user->hasAcceptedActiveTerms()) {
            return false;
        }

        // For non-lifetime memberships, check if payment is required
        if ($membership->type && method_exists($membership->type, 'requires_renewal') && $membership->type->requires_renewal) {
            // If membership has expired, they're not in good standing
            if ($membership->expires_at && $membership->expires_at->isPast()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the reason why a member is not in good standing.
     */
    public function getStandingReason(User $user, ?Membership $membership = null): ?string
    {
        $membership = $membership ?? $user->activeMembership;
        
        if (!$membership) {
            return 'No active membership found.';
        }

        if (!$membership->isActive()) {
            return "Membership status is: {$membership->status}";
        }

        if (isset($membership->good_standing) && !$membership->good_standing) {
            return 'Membership is not in good standing.';
        }

        if ($membership->isExpired()) {
            return 'Membership has expired.';
        }

        if ($membership->status === 'suspended') {
            return 'Membership is suspended.';
        }

        if ($membership->status === 'revoked') {
            return 'Membership has been revoked.';
        }

        // Check if Terms & Conditions have been accepted
        $activeTerms = \App\Models\TermsVersion::active();
        if ($activeTerms && !$user->hasAcceptedActiveTerms()) {
            return 'Pending Terms Acceptance';
        }

        return null; // In good standing
    }

    /**
     * Update good standing status based on current conditions.
     */
    public function updateGoodStanding(Membership $membership): bool
    {
        $user = $membership->user;
        $wasInGoodStanding = isset($membership->good_standing) ? $membership->good_standing : true;
        $isInGoodStanding = $this->isInGoodStanding($user, $membership);

        if (isset($membership->good_standing) && $wasInGoodStanding !== $isInGoodStanding) {
            $membership->update(['good_standing' => $isInGoodStanding]);
            
            // Log status change if MemberStatusHistory model exists
            if (class_exists(\App\Models\MemberStatusHistory::class)) {
                \App\Models\MemberStatusHistory::create([
                    'user_id' => $user->id,
                    'membership_id' => $membership->id,
                    'status' => $isInGoodStanding ? 'active' : $membership->status,
                    'previous_status' => $wasInGoodStanding ? 'active' : null,
                    'reason' => $isInGoodStanding 
                        ? 'Good standing restored' 
                        : $this->getStandingReason($user, $membership),
                    'changed_by' => auth()->id(),
                ]);
            }
        }

        return $isInGoodStanding;
    }
}

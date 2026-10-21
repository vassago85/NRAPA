<?php

declare(strict_types=1);

namespace App\Support\Members;

use App\Models\User;
use DateTimeInterface;

/**
 * Token-replacement helper for admin → member letters (bulk messages, preview,
 * approval chase, and anywhere else we want a per-member personalised text).
 *
 * Design rules (from the composer plan):
 *
 *  - Tokens are curly-brace single-word placeholders: `{first_name}`,
 *    `{membership_type}` etc. They are case-sensitive and must match exactly.
 *  - Unknown tokens are left intact — admins can type `{like_this}` in a
 *    policy quote without us silently blanking it out.
 *  - Empty values use a sensible neutral phrase so the letter never reads
 *    "Your membership is ." or "Valid until .". For `{expiry}` on a lifetime
 *    membership that neutral phrase is "does not expire".
 *  - This helper is read-only. It never touches the database or dispatches
 *    events. The caller is responsible for persisting / sending.
 */
final class MemberLetter
{
    /**
     * The list of tokens the composer UI advertises. Keep this in sync with
     * the chip buttons on the admin members page so admins only ever see
     * tokens that actually resolve.
     *
     * @var array<int, array{token: string, label: string, description: string}>
     */
    public const TOKENS = [
        ['token' => '{first_name}', 'label' => 'First name', 'description' => "The member's first name."],
        ['token' => '{full_name}', 'label' => 'Full name', 'description' => "The member's full name as it appears on their profile."],
        ['token' => '{membership_type}', 'label' => 'Membership type', 'description' => 'The name of the current membership type (e.g. "Annual Dedicated").'],
        ['token' => '{status}', 'label' => 'Status', 'description' => 'Current membership status (Active, Expired, Pending, Suspended, or No membership).'],
        ['token' => '{expiry}', 'label' => 'Expiry date', 'description' => 'Membership expiry date (e.g. "31 December 2026"), or "does not expire" for lifetime.'],
        ['token' => '{expiry_date}', 'label' => 'Expiry date (long)', 'description' => 'Alias for {expiry}.'],
        ['token' => '{membership_number}', 'label' => 'Member number', 'description' => 'The NRAPA member number (e.g. "NRAPA-00123").'],
    ];

    /**
     * Replace every known token in $template with values resolved from $user.
     * Unknown tokens are left untouched.
     */
    public static function render(string $template, User $user): string
    {
        $values = self::valuesFor($user);

        return str_replace(array_keys($values), array_values($values), $template);
    }

    /**
     * Resolve the full token -> value map for a single member. Exposed so the
     * composer preview panel can show "{first_name} = Jane" style debugging
     * output without re-implementing the lookup.
     *
     * @return array<string, string>
     */
    public static function valuesFor(User $user): array
    {
        $membership = $user->activeMembership;
        $type = $membership?->type;

        $fullName = trim((string) $user->name);
        $firstName = self::firstNameOf($fullName);

        $expires = $membership?->expires_at;
        $isLifetime = (bool) ($type?->isLifetime() ?? false);
        $expiryText = self::formatExpiry($expires, $isLifetime);

        $status = $user->membershipState()->label();
        $typeName = $type?->name ?? '';
        $memberNumber = $user->formatted_member_number;

        return [
            '{first_name}' => $firstName !== '' ? $firstName : 'Member',
            '{full_name}' => $fullName !== '' ? $fullName : 'Member',
            '{membership_type}' => $typeName,
            '{status}' => $status,
            '{expiry}' => $expiryText,
            '{expiry_date}' => $expiryText,
            '{membership_number}' => $memberNumber,
        ];
    }

    /**
     * Split "Jane Mary Smith" -> "Jane". Deliberately simple: use the first
     * whitespace-separated token. Anything more clever (handling titles,
     * suffixes, double-barrelled names) belongs on the profile, not here.
     */
    private static function firstNameOf(string $name): string
    {
        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $name) ?: [$name];

        return (string) ($parts[0] ?? '');
    }

    /**
     * Human-friendly expiry phrase. Lifetime (or any null expiry on a
     * lifetime-type membership) becomes "does not expire". A null expiry on
     * a non-lifetime type becomes an empty-but-safe "not set" so the letter
     * reads cleanly rather than exposing a bare empty-string hole.
     */
    private static function formatExpiry(?DateTimeInterface $expires, bool $isLifetime): string
    {
        if ($isLifetime) {
            return 'does not expire';
        }

        if (! $expires) {
            return 'not set';
        }

        return $expires->format('j F Y');
    }
}

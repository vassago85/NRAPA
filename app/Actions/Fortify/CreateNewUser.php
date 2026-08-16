<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Rules\TurnstileToken;
use App\Services\NtfyService;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $input['phone'] = User::normalizePhone($input['phone'] ?? '') ?? '';

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            // POPIA: registrants must actively accept T&Cs and the privacy
            // policy before we store any personal data. Fortify posts the
            // form as `terms_accepted=1` when the box is ticked.
            'terms_accepted' => ['accepted'],
            // Bot protection. See App\Rules\TurnstileToken — a blank secret
            // (local/CI) makes this a no-op so tests keep working without
            // needing a Cloudflare account.
            'cf-turnstile-response' => [new TurnstileToken(request()->ip())],
        ], [
            'terms_accepted.accepted' => 'You must accept the Terms & Conditions and Privacy Policy to create an account.',
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'password' => $input['password'],
            'password_set_at' => now(),
            'role' => User::ROLE_MEMBER,
        ]);

        try {
            app(NtfyService::class)->notifyAdmins(
                'new_member',
                'New Member Registration',
                "{$user->name} ({$user->email}) has registered as a new member.",
            );
        } catch (\Exception $e) {}

        return $user;
    }
}

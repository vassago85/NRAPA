<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository as BaseDatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class DatabaseTokenRepository extends BaseDatabaseTokenRepository
{
    /**
     * Store a reset token in one statement.
     *
     * The framework deletes the existing row, then inserts. Two overlapping
     * requests (a double-submitted forgot-password form, or an admin resend
     * racing the member) can both delete nothing and the second insert hits
     * password_reset_tokens.PRIMARY. Upsert updates that row instead.
     */
    public function create(CanResetPasswordContract $user)
    {
        $email = $user->getEmailForPasswordReset();
        $token = $this->createNewToken();

        $this->getTable()->upsert(
            [$this->getPayload($email, $token)],
            ['email'],
            ['token', 'created_at'],
        );

        return $token;
    }
}

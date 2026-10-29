<?php

use App\Mail\SetPasswordLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('reset password link can be requested', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    // NRAPA overrides sendPasswordResetNotification to send the SetPasswordLink
    // mailable instead of the default ResetPassword notification.
    Mail::assertSent(SetPasswordLink::class, fn ($mail) => $mail->hasTo($user->email));
});

test('reset password screen can be rendered', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Mail::assertSent(SetPasswordLink::class, function ($mail) use ($user) {
        $response = $this->get(route('password.reset', ['token' => $mail->token, 'email' => $user->email]));

        $response->assertOk();

        return $mail->hasTo($user->email);
    });
});

test('password can be reset with valid token', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Mail::assertSent(SetPasswordLink::class, function ($mail) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $mail->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        return $mail->hasTo($user->email);
    });
});

test('an existing reset token is replaced with one atomic write', function () {
    $user = User::factory()->create();

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make('stale-token'),
        'created_at' => now()->subHour(),
    ]);

    $writes = [];
    DB::listen(function ($query) use (&$writes) {
        $sql = strtolower($query->sql);

        if (! str_contains($sql, 'password_reset_tokens')) {
            return;
        }

        if (str_starts_with(ltrim($sql), 'insert') || str_starts_with(ltrim($sql), 'delete')) {
            $writes[] = $sql;
        }
    });

    $token = Password::broker()->createToken($user);

    // Delete-then-insert races: two requests can both delete nothing and the
    // second insert hits password_reset_tokens.PRIMARY. One upsert cannot.
    expect($writes)->toHaveCount(1)
        ->and($writes[0])->toContain(DB::getDriverName() === 'sqlite' ? 'on conflict' : 'on duplicate key')
        ->and(DB::table('password_reset_tokens')->where('email', $user->email)->count())->toBe(1)
        ->and(Password::broker()->tokenExists($user, $token))->toBeTrue()
        ->and(Password::broker()->tokenExists($user, 'stale-token'))->toBeFalse();
});

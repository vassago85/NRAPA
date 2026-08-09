<?php

use App\Mail\SetPasswordLink;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

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

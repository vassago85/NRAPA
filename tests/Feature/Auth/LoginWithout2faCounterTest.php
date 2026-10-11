<?php

use App\Models\User;

test('admins without 2fa increment the login counter', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'logins_without_2fa' => 0,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->logins_without_2fa)->toBe(1);
});

test('the login counter stops at the 2fa limit so the tinyint column cannot overflow', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'logins_without_2fa' => 255,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticated();
    expect($user->fresh()->logins_without_2fa)->toBe(255);

    $user->incrementLoginWithout2FA();

    expect($user->fresh()->logins_without_2fa)->toBe(255);
});

test('admins who already reached the 2fa login limit are not incremented further', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'logins_without_2fa' => User::MAX_LOGINS_WITHOUT_2FA,
    ]);

    $user->incrementLoginWithout2FA();

    expect($user->fresh()->logins_without_2fa)->toBe(User::MAX_LOGINS_WITHOUT_2FA);
});

test('admins with 2fa enabled do not increment the login counter', function () {
    $user = User::factory()->withTwoFactor()->create([
        'role' => User::ROLE_ADMIN,
        'logins_without_2fa' => 3,
    ]);

    $user->incrementLoginWithout2FA();

    expect($user->fresh()->logins_without_2fa)->toBe(3);
});

test('members do not increment the login counter', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'logins_without_2fa' => 0,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->logins_without_2fa)->toBe(0);
});

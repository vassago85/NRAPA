<?php

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'phone' => '0821234567',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms_accepted' => '1',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('registration is rejected without accepting terms', function () {
    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '0821234568',
        'password' => 'password',
        'password_confirmation' => 'password',
        // terms_accepted intentionally omitted
    ]);

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors('terms_accepted');
    $this->assertGuest();
    expect(\App\Models\User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

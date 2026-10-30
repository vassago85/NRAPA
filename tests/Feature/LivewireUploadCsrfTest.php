<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

it('skips csrf on the livewire 4 file upload path because the signed url is the authenticator', function () {
    $middleware = app(ValidateCsrfToken::class);
    $matches = (new ReflectionMethod($middleware, 'inExceptArray'))
        ->invoke($middleware, Request::create(EndpointResolver::uploadPath(), 'POST'));

    expect($matches)->toBeTrue();
});

it('still requires csrf on an ordinary member post', function () {
    $middleware = app(ValidateCsrfToken::class);
    $matches = (new ReflectionMethod($middleware, 'inExceptArray'))
        ->invoke($middleware, Request::create('/member/documents', 'POST'));

    expect($matches)->toBeFalse();
});

<?php

use Illuminate\Support\Facades\Log;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

it('answers a missing component javascript module without an exception', function () {
    Log::spy();

    $url = str_replace('{component}', 'auth--login', EndpointResolver::componentJsPath());

    $response = $this->get($url);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('javascript')
        ->and($response->getContent())->toContain('export function run');

    Log::shouldNotHaveReceived('log');
});

it('answers a missing component stylesheet without an exception', function () {
    Log::spy();

    $url = str_replace('{component}', 'auth--login', EndpointResolver::componentCssPath());

    $this->get($url)->assertOk();

    Log::shouldNotHaveReceived('log');
});

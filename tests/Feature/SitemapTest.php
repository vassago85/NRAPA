<?php

use function Pest\Laravel\get;

it('serves sitemap xml with lastmod and public routes', function () {
    $response = get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/xml');

    $content = $response->getContent();
    expect($content)->toContain('http://www.sitemaps.org/schemas/sitemap/0.9')
        ->and($content)->toMatch('/<loc>[^<]+<\/loc>/')
        ->and($content)->toContain('<lastmod>')
        ->and($content)->toContain(route('info.about'))
        ->and($content)->toContain(route('privacy-policy'));
});

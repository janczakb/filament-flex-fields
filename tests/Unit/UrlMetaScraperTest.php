<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\UrlMeta\UrlMetaScraper;

it('parses open graph metadata from the head section only', function () {
    $html = <<<'HTML'
    <!doctype html>
    <html>
    <head>
        <title>Fallback Title</title>
        <meta property="og:title" content="OG Title">
        <meta property="og:description" content="OG Description">
        <meta property="og:image" content="/images/preview.jpg">
    </head>
    <body><p>Large body content that should never be parsed.</p></body>
    </html>
    HTML;

    $meta = app(UrlMetaScraper::class)->parseFromHtml($html, 'https://example.com/blog/post');

    expect($meta)->toMatchArray([
        'title' => 'OG Title',
        'description' => 'OG Description',
        'image' => 'https://example.com/images/preview.jpg',
    ]);
});

it('falls back to twitter and standard meta tags when open graph is missing', function () {
    $html = <<<'HTML'
    <head>
        <meta name="twitter:title" content="Twitter Title">
        <meta name="twitter:description" content="Twitter Description">
        <meta name="twitter:image" content="https://cdn.example.com/card.png">
    </head>
    HTML;

    $meta = app(UrlMetaScraper::class)->parseFromHtml($html, 'https://example.com');

    expect($meta)->toMatchArray([
        'title' => 'Twitter Title',
        'description' => 'Twitter Description',
        'image' => 'https://cdn.example.com/card.png',
    ]);
});

it('returns title only when no other metadata is present', function () {
    $html = '<head><title>Only Title</title></head>';

    $meta = app(UrlMetaScraper::class)->parseFromHtml($html, 'https://example.com');

    expect($meta)->toBe([
        'title' => 'Only Title',
    ]);
});

it('rejects data uri images from open graph metadata', function () {
    $html = <<<'HTML'
    <head>
        <meta property="og:image" content="data:image/png;base64,iVBORw0KGgo=">
    </head>
    HTML;

    $meta = app(UrlMetaScraper::class)->parseFromHtml($html, 'https://example.com');

    expect($meta)->toBe([]);
});

it('blocks localhost and loopback scrape targets', function (string $url) {
    expect(app(UrlMetaScraper::class)->isScrapableUrl($url))->toBeFalse();
})->with([
    'localhost' => ['http://localhost/page'],
    'localhost subdomain' => ['http://app.localhost/page'],
    '127.0.0.1' => ['http://127.0.0.1/page'],
    'ipv6 loopback' => ['http://[::1]/page'],
]);

it('blocks private, link-local, and metadata hostnames', function (string $url) {
    expect(app(UrlMetaScraper::class)->isScrapableUrl($url))->toBeFalse();
})->with([
    '10.x' => ['http://10.0.0.1/internal'],
    '172.16.x' => ['http://172.16.0.1/internal'],
    '192.168.x' => ['http://192.168.1.1/internal'],
    '169.254.x' => ['http://169.254.169.254/latest/meta-data/'],
    'metadata.google.internal' => ['http://metadata.google.internal/computeMetadata/v1/'],
    'metadata.goog' => ['http://metadata.goog/computeMetadata/v1/'],
    'local domain' => ['http://app.local/admin'],
    'internal domain' => ['http://service.internal/admin'],
]);

it('blocks non-http schemes and malformed urls', function (string $url) {
    expect(app(UrlMetaScraper::class)->isScrapableUrl($url))->toBeFalse();
})->with([
    'ftp' => ['ftp://example.com/file'],
    'file' => ['file:///etc/passwd'],
    'data' => ['data:text/html,hello'],
    'not-a-url' => ['not-a-url'],
]);

it('allows public domains when dns resolves to public ips', function () {
    if (empty(@gethostbynamel('example.com'))) {
        $this->markTestSkipped('example.com did not resolve in this environment.');
    }

    expect(app(UrlMetaScraper::class)->isScrapableUrl('https://example.com/page'))->toBeTrue();
});

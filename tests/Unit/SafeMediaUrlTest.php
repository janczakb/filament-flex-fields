<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Security\SafeMediaUrl;

it('allows https and relative media urls', function () {
    expect(SafeMediaUrl::isAllowed('https://example.com/video.mp4'))->toBeTrue()
        ->and(SafeMediaUrl::sanitize('https://example.com/video.mp4'))->toBe('https://example.com/video.mp4')
        ->and(SafeMediaUrl::isAllowed('/storage/media/clip.mp4'))->toBeTrue()
        ->and(SafeMediaUrl::sanitize('/media/audio.mp3'))->toBe('/media/audio.mp3');
});

it('allows youtube and vimeo embed urls', function () {
    expect(SafeMediaUrl::isAllowed('https://www.youtube.com/watch?v=dQw4w9WgXcQ'))->toBeTrue()
        ->and(SafeMediaUrl::isAllowed('https://youtu.be/dQw4w9WgXcQ'))->toBeTrue()
        ->and(SafeMediaUrl::isAllowed('https://player.vimeo.com/video/12345'))->toBeTrue();
});

it('blocks dangerous and empty media urls', function () {
    expect(SafeMediaUrl::isAllowed('javascript:alert(1)'))->toBeFalse()
        ->and(SafeMediaUrl::isAllowed('data:text/html,hello'))->toBeFalse()
        ->and(SafeMediaUrl::isAllowed('vbscript:msgbox(1)'))->toBeFalse()
        ->and(SafeMediaUrl::isAllowed('file:///etc/passwd'))->toBeFalse()
        ->and(SafeMediaUrl::isAllowed('   '))->toBeFalse()
        ->and(SafeMediaUrl::sanitize(null))->toBeNull();
});

it('blocks http urls unless configured', function () {
    config(['filament-flex-fields.security.allow_http_media' => false]);

    expect(SafeMediaUrl::isAllowed('http://example.com/video.mp4'))->toBeFalse();

    config(['filament-flex-fields.security.allow_http_media' => true]);

    expect(SafeMediaUrl::isAllowed('http://example.com/video.mp4'))->toBeTrue();
});

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\ProgressColor;

it('normalizes semantic and hex progress colors', function () {
    expect(ProgressColor::normalize('primary'))->toBe('primary')
        ->and(ProgressColor::normalize('#EF4444'))->toBe('#ef4444')
        ->and(ProgressColor::normalize('rgb(34 197 94)'))->toBe('rgb(34 197 94)');
});

it('parses rgb channels from hex and rgb strings', function () {
    expect(ProgressColor::parseRgbChannels('#ef4444'))->toBe([239, 68, 68])
        ->and(ProgressColor::parseRgbChannels('rgb(34 197 94)'))->toBe([34, 197, 94]);
});

it('rejects unsupported progress color formats', function () {
    ProgressColor::normalize('neon');
})->throws(InvalidArgumentException::class);

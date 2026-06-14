<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\AudioWaveformGenerator;

it('generates deterministic waveform fingerprints from audio source', function () {
    $first = AudioWaveformGenerator::fromFingerprint('https://example.com/track-a.mp3');
    $second = AudioWaveformGenerator::fromFingerprint('https://example.com/track-a.mp3');
    $other = AudioWaveformGenerator::fromFingerprint('https://example.com/track-b.mp3');

    expect($first)->toBe($second)
        ->and($first)->not->toBe($other)
        ->and($first)->toHaveCount(AudioWaveformGenerator::SAMPLE_COUNT);

    foreach ($first as $peak) {
        expect($peak)->toBeGreaterThanOrEqual(8)->toBeLessThanOrEqual(100);
    }
});

it('returns placeholder waveform for empty fingerprint', function () {
    expect(AudioWaveformGenerator::placeholderWaveform(8))
        ->toBe([12, 12, 12, 12, 12, 12, 12, 12]);
});

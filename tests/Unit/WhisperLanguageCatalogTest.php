<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\WhisperLanguageCatalog;

it('exposes whisper-web aligned iso language catalog', function () {
    $languages = WhisperLanguageCatalog::options();

    expect($languages)->not->toBeEmpty()
        ->and($languages[0])->toBe(['code' => null, 'label' => 'Auto detect'])
        ->and(collect($languages)->firstWhere('code', 'pl'))->toBe(['code' => 'pl', 'label' => 'Polish'])
        ->and(collect($languages)->firstWhere('code', 'en'))->toBe(['code' => 'en', 'label' => 'English'])
        ->and(count($languages))->toBeGreaterThan(90);
});

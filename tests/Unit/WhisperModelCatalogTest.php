<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\WhisperModelCatalog;

it('exposes whisper-web aligned model catalog', function () {
    $models = WhisperModelCatalog::models();

    expect($models)->toHaveCount(6)
        ->and(collect($models)->firstWhere('id', 'Xenova/whisper-tiny'))->toBe([
            'id' => 'Xenova/whisper-tiny',
            'multilingual' => true,
            'distil' => false,
            'sizes' => [41, 152],
        ])
        ->and(collect($models)->firstWhere('id', 'distil-whisper/distil-medium.en'))->not->toBeNull();
});

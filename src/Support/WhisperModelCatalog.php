<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

/**
 * Whisper model catalog aligned with Xenova/whisper-web.
 *
 * @see https://github.com/xenova/whisper-web/blob/main/src/components/AudioManager.tsx
 */
final class WhisperModelCatalog
{
    /**
     * @return list<array{
     *     id: string,
     *     multilingual: bool,
     *     distil: bool,
     *     sizes: list<int>
     * }>
     */
    public static function models(): array
    {
        return [
            [
                'id' => 'Xenova/whisper-tiny',
                'multilingual' => true,
                'distil' => false,
                'sizes' => [41, 152],
            ],
            [
                'id' => 'Xenova/whisper-base',
                'multilingual' => true,
                'distil' => false,
                'sizes' => [77, 291],
            ],
            [
                'id' => 'Xenova/whisper-small',
                'multilingual' => true,
                'distil' => false,
                'sizes' => [249],
            ],
            [
                'id' => 'Xenova/whisper-medium',
                'multilingual' => true,
                'distil' => false,
                'sizes' => [776],
            ],
            [
                'id' => 'distil-whisper/distil-medium.en',
                'multilingual' => false,
                'distil' => true,
                'sizes' => [402],
            ],
            [
                'id' => 'distil-whisper/distil-large-v2',
                'multilingual' => false,
                'distil' => true,
                'sizes' => [767],
            ],
        ];
    }
}

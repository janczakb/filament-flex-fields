<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\TipTapExtensions;

use Filament\Forms\Components\RichEditor\TipTapExtensions\ImageExtension;

class FlexRichContentImageExtension extends ImageExtension
{
    /**
     * @return array<string, array<mixed>>
     */
    public function addAttributes(): array
    {
        return [
            ...parent::addAttributes(),
            'srcset' => [
                'renderHTML' => fn ($attributes): array => filled($attributes->srcset ?? null)
                    ? ['srcset' => $attributes->srcset]
                    : [],
            ],
            'sizes' => [
                'renderHTML' => fn ($attributes): array => filled($attributes->sizes ?? null)
                    ? ['sizes' => $attributes->sizes]
                    : [],
            ],
            'decoding' => [
                'renderHTML' => fn ($attributes): array => filled($attributes->decoding ?? null)
                    ? ['decoding' => $attributes->decoding]
                    : [],
            ],
        ];
    }
}

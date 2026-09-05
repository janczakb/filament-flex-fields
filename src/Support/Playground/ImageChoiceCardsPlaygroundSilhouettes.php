<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

/**
 * Body-type demo images for ImageChoiceCards playground.
 *
 * Served as published static WebP assets (not inline base64) so the playground
 * HTML stays small and mobile scroll/decode stays smooth.
 */
final class ImageChoiceCardsPlaygroundSilhouettes
{
    /**
     * @return array{slim: string, average: string, athletic: string, shredded: string}
     */
    public static function dataUris(): array
    {
        return self::urls();
    }

    /**
     * @return array{slim: string, average: string, athletic: string, shredded: string}
     */
    public static function urls(): array
    {
        return [
            'slim' => FlexFieldAssets::assetUrl('playground/image-choice-silhouettes/slim.webp'),
            'average' => FlexFieldAssets::assetUrl('playground/image-choice-silhouettes/average.webp'),
            'athletic' => FlexFieldAssets::assetUrl('playground/image-choice-silhouettes/athletic.webp'),
            'shredded' => FlexFieldAssets::assetUrl('playground/image-choice-silhouettes/shredded.webp'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\RichEditor\Concerns;

use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorImageVariant;

/**
 * Optional Spatie Media Library integration for FlexRichEditor image variants.
 *
 * Register the same variant names on the model that you pass to FlexRichEditor::imageVariants().
 */
trait RegistersFlexRichEditorMediaConversions
{
    /**
     * @param  array<string, array<string, mixed>|RichEditorImageVariant>  $variants
     */
    protected function registerFlexRichEditorMediaConversions(array $variants): void
    {
        if (! method_exists($this, 'addMediaConversion')) {
            return;
        }

        $fitEnum = enum_exists('Spatie\\Image\\Enums\\Fit')
            ? 'Spatie\\Image\\Enums\\Fit'
            : null;

        foreach (RichEditorImageVariant::normalizeCollection($variants) as $variant) {
            if ($variant->master) {
                continue;
            }

            $conversion = $this->addMediaConversion($variant->name);

            if ($variant->webp && method_exists($conversion, 'format')) {
                $conversion->format('webp');
            }

            if ($variant->maxLongEdge && $fitEnum !== null && method_exists($conversion, 'fit')) {
                $conversion
                    ->fit($fitEnum::Max, $variant->maxLongEdge, $variant->maxLongEdge)
                    ->keepOriginalImageSize();
            } elseif (($variant->maxWidth || $variant->maxHeight) && $fitEnum !== null && method_exists($conversion, 'fit')) {
                $conversion->fit(
                    $fitEnum::Max,
                    $variant->maxWidth ?? 99999,
                    $variant->maxHeight ?? 99999,
                );
            }

            if ($variant->optimize && method_exists($conversion, 'quality')) {
                $conversion->quality(85);
            }

            if (method_exists($conversion, 'nonQueued')) {
                $conversion->nonQueued();
            }
        }
    }

    public function getFlexRichEditorVariantUrl(object $media, string $variant, ?string $fallback = null): string
    {
        if (method_exists($media, 'hasGeneratedConversion') && $media->hasGeneratedConversion($variant)) {
            return $media->getUrl($variant);
        }

        return $fallback ?? (method_exists($media, 'getUrl') ? $media->getUrl() : '');
    }
}

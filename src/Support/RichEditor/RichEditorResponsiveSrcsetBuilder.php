<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\RichEditor;

class RichEditorResponsiveSrcsetBuilder
{
    /**
     * @param  list<RichEditorImageVariant>  $variants
     * @param  callable(object, string): (?string)  $variantUrlResolver
     * @return array{src: ?string, srcset: ?string, sizes: ?string, width: ?int}
     */
    public function buildFromSpatieMedia(
        object $media,
        array $variants,
        callable $variantUrlResolver,
        string $fallbackUrl,
        string $sizes,
    ): array {
        $candidates = [];

        foreach ($variants as $variant) {
            $width = $variant->maxLongEdge ?? $variant->maxWidth;

            if (! $width) {
                continue;
            }

            if ($variant->master) {
                if ($fallbackUrl !== '') {
                    $candidates[] = [
                        'url' => $fallbackUrl,
                        'width' => (int) $width,
                    ];
                }

                continue;
            }

            if (! $this->mediaHasGeneratedConversion($media, $variant->name)) {
                continue;
            }

            $url = $variantUrlResolver($media, $variant->name);

            if (! is_string($url) || $url === '') {
                continue;
            }

            $candidates[] = [
                'url' => $url,
                'width' => (int) $width,
            ];
        }

        if ($candidates === []) {
            return [
                'src' => $fallbackUrl,
                'srcset' => null,
                'sizes' => $sizes,
                'width' => null,
            ];
        }

        if (count($candidates) === 1) {
            $only = $candidates[0];

            return [
                'src' => $only['url'],
                'srcset' => null,
                'sizes' => $sizes,
                'width' => $only['width'],
            ];
        }

        return $this->compileCandidates($candidates, $sizes);
    }

    protected function mediaHasGeneratedConversion(object $media, string $conversionName): bool
    {
        if (! is_callable([$media, 'hasGeneratedConversion'])) {
            return false;
        }

        return $media->hasGeneratedConversion($conversionName);
    }

    /**
     * @param  callable(string): (?string)  $urlResolver
     * @return array{src: ?string, srcset: ?string, sizes: ?string, width: ?int}
     */
    public function build(
        RichEditorAttachmentManifest $manifest,
        callable $urlResolver,
        string $sizes,
        bool $lazy = true,
    ): array {
        $candidates = [];

        foreach ($manifest->widths as $variantName => $width) {
            $path = $variantName === 'master'
                ? $manifest->master
                : ($manifest->variants[$variantName] ?? null);

            if (! is_string($path) || $path === '') {
                continue;
            }

            $url = $urlResolver($path);

            if (! is_string($url) || $url === '') {
                continue;
            }

            $candidates[] = [
                'url' => $url,
                'width' => (int) $width,
                'path' => $path,
            ];
        }

        if ($candidates === []) {
            $masterUrl = $urlResolver($manifest->master);

            return [
                'src' => $masterUrl,
                'srcset' => null,
                'sizes' => $sizes,
                'width' => $manifest->widths['master'] ?? null,
            ];
        }

        return $this->compileCandidates($candidates, $sizes);
    }

    /**
     * @param  list<array{url: string, width: int}>  $candidates
     * @return array{src: ?string, srcset: ?string, sizes: ?string, width: ?int}
     */
    protected function compileCandidates(array $candidates, string $sizes): array
    {
        usort($candidates, fn (array $left, array $right): int => $left['width'] <=> $right['width']);

        $srcset = collect($candidates)
            ->map(fn (array $candidate): string => "{$candidate['url']} {$candidate['width']}w")
            ->implode(', ');

        $default = $candidates[array_key_last($candidates)];

        return [
            'src' => $default['url'],
            'srcset' => $srcset,
            'sizes' => $sizes,
            'width' => $default['width'],
        ];
    }
}

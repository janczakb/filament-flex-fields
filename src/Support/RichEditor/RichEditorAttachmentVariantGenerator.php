<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\RichEditor;

use Illuminate\Contracts\Filesystem\Filesystem;

class RichEditorAttachmentVariantGenerator
{
    /**
     * @param  list<RichEditorImageVariant>  $variants
     */
    public function generate(Filesystem $disk, string $masterPath, array $variants): RichEditorAttachmentManifest
    {
        $variants = RichEditorImageVariant::ensureMasterVariant($variants);

        if ($variants === []) {
            return new RichEditorAttachmentManifest(master: $masterPath);
        }

        $masterVariant = collect($variants)->first(fn (RichEditorImageVariant $variant): bool => $variant->master)
            ?? $variants[0];

        $absoluteMasterPath = method_exists($disk, 'path') ? $disk->path($masterPath) : null;

        if (! is_string($absoluteMasterPath) || ! is_file($absoluteMasterPath)) {
            return new RichEditorAttachmentManifest(master: $masterPath);
        }

        $processedMasterPath = $masterVariant->toImageProcessor()->process($disk, $masterPath);

        $absoluteSourcePath = method_exists($disk, 'path') ? $disk->path($processedMasterPath) : null;

        if (! is_string($absoluteSourcePath) || ! is_file($absoluteSourcePath)) {
            return new RichEditorAttachmentManifest(master: $processedMasterPath);
        }

        $generatedVariants = [];
        $widths = [];

        $masterWidth = $this->resolveImageWidth($disk, $processedMasterPath);

        if ($masterWidth !== null) {
            $widths['master'] = $masterWidth;
        }

        foreach ($variants as $variant) {
            if ($variant->master) {
                continue;
            }

            $variantPath = RichEditorAttachmentPaths::variantPath(
                $processedMasterPath,
                $variant->name,
                $variant->webp,
            );

            $absoluteVariantPath = method_exists($disk, 'path') ? $disk->path($variantPath) : null;

            if (! is_string($absoluteVariantPath)) {
                continue;
            }

            $directory = dirname($absoluteVariantPath);

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            copy($absoluteSourcePath, $absoluteVariantPath);

            $processedVariantPath = $variant->toImageProcessor()->process($disk, $variantPath);
            $generatedVariants[$variant->name] = $processedVariantPath;

            $variantWidth = $this->resolveImageWidth($disk, $processedVariantPath);

            if ($variantWidth !== null) {
                $widths[$variant->name] = $variantWidth;
            }
        }

        $manifest = new RichEditorAttachmentManifest(
            master: $processedMasterPath,
            variants: $generatedVariants,
            widths: $widths,
        );

        $this->writeManifest($disk, $manifest);

        return $manifest;
    }

    protected function writeManifest(Filesystem $disk, RichEditorAttachmentManifest $manifest): void
    {
        $disk->put(
            $manifest->manifestPath(),
            (string) json_encode($manifest->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    protected function resolveImageWidth(Filesystem $disk, string $path): ?int
    {
        $absolutePath = method_exists($disk, 'path') ? $disk->path($path) : null;

        if (! is_string($absolutePath) || ! is_file($absolutePath)) {
            return null;
        }

        $size = @getimagesize($absolutePath);

        if (! is_array($size) || ! isset($size[0])) {
            return null;
        }

        return (int) $size[0];
    }
}

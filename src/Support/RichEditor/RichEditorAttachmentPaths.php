<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\RichEditor;

use Illuminate\Support\Str;

final class RichEditorAttachmentPaths
{
    public static function manifestPath(string $masterPath): string
    {
        return Str::of($masterPath)->beforeLast('.').'.flex-variants.json';
    }

    public static function variantPath(string $masterPath, string $variantName, bool $webp = false): string
    {
        $extension = $webp ? 'webp' : (string) Str::of($masterPath)->afterLast('.');
        $basename = (string) Str::of($masterPath)->beforeLast('.');

        return "{$basename}__{$variantName}.{$extension}";
    }
}

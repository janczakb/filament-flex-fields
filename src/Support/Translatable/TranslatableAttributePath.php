<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

use Filament\Forms\Components\Field;

/**
 * Resolves form state paths and model storage attributes for locale fields.
 */
final class TranslatableAttributePath
{
    public static function relativeBasePath(Field $template): string
    {
        $relativePath = $template->getStatePath(isAbsolute: false);

        if (filled($relativePath)) {
            return (string) $relativePath;
        }

        return (string) $template->getName();
    }

    public static function localeStatePath(Field $template, string $locale): string
    {
        return TranslatableTitle::sourcePath(self::relativeBasePath($template), $locale);
    }

    public static function storageAttribute(Field $template): string
    {
        return (string) $template->getName();
    }
}

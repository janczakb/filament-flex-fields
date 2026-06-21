<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

final class Translations
{
    /**
     * @param  array<string, mixed>  $replace
     */
    public static function get(string $key, array $replace = []): string
    {
        $translation = __($key, $replace);

        return is_string($translation) ? $translation : $key;
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

/**
 * Filament / Illuminate Js::from() (Laravel 13.31+ / Filament 5.8+) embeds Alpine
 * config as JSON.parse('{\u0022key\u0022:…}'). Older stacks used bare `key: value`.
 * Accept both so HTML smoke stays green across supported Filament 5.x minors.
 */
final class AlpineJsHtml
{
    public static function containsFlag(string $html, string $key, bool $value): bool
    {
        $jsonValue = $value ? 'true' : 'false';
        $legacy = "{$key}: {$jsonValue}";
        $escaped = '\\u0022'.$key.'\\u0022:'.$jsonValue;

        return str_contains($html, $legacy) || str_contains($html, $escaped);
    }

    public static function containsString(string $html, string $key, string $value): bool
    {
        $legacyPatterns = [
            "/{$key}[\"']?\\s*:\\s*[\"']".preg_quote($value, '/')."[\"']/",
            '/\\\\u0022'.preg_quote($key, '/').'\\\\u0022:\\\\u0022'.preg_quote($value, '/').'\\\\u0022/',
        ];

        foreach ($legacyPatterns as $pattern) {
            if (preg_match($pattern, $html) === 1) {
                return true;
            }
        }

        $escaped = '\\u0022'.$key.'\\u0022:\\u0022'.$value.'\\u0022';

        return str_contains($html, $escaped);
    }

    public static function containsEmptyArray(string $html, string $key): bool
    {
        $legacy = "{$key}: []";
        $escaped = '\\u0022'.$key.'\\u0022:[]';

        return str_contains($html, $legacy) || str_contains($html, $escaped);
    }
}

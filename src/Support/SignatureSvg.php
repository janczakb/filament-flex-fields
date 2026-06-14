<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

class SignatureSvg
{
    public const int VIEWBOX_WIDTH = 1000;

    public const int VIEWBOX_HEIGHT = 320;

    public static function isEmpty(?string $svg): bool
    {
        if (! is_string($svg) || trim($svg) === '') {
            return true;
        }

        return self::countPaths($svg) === 0;
    }

    public static function countPaths(string $svg): int
    {
        return preg_match_all('/<path\b/i', $svg) ?: 0;
    }

    public static function byteSize(string $svg): int
    {
        return strlen($svg);
    }

    public static function isValid(?string $svg): bool
    {
        if ($svg === null || trim($svg) === '') {
            return true;
        }

        if (self::byteSize($svg) > 1024 * 256) {
            return false;
        }

        if (preg_match('/<\?(php)?|<!DOCTYPE|<script|on\w+\s*=|javascript:/i', $svg)) {
            return false;
        }

        if (! preg_match('/^<svg\b/i', trim($svg))) {
            return false;
        }

        if (preg_match('/<(?!svg\b|path\b|\/svg>|\/path>)[a-z]/i', $svg)) {
            return false;
        }

        return preg_match('/\bd=["\'][^"\']+["\']/i', $svg) === 1;
    }

    public static function normalize(?string $svg): ?string
    {
        if ($svg === null || trim($svg) === '') {
            return null;
        }

        $svg = trim($svg);

        if (! self::isValid($svg)) {
            return null;
        }

        return preg_replace('/>\s+</', '><', $svg) ?? $svg;
    }
}

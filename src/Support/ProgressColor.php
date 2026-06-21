<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use InvalidArgumentException;

class ProgressColor
{
    /** @var list<string> */
    public const SEMANTIC = ['primary', 'success', 'warning', 'danger'];

    public static function isSemantic(string $color): bool
    {
        return in_array(strtolower(trim($color)), self::SEMANTIC, true);
    }

    public static function normalize(string $color): string
    {
        $color = trim($color);

        if ($color === '') {
            throw new InvalidArgumentException('Progress color cannot be empty.');
        }

        if (self::isSemantic($color)) {
            return strtolower($color);
        }

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color) === 1) {
            return strtolower($color);
        }

        if (preg_match('/^rgba?\(/i', $color) === 1 || preg_match('/^hsla?\(/i', $color) === 1) {
            return $color;
        }

        throw new InvalidArgumentException("Progress color [{$color}] must be a semantic token (primary, success, warning, danger) or a CSS color (hex, rgb, rgba, hsl).");
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function parseRgbChannels(string $color): array
    {
        $normalized = self::isSemantic($color)
            ? self::semanticToRgbChannels($color)
            : self::parseCssColorChannels(self::normalize($color));

        return $normalized;
    }

    public static function toRgbString(int $red, int $green, int $blue): string
    {
        return "rgb({$red} {$green} {$blue})";
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    protected static function semanticToRgbChannels(string $token): array
    {
        return match (strtolower($token)) {
            'success' => [34, 197, 94],
            'warning' => [245, 158, 11],
            'danger' => [239, 68, 68],
            default => [99, 102, 241],
        };
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    protected static function parseCssColorChannels(string $color): array
    {
        if (preg_match('/^#([0-9a-f]{3})$/i', $color, $matches) === 1) {
            return [
                (int) hexdec(str_repeat($matches[1][0], 2)),
                (int) hexdec(str_repeat($matches[1][1], 2)),
                (int) hexdec(str_repeat($matches[1][2], 2)),
            ];
        }

        if (preg_match('/^#([0-9a-f]{6})$/i', $color, $matches) === 1) {
            return [
                (int) hexdec(substr($matches[1], 0, 2)),
                (int) hexdec(substr($matches[1], 2, 2)),
                (int) hexdec(substr($matches[1], 4, 2)),
            ];
        }

        if (preg_match('/rgb\(\s*(\d+)\s+(\d+)\s+(\d+)\s*\)/', $color, $matches) === 1) {
            return [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
        }

        if (preg_match('/rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/', $color, $matches) === 1) {
            return [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
        }

        throw new InvalidArgumentException("Progress color [{$color}] cannot be converted to RGB channels for interpolation.");
    }
}

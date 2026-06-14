<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\DateTime;

class DateTimeLocaleOrder
{
    /**
     * @return list<string>
     */
    public static function dateSegmentParts(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        if (! extension_loaded('intl')) {
            return ['month', 'day', 'year'];
        }

        $formatter = \IntlDateFormatter::create(
            $locale,
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::NONE,
        );

        if ($formatter === false) {
            return ['month', 'day', 'year'];
        }

        $pattern = $formatter->getPattern();
        $parts = [];

        foreach (preg_split('/[^\p{L}]+/u', $pattern, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            if (preg_match('/^d+$/i', $token) !== 0) {
                $parts[] = 'day';
            } elseif (preg_match('/^M+$/', $token) !== 0 || preg_match('/^L+$/', $token) !== 0) {
                $parts[] = 'month';
            } elseif (preg_match('/^y+$/i', $token) !== 0) {
                $parts[] = 'year';
            }
        }

        if (count($parts) !== 3) {
            return ['month', 'day', 'year'];
        }

        return $parts;
    }

    public static function isDayFirst(?string $locale = null): bool
    {
        $parts = self::dateSegmentParts($locale);

        return ($parts[0] ?? '') === 'day';
    }

    /**
     * @param  list<string>  $parts
     */
    public static function separatorAfter(string $part, array $parts, ?string $locale = null): string
    {
        $index = array_search($part, $parts, true);

        if ($index === false || $index >= count($parts) - 1) {
            return '';
        }

        $locale = $locale ?? app()->getLocale();

        if (! extension_loaded('intl')) {
            return self::fallbackSeparator($part);
        }

        $formatter = \IntlDateFormatter::create(
            $locale,
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::NONE,
        );

        if ($formatter === false) {
            return self::fallbackSeparator($part);
        }

        $sample = $formatter->format(new \DateTimeImmutable('2024-06-15'));

        if (is_string($sample) && preg_match('/\d([^0-9\s]+)\d/', $sample, $matches) === 1) {
            return $matches[1];
        }

        return self::fallbackSeparator($part);
    }

    protected static function fallbackSeparator(string $part): string
    {
        return match ($part) {
            'month', 'day' => '/',
            default => '',
        };
    }
}

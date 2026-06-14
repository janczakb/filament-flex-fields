<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\DateTime;

use Bjanczak\FilamentFlexFields\Enums\MonthDisplay;

class DateTimeMonthLabels
{
    /**
     * @return list<string>
     */
    public static function labels(?string $locale, MonthDisplay $display): array
    {
        if ($display === MonthDisplay::Numeric) {
            return array_map(
                static fn (int $month): string => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                range(1, 12),
            );
        }

        if (! extension_loaded('intl')) {
            return self::fallbackLabels($display);
        }

        $locale = self::normalizeLocale($locale);
        $pattern = $display === MonthDisplay::Long ? 'LLLL' : 'MMM';
        $labels = [];

        for ($month = 1; $month <= 12; $month++) {
            $formatter = \IntlDateFormatter::create(
                $locale,
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                null,
                null,
                $pattern,
            );

            $labels[] = $formatter !== false
                ? (string) $formatter->format(new \DateTimeImmutable(sprintf('2024-%02d-01', $month)))
                : self::fallbackLabels($display)[$month - 1];
        }

        return $labels;
    }

    public static function format(int $month, ?string $locale, MonthDisplay $display, bool $forceLeadingZeros = true): string
    {
        if ($display === MonthDisplay::Numeric) {
            return str_pad((string) $month, 2, $forceLeadingZeros ? '0' : ' ', STR_PAD_LEFT);
        }

        return self::labels($locale, $display)[$month - 1] ?? str_pad((string) $month, 2, '0', STR_PAD_LEFT);
    }

    public static function parse(string $input, ?string $locale, MonthDisplay $display): ?int
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        if ($display === MonthDisplay::Numeric || ctype_digit($input)) {
            $month = (int) $input;

            return $month >= 1 && $month <= 12 ? $month : null;
        }

        $normalizedInput = self::normalizeLabel($input);
        $labels = self::labels($locale, $display);
        $matches = [];

        foreach ($labels as $index => $label) {
            $normalizedLabel = self::normalizeLabel($label);

            if ($normalizedLabel === $normalizedInput) {
                return $index + 1;
            }

            if (str_starts_with($normalizedLabel, $normalizedInput)) {
                $matches[] = $index + 1;
            }
        }

        if (count($matches) === 1) {
            return $matches[0];
        }

        return null;
    }

    public static function segmentMaxLength(?string $locale, MonthDisplay $display): int
    {
        if ($display === MonthDisplay::Numeric) {
            return 2;
        }

        return max(array_map(mb_strlen(...), self::labels($locale, $display))) ?: ($display === MonthDisplay::Long ? 9 : 3);
    }

    public static function segmentPlaceholder(?string $locale, MonthDisplay $display): string
    {
        if ($display === MonthDisplay::Numeric) {
            return 'mm';
        }

        $labels = self::labels($locale, $display);

        return $labels[0] ?? ($display === MonthDisplay::Long ? 'month' : 'mmm');
    }

    protected static function normalizeLocale(?string $locale): string
    {
        if (! filled($locale)) {
            return 'en-US';
        }

        return str_replace('_', '-', trim((string) $locale));
    }

    protected static function normalizeLabel(string $value): string
    {
        $normalized = mb_strtolower(trim($value));

        if (class_exists(\Normalizer::class)) {
            $normalized = (string) \Normalizer::normalize($normalized, \Normalizer::FORM_D);
            $normalized = preg_replace('/\p{Mn}/u', '', $normalized) ?? $normalized;
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    protected static function fallbackLabels(MonthDisplay $display): array
    {
        if ($display === MonthDisplay::Long) {
            return [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December',
            ];
        }

        return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    }
}

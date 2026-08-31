<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Locale;

use Carbon\Carbon;
use DateTimeInterface;
use NumberFormatter;

final class FlexFieldsLocale
{
    /** @var list<string> */
    private const SUPPORTED_LOCALES = [
        'en',
        'pl',
        'de',
        'fr',
        'es',
        'pt_BR',
        'nl',
        'it',
    ];

    /**
     * @return list<string>
     */
    public static function supportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public static function resolve(?string $locale): string
    {
        $normalized = self::normalizeLocale($locale);

        if ($normalized === '') {
            $normalized = self::normalizeLocale(self::applicationLocale());
        }

        if ($normalized === '' || ! in_array($normalized, self::SUPPORTED_LOCALES, true)) {
            return 'en';
        }

        return $normalized;
    }

    public static function formatMoney(float|string $amount, string $currency, ?string $locale = null): string
    {
        $numericAmount = is_string($amount) ? (float) $amount : $amount;
        $locale = self::resolve($locale);

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency($numericAmount, strtoupper($currency));

            if ($formatted !== false) {
                return $formatted;
            }
        }

        return sprintf('%s %.2f', strtoupper($currency), $numericAmount);
    }

    public static function formatDate(DateTimeInterface|string $date, ?string $locale = null): string
    {
        $resolvedLocale = self::resolve($locale);
        $dateTime = self::resolveDateTime($date);

        if (extension_loaded('intl')) {
            $formatter = \IntlDateFormatter::create(
                $resolvedLocale,
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::NONE,
            );

            if ($formatter !== false) {
                $formatted = $formatter->format($dateTime);

                if ($formatted !== false) {
                    return $formatted;
                }
            }
        }

        return $dateTime->format('Y-m-d');
    }

    private static function applicationLocale(): ?string
    {
        if (! function_exists('app')) {
            return null;
        }

        try {
            $app = app();

            if (! method_exists($app, 'getLocale')) {
                return null;
            }

            return $app->getLocale();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function normalizeLocale(?string $locale): string
    {
        if ($locale === null) {
            return '';
        }

        $locale = trim(str_replace('-', '_', $locale));

        if ($locale === '') {
            return '';
        }

        if (str_contains($locale, '_')) {
            [$language, $region] = explode('_', $locale, 2);

            return strtolower($language).'_'.strtoupper($region);
        }

        return strtolower($locale);
    }

    private static function resolveDateTime(DateTimeInterface|string $date): DateTimeInterface
    {
        if ($date instanceof DateTimeInterface) {
            return $date;
        }

        return Carbon::parse($date);
    }
}

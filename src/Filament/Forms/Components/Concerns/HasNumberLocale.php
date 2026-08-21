<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Closure;
use NumberFormatter;

/**
 * Opt-in locale-aware numeric display for slider / stepper style fields.
 *
 * Without `->locale()` the output is byte-for-byte what it has always been
 * (a plain `.`-decimal string with no grouping), so this is additive only.
 */
trait HasNumberLocale
{
    protected string|Closure|null $numberLocale = null;

    public function locale(string|Closure|null $locale): static
    {
        $this->numberLocale = $locale;

        return $this;
    }

    public function getLocale(): ?string
    {
        $locale = $this->evaluate($this->numberLocale);

        return filled($locale) ? (string) $locale : null;
    }

    /**
     * Mirrors `formatDecimal()` in resources/js/core/number-format.js so the
     * server-rendered value and the hydrated Alpine value agree.
     */
    public function formatNumberForLocale(float $value, ?int $decimalPlaces): string
    {
        $locale = $this->getLocale();

        if ($locale === null || ! class_exists(NumberFormatter::class)) {
            return $decimalPlaces === null
                ? (string) $value
                : number_format($value, $decimalPlaces, '.', '');
        }

        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimalPlaces ?? 0);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimalPlaces ?? 20);

        $formatted = $formatter->format($value);

        if ($formatted === false) {
            return $decimalPlaces === null
                ? (string) $value
                : number_format($value, $decimalPlaces, '.', '');
        }

        return $formatted;
    }

    /**
     * Decimal separator for the configured locale, or `.` when none is set.
     */
    public function getLocaleDecimalSeparator(): string
    {
        $locale = $this->getLocale();

        if ($locale === null || ! class_exists(NumberFormatter::class)) {
            return '.';
        }

        $symbol = (new NumberFormatter($locale, NumberFormatter::DECIMAL))
            ->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        return $symbol === false ? '.' : $symbol;
    }
}

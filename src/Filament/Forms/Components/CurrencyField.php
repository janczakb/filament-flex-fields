<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\StateCasts\CurrencyFieldStateCast;
use Bjanczak\FilamentFlexFields\Support\CurrencyCountries;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use InvalidArgumentException;

class CurrencyField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasPlaceholder;

    protected string $view = 'filament-flex-fields::forms.components.currency-field';

    protected string|Closure $defaultCurrency = 'PLN';

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $currencies = null;

    protected string|Closure|null $locale = null;

    protected float|int|Closure|null $min = null;

    protected float|int|Closure|null $max = null;

    protected bool|Closure $allowNegative = false;

    protected bool|Closure $animated = true;

    protected bool|Closure $commitDecimalsOnBlur = true;

    protected bool|Closure $searchable = true;

    protected string|Closure $variant = 'primary';

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(function (CurrencyField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                $normalized = $component->normalizeState($value);
                $amount = $component->extractAmount($normalized);
                $currency = $component->extractCurrency($normalized);

                if ($component->isRequired() && $amount === null) {
                    $fail(__('validation.required', ['attribute' => $component->getLabel()]));

                    return;
                }

                if ($amount === null) {
                    return;
                }

                if (! $component->allowsNegative() && $amount < 0) {
                    $fail(__('filament-flex-fields::default.validation.currency.negative'));

                    return;
                }

                $min = $component->getMinMinorUnits($currency);

                if ($min !== null && $amount < $min) {
                    $fail(__('filament-flex-fields::default.validation.currency.min', [
                        'min' => CurrencyCountries::formatMajor($component->getMin(), $currency, $component->getLocale($currency)),
                    ]));

                    return;
                }

                $max = $component->getMaxMinorUnits($currency);

                if ($max !== null && $amount > $max) {
                    $fail(__('filament-flex-fields::default.validation.currency.max', [
                        'max' => CurrencyCountries::formatMajor($component->getMax(), $currency, $component->getLocale($currency)),
                    ]));
                }
            };
        });
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat', 'soft'], true)) {
            throw new InvalidArgumentException("Currency field variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            app(CurrencyFieldStateCast::class, ['field' => $this]),
        ];
    }

    public function getRequiredValidationRule(): string|Closure
    {
        return 'nullable';
    }

    public function currency(string|Closure $currencyCode): static
    {
        $this->defaultCurrency = $currencyCode;

        return $this;
    }

    /**
     * @param  list<string>|Closure|null  $currencies
     */
    public function currencies(array|Closure|null $currencies): static
    {
        $this->currencies = $currencies;

        return $this;
    }

    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Minimum value in major units (e.g. 0 or 10.50).
     */
    public function min(float|int|Closure|null $value): static
    {
        $this->min = $value;

        return $this;
    }

    /**
     * Maximum value in major units (e.g. 999999.99).
     */
    public function max(float|int|Closure|null $value): static
    {
        $this->max = $value;

        return $this;
    }

    public function allowNegative(bool|Closure $condition = true): static
    {
        $this->allowNegative = $condition;

        return $this;
    }

    public function animated(bool|Closure $condition = true): static
    {
        $this->animated = $condition;

        return $this;
    }

    public function commitDecimalsOnBlur(bool|Closure $condition = true): static
    {
        $this->commitDecimalsOnBlur = $condition;

        return $this;
    }

    public function searchable(bool|Closure $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    public function hasCurrencySelect(): bool
    {
        return $this->getAllowedCurrencyCodes() !== null;
    }

    /**
     * @return list<string>|null
     */
    public function getAllowedCurrencyCodes(): ?array
    {
        $currencies = $this->evaluate($this->currencies);

        if ($currencies === null) {
            return null;
        }

        return CurrencyCountries::resolve($currencies);
    }

    /**
     * @return list<string>
     */
    public function getResolvedCurrencyCodes(): array
    {
        $allowed = $this->getAllowedCurrencyCodes();

        if ($allowed !== null) {
            return $allowed;
        }

        return [strtoupper((string) $this->evaluate($this->defaultCurrency))];
    }

    public function getDefaultCurrencyCode(): string
    {
        $currency = strtoupper((string) $this->evaluate($this->defaultCurrency));
        $resolved = $this->getResolvedCurrencyCodes();

        if (in_array($currency, $resolved, true)) {
            return $currency;
        }

        return $resolved[0] ?? 'PLN';
    }

    public function getLocale(?string $currencyCode = null): string
    {
        $locale = $this->evaluate($this->locale);

        if (filled($locale)) {
            return (string) $locale;
        }

        $currencyCode ??= $this->getDefaultCurrencyCode();

        return CurrencyCountries::locale($currencyCode);
    }

    public function getMin(): ?float
    {
        $min = $this->evaluate($this->min);

        if ($min === null || $min === '') {
            return null;
        }

        return (float) $min;
    }

    public function getMax(): ?float
    {
        $max = $this->evaluate($this->max);

        if ($max === null || $max === '') {
            return null;
        }

        return (float) $max;
    }

    public function getMinMinorUnits(?string $currencyCode = null): ?int
    {
        $min = $this->getMin();

        if ($min === null) {
            return null;
        }

        $currencyCode ??= $this->getDefaultCurrencyCode();

        return CurrencyCountries::toMinorUnits($min, $currencyCode);
    }

    public function getMaxMinorUnits(?string $currencyCode = null): ?int
    {
        $max = $this->getMax();

        if ($max === null) {
            return null;
        }

        $currencyCode ??= $this->getDefaultCurrencyCode();

        return CurrencyCountries::toMinorUnits($max, $currencyCode);
    }

    public function allowsNegative(): bool
    {
        return (bool) $this->evaluate($this->allowNegative);
    }

    public function isAnimated(): bool
    {
        return (bool) $this->evaluate($this->animated);
    }

    public function shouldCommitDecimalsOnBlur(): bool
    {
        return (bool) $this->evaluate($this->commitDecimalsOnBlur);
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->searchable);
    }

    /**
     * @return list<array{code: string, symbol: string, name: string, decimals: int, locale: string}>
     */
    public function getCurrenciesMetadata(): array
    {
        return CurrencyCountries::metadata($this->getAllowedCurrencyCodes() ?? $this->getResolvedCurrencyCodes());
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public function getCurrencySelectOptions(): array
    {
        return CurrencyCountries::selectOptions($this->getAllowedCurrencyCodes());
    }

    public function normalizeState(mixed $state): int|array|null
    {
        $defaultCurrency = $this->getDefaultCurrencyCode();
        $resolved = $this->getResolvedCurrencyCodes();

        if ($this->hasCurrencySelect()) {
            if (is_int($state)) {
                return [
                    'amount' => $state,
                    'currency' => $defaultCurrency,
                ];
            }

            if (! is_array($state)) {
                return [
                    'amount' => $this->normalizeAmount($state, $defaultCurrency),
                    'currency' => $defaultCurrency,
                ];
            }

            $currency = strtoupper((string) ($state['currency'] ?? $defaultCurrency));

            if (! in_array($currency, $resolved, true)) {
                $currency = $defaultCurrency;
            }

            return [
                'amount' => $this->normalizeAmount($state['amount'] ?? null, $currency),
                'currency' => $currency,
            ];
        }

        if (is_array($state)) {
            $currency = strtoupper((string) ($state['currency'] ?? $defaultCurrency));

            return $this->normalizeAmount($state['amount'] ?? null, $currency);
        }

        return $this->normalizeAmount($state, $defaultCurrency);
    }

    public function extractAmount(int|array|null $state): ?int
    {
        if (is_array($state)) {
            return $state['amount'] ?? null;
        }

        return $state;
    }

    public function extractCurrency(int|array|null $state): string
    {
        if (is_array($state)) {
            return strtoupper((string) ($state['currency'] ?? $this->getDefaultCurrencyCode()));
        }

        return $this->getDefaultCurrencyCode();
    }

    protected function normalizeAmount(mixed $amount, string $currencyCode): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        if (is_float($amount) || (is_string($amount) && str_contains((string) $amount, '.'))) {
            return CurrencyCountries::toMinorUnits($amount, $currencyCode);
        }

        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Currency amount must be numeric.');
        }

        return (int) $amount;
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-currency-field',
            'fff-flex-text-input-field',
            'fff-currency-field--'.$this->getSize(),
            'fff-flex-text-input-field--'.$this->getSize(),
            'fff-currency-field--'.$this->getVariant(),
            'fff-flex-text-input-field--'.$this->getVariant(),
        ];
    }

    /**
     * @return array{
     *     isEmpty: bool,
     *     negative: bool,
     *     segments: list<array{type: string, char: string, key: string, ghost: bool}>,
     *     symbol: string,
     *     currencyCode: string,
     * }
     */
    public function getInitialDisplay(mixed $state = null): array
    {
        $resolvedState = func_num_args() > 0 ? $state : $this->getState();

        $state = $this->normalizeState($resolvedState);
        $currencyCode = $this->extractCurrency($state);
        $amount = $this->extractAmount($state);
        $decimals = CurrencyCountries::decimals($currencyCode);
        $locale = $this->resolveDisplayLocale($currencyCode);
        $edit = CurrencyCountries::editStateFromMinor($amount, $decimals);
        $isEmpty = $edit['wholeDigits'] === ''
            && $edit['fracDigits'] === ''
            && ! $edit['inDecimal'];

        $segments = $isEmpty
            ? []
            : array_values(array_filter(
                CurrencyCountries::buildDisplaySegments($edit, $locale, $decimals),
                fn (array $segment): bool => ! ($segment['type'] === 'digit' && $segment['ghost']),
            ));

        return [
            'isEmpty' => $isEmpty,
            'negative' => $edit['negative'] && ! $isEmpty,
            'segments' => $segments,
            'symbol' => CurrencyCountries::symbol($currencyCode),
            'currencyCode' => $currencyCode,
        ];
    }

    protected function resolveDisplayLocale(string $currencyCode): string
    {
        if ($this->locale === null) {
            return CurrencyCountries::locale($currencyCode);
        }

        $configuredLocale = $this->evaluate($this->locale);

        if (filled($configuredLocale)) {
            return (string) $configuredLocale;
        }

        return CurrencyCountries::locale($currencyCode);
    }
}

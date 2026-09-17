<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldRounding;
use Bjanczak\FilamentFlexFields\Enums\PhoneNationalFormat;
use Bjanczak\FilamentFlexFields\StateCasts\PhoneFieldStateCast;
use Bjanczak\FilamentFlexFields\Support\CountryRegistry;
use Bjanczak\FilamentFlexFields\Support\CountryRegistryQueue;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\PhoneCountries;
use Bjanczak\FilamentFlexFields\Support\PhoneNumberInsights;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

class PhoneField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasFieldRounding;
    use HasPlaceholder;

    protected string $view = 'filament-flex-fields::forms.components.phone-field';

    protected string|Closure $variant = 'primary';

    protected string|Closure $defaultCountry = 'PL';

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $countries = null;

    /**
     * @var list<string>|Closure
     */
    protected array|Closure $exceptCountries = [];

    protected bool|Closure $searchable = true;

    protected string|BackedEnum|Htmlable|Closure|null $suffixIcon = null;

    protected bool|Closure $showSuffixIcon = true;

    protected bool|Closure $showInternationalPrefix = true;

    protected bool|Closure $mobileOnly = false;

    protected bool|Closure $fixedLineOnly = false;

    /**
     * Explicit libphonenumber types. When set, overrides mobileOnly/fixedLineOnly sugar.
     *
     * @var list<PhoneNumberType|string|int>|Closure|null
     */
    protected array|Closure|null $allowTypes = null;

    protected bool|Closure $strictTypes = false;

    protected bool|Closure $validateForRegion = false;

    protected PhoneNationalFormat|string|Closure $nationalFormat = PhoneNationalFormat::National;

    /**
     * Optional dehydrated format keys: international, rfc3966.
     *
     * @var list<string>|Closure
     */
    protected array|Closure $includeFormats = [];

    /**
     * Optional dehydrated metadata keys: carrier, geo, timezones, type.
     *
     * @var list<string>|Closure
     */
    protected array|Closure $includeMetadata = [];

    protected bool|Closure $browserLocaleDefault = false;

    protected bool|Closure $browserLocaleSortFirst = false;

    protected string|Closure|null $locale = null;

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat', 'soft'], true)) {
            throw new InvalidArgumentException("Phone field variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(function (PhoneField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (! is_array($value)) {
                    $fail(__('validation.array', ['attribute' => $attribute]));

                    return;
                }

                $normalized = $component->normalizeState($value);
                if ($component->isRequired()) {
                    if ($normalized['national'] === '') {
                        $fail(__('validation.required', ['attribute' => $component->getLabel()]));

                        return;
                    }
                }

                if ($normalized['national'] === '') {
                    return;
                }

                $message = $component->getPhoneValidationMessage($normalized);
                if ($message !== null) {
                    $fail($message);
                }
            };
        });
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            app(PhoneFieldStateCast::class, ['field' => $this]),
        ];
    }

    public function getRequiredValidationRule(): string|Closure
    {
        return 'nullable';
    }

    /**
     * @param  list<string>|Closure|null  $countries
     */
    public function countries(array|Closure|null $countries): static
    {
        $this->countries = $countries;

        return $this;
    }

    /**
     * @param  list<string>|Closure  $countries
     */
    public function exceptCountries(array|Closure $countries): static
    {
        $this->exceptCountries = $countries;

        return $this;
    }

    public function defaultCountry(string|Closure $countryCode): static
    {
        $this->defaultCountry = $countryCode;

        return $this;
    }

    public function browserLocaleDefault(bool|Closure $condition = true): static
    {
        $this->browserLocaleDefault = $condition;

        return $this;
    }

    public function browserLocaleSortFirst(bool|Closure $condition = true): static
    {
        $this->browserLocaleSortFirst = $condition;

        return $this;
    }

    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        $locale = $this->evaluate($this->locale);

        return filled($locale) ? (string) $locale : app()->getLocale();
    }

    public function shouldUseBrowserLocaleDefault(): bool
    {
        return (bool) $this->evaluate($this->browserLocaleDefault);
    }

    public function shouldSortCountriesByBrowserLocale(): bool
    {
        return (bool) $this->evaluate($this->browserLocaleSortFirst);
    }

    public function getBrowserLocaleCountryCode(): ?string
    {
        return PhoneCountries::fromBrowserLocale($this->getAllowedCountryCodes());
    }

    public function searchable(bool|Closure $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    public function suffixIcon(string|BackedEnum|Htmlable|Closure|bool|null $icon = null): static
    {
        if (is_bool($icon)) {
            $this->showSuffixIcon = $icon;

            return $this;
        }

        if ($icon !== null) {
            $this->suffixIcon = $icon;
            $this->showSuffixIcon = true;
        }

        return $this;
    }

    public function internationalPrefix(bool|Closure $condition = true): static
    {
        $this->showInternationalPrefix = $condition;

        return $this;
    }

    public function mobileOnly(bool|Closure $condition = true): static
    {
        $this->mobileOnly = $condition;

        return $this;
    }

    public function fixedLineOnly(bool|Closure $condition = true): static
    {
        $this->fixedLineOnly = $condition;

        return $this;
    }

    /**
     * Restrict accepted libphonenumber number types.
     *
     * @param  list<PhoneNumberType|string|int>|Closure|null  $types
     */
    public function allowTypes(array|Closure|null $types): static
    {
        $this->allowTypes = $types;

        return $this;
    }

    /**
     * When true, FIXED_LINE_OR_MOBILE is not auto-accepted alongside MOBILE / FIXED_LINE.
     */
    public function strictTypes(bool|Closure $condition = true): static
    {
        $this->strictTypes = $condition;

        return $this;
    }

    /**
     * Use {@see PhoneNumberUtil::isValidNumberForRegion()} against the selected country.
     */
    public function validateForRegion(bool|Closure $condition = true): static
    {
        $this->validateForRegion = $condition;

        return $this;
    }

    public function nationalFormat(PhoneNationalFormat|string|Closure $format): static
    {
        $this->nationalFormat = $format;

        return $this;
    }

    /**
     * Digits-only `national` after successful normalize (opt-in; default remains NATIONAL).
     */
    public function nationalDigitsOnly(bool|Closure $condition = true): static
    {
        if ($condition instanceof Closure) {
            $this->nationalFormat = function () use ($condition): PhoneNationalFormat {
                return ((bool) $this->evaluate($condition))
                    ? PhoneNationalFormat::Digits
                    : PhoneNationalFormat::National;
            };

            return $this;
        }

        $this->nationalFormat = ((bool) $condition)
            ? PhoneNationalFormat::Digits
            : PhoneNationalFormat::National;

        return $this;
    }

    /**
     * @param  list<string>|Closure  $formats  `international`, `rfc3966`
     */
    public function includeFormats(array|Closure $formats): static
    {
        $this->includeFormats = $formats;

        return $this;
    }

    /**
     * @param  list<string>|Closure  $metadata  `carrier`, `geo`, `timezones`, `type`
     */
    public function includeMetadata(array|Closure $metadata): static
    {
        $this->includeMetadata = $metadata;

        return $this;
    }

    /**
     * @return list<string>|null
     */
    public function getAllowedCountryCodes(): ?array
    {
        $countries = $this->evaluate($this->countries);

        if ($countries === null) {
            return null;
        }

        return array_values(array_map(strtoupper(...), $countries));
    }

    /**
     * @return list<string>
     */
    public function getExceptCountryCodes(): array
    {
        $except = $this->evaluate($this->exceptCountries);

        return array_values(array_map(strtoupper(...), $except));
    }

    public function getDefaultCountryCode(): string
    {
        if ($this->shouldUseBrowserLocaleDefault()) {
            $detected = $this->getBrowserLocaleCountryCode();

            if ($detected !== null) {
                return $detected;
            }
        }

        $country = strtoupper((string) $this->evaluate($this->defaultCountry));
        $allowed = PhoneCountries::resolve($this->getAllowedCountryCodes(), $this->getExceptCountryCodes());

        if (in_array($country, $allowed, true)) {
            return $country;
        }

        return $allowed[0] ?? 'US';
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->searchable);
    }

    public function hasSuffixIcon(): bool
    {
        return (bool) $this->evaluate($this->showSuffixIcon);
    }

    public function showsInternationalPrefix(): bool
    {
        return (bool) $this->evaluate($this->showInternationalPrefix);
    }

    public function isMobileOnly(): bool
    {
        return (bool) $this->evaluate($this->mobileOnly);
    }

    public function isFixedLineOnly(): bool
    {
        return (bool) $this->evaluate($this->fixedLineOnly);
    }

    /**
     * @return list<PhoneNumberType>|null
     */
    public function getAllowedTypes(): ?array
    {
        $explicit = $this->evaluate($this->allowTypes);

        if (is_array($explicit) && $explicit !== []) {
            return PhoneNumberInsights::normalizeAllowedTypes($explicit);
        }

        if ($this->isMobileOnly() && $this->isFixedLineOnly()) {
            throw new InvalidArgumentException('PhoneField cannot require both mobileOnly() and fixedLineOnly().');
        }

        if ($this->isMobileOnly()) {
            return [PhoneNumberType::MOBILE];
        }

        if ($this->isFixedLineOnly()) {
            return [PhoneNumberType::FIXED_LINE];
        }

        return null;
    }

    public function usesStrictTypes(): bool
    {
        return (bool) $this->evaluate($this->strictTypes);
    }

    public function shouldValidateForRegion(): bool
    {
        return (bool) $this->evaluate($this->validateForRegion);
    }

    public function getNationalFormat(): PhoneNationalFormat
    {
        $format = $this->evaluate($this->nationalFormat);

        if ($format instanceof PhoneNationalFormat) {
            return $format;
        }

        return PhoneNationalFormat::tryFrom(strtolower((string) $format))
            ?? throw new InvalidArgumentException("Unsupported phone national format [{$format}].");
    }

    public function storesNationalDigitsOnly(): bool
    {
        return $this->getNationalFormat() === PhoneNationalFormat::Digits;
    }

    /**
     * @return list<string>
     */
    public function getIncludedFormats(): array
    {
        $formats = $this->evaluate($this->includeFormats);

        if (! is_array($formats)) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (mixed $format): string => strtolower((string) $format),
            $formats,
        )));
    }

    /**
     * @return list<string>
     */
    public function getIncludedMetadata(): array
    {
        $metadata = $this->evaluate($this->includeMetadata);

        if (! is_array($metadata)) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (mixed $item): string => strtolower((string) $item),
            $metadata,
        )));
    }

    public function getCountryPool(): string
    {
        return CountryRegistry::POOL_PHONE;
    }

    public function hasCustomCountryCodeFilter(): bool
    {
        return $this->getAllowedCountryCodes() !== null || $this->getExceptCountryCodes() !== [];
    }

    public function getCountryFilterKey(): ?string
    {
        if (! $this->hasCustomCountryCodeFilter()) {
            return null;
        }

        return CountryRegistryQueue::registerCountryFilter($this->getResolvedCountryCodes());
    }

    /**
     * @return list<string>
     */
    public function getResolvedCountryCodes(): array
    {
        return PhoneCountries::resolve(
            $this->getAllowedCountryCodes(),
            $this->getExceptCountryCodes(),
        );
    }

    /**
     * @return array{code: string, name: string, dial_code: string, flag_url: string}|null
     */
    public function getSelectedCountryMetadata(): ?array
    {
        $stateValue = $this->getState();
        $selectedCode = is_array($stateValue) && filled($stateValue['country'] ?? null)
            ? strtoupper((string) $stateValue['country'])
            : $this->getDefaultCountryCode();

        $allowed = $this->getResolvedCountryCodes();

        if (! in_array($selectedCode, $allowed, true)) {
            return null;
        }

        $metadata = PhoneCountries::metadata([$selectedCode], $this->getExceptCountryCodes(), $this->getLocale());

        return $metadata[0] ?? null;
    }

    /**
     * Country metadata for the Alpine dropdown. Built from {@see PhoneCountries::metadata()}
     * which memoizes per allowed/except code-set — only the resolved subset is serialized to @js().
     *
     * @return list<array{code: string, name: string, dial_code: string, flag_url: string}>
     */
    public function getCountriesMetadata(): array
    {
        $metadata = PhoneCountries::metadata(
            $this->getAllowedCountryCodes(),
            $this->getExceptCountryCodes(),
            $this->getLocale(),
        );

        if ($this->shouldSortCountriesByBrowserLocale()) {
            return PhoneCountries::sortWithPreferredFirst(
                $metadata,
                $this->getBrowserLocaleCountryCode(),
            );
        }

        return $metadata;
    }

    /**
     * @return array<string, array{label: string, image: string, description: string}>
     */
    public function getCountrySelectOptions(): array
    {
        return PhoneCountries::selectOptions(
            $this->getAllowedCountryCodes(),
            $this->getExceptCountryCodes(),
            $this->getLocale(),
        );
    }

    public function getDefaultSuffixIcon(): string|BackedEnum|Htmlable
    {
        $icon = config('filament-flex-fields.ui.phone_suffix_icon');

        if (is_string($icon) && filled($icon)) {
            return $icon;
        }

        return GravityIcon::Smartphone;
    }

    public function getSuffixIcon(): string|BackedEnum|Htmlable|null
    {
        if (! $this->hasSuffixIcon()) {
            return null;
        }

        $icon = $this->evaluate($this->suffixIcon);

        return $icon ?? $this->getDefaultSuffixIcon();
    }

    /**
     * Resolved input placeholder: custom value, else libphonenumber example for country/type, else lang default.
     */
    public function getPhonePlaceholder(): string
    {
        $custom = $this->getPlaceholder();

        if (filled($custom)) {
            return (string) $custom;
        }

        $example = $this->getExampleNationalPlaceholder();

        if (filled($example)) {
            return $example;
        }

        return (string) __('filament-flex-fields::default.phone.placeholder');
    }

    /**
     * National-formatted example for the current default country and type constraints.
     */
    public function getExampleNationalPlaceholder(): ?string
    {
        try {
            $util = PhoneNumberUtil::getInstance();
            $country = $this->getDefaultCountryCode();
            $allowedTypes = $this->getAllowedTypes();

            $example = match (true) {
                $allowedTypes !== null && count($allowedTypes) === 1 => $util->getExampleNumberForType($country, $allowedTypes[0]),
                $this->isMobileOnly() => $util->getExampleNumberForType($country, PhoneNumberType::MOBILE),
                $this->isFixedLineOnly() => $util->getExampleNumberForType($country, PhoneNumberType::FIXED_LINE),
                default => $util->getExampleNumber($country),
            };

            if ($example === null) {
                return null;
            }

            return PhoneNumberInsights::formatNationalDisplay(
                $example,
                $util,
                $this->storesNationalDigitsOnly(),
            );
        } catch (NumberParseException | InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>|string|null  $state
     * @return array<string, mixed>
     */
    public function normalizeState(mixed $state): array
    {
        $defaultCountry = $this->getDefaultCountryCode();
        $allowed = PhoneCountries::resolve($this->getAllowedCountryCodes(), $this->getExceptCountryCodes());
        $digitsOnly = $this->storesNationalDigitsOnly();

        if (is_string($state)) {
            $state = $this->parseStringState($state, $defaultCountry, $allowed);
        }

        if (! is_array($state)) {
            $state = [];
        }

        $country = strtoupper((string) ($state['country'] ?? $defaultCountry));

        if (! in_array($country, $allowed, true)) {
            $country = $defaultCountry;
        }

        $national = PhoneNumberInsights::nationalDigits((string) ($state['national'] ?? ''));
        $e164 = trim((string) ($state['e164'] ?? ''));
        $extra = [];

        if ($national !== '') {
            try {
                $util = PhoneNumberUtil::getInstance();
                $parsed = $util->parse($national, $country);

                if ($util->isValidNumber($parsed)) {
                    $e164 = $util->format($parsed, PhoneNumberFormat::E164);
                    $national = PhoneNumberInsights::formatNationalDisplay($parsed, $util, $digitsOnly);
                    $extra = PhoneNumberInsights::enrich(
                        $parsed,
                        $util,
                        $this->getIncludedFormats(),
                        $this->getIncludedMetadata(),
                        $this->getLocale(),
                    );
                } elseif ($e164 === '') {
                    $e164 = PhoneCountries::dialCode($country).$national;
                }
            } catch (NumberParseException) {
                if ($e164 === '' && $national !== '') {
                    $e164 = PhoneCountries::dialCode($country).$national;
                }
            }
        } else {
            $e164 = '';
        }

        return [
            'country' => $country,
            'national' => $national,
            'e164' => $e164,
            ...$extra,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function getPhoneValidationMessage(array $state): ?string
    {
        if (($state['national'] ?? '') === '') {
            return null;
        }

        // Surface mobile+fixed conflict early (also throws from getAllowedTypes()).
        $allowedTypes = $this->getAllowedTypes();

        try {
            $util = PhoneNumberUtil::getInstance();
            $raw = filled($state['e164'] ?? null)
                ? (string) $state['e164']
                : (string) $state['national'];
            $parsed = $util->parse($raw, (string) $state['country']);

            if (! $util->isValidNumber($parsed)) {
                return __('filament-flex-fields::default.validation.phone.invalid');
            }

            if ($this->shouldValidateForRegion() && ! $util->isValidNumberForRegion($parsed, (string) $state['country'])) {
                return __('filament-flex-fields::default.validation.phone.invalid_for_region');
            }

            $type = $util->getNumberType($parsed);

            if ($allowedTypes !== null && ! PhoneNumberInsights::typeIsAllowed($type, $allowedTypes, $this->usesStrictTypes())) {
                if ($this->isMobileOnly() && $this->getEvaluatedAllowTypes() === null) {
                    return __('filament-flex-fields::default.validation.phone.mobile_only');
                }

                if ($this->isFixedLineOnly() && $this->getEvaluatedAllowTypes() === null) {
                    return __('filament-flex-fields::default.validation.phone.fixed_line_only');
                }

                return __('filament-flex-fields::default.validation.phone.type_not_allowed');
            }
        } catch (NumberParseException) {
            return __('filament-flex-fields::default.validation.phone.invalid');
        }

        return null;
    }

    /**
     * @return list<PhoneNumberType|string|int>|null
     */
    protected function getEvaluatedAllowTypes(): ?array
    {
        $explicit = $this->evaluate($this->allowTypes);

        return is_array($explicit) && $explicit !== [] ? $explicit : null;
    }

    /**
     * @param  list<string>  $allowed
     * @return array<string, mixed>
     */
    protected function parseStringState(string $value, string $defaultCountry, array $allowed): array
    {
        $value = trim($value);
        $digitsOnly = $this->storesNationalDigitsOnly();

        if ($value === '') {
            return [
                'country' => $defaultCountry,
                'national' => '',
                'e164' => '',
            ];
        }

        try {
            $util = PhoneNumberUtil::getInstance();
            $parsed = $util->parse($value, $defaultCountry);
            $region = $util->getRegionCodeForNumber($parsed) ?? $defaultCountry;

            if (! in_array($region, $allowed, true)) {
                $region = $defaultCountry;
            }

            $base = [
                'country' => $region,
                'national' => PhoneNumberInsights::formatNationalDisplay($parsed, $util, $digitsOnly),
                'e164' => $util->format($parsed, PhoneNumberFormat::E164),
            ];

            if ($util->isValidNumber($parsed)) {
                return [
                    ...$base,
                    ...PhoneNumberInsights::enrich(
                        $parsed,
                        $util,
                        $this->getIncludedFormats(),
                        $this->getIncludedMetadata(),
                        $this->getLocale(),
                    ),
                ];
            }

            return $base;
        } catch (NumberParseException) {
            return [
                'country' => $defaultCountry,
                'national' => PhoneNumberInsights::nationalDigits($value),
                'e164' => '',
            ];
        }
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-phone-field',
            'fff-flex-text-input-field',
            'fff-phone-field--'.$this->getSize(),
            'fff-rounding-'.$this->getRounding(),
            'fff-flex-text-input-field--'.$this->getSize(),
            'fff-rounding-'.$this->getRounding(),
            'fff-phone-field--'.$this->getVariant(),
            'fff-flex-text-input-field--'.$this->getVariant(),
        ];
    }
}

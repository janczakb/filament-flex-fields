<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\StateCasts\CountryFieldStateCast;
use Bjanczak\FilamentFlexFields\Support\Countries;
use Bjanczak\FilamentFlexFields\Support\CountryRegistry;
use Bjanczak\FilamentFlexFields\Support\CountryRegistryQueue;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use InvalidArgumentException;

class CountryField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasPlaceholder;

    protected string $view = 'filament-flex-fields::forms.components.country-field';

    protected string|Closure $variant = 'primary';

    protected string|Closure|null $defaultCountry = null;

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $countries = null;

    /**
     * @var list<string>|Closure
     */
    protected array|Closure $exceptCountries = [];

    protected bool|Closure $searchable = true;

    protected bool|Closure $showCountryCode = false;

    protected bool|Closure $showDialCode = false;

    protected bool|Closure $browserLocaleDefault = false;

    protected bool|Closure $browserLocaleSortFirst = false;

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat', 'soft'], true)) {
            throw new InvalidArgumentException("Country field variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(function (CountryField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if ($component->isRequired() && blank($value)) {
                    $fail(__('validation.required', ['attribute' => $component->getLabel()]));

                    return;
                }

                if (blank($value)) {
                    return;
                }

                $submitted = strtoupper(trim((string) $value));
                $allowed = $component->getResolvedCountryCodes();

                if (! in_array($submitted, $allowed, true)) {
                    $fail(__('validation.in', ['attribute' => $component->getLabel()]));
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
            app(CountryFieldStateCast::class, ['field' => $this]),
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

    public function defaultCountry(string|Closure|null $countryCode): static
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

    public function showCountryCode(bool|Closure $condition = true): static
    {
        $this->showCountryCode = $condition;

        return $this;
    }

    public function showDialCode(bool|Closure $condition = true): static
    {
        $this->showDialCode = $condition;

        return $this;
    }

    public function searchable(bool|Closure $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
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
        return Countries::fromBrowserLocale($this->getResolvedCountryCodes());
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->searchable);
    }

    public function shouldShowCountryCode(): bool
    {
        return (bool) $this->evaluate($this->showCountryCode);
    }

    public function shouldShowDialCode(): bool
    {
        return (bool) $this->evaluate($this->showDialCode);
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

    /**
     * @return list<string>
     */
    public function getResolvedCountryCodes(): array
    {
        return Countries::resolve(
            $this->getAllowedCountryCodes(),
            $this->getExceptCountryCodes(),
        );
    }

    public function getDefaultCountryCode(): ?string
    {
        if ($this->shouldUseBrowserLocaleDefault()) {
            $detected = $this->getBrowserLocaleCountryCode();

            if ($detected !== null) {
                return $detected;
            }
        }

        $default = $this->evaluate($this->defaultCountry);

        if (blank($default)) {
            return null;
        }

        $country = strtoupper((string) $default);
        $allowed = $this->getResolvedCountryCodes();

        if (in_array($country, $allowed, true)) {
            return $country;
        }

        return $allowed[0] ?? null;
    }

    public function getCountryPool(): string
    {
        return CountryRegistry::POOL_ISO;
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
     * @return array{code: string, name: string, dial_code: string|null, flag_url: string}|null
     */
    public function getSelectedCountryMetadata(): ?array
    {
        $stateValue = $this->getState();
        $selectedCode = filled($stateValue) ? strtoupper((string) $stateValue) : null;

        if ($selectedCode === null) {
            return null;
        }

        $allowed = $this->getResolvedCountryCodes();

        if (! in_array($selectedCode, $allowed, true)) {
            return null;
        }

        $metadata = Countries::metadata([$selectedCode], $this->getExceptCountryCodes());

        return $metadata[0] ?? null;
    }

    /**
     * Country metadata for the Alpine dropdown. Built from {@see Countries::metadata()}
     * which memoizes per allowed/except code-set — only the resolved subset is serialized to @js().
     *
     * @return list<array{code: string, name: string, dial_code: string|null, flag_url: string}>
     */
    public function getCountriesMetadata(): array
    {
        $metadata = Countries::metadata(
            $this->getAllowedCountryCodes(),
            $this->getExceptCountryCodes(),
        );

        if ($this->shouldSortCountriesByBrowserLocale()) {
            return Countries::sortWithPreferredFirst(
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
        return Countries::selectOptions(
            $this->getAllowedCountryCodes(),
            $this->getExceptCountryCodes(),
        );
    }

    public function normalizeState(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        $country = strtoupper(trim((string) $state));
        $allowed = $this->getResolvedCountryCodes();

        if (in_array($country, $allowed, true)) {
            return $country;
        }

        $default = $this->getDefaultCountryCode();

        if ($default !== null && in_array($default, $allowed, true)) {
            return $default;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-country-field',
            'fff-flex-text-input-field',
            'fff-country-field--'.$this->getSize(),
            'fff-flex-text-input-field--'.$this->getSize(),
            'fff-country-field--'.$this->getVariant(),
            'fff-flex-text-input-field--'.$this->getVariant(),
        ];
    }
}

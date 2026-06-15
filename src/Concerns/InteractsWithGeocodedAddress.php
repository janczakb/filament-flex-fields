<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Bjanczak\FilamentFlexFields\Enums\MapboxSearchType;
use Closure;
use Filament\Forms\Components\Field;
use InvalidArgumentException;

trait InteractsWithGeocodedAddress
{
    public const string STORE_STRUCTURED = 'structured';

    public const string STORE_STRING = 'string';

    /**
     * @var list<string>|Closure
     */
    protected array|Closure $geocodedAddressFields = [];

    protected string|Closure $geocodedAddressStoreFormat = self::STORE_STRUCTURED;

    protected string|Closure $geocodedAddressStringFormat = '{place_name}';

    /**
     * @var list<string>|Closure
     */
    protected array|Closure $geocodedAddressRequiredFields = [];

    protected string|Closure|null $geocodedAddressMapboxToken = null;

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $geocodedAddressCountries = null;

    protected bool|Closure $streetAddressesOnly = false;

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $geocodedAddressSearchTypes = null;

    protected string|Closure|null $geocodedLanguage = null;

    protected int|Closure $geocodedMinSearchLength = 2;

    protected int|Closure $geocodedSearchDebounce = 350;

    protected function setUpGeocodedAddress(): void
    {
        $this->afterStateHydrated(function (Field $component, mixed $state): void {
            $component->state($component->hydrateToCanonical($state));
        });

        $this->dehydrateStateUsing(fn (Field $component, mixed $state): mixed => $component->dehydrateFromCanonical(
            is_array($state) ? $state : [],
        ));

        $this->rule(function (Field $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                $canonical = $component->hydrateToCanonical($value);

                if ($component->isRequired() && ! $component->hasAnyStoredValue($canonical)) {
                    $fail(__('validation.required', ['attribute' => $component->getLabel()]));

                    return;
                }

                if (! $component->hasAnyStoredValue($canonical)) {
                    return;
                }

                $message = $component->getValidationMessage($canonical);

                if ($message !== null) {
                    $fail($message);
                }
            };
        });
    }

    /**
     * @return list<string>
     */
    abstract protected function geocodedAddressFieldKeys(): array;

    abstract protected function geocodedAddressTranslationKey(): string;

    abstract protected function shouldInferPlaceNameFromCoordinates(): bool;

    /**
     * @param  list<string>|Closure  $fields
     */
    public function fields(array|Closure $fields): static
    {
        $this->geocodedAddressFields = $fields;

        return $this;
    }

    public function storeFormat(string|Closure $format): static
    {
        $format = $this->evaluate($format);

        if (! in_array($format, [self::STORE_STRUCTURED, self::STORE_STRING], true)) {
            throw new InvalidArgumentException("Geocoded address store format [{$format}] is not supported.");
        }

        $this->geocodedAddressStoreFormat = $format;

        return $this;
    }

    public function stringFormat(string|Closure $format): static
    {
        $this->geocodedAddressStringFormat = $format;

        return $this;
    }

    /**
     * @param  list<string>|Closure  $fields
     */
    public function requiredFields(array|Closure $fields): static
    {
        $this->geocodedAddressRequiredFields = $fields;

        return $this;
    }

    public function mapboxToken(string|Closure|null $token): static
    {
        $this->geocodedAddressMapboxToken = $token;

        return $this;
    }

    /**
     * @param  list<string>|Closure|null  $countries
     */
    public function countries(array|Closure|null $countries): static
    {
        $this->geocodedAddressCountries = $countries;

        return $this;
    }

    public function streetAddressesOnly(bool|Closure $condition = true): static
    {
        $this->streetAddressesOnly = $condition;

        return $this;
    }

    /**
     * @param  list<MapboxSearchType|string>|Closure|null  $types  null searches all Mapbox place types
     */
    public function searchTypes(array|Closure|null $types): static
    {
        $this->geocodedAddressSearchTypes = $types;

        return $this;
    }

    public function language(string|Closure|null $language): static
    {
        $this->geocodedLanguage = $language;

        return $this;
    }

    public function minSearchLength(int|Closure $length): static
    {
        $this->geocodedMinSearchLength = $length;

        return $this;
    }

    public function searchDebounce(int|Closure $milliseconds): static
    {
        $this->geocodedSearchDebounce = $milliseconds;

        return $this;
    }

    public function isStreetAddressesOnly(): bool
    {
        return (bool) $this->evaluate($this->streetAddressesOnly);
    }

    /**
     * @return list<string>|null
     */
    public function getSearchTypes(): ?array
    {
        if ($this->isStreetAddressesOnly()) {
            return [MapboxSearchType::Address->value];
        }

        return $this->normalizeSearchTypes($this->evaluate($this->geocodedAddressSearchTypes));
    }

    /**
     * @param  list<MapboxSearchType|string>|null  $types
     * @return list<string>|null
     */
    protected function normalizeSearchTypes(mixed $types): ?array
    {
        if ($types === null) {
            return null;
        }

        if (! is_array($types) || $types === []) {
            return null;
        }

        $allowed = MapboxSearchType::values();
        $normalized = [];

        foreach ($types as $type) {
            $value = $type instanceof MapboxSearchType ? $type->value : strtolower(trim((string) $type));

            if ($value === '') {
                continue;
            }

            if (! in_array($value, $allowed, true)) {
                throw new InvalidArgumentException("Mapbox search type [{$value}] is not supported.");
            }

            $normalized[] = $value;
        }

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function hasStreetAddress(array $state): bool
    {
        $street = $state['street'] ?? null;

        return is_string($street) && trim($street) !== '';
    }

    /**
     * @return list<string>
     */
    public function getFields(): array
    {
        $fields = $this->evaluate($this->geocodedAddressFields);

        if (! is_array($fields) || $fields === []) {
            throw new InvalidArgumentException('Geocoded address field requires at least one field via fields().');
        }

        $allowed = $this->geocodedAddressFieldKeys();
        $normalized = [];

        foreach ($fields as $field) {
            $field = (string) $field;

            if (! in_array($field, $allowed, true)) {
                throw new InvalidArgumentException("Geocoded address field [{$field}] is not supported.");
            }

            $normalized[] = $field;
        }

        return array_values(array_unique($normalized));
    }

    public function getStoreFormat(): string
    {
        return (string) $this->evaluate($this->geocodedAddressStoreFormat);
    }

    public function getStringFormat(): string
    {
        return (string) $this->evaluate($this->geocodedAddressStringFormat);
    }

    /**
     * @return list<string>
     */
    public function getRequiredFields(): array
    {
        $required = $this->evaluate($this->geocodedAddressRequiredFields);

        if (! is_array($required)) {
            return [];
        }

        return array_values(array_intersect($required, $this->getFields()));
    }

    public function getMapboxToken(): ?string
    {
        $token = $this->evaluate($this->geocodedAddressMapboxToken);

        if (is_string($token) && filled($token)) {
            return $token;
        }

        $configured = config('filament-flex-fields.mapbox.access_token');

        return is_string($configured) && filled($configured) ? $configured : null;
    }

    public function usesServerGeocoding(): bool
    {
        return (bool) config('filament-flex-fields.mapbox.use_server_proxy', true);
    }

    public function getGeocodeSearchUrl(): ?string
    {
        if (! $this->usesServerGeocoding()) {
            return null;
        }

        return route('filament-flex-fields.geocode.search');
    }

    public function getGeocodeReverseUrl(): ?string
    {
        if (! $this->usesServerGeocoding()) {
            return null;
        }

        return route('filament-flex-fields.geocode.reverse');
    }

    public function getLanguage(): string
    {
        $language = $this->evaluate($this->geocodedLanguage);

        if (is_string($language) && $language !== '') {
            return $language;
        }

        $configured = config('filament-flex-fields.mapbox.default_language');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $locale = (string) app()->getLocale();

        return $locale !== '' ? str_replace('_', '-', $locale) : 'en';
    }

    public function getMinSearchLength(): int
    {
        return max(0, (int) $this->evaluate($this->geocodedMinSearchLength));
    }

    public function getSearchDebounce(): int
    {
        return max(0, (int) $this->evaluate($this->geocodedSearchDebounce));
    }

    /**
     * @return list<string>|null
     */
    public function getCountries(): ?array
    {
        $countries = $this->evaluate($this->geocodedAddressCountries);

        if ($countries === null) {
            return null;
        }

        if (! is_array($countries)) {
            return null;
        }

        return array_values(array_map(fn (mixed $code): string => strtoupper((string) $code), $countries));
    }

    public function getRequiredValidationRule(): string|Closure
    {
        return 'nullable';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEmptyCanonicalState(): array
    {
        $state = [];

        foreach ($this->geocodedAddressFieldKeys() as $field) {
            $state[$field] = null;
        }

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function hydrateToCanonical(mixed $state): array
    {
        $canonical = $this->getEmptyCanonicalState();
        $coordinateFields = ['lat', 'lng'];

        if (is_string($state)) {
            $canonical['place_name'] = trim($state) !== '' ? trim($state) : null;

            return $this->normalizeCanonical($canonical);
        }

        if (! is_array($state)) {
            return $canonical;
        }

        foreach ($this->geocodedAddressFieldKeys() as $field) {
            if (! array_key_exists($field, $state)) {
                continue;
            }

            $value = $state[$field];

            if ($value === null || $value === '') {
                $canonical[$field] = null;

                continue;
            }

            if (in_array($field, $coordinateFields, true)) {
                $canonical[$field] = is_numeric($value) ? (float) $value : null;

                continue;
            }

            $canonical[$field] = trim((string) $value);
        }

        return $this->normalizeCanonical($canonical);
    }

    public function dehydrateFromCanonical(array $state): mixed
    {
        $canonical = $this->normalizeCanonical($state);

        if (! $this->hasAnyStoredValue($canonical)) {
            return $this->getStoreFormat() === self::STORE_STRING ? null : [];
        }

        if ($this->getStoreFormat() === self::STORE_STRING) {
            return $this->formatString($canonical);
        }

        $output = [];

        foreach ($this->getFields() as $field) {
            $output[$field] = $canonical[$field];
        }

        return $output;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function normalizeCanonical(array $state): array
    {
        $canonical = $this->getEmptyCanonicalState();
        $coordinateFields = ['lat', 'lng'];

        foreach ($this->geocodedAddressFieldKeys() as $field) {
            $value = $state[$field] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($field, $coordinateFields, true)) {
                $canonical[$field] = is_numeric($value) ? round((float) $value, 7) : null;

                continue;
            }

            $canonical[$field] = trim((string) $value);
        }

        if ($this->shouldInferPlaceNameFromCoordinates()) {
            if (
                filled($canonical['lat'] ?? null)
                && filled($canonical['lng'] ?? null)
                && blank($canonical['place_name'] ?? null)
            ) {
                $parts = array_filter([
                    $canonical['street'] ?? null,
                    $canonical['postcode'] ?? null,
                    $canonical['city'] ?? null,
                    $canonical['country_name'] ?? $canonical['country'] ?? null,
                ]);

                if ($parts !== []) {
                    $canonical['place_name'] = implode(', ', $parts);
                }
            }
        }

        return $canonical;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function hasAnyStoredValue(array $state): bool
    {
        foreach ($this->getFields() as $field) {
            if (filled($state[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function formatString(array $state): string
    {
        $format = $this->getStringFormat();

        return preg_replace_callback('/\{([a-z_]+)\}/', function (array $matches) use ($state): string {
            $key = $matches[1];
            $value = $state[$key] ?? '';

            return filled($value) ? (string) $value : '';
        }, $format) ?? '';
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function getSummaryLabel(array $state): ?string
    {
        $canonical = $this->normalizeCanonical($state);

        if (! $this->hasAnyStoredValue($canonical)) {
            return null;
        }

        if (filled($canonical['place_name'])) {
            return (string) $canonical['place_name'];
        }

        return $this->formatString($canonical) ?: null;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function getValidationMessage(array $state): ?string
    {
        $canonical = $this->normalizeCanonical($state);
        $translationKey = $this->geocodedAddressTranslationKey();

        foreach ($this->getRequiredFields() as $field) {
            if (blank($canonical[$field] ?? null)) {
                return __("filament-flex-fields::default.validation.{$translationKey}.required_field", [
                    'field' => __("filament-flex-fields::default.{$translationKey}.fields.{$field}"),
                ]);
            }
        }

        if (
            in_array('lat', $this->getFields(), true)
            && filled($canonical['lat'] ?? null)
            && ($canonical['lat'] < -90 || $canonical['lat'] > 90)
        ) {
            return __("filament-flex-fields::default.validation.{$translationKey}.invalid_latitude");
        }

        if (
            in_array('lng', $this->getFields(), true)
            && filled($canonical['lng'] ?? null)
            && ($canonical['lng'] < -180 || $canonical['lng'] > 180)
        ) {
            return __("filament-flex-fields::default.validation.{$translationKey}.invalid_longitude");
        }

        $countries = $this->getCountries();

        if (
            $countries !== null
            && in_array('country', $this->getFields(), true)
            && filled($canonical['country'] ?? null)
            && ! in_array(strtoupper((string) $canonical['country']), $countries, true)
        ) {
            return __("filament-flex-fields::default.validation.{$translationKey}.country_not_allowed");
        }

        if ($this->isStreetAddressesOnly() && $this->hasAnyStoredValue($canonical) && ! $this->hasStreetAddress($canonical)) {
            return __("filament-flex-fields::default.validation.{$translationKey}.street_address_required");
        }

        return null;
    }
}

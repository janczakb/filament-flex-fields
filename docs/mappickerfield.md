---
title: "MapPickerField"
description: Mapbox-powered address picker with geocoding search, draggable pin, and configurable stored fields.
---

![MapPickerField](/art/sc-6.webp)

[← Back to Table of Contents](/docs/index)

### Summary

Mapbox-powered address picker with geocoding search, draggable pin, and configurable stored fields. Supports both structured array output (coordinates, street, city, etc.) and formatted string output. Ideal for location selection in delivery, real estate, or event management apps.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField` |
| **State type (internal)** | Canonical array with keys from `ALL_FIELDS` |
| **Dehydrated state** | Subset of fields per `fields()`, or formatted string per `storeFormat()` |
| **FieldType** | `map_picker` |
| **Playground** | `map-picker` slug in Flex Fields playground |

Requires a Mapbox access token in `config('filament-flex-fields.mapbox.access_token')` or per-field via `mapboxToken()`.

---

### Basic usage

#### Structured Location (Coordinates + Address)
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField;

MapPickerField::make('location')
    ->label('Office address')
    ->fields(['lat', 'lng', 'street', 'city', 'postcode', 'country', 'place_name'])
    ->requiredFields(['lat', 'lng', 'city'])
    ->streetAddressesOnly()
    ->defaultCenter([52.2297, 21.0122])
    ->defaultZoom(12);
```

#### Formatted String Output
```php
MapPickerField::make('venue')
    ->storeFormat(MapPickerField::STORE_STRING)
    ->stringFormat('{street}, {city} ({country})')
    ->countries(['PL', 'DE']);
```

---

### State & validation

#### Canonical internal keys
`lat`, `lng`, `street`, `city`, `region`, `postcode`, `country`, `country_name`, `place_name`

#### Stored value
| `storeFormat` | Dehydrated value |
|---------------|------------------|
| `structured` (default) | Array with only keys from `fields()` |
| `string` | Single string from `stringFormat()` placeholders `{field}` |

#### Validation rules (built-in)
| Behaviour | Detail |
|-----------|--------|
| `required()` | At least one configured field must have a value |
| `requiredFields()` | Listed fields must be filled when any value present |
| Latitude/Longitude | Automatically validated when present in `fields()` |
| `streetAddressesOnly()` | Ensures a full street address is selected; area-level results are rejected |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `fields(array $fields)` | Setup | default | Which address parts to store and show |
| `storeFormat(string $format)` | Setup | `structured` | `structured` or `string` |
| `stringFormat(string $format)` | Setup | `{place_name}` | Template for string storage |
| `requiredFields(array $fields)` | Setup | `[]` | Fields required when partially filled |
| `mapboxToken(string $token)` | Setup | config | Override Mapbox access token |
| `defaultCenter(array $center)` | Setup | Warsaw | `[latitude, longitude]` |
| `defaultZoom(int $zoom)` | Setup | `12` | Initial map zoom `1`–`22` |
| `searchable(bool $condition)` | Setup | `true` | Show address search box |
| `countries(array $countries)` | Setup | `null` | Restrict results to ISO country codes |
| `streetAddressesOnly(bool $cond)` | Setup | `false` | Restrict to full street addresses only |
| `searchTypes(array $types)` | Setup | `null` | Limit results to specific place types |
| `language(string\|null $language)` | Setup | config / locale | Preferred results language (`null` = auto) |
| `minSearchLength(int $length)` | Setup | `2` | Characters before search fires |
| `searchDebounce(int $ms)` | Setup | `350` | Debounce after typing |
| `readOnly(bool $condition)` | Setup | `false` | Disable map interaction |

> **POI note:** `searchTypes([MapboxSearchType::Poi])` returns general Mapbox points of interest — not restaurant/cafe category filters (Geocoding v5 limitation).

---

### FlexField / Flex Forms schema config

Shared with [AddressAutocompleteField](/docs/addressautocompletefield) via `AppliesGeocodingStudioConfig` / `GeocodingSearchScope`:

| Config key | Behaviour |
|------------|-----------|
| `layout` | Studio: `map` (default) or `input` (switches block to address autocomplete) |
| `search_scope` | Preset: `all`, `address`, `cities`, `regions`, `countries`, `admin`, `poi`, `custom` |
| `search_area` + `countries` | Global vs ISO whitelist |
| `language_mode` + `language` | Auto locale vs manual code |
| `default_center` / `default_zoom` | Map-only defaults |
| `search_types` / `street_addresses_only` | Applied when `search_scope=custom` |

---

### Real-world examples

#### Pickup Point (POI only)
```php
use Bjanczak\FilamentFlexFields\Enums\MapboxSearchType;

MapPickerField::make('pickup_point')
    ->searchTypes([MapboxSearchType::Poi])
    ->fields(['place_name', 'lat', 'lng'])
    ->required();
```

#### City Selector
```php
MapPickerField::make('city')
    ->searchTypes([MapboxSearchType::Place])
    ->fields(['city', 'country'])
    ->storeFormat('string')
    ->stringFormat('{city}, {country}');
```

---

### Playground

`/admin/flex-fields-playground/map-picker`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [AddressAutocompleteField](/docs/addressautocompletefield) | Address search without a map |
| [CountryField](/docs/countryfield) | Simple country dropdown |
| [FlexTextInput](/docs/flextextinput) | Manual address entry |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-map-picker-field` | Root wrapper |
| `fff-map-picker` | Alpine root container |
| `fff-map-picker__search-wrap` | Search input + dropdown container |
| `fff-map-picker__dropdown-panel` | Autocomplete suggestions list |
| `fff-map-picker__selection-error` | Inline error for rejected results |
| `fff-map-picker__canvas` | Mapbox map container |
| `fff-map-picker__summary` | Selected address summary below map |

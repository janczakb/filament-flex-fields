# MapPickerField

![MapPickerField](/art/sc-6.png)

[← Back to Table of Contents](/docs/index)


### Summary

Mapbox-powered address picker with geocoding search, draggable pin, and configurable stored fields.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField` |
| **State type (internal)** | Canonical array with keys from `ALL_FIELDS` |
| **Dehydrated state** | Subset of fields per `fields()`, or formatted string per `storeFormat()` |
| **FieldType** | `map_picker` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField;

MapPickerField::make('location')
    ->label('Office address')
    ->fields(['lat', 'lng', 'street', 'city', 'postcode', 'country', 'place_name'])
    ->requiredFields(['lat', 'lng', 'city'])
    ->streetAddressesOnly()
    ->defaultCenter([52.2297, 21.0122])
    ->defaultZoom(12)
    ->countries(['PL', 'DE']);

MapPickerField::make('venue')
    ->storeFormat(MapPickerField::STORE_STRING)
    ->stringFormat('{street}, {city} ({country})');
```

Requires Mapbox token in `config('filament-flex-fields.mapbox.access_token')` or per-field `mapboxToken()`.

### State format

**Canonical internal keys** (`ALL_FIELDS`):

`lat`, `lng`, `street`, `city`, `region`, `postcode`, `country`, `country_name`, `place_name`

| `storeFormat` | Dehydrated value |
|---------------|------------------|
| `structured` (default) | Array with only keys from `fields()` |
| `string` | Single string from `stringFormat()` placeholders `{field}` |

On hydrate, strings set `place_name`; arrays merge into canonical state. Coordinates rounded to 7 decimal places.

### Validation

| Behaviour | Detail |
|-----------|--------|
| `required()` | At least one configured field must have a value |
| `requiredFields()` | Listed fields must be filled when any value present |
| Latitude | Must be −90…90 when `lat` is in `fields()` |
| Longitude | Must be −180…180 when `lng` is in `fields()` |
| `countries()` | `country` must be in whitelist when set |
| `streetAddressesOnly()` | `street` must be filled; cities, regions, and other non-address results are rejected |

### Configuration API

#### `fields(array|Closure $fields)`


Which address parts to store and show. Must include at least one valid key from `ALL_FIELDS`. Default: `lat`, `lng`, `street`, `city`, `postcode`, `country`, `place_name`.

```php
MapPickerField::make('field_name')
    ->fields(['value1', 'value2']);
```
#### `storeFormat(string|Closure $format)`


`MapPickerField::STORE_STRUCTURED` or `MapPickerField::STORE_STRING`.

```php
MapPickerField::make('field_name')
    ->storeFormat('value');
```
#### `stringFormat(string|Closure $format)`


Template for string storage. Placeholders: `{lat}`, `{lng}`, `{street}`, etc. Default: `{place_name}`.

```php
MapPickerField::make('field_name')
    ->stringFormat('value');
```
#### `requiredFields(array|Closure $fields)`


Fields required when the location is partially filled. Intersected with `fields()`.

```php
MapPickerField::make('field_name')
    ->requiredFields(['value1', 'value2']);
```
#### `mapboxToken(string|Closure|null $token)`


Override Mapbox access token. Falls back to config.

```php
MapPickerField::make('field_name')
    ->mapboxToken('value');
```
#### `defaultCenter(array|Closure $center)`


`[latitude, longitude]`. Default: Warsaw `[52.2297, 21.0122]`.

```php
MapPickerField::make('field_name')
    ->defaultCenter(['value1', 'value2']);
```
#### `defaultZoom(int|Closure $zoom)`


Initial map zoom `1`–`22`. Default: `12`.

```php
MapPickerField::make('field_name')
    ->defaultZoom(10);
```
#### `searchable(bool|Closure $condition = true)`


Address search box. Default: `true`.

```php
MapPickerField::make('field_name')
    ->searchable(true);
```
#### `countries(array|Closure|null $countries)`


Restrict geocoding results to ISO country codes. `null` = worldwide.

```php
MapPickerField::make('field_name')
    ->countries(['PL', 'DE', 'US']);
```
#### `streetAddressesOnly(bool|Closure $condition = true)`


Restrict search, map clicks, and pin drags to **full street addresses** only. Uses Mapbox `types=address`, filters autocomplete results client-side, and validates that `street` is present. Cities, regions, postcodes alone, and other area-level results cannot be selected. Default: `false`.

When enabled, this overrides `searchTypes()` and always uses `address`.

```php
MapPickerField::make('field_name')
    ->streetAddressesOnly(true);
```
#### `searchTypes(array|Closure|null $types)`


Limit Mapbox Geocoding API results to specific place types. Pass `null` (default) to search **all** supported types (addresses, POI, cities, regions, etc.).

Use `Bjanczak\FilamentFlexFields\Enums\MapboxSearchType` or string values: `country`, `region`, `postcode`, `district`, `place`, `locality`, `neighborhood`, `address`, `poi`.

```php
use Bjanczak\FilamentFlexFields\Enums\MapboxSearchType;

// Shops, restaurants, landmarks only
MapPickerField::make('pickup_point')
    ->searchTypes([MapboxSearchType::Poi]);

// Streets and POI
MapPickerField::make('location')
    ->searchTypes([MapboxSearchType::Address, MapboxSearchType::Poi]);

// Cities only
MapPickerField::make('city')
    ->searchTypes([MapboxSearchType::Place]);
```
#### `readOnly(bool|Closure $condition = true)`


Disable map interaction.

```php
MapPickerField::make('field_name')
    ->readOnly(true);
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getFields()` | `list&lt;string&gt;` | Configured field keys |
| `getStoreFormat()` | `string` | `structured` or `string` |
| `getStringFormat()` | `string` | String template |
| `getRequiredFields()` | `list&lt;string&gt;` | Required subset |
| `getMapboxToken()` | `string\|null` | Resolved token |
| `getDefaultCenter()` | `array&lt;0: float, 1: float&gt;` | Map center |
| `getDefaultZoom()` | `int` | Zoom level |
| `isSearchable()` | `bool` | Search enabled |
| `isStreetAddressesOnly()` | `bool` | Street-address restriction enabled |
| `getSearchTypes()` | `list&lt;string&gt;\|null` | Mapbox `types` filter (`null` = all types) |
| `getCountries()` | `list&lt;string&gt;\|null` | Country filter |
| `hasStreetAddress(array $state)` | `bool` | Whether canonical state has a street name |
| `getEmptyCanonicalState()` | `array` | All keys `null` |
| `hydrateToCanonical(mixed $state)` | `array` | Normalize incoming state |
| `dehydrateFromCanonical(array $state)` | `mixed` | Output for save |
| `normalizeCanonical(array $state)` | `array` | Merge + auto `place_name` |
| `hasAnyStoredValue(array $state)` | `bool` | Any configured field filled |
| `formatString(array $state)` | `string` | Apply string template |
| `getSummaryLabel(array $state)` | `string\|null` | Display label |
| `getValidationMessage(array $state)` | `string\|null` | First validation error |
| `getWrapperClasses()` | `array` | `fff-map-picker-field` |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `fields` | `fields()` |
| `store_format` | `storeFormat()` |
| `string_format` | `stringFormat()` |
| `required_fields` | `requiredFields()` |
| `mapbox_token` | `mapboxToken()` |
| `default_center` | `defaultCenter()` |
| `default_zoom` | `defaultZoom()` |
| `searchable` | `searchable()` |
| `countries` | `countries()` |
| `street_addresses_only` | `streetAddressesOnly()` |
| `search_types` | `searchTypes()` — e.g. `['poi']`, `['address', 'poi']`, or `null` for all |

### CSS classes

| Class | Role |
|-------|------|
| `fff-map-picker-field` | Root wrapper |
| `fff-map-picker` | Alpine root |
| `fff-map-picker__search-wrap` | Search input + dropdown container |
| `fff-map-picker__dropdown-panel` | Autocomplete suggestions list |
| `fff-map-picker__selection-error` | Inline error when a non-street result is rejected |
| `fff-map-picker__canvas` | Mapbox map container |
| `fff-map-picker__summary` | Selected address summary below map |

### Implementation notes

- Set `MAPBOX_ACCESS_TOKEN` or `filament-flex-fields.mapbox.access_token` before use.
- When `lat`/`lng` exist but `place_name` is empty, a summary is built from street, postcode, city, and country.
- Shared geocoding helpers live in `resources/js/support/mapbox-geocoding.js` (also used by `AddressAutocompleteField`).
- With `streetAddressesOnly()`, a map click or pin drag outside a resolvable street address shows an inline error and does not update state (the pin snaps back on drag).
- **Livewire:** uses `wire:ignore` + `$entangle` for state and `wire:key` over config props for remounts — see [Shared concepts → wire:ignore strategy](/docs/shared-concepts#livewire-wireignore-strategy-map--heavy-alpine-fields).

---

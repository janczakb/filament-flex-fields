# AddressAutocompleteField

[← Back to Table of Contents](index.md)


### Summary

Mapbox-powered **address search** without a map — combobox autocomplete with structured or string storage. Shares geocoding logic with `MapPickerField` but omits coordinates by default.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField` |
| **State type (internal)** | Canonical array with keys from `ALL_FIELDS` |
| **Dehydrated state** | Subset of fields per `fields()`, or formatted string per `storeFormat()` |
| **FieldType** | `address_autocomplete` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField;

AddressAutocompleteField::make('delivery_address')
    ->label('Delivery address')
    ->fields(['street', 'city', 'postcode', 'country', 'place_name'])
    ->requiredFields(['city'])
    ->streetAddressesOnly()
    ->countries(['PL'])
    ->size('md')
    ->variant('primary');

AddressAutocompleteField::make('billing_city')
    ->storeFormat(AddressAutocompleteField::STORE_STRING)
    ->stringFormat('{city}, {country_name}')
    ->variant('secondary')
    ->size('sm');
```

Requires Mapbox token in `config('filament-flex-fields.mapbox.access_token')` or per-field `mapboxToken()`.

### State format

**Canonical internal keys** (`ALL_FIELDS`):

`street`, `city`, `region`, `postcode`, `country`, `country_name`, `place_name`

No `lat`/`lng` — use `MapPickerField` when coordinates are required.

| `storeFormat` | Dehydrated value |
|---------------|------------------|
| `structured` (default) | Array with only keys from `fields()` |
| `string` | Single string from `stringFormat()` placeholders `{field}` |

On hydrate, strings set `place_name`; arrays merge into canonical state.

### Validation

| Behaviour | Detail |
|-----------|--------|
| `required()` | At least one configured field must have a value |
| `requiredFields()` | Listed fields must be filled when any value present |
| `countries()` | `country` must be in whitelist when set |
| `streetAddressesOnly()` | `street` must be filled; cities, regions, and other non-address results are rejected |
| `searchTypes()` | Limits autocomplete (and reverse geocode on `MapPickerField`) to given Mapbox place types; does not add extra validation beyond `requiredFields()` unless combined with `streetAddressesOnly()` |

### Configuration API

Shared with `MapPickerField` (via `InteractsWithGeocodedAddress`): `fields()`, `storeFormat()`, `stringFormat()`, `requiredFields()`, `mapboxToken()`, `countries()`, `streetAddressesOnly()`, `searchTypes()`.

#### `searchTypes(array|Closure|null $types)`


Limit Mapbox results to specific place types. `null` (default) = all types. See `MapboxSearchType` enum in MapPickerField docs.

```php
use Bjanczak\FilamentFlexFields\Enums\MapboxSearchType;

AddressAutocompleteField::make('store')
    ->searchTypes([MapboxSearchType::Poi]);
```

#### `minSearchLength(int|Closure $length)`


Sets the minimum number of characters the user must type before autocomplete suggestions are requested from Mapbox. Default is `2`.

```php
AddressAutocompleteField::make('address')
    ->minSearchLength(3); // Start search on 3+ characters
```

#### `searchDebounce(int|Closure $milliseconds)`


Sets the debounce delay in milliseconds before triggering the Mapbox API request after the user stops typing. Default is `350`.

```php
AddressAutocompleteField::make('address')
    ->searchDebounce(500); // 500ms typing delay
```
### Public helper methods

Same geocoded-address helpers as `MapPickerField` (`hydrateToCanonical`, `dehydrateFromCanonical`, `getSummaryLabel`, `isStreetAddressesOnly()`, `hasStreetAddress()`, etc.) — see MapPickerField table. Map-specific methods (`getDefaultCenter`, `getDefaultZoom`) are not available. Additional icon getters: `getPrefixIcon()`, `getClearIcon()`.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `fields` | `fields()` |
| `store_format` | `storeFormat()` |
| `string_format` | `stringFormat()` |
| `required_fields` | `requiredFields()` |
| `mapbox_token` | `mapboxToken()` |
| `searchable` | `searchable()` |
| `countries` | `countries()` |
| `street_addresses_only` | `streetAddressesOnly()` |
| `search_types` | `searchTypes()` — e.g. `['poi']` or `null` for all |
| `language` | `language()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `placeholder` | `placeholder()` |

Config defaults (`config/filament-flex-fields.php` → `ui`):

| Config key | Default |
|------------|---------|
| `address_autocomplete_size` | `md` |
| `address_autocomplete_variant` | `primary` |
| `address_autocomplete_prefix_icon` | `gravityui-map-pin` |
| `address_autocomplete_clear_icon` | `gravityui-circle-xmark` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-address-autocomplete-field` | Filament wrapper |
| `fff-address-autocomplete` | Alpine root (FlexTextInput shell) |
| `fff-address-autocomplete__search-wrap` | Input + dropdown positioning context |
| `fff-flex-text-input__shell` | Rounded input container (`primary` / `secondary` / `flat`) |
| `fff-map-picker__dropdown-panel` | Autocomplete suggestions (shared with MapPicker) |
| `fff-address-autocomplete__selection-error` | Inline error when a non-street result is rejected |

### Implementation notes

- Alpine component: `address-autocomplete` (built from `resources/js/components/address-autocomplete.js`).
- Shared geocoding helpers live in `resources/js/support/mapbox-geocoding.js` (also used by `MapPickerField`).
- UI matches `FlexTextInput` (`primary` / `secondary` / `flat` variants, Gravity UI icons).
- Autocomplete dropdown reuses MapPicker dropdown styles, including skeleton loading states.
- Unlike `MapPickerField`, coordinates are not stored; only address fields from `fields()` are dehydrated.
- **Livewire:** uses `wire:ignore` + `$entangle` for state and `wire:key` over config props for remounts — see [Shared concepts → wire:ignore strategy](shared-concepts.md#livewire-wireignore-strategy-map--heavy-alpine-fields).

---

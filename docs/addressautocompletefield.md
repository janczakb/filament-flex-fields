---
title: "AddressAutocompleteField"
description: Mapbox-powered address search without a map — combobox autocomplete with structured or string storage.
---

[← Back to Table of Contents](/docs/index)

### Summary

Mapbox-powered **address search** without a map — combobox autocomplete with structured or string storage. Shares geocoding logic with `MapPickerField` but omits coordinates by default.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField` |
| **State type** | `array` — canonical address structure |
| **Model cast** | `'address' => 'array'` · `'address' => 'string'` |
| **FieldType** | `address_autocomplete` |
| **Playground** | `address-autocomplete` slug in Flex Fields playground |

Requires Mapbox token in `config('filament-flex-fields.mapbox.access_token')` or per-field `mapboxToken()`.

---

### Basic usage

#### Structured address storage

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
```

#### String storage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField;

AddressAutocompleteField::make('billing_city')
    ->storeFormat(AddressAutocompleteField::STORE_STRING)
    ->stringFormat('{city}, {country_name}')
    ->variant('secondary')
    ->size('sm');
```

---

### State & storage

#### Canonical keys

The field uses a standard set of keys for internal state:
`street`, `city`, `region`, `postcode`, `country`, `country_name`, `place_name`.

#### Storage formats

| `storeFormat` | Dehydrated value |
|---------------|------------------|
| `structured` (default) | Array with only keys from `fields()` |
| `string` | Single string from `stringFormat()` placeholders `{field}` |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `fields(array\|Closure $fields)` | Setup | all | Address components to store in the array |
| `storeFormat(string\|Closure $format)` | Setup | `'structured'` | `structured` or `string` |
| `stringFormat(string\|Closure $format)` | Setup | auto | Template for string storage (e.g. `{street}, {city}`) |
| `requiredFields(array\|Closure $fields)` | Setup | `[]` | Fields that must be present in the result |
| `mapboxToken(string\|Closure\|null $token)` | Setup | config | Mapbox API access token |
| `countries(array\|Closure\|null $countries)` | Setup | `null` | Whitelist of ISO country codes |
| `streetAddressesOnly(bool\|Closure $condition = true)` | Setup | `false` | Reject results without a house number |
| `searchTypes(array\|Closure\|null $types)` | Setup | `null` | Limit results to types (e.g. `poi`, `place`) |
| `minSearchLength(int\|Closure $length)` | Setup | `2` | Minimum characters to trigger search |
| `searchDebounce(int\|Closure $ms)` | Setup | `350` | Milliseconds to wait after typing |
| `language(string\|Closure\|null $language)` | Setup | config | Preferred results language |
| `prefixIcon(...)` | Setup | `MapPin` | Custom leading icon |
| `clearIcon(...)` | Setup | `CircleXmark` | Custom clear button icon |
| `variant(string\|Closure $variant)` | Setup | `'primary'` | Visual style: `primary`, `secondary`, `flat` |
| `size(string\|ControlSize\|Closure $size)` | Setup | `'md'` | Control size: `sm`, `md`, `lg` |
| `rounding(string\|Closure\|null $rounding)` | Setup | config | Border radius token |

#### `searchTypes()`

Limit results to specific Mapbox place types:

```php
use Bjanczak\FilamentFlexFields\Enums\MapboxSearchType;

AddressAutocompleteField::make('venue')
    ->searchTypes([MapboxSearchType::Poi]);
```

---

### Real-world examples

#### Simple city picker

```php
AddressAutocompleteField::make('city')
    ->searchTypes(['place'])
    ->fields(['city', 'country_name'])
    ->storeFormat('string')
    ->stringFormat('{city}, {country_name}');
```

#### Full shipping address

```php
AddressAutocompleteField::make('shipping_address')
    ->streetAddressesOnly()
    ->fields(['street', 'city', 'postcode', 'country'])
    ->requiredFields(['street', 'city', 'postcode'])
    ->countries(['US', 'CA']);
```

---

### Playground

`/admin/flex-fields-playground/address-autocomplete`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [MapPickerField](/docs/mappickerfield) | When you also need to store coordinates (lat/lng) |
| [CountryField](/docs/countryfield) | For picking a country only |
| [FlexTextInput](/docs/flextextinput) | For manual address entry without Mapbox |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-address-autocomplete-field` | Root wrapper |
| `fff-address-autocomplete__search-wrap` | Input + dropdown container |
| `fff-map-picker__dropdown-panel` | Suggestions list |
| `fff-address-autocomplete__selection-error` | Validation error overlay |

---

### Performance

| Mechanism | What it does |
|-----------|--------------|
| **Debounced Search** | Prevents excessive API calls while typing |
| **Lazy JS** | Mapbox geocoding logic is loaded only when needed |
| **Wire:ignore** | Uses `wire:ignore` to prevent Livewire from disrupting Alpine state |

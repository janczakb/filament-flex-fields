---
title: "CountryField"
description: Searchable country picker with circle flags and ISO 3166-1 alpha-2 code storage.
---

[← Back to Table of Contents](/docs/index)

### Summary

Searchable country picker with circle flags. Stores a single **ISO 3166-1 alpha-2** code. Uses the full country list (~255 codes) with optimized flag assets.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField` |
| **State type** | `string\|null` — ISO alpha-2 country code |
| **Model cast** | `'country_code' => 'string'` |
| **FieldType** | `country` |
| **Playground** | `country-field` slug in Flex Fields playground |

---

### Basic usage

#### Standard country picker

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;

CountryField::make('country')
    ->label('Country')
    ->defaultCountry('PL')
    ->required();
```

#### Whitelist and browser detection

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;

CountryField::make('shipping_country')
    ->countries(['PL', 'DE', 'GB', 'US', 'FR'])
    ->exceptCountries(['RU'])
    ->showCountryCode()
    ->browserLocaleDefault()
    ->browserLocaleSortFirst();
```

---

### State & validation

#### Stored value

State is a single **uppercase ISO 3166-1 alpha-2 string**.

```php
$record->country_code; // string|null — e.g. "PL", "US", "DE"
```

#### Validation rules

Built-in validation ensures the submitted code is in the resolved country list.

| Rule | Detail |
|------|--------|
| `nullable` | Always (unless `required()`) |
| `in(...)` | Value must match a configured ISO alpha-2 code |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `countries(array\|Closure\|null $countries)` | Setup | `null` | Whitelist of ISO alpha-2 codes |
| `exceptCountries(array\|Closure $countries)` | Setup | `[]` | Blacklist of ISO alpha-2 codes |
| `defaultCountry(string\|Closure\|null $countryCode)` | Setup | `'PL'` | Fallback ISO code |
| `browserLocaleDefault(bool\|Closure $condition = true)` | Setup | `false` | Pre-select from browser locale |
| `browserLocaleSortFirst(bool\|Closure $condition = true)` | Setup | `false` | Sort browser country to top of list |
| `showCountryCode(bool\|Closure $condition = true)` | Setup | `false` | Show ISO code (e.g. `PL`) next to name |
| `showDialCode(bool\|Closure $condition = true)` | Setup | `false` | Show international dial code (e.g. `+48`) |
| `searchable(bool\|Closure $condition = true)` | Setup | `true` | Show search input in dropdown |
| `variant(string\|Closure $variant)` | Setup | `'primary'` | Visual style: `primary`, `secondary`, `flat`, `soft` |
| `size(string\|ControlSize\|Closure $size)` | Setup | `'md'` | Control size: `sm`, `md`, `lg` |
| `rounding(string\|Closure\|null $rounding)` | Setup | config | Border radius token |

#### `countries()` / `exceptCountries()`

Limit the list to specific markets:

```php
CountryField::make('country')
    ->countries(['US', 'CA', 'MX']);
```

#### `showDialCode()`

Useful for address forms where phone context is helpful:

```php
CountryField::make('country')->showDialCode();
```

---

### Real-world examples

#### Shipping address form

```php
public static function form(Form $form): Form
{
    return $form->schema([
        CountryField::make('shipping_country')
            ->label('Delivery Country')
            ->countries(config('app.shipping_countries'))
            ->browserLocaleDefault()
            ->required(),
    ]);
}
```

#### Multi-language support

Country names are automatically translated based on the application locale using the package's built-in translation files.

---

### Playground

`/admin/flex-fields-playground/country-field`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [PhoneField](/docs/phonefield) | For picking a country as part of a phone number input |
| [TimezoneField](/docs/timezonefield) | For picking a timezone instead of a country |
| [AddressAutocompleteField](/docs/addressautocompletefield) | For full address search with Mapbox |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-country-field` | Root wrapper |
| `fff-country-field--{sm\|md\|lg}` | Size modifier |
| `fff-country-field__trigger` | Picker button |
| `fff-country-field__flag` | Circle flag image |
| `fff-country-field__menu` | Dropdown panel |
| `fff-country-field__search` | Search input |

---

### Performance

| Mechanism | What it does |
|-----------|--------------|
| **Flag CDN** | Optimized SVG flags served via a fast CDN for minimal bundle size |
| **Memoized Metadata** | Country lists and translations are cached per-request |
| **Teleport** | Uses `x-teleport="body"` to avoid overflow clipping in modals |

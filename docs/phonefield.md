---
title: "PhoneField"
---

![PhoneField](/art/sc-23.png)

[← Back to Table of Contents](/docs/index)


### Summary

International phone input with searchable country picker, libphonenumber validation, and structured state (`country`, `national`, `e164`).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField` |
| **State type** | `array&lt;country: string, national: string, e164: string&gt;` |
| **FieldType** | `phone` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;

PhoneField::make('phone')
    ->label('Mobile number')
    ->defaultCountry('PL')
    ->required();

PhoneField::make('contact_phone')
    ->countries(['PL', 'DE', 'GB', 'US'])
    ->mobileOnly()
    ->browserLocaleDefault()
    ->browserLocaleSortFirst();
```

Load from an E.164 string on edit:

```php
PhoneField::make('phone')
    ->afterStateHydrated(function (PhoneField $component, mixed $state): void {
        if (is_string($state) && filled($state)) {
            $component->state($component->normalizeState($state));
        }
    });
```

Store only E.164 in the database:

```php
PhoneField::make('phone')
    ->dehydrateStateUsing(fn (array $state): ?string => filled($state['e164'] ?? null)
        ? $state['e164']
        : null);
```

### State format

| Key | Description | Example |
|-----|-------------|---------|
| `country` | ISO 3166-1 alpha-2 region code | `PL` |
| `national` | National number digits only | `512345678` |
| `e164` | E.164 format when valid | `+48512345678` |

On hydrate and dehydrate, `normalizeState()` runs automatically. A plain `string` state (e.g. `+48 512 345 678`) is parsed on hydrate.

### Validation

| Behaviour | Detail |
|-----------|--------|
| Built-in | Custom rule on normalized array; uses libphonenumber |
| `required()` | `national` must not be empty |
| `mobileOnly()` | Number type must be mobile (or fixed-line-or-mobile) |
| `fixedLineOnly()` | Number type must be fixed line (or fixed-line-or-mobile) |
| Filament `required` rule | Overridden to `nullable` — validation handled by custom rule |

> Do not combine `mobileOnly()` and `fixedLineOnly()` on the same field — throws `InvalidArgumentException`.

### Configuration API

#### `variant(string|Closure $variant)`


Visual style shared with FlexTextInput. Values: `primary` (default), `secondary`, `flat`.

```php
->variant('secondary')
```

#### `size(string|ControlSize|Closure $size)`


Control height. See [Control size](/docs/shared-concepts). Default: `md`.

```php
PhoneField::make('field_name')
    ->size('md');
```
#### `defaultCountry(string|Closure $countryCode)`


ISO country code when no country is selected. Default: `PL`. Falls back to first allowed country or `US`.

```php
PhoneField::make('field_name')
    ->defaultCountry('value');
```
#### `countries(array|Closure|null $countries)`


Whitelist of ISO codes. `null` = all countries (minus `exceptCountries`).

```php
->countries(['PL', 'DE', 'FR'])
```

#### `exceptCountries(array|Closure $countries)`


Blacklist applied after the whitelist. Default: `[]`.

```php
PhoneField::make('field_name')
    ->exceptCountries(['value1', 'value2']);
```
#### `searchable(bool|Closure $condition = true)`


Show search input in the country dropdown. Default: `true`.

```php
PhoneField::make('field_name')
    ->searchable(true);
```
#### `suffixIcon(string|BackedEnum|Htmlable|Closure|bool|null $icon = null)`


Trailing icon. Pass `false` to hide. Pass an icon name to set a custom icon. Default: config `filament-flex-fields.ui.phone_suffix_icon` or `GravityIcon::Smartphone`.

```php
->suffixIcon(false)
->suffixIcon('heroicon-o-device-phone-mobile')
```

#### `internationalPrefix(bool|Closure $condition = true)`


Show dial code prefix next to the national input. Default: `true`.

```php
PhoneField::make('field_name')
    ->internationalPrefix(true);
```
#### `mobileOnly(bool|Closure $condition = true)`


Restrict to mobile numbers.

```php
PhoneField::make('field_name')
    ->mobileOnly(true);
```
#### `fixedLineOnly(bool|Closure $condition = true)`


Restrict to fixed-line numbers.

```php
PhoneField::make('field_name')
    ->fixedLineOnly(true);
```
#### `browserLocaleDefault(bool|Closure $condition = true)`


When enabled and national number is empty, pre-select country from `Accept-Language` / browser locale.

```php
PhoneField::make('field_name')
    ->browserLocaleDefault(true);
```
#### `browserLocaleSortFirst(bool|Closure $condition = true)`


Sort country list with browser locale country first.

```php
PhoneField::make('field_name')
    ->browserLocaleSortFirst(true);
```
#### `placeholder(string|Closure|null $placeholder)`


Inherited from Filament `HasPlaceholder`.

```php
PhoneField::make('field_name')
    ->placeholder('Enter value...');
```
#### `readOnly(bool|Closure $condition = true)`


Inherited from Filament `CanBeReadOnly`.

```php
PhoneField::make('field_name')
    ->readOnly(true);
```
#### `focusOutline(bool|Closure $condition = true)`


Inherited from `HasFieldFocusOutline`.

```php
PhoneField::make('field_name')
    ->focusOutline(true);
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getVariant()` | `string` | Resolved variant |
| `getAllowedCountryCodes()` | `list&lt;string&gt;\|null` | Whitelist or `null` |
| `getExceptCountryCodes()` | `list&lt;string&gt;` | Blacklist |
| `getDefaultCountryCode()` | `string` | Effective default region |
| `isSearchable()` | `bool` | Country search enabled |
| `hasSuffixIcon()` | `bool` | Suffix icon visible |
| `showsInternationalPrefix()` | `bool` | Dial prefix visible |
| `isMobileOnly()` | `bool` | Mobile validation |
| `isFixedLineOnly()` | `bool` | Fixed-line validation |
| `shouldUseBrowserLocaleDefault()` | `bool` | Browser locale default |
| `shouldSortCountriesByBrowserLocale()` | `bool` | Browser locale sort |
| `getBrowserLocaleCountryCode()` | `string\|null` | Detected locale country |
| `getCountriesMetadata()` | `list&lt;array&gt;` | `code`, `name`, `dial_code`, `flag_url` |
| `getCountrySelectOptions()` | `array` | Options for internal select |
| `getDefaultSuffixIcon()` | `string\|BackedEnum\|Htmlable` | Default suffix icon |
| `getSuffixIcon()` | `string\|BackedEnum\|Htmlable\|null` | Resolved suffix icon |
| `normalizeState(mixed $state)` | `array` | Canonical `{country, national, e164}` |
| `getPhoneValidationMessage(array $state)` | `string\|null` | Error message or `null` |
| `getWrapperClasses()` | `list&lt;string&gt;` | CSS class list |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `variant` | `variant()` |
| `default_country` | `defaultCountry()` |
| `countries` | `countries()` |
| `except_countries` | `exceptCountries()` |
| `searchable` | `searchable()` |
| `suffix_icon` | `suffixIcon()` |
| `international_prefix` | `internationalPrefix()` |
| `mobile_only` | `mobileOnly()` |
| `fixed_line_only` | `fixedLineOnly()` |
| `browser_locale_default` | `browserLocaleDefault()` |
| `browser_locale_sort_first` | `browserLocaleSortFirst()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-phone-field` | Root wrapper |
| `fff-phone-field--{sm\|md\|lg}` | Size modifier |
| `fff-phone-field__country-trigger` | Country picker button |
| `fff-phone-field__country-menu` | Teleported dropdown (`is-positioned` when open) |
| `fff-phone-field__control` | National number input area |
| `fff-phone-field__dial-prefix` | International prefix display |

Shares FlexTextInput shell classes (`fff-flex-text-input-field`, variant modifiers).

### Implementation notes

- Country dropdown uses `x-teleport="body"` to avoid overflow clipping.
- Depends on `giggsey/libphonenumber-for-php` for parsing and validation.
- Empty national number dehydrates to `e164: ''` regardless of partial dial prefix.

---

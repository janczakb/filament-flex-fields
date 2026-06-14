# CountryField

[← Back to Table of Contents](index.md)


### Summary

Searchable country picker with circle flags. Stores a single **ISO 3166-1 alpha-2** code. Uses the full country list (~255 codes) — broader than `PhoneField`’s libphonenumber region set — with the same flag assets as the phone country dropdown.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField` |
| **State type** | `string\|null` — ISO alpha-2 country code |
| **FieldType** | `country` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;

CountryField::make('country')
    ->label('Country')
    ->defaultCountry('PL')
    ->required();

CountryField::make('shipping_country')
    ->countries(['PL', 'DE', 'GB', 'US'])
    ->exceptCountries(['RU', 'BY'])
    ->showCountryCode()
    ->browserLocaleDefault()
    ->browserLocaleSortFirst();
```

### State format

| Value | Description | Example |
|-------|-------------|---------|
| Selected country | Uppercase ISO 3166-1 alpha-2 | `PL`, `US`, `DE` |
| Empty | `null` when cleared or invalid | `null` |

On hydrate and dehydrate, `normalizeState()` uppercases and validates against the resolved country list. Invalid stored values fall back to `defaultCountry()` when allowed, otherwise `null`.

### Validation

| Behaviour | Detail |
|-----------|--------|
| Built-in | Custom rule — submitted code must be in resolved list |
| `required()` | Value must not be blank |
| Filament `required` rule | Overridden to `nullable` — validation handled by custom rule |

### Configuration API

#### `variant(string|Closure $variant)`


Visual style shared with FlexTextInput. Values: `primary` (default), `secondary`, `flat`.

```php
CountryField::make('field_name')
    ->variant('primary');
```
#### `size(string|ControlSize|Closure $size)`


Control height. See [Control size](shared-concepts.md). Default: `md` (config: `filament-flex-fields.ui.country_size`).

```php
CountryField::make('field_name')
    ->size('md');
```
#### `defaultCountry(string|Closure|null $countryCode)`


ISO code when no value is selected. Default: `PL` (config: `filament-flex-fields.ui.country_default_country`). Falls back to first allowed country when the default is not in the list.

```php
CountryField::make('field_name')
    ->defaultCountry('value');
```
#### `countries(array|Closure|null $countries)`


Whitelist of ISO codes. `null` = all countries from the built-in ISO list (minus `exceptCountries`).

```php
->countries(['PL', 'DE', 'FR'])
```

#### `exceptCountries(array|Closure $countries)`


Blacklist applied after the whitelist. Default: `[]`.

```php
CountryField::make('field_name')
    ->exceptCountries(['value1', 'value2']);
```
#### `searchable(bool|Closure $condition = true)`


Show search input in the dropdown. Default: `true`. Search matches country name, code, and dial code.

```php
CountryField::make('field_name')
    ->searchable(true);
```
#### `showCountryCode(bool|Closure $condition = true)`


Show ISO code next to the country name in the trigger and menu. Default: `false`.

```php
CountryField::make('field_name')
    ->showCountryCode(true);
```
#### `showDialCode(bool|Closure $condition = true)`


Show international dial code when available (libphonenumber-supported regions). Default: `false`.

```php
CountryField::make('field_name')
    ->showDialCode(true);
```
#### `browserLocaleDefault(bool|Closure $condition = true)`


When enabled and state is empty on hydrate, pre-select country from `Accept-Language` (server) or `navigator.languages` (client Alpine).

```php
CountryField::make('field_name')
    ->browserLocaleDefault(true);
```
#### `browserLocaleSortFirst(bool|Closure $condition = true)`


Sort country list with browser-detected country first.

```php
CountryField::make('field_name')
    ->browserLocaleSortFirst(true);
```
#### `placeholder(string|Closure|null $placeholder)`


Inherited from Filament `HasPlaceholder`. Default translation: `filament-flex-fields::country.placeholder`.

```php
CountryField::make('field_name')
    ->placeholder('Enter value...');
```
#### `readOnly(bool|Closure $condition = true)`


Inherited from Filament `CanBeReadOnly`.

```php
CountryField::make('field_name')
    ->readOnly(true);
```
#### `focusOutline(bool|Closure $condition = true)`


Inherited from `HasFieldFocusOutline`. Default: `false`.

```php
CountryField::make('field_name')
    ->focusOutline(true);
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getVariant()` | `string` | Resolved variant |
| `getAllowedCountryCodes()` | `list<string>\|null` | Whitelist or `null` |
| `getExceptCountryCodes()` | `list<string>` | Blacklist |
| `getResolvedCountryCodes()` | `list<string>` | Effective allowed codes |
| `getDefaultCountryCode()` | `string\|null` | Effective default |
| `isSearchable()` | `bool` | Search enabled |
| `shouldShowCountryCode()` | `bool` | ISO code visible |
| `shouldShowDialCode()` | `bool` | Dial code visible |
| `shouldUseBrowserLocaleDefault()` | `bool` | Browser locale default |
| `shouldSortCountriesByBrowserLocale()` | `bool` | Browser locale sort |
| `getBrowserLocaleCountryCode()` | `string\|null` | Detected locale country |
| `getCountriesMetadata()` | `list<array>` | `code`, `name`, `dial_code`, `flag_url` |
| `getCountrySelectOptions()` | `array` | Options map for internal select |
| `normalizeState(mixed $state)` | `string\|null` | Canonical country code |
| `getWrapperClasses()` | `list<string>` | CSS class list |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `variant` | `variant()` |
| `default_country` | `defaultCountry()` |
| `countries` | `countries()` |
| `except_countries` | `exceptCountries()` |
| `searchable` | `searchable()` |
| `browser_locale_default` | `browserLocaleDefault()` |
| `browser_locale_sort_first` | `browserLocaleSortFirst()` |
| `show_country_code` | `showCountryCode()` |
| `show_dial_code` | `showDialCode()` |

Config defaults (`config/filament-flex-fields.php` → `ui`):

| Config key | Default |
|------------|---------|
| `country_size` | `md` |
| `country_variant` | `primary` |
| `country_default_country` | `PL` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-country-field` | Root wrapper |
| `fff-country-field--{sm\|md\|lg}` | Size modifier |
| `fff-country-field__trigger` | Picker button (flag + label) |
| `fff-country-field__flag` | Circle flag image |
| `fff-country-field__menu` | Teleported dropdown (`is-positioned` when open) |
| `fff-country-field__search` | Dropdown search input |
| `fff-country-field__option` | Menu row |

Shares FlexTextInput shell classes (`fff-flex-text-input-field`, variant modifiers).

### Implementation notes

- Country names use `filament-flex-fields::countries.{CODE}` translations when published, then `locale_get_display_region()` as fallback.
- Flag URLs reuse `PhoneCountries::flagUrl()` (same CDN as `PhoneField`).
- Dropdown uses `x-teleport="body"` to avoid overflow clipping.
- SSR label is rendered server-side; Alpine `displayReady` swaps to live state without layout flash on reload.
- Dial codes are only shown for regions supported by libphonenumber — not every ISO code has a dial code.

---

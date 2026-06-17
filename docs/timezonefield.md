---
title: "TimezoneField"
---

[← Back to Table of Contents](/docs/index)


### Summary

Searchable IANA timezone picker with a **Gravity UI clock** icon on the trigger and in menu rows. Stores a single timezone identifier string.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField` |
| **State type** | `string\|null` — IANA timezone identifier |
| **FieldType** | — (use directly in Filament forms; not yet mapped in `FieldType`) |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

TimezoneField::make('timezone')
    ->label('Timezone')
    ->defaultTimezone('Europe/Warsaw')
    ->showOffset()
    ->required();

TimezoneField::make('user_timezone')
    ->timezones(['Europe/Warsaw', 'UTC', 'America/New_York'])
    ->browserTimezoneDefault()
    ->browserTimezoneSortFirst()
    ->prefixIcon(GravityIcon::Clock); // default when omitted
```

### State format

| Value | Description | Example |
|-------|-------------|---------|
| Selected timezone | IANA identifier | `Europe/Warsaw`, `UTC`, `America/New_York` |
| Empty | `null` when cleared or invalid | `null` |

On hydrate and dehydrate, `normalizeState()` trims and validates against the resolved timezone list. Invalid stored values fall back to `defaultTimezone()` when allowed, otherwise `null`.

### Validation

| Behaviour | Detail |
|-----------|--------|
| Built-in | Custom rule — submitted identifier must be in resolved list |
| `required()` | Value must not be blank |
| Filament `required` rule | Overridden to `nullable` — validation handled by custom rule |

### Configuration API

#### `variant(string|Closure $variant)`


Visual style shared with FlexTextInput. Values: `primary` (default), `secondary`, `flat`.

```php
TimezoneField::make('field_name')
    ->variant('primary');
```
#### `size(string|ControlSize|Closure $size)`


Control height. See [Control size](/docs/shared-concepts). Default: `md`.

```php
TimezoneField::make('field_name')
    ->size('md');
```
#### `defaultTimezone(string|Closure|null $timezone)`


IANA identifier when no value is selected. Falls back to first allowed timezone when the default is not in the list.

```php
TimezoneField::make('field_name')
    ->defaultTimezone('Europe/Warsaw');
```
#### `timezones(array|Closure|null $timezones)`


Whitelist of IANA identifiers. `null` = full PHP `timezone_identifiers_list()` (~419 zones, minus `exceptTimezones`).

```php
->timezones(['Europe/Warsaw', 'UTC', 'America/Chicago'])
```

#### `exceptTimezones(array|Closure $timezones)`


Blacklist applied after the whitelist. Default: `[]`.

```php
TimezoneField::make('field_name')
    ->exceptTimezones(['UTC', 'GMT']);
```
#### `searchable(bool|Closure $condition = true)`


Show search input in the dropdown. Default: `true`. Search matches identifier, region, and UTC offset.

```php
TimezoneField::make('field_name')
    ->searchable(true);
```
#### `showOffset(bool|Closure $condition = true)`


Show UTC offset badge (e.g. `UTC+02:00`) in trigger and menu. Default: `true`. Offset reflects **current** DST rules.

```php
TimezoneField::make('field_name')
    ->showOffset(true);
```
#### `prefixIcon(string|BackedEnum|Htmlable|Closure|null $icon)`


Leading icon on trigger and menu rows. Default: `GravityIcon::Clock` (`gravityui-clock`).

```php
->prefixIcon('heroicon-o-clock')
```

#### `browserTimezoneDefault(bool|Closure $condition = true)`


When enabled and state is empty on hydrate, pre-select timezone from:

1. **Client:** `Intl.DateTimeFormat().resolvedOptions().timeZone` (Alpine on init)
2. **Server:** `config('app.timezone')` when not `UTC` and identifier is allowed

```php
TimezoneField::make('field_name')
    ->browserTimezoneDefault(true);
```
#### `browserTimezoneSortFirst(bool|Closure $condition = true)`


Sort timezone list with browser-detected timezone first.

```php
TimezoneField::make('field_name')
    ->browserTimezoneSortFirst(true);
```
#### `placeholder(string|Closure|null $placeholder)`


Inherited from Filament `HasPlaceholder`. Default translation: `filament-flex-fields::timezone.placeholder`.

```php
TimezoneField::make('field_name')
    ->placeholder('Enter value...');
```
#### `readOnly(bool|Closure $condition = true)`


Inherited from Filament `CanBeReadOnly`.

```php
TimezoneField::make('field_name')
    ->readOnly(true);
```
#### `focusOutline(bool|Closure $condition = true)`


Inherited from `HasFieldFocusOutline`. Default: `false`.

```php
TimezoneField::make('field_name')
    ->focusOutline(true);
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getVariant()` | `string` | Resolved variant |
| `getAllowedTimezoneIdentifiers()` | `list&lt;string&gt;\|null` | Whitelist or `null` |
| `getExceptTimezoneIdentifiers()` | `list&lt;string&gt;` | Blacklist |
| `getResolvedTimezoneIdentifiers()` | `list&lt;string&gt;` | Effective allowed identifiers |
| `getDefaultTimezoneIdentifier()` | `string\|null` | Effective default |
| `isSearchable()` | `bool` | Search enabled |
| `shouldShowOffset()` | `bool` | Offset badge visible |
| `getPrefixIcon()` | `string\|BackedEnum\|Htmlable` | Resolved prefix icon |
| `shouldUseBrowserTimezoneDefault()` | `bool` | Browser timezone default |
| `shouldSortTimezonesByBrowserTimezone()` | `bool` | Browser timezone sort |
| `getBrowserTimezoneIdentifier()` | `string\|null` | Server-side detected timezone |
| `getTimezonesMetadata()` | `list&lt;array&gt;` | `id`, `label`, `offset`, `offset_seconds`, `region` |
| `getTimezoneSelectOptions()` | `array` | Options map for internal select |
| `normalizeState(mixed $state)` | `string\|null` | Canonical timezone identifier |
| `getWrapperClasses()` | `list&lt;string&gt;` | CSS class list |

### CSS classes

| Class | Role |
|-------|------|
| `fff-timezone-field` | Root wrapper |
| `fff-timezone-field--{sm\|md\|lg}` | Size modifier |
| `fff-timezone-field__trigger` | Picker button (clock icon + label) |
| `fff-timezone-field__icon` | Clock icon in trigger |
| `fff-timezone-field__offset` | UTC offset badge |
| `fff-timezone-field__menu` | Teleported dropdown (`is-positioned` when open) |
| `fff-timezone-field__search` | Dropdown search input |
| `fff-timezone-field__option` | Menu row |

Shares FlexTextInput shell classes (`fff-flex-text-input-field`, variant modifiers).

### Implementation notes

- Timezone list comes from PHP `timezone_identifiers_list()` via `Timezones` support class.
- Labels use format `{identifier} ({UTC±HH:MM})` — e.g. `Europe/Warsaw (UTC+02:00)`.
- Dropdown uses `x-teleport="body"` to avoid overflow clipping.
- SSR label is rendered server-side; Alpine `displayReady` swaps to live state without layout flash on reload.
- Browser timezone detection runs client-side on empty fields; server hydrate uses `config('app.timezone')` as a fallback when not in console.

---

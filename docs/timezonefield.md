---
title: "TimezoneField"
description: Searchable IANA timezone picker with browser detection and UTC offset display.
---

[← Back to Table of Contents](/docs/index)

### Summary

Searchable IANA timezone picker with a **Gravity UI clock** icon on the trigger and in menu rows. Stores a single timezone identifier string.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField` |
| **State type** | `string\|null` — IANA timezone identifier |
| **Model cast** | `'timezone' => 'string'` |
| **FieldType** | *(no dedicated FieldType mapping yet — use the class directly)* |
| **Playground** | `timezone-field` slug in Flex Fields playground |

---

### Basic usage

#### Standard timezone picker

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField;

TimezoneField::make('timezone')
    ->label('User Timezone')
    ->defaultTimezone('Europe/Warsaw')
    ->showOffset()
    ->required();
```

#### Browser-aware detection

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField;

TimezoneField::make('timezone')
    ->browserTimezoneDefault()
    ->browserTimezoneSortFirst();
```

---

### State & validation

#### Stored value

State is a single **IANA identifier string**.

```php
$record->timezone; // string|null — e.g. "Europe/Warsaw"
```

#### Validation rules

Built-in validation ensures the submitted identifier is in the resolved timezone list.

| Rule | Detail |
|------|--------|
| `nullable` | Always (unless `required()`) |
| `in(...)` | Value must match a configured IANA identifier |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `timezones(array\|Closure\|null $timezones)` | Setup | `null` | Whitelist of IANA identifiers |
| `exceptTimezones(array\|Closure $timezones)` | Setup | `[]` | Blacklist of IANA identifiers |
| `defaultTimezone(string\|Closure\|null $timezone)` | Setup | `null` | Fallback IANA identifier |
| `browserTimezoneDefault(bool\|Closure $condition = true)` | Setup | `false` | Pre-select from browser settings |
| `browserTimezoneSortFirst(bool\|Closure $condition = true)` | Setup | `false` | Sort browser timezone to top of list |
| `showOffset(bool\|Closure $condition = true)` | Setup | `true` | Show UTC offset badge (e.g. `UTC+02:00`) |
| `searchable(bool\|Closure $condition = true)` | Setup | `true` | Show search input in dropdown |
| `prefixIcon(...)` | Setup | `Clock` | Custom leading icon |
| `variant(string\|Closure $variant)` | Setup | `'primary'` | Visual style: `primary`, `secondary`, `flat`, `soft` |
| `size(string\|ControlSize\|Closure $size)` | Setup | `'md'` | Control size: `sm`, `md`, `lg` |
| `rounding(string\|Closure\|null $rounding)` | Setup | config | Border radius token |

#### `timezones()` / `exceptTimezones()`

Limit the list to specific regions or exclude problematic zones:

```php
TimezoneField::make('timezone')
    ->timezones(['UTC', 'Europe/Warsaw', 'America/New_York'])
    ->exceptTimezones(['GMT']);
```

#### `browserTimezoneDefault()`

When enabled and state is empty, it attempts to detect the user's timezone via Alpine/JS.

```php
TimezoneField::make('timezone')->browserTimezoneDefault();
```

---

### Real-world examples

#### User profile settings

```php
public static function form(Form $form): Form
{
    return $form->schema([
        TimezoneField::make('timezone')
            ->label('Your local time')
            ->browserTimezoneDefault()
            ->browserTimezoneSortFirst()
            ->required(),
    ]);
}
```

#### Event scheduler

```php
TimezoneField::make('event_timezone')
    ->label('Event Location Timezone')
    ->searchable()
    ->showOffset();
```

---

### Playground

`/admin/flex-fields-playground/timezone-field`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [CountryField](/docs/countryfield) | For picking a country instead of a specific timezone |
| [FlexDateTimePicker](/docs/flexdatetimepicker) | For picking a date/time with optional timezone support |
| [SelectField](/docs/selectfield) | For a generic dropdown if IANA identifiers aren't needed |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-timezone-field` | Root wrapper |
| `fff-timezone-field--{sm\|md\|lg}` | Size modifier |
| `fff-timezone-field__trigger` | Picker button |
| `fff-timezone-field__offset` | UTC offset badge |
| `fff-timezone-field__menu` | Dropdown panel |
| `fff-timezone-field__search` | Search input |

---

### Performance

| Mechanism | What it does |
|-----------|--------------|
| **Virtual Scroll** | Efficiently renders long lists of timezones (~400+ zones) |
| **SSR Label** | Renders the selected timezone label server-side for zero layout flash |
| **Teleport** | Uses `x-teleport="body"` to avoid overflow clipping in modals |

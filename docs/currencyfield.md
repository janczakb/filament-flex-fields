---
title: "CurrencyField"
description: Revolut-style currency input with locale-aware formatting, digit animations, optional currency picker, and minor-unit storage.
---

![CurrencyField](/art/sc-5.png)

[← Back to Table of Contents](/docs/index)

### Summary

Revolut-style currency input: locale-aware formatting, digit animations, optional currency picker, and **minor-unit** storage.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField` |
| **State type** | `int\|null` (single) or `array<amount: int\|null, currency: string>` (multi) |
| **Model cast** | `'price' => 'integer'` · `'budget' => 'array'` |
| **FieldType** | `currency` |
| **Playground** | `currency-field` slug in Flex Fields playground |

---

### Basic usage

#### Fixed currency (PLN)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;

CurrencyField::make('price')
    ->label('Amount')
    ->currency('PLN')
    ->locale('pl_PL')
    ->required();
```

#### Multi-currency with picker

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;

CurrencyField::make('budget')
    ->currencies(['EUR', 'USD', 'GBP', 'PLN'])
    ->currency('EUR')
    ->min(0)
    ->max(99999.99);
```

---

### State & storage

#### Minor units

Amounts are stored as the smallest currency unit (e.g. grosze, cents). Commas, spaces, and currency symbols are **display only**.

| Display | Minor units (`int`) |
|---------|---------------------|
| `99,99 PLN` | `9999` |
| `1 250,50 EUR` | `125050` |
| `¥1,500` (JPY, 0 decimals) | `1500` |

Multi-currency state:

```php
[
    'amount' => 125050,   // minor units
    'currency' => 'EUR',
]
```

#### Hydration (`normalizeState`)

On load, the field normalizes incoming values:
- `6666660` (`int`) → Treated as **minor units** → `6666660`
- `66666.60` (`float`) → Treated as **major units** → converted to `6666660`
- `"12.50"` (`string` with `.`) → Major units → `1250`

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `currency(string\|Closure $currencyCode)` | Setup | `'PLN'` | Default currency code |
| `currencies(array\|Closure\|null $currencies)` | Setup | `null` | Whitelist for currency picker |
| `locale(string\|Closure\|null $locale)` | Setup | auto | Formatting locale (e.g. `en_US`) |
| `min(float\|int\|Closure\|null $value)` | Setup | `null` | Minimum value in **major units** |
| `max(float\|int\|Closure\|null $value)` | Setup | `null` | Maximum value in **major units** |
| `allowNegative(bool\|Closure $condition = true)` | Setup | `false` | Allow negative amounts |
| `animated(bool\|Closure $condition = true)` | Setup | `true` | Enable digit animations |
| `commitDecimalsOnBlur(bool\|Closure $condition = true)` | Setup | `true` | Show ghost trailing zeros while focused (editing aid). Blur display keeps typed fraction (e.g. `,5`); storage still pads to currency decimals. |
| `searchable(bool\|Closure $condition = true)` | Setup | `true` | Enable search in currency picker |
| `variant(string\|Closure $variant)` | Setup | `'primary'` | Visual style: `primary`, `secondary`, `flat`, `soft` |
| `size(string\|ControlSize\|Closure $size)` | Setup | `'md'` | Control size: `sm`, `md`, `lg` |
| `rounding(string\|Closure\|null $rounding)` | Setup | config | Border radius token |

#### `min()` / `max()`

Defined in **major units** (e.g. `10.50`) but validated internally as minor units.

```php
CurrencyField::make('price')
    ->min(0)
    ->max(10000);
```

---

### Custom database formats

By default, `CurrencyField` saves minor units as integers. If your database uses decimals, override dehydration:

#### Store major units (`decimal`)

```php
CurrencyField::make('price')
    ->currency('PLN')
    ->dehydrateStateUsing(fn (?int $state) => $state === null ? null : $state / 100);
```

#### Eloquent cast (Recommended)

Map at the model layer to keep form logic clean:

```php
// Model
protected function casts(): array
{
    return [
        'price' => MinorUnitsCast::class, // Custom cast: DB decimal ↔ app int
    ];
}
```

---

### Real-world examples

#### Product price in a resource

```php
public static function form(Form $form): Form
{
    return $form->schema([
        CurrencyField::make('price')
            ->currency('USD')
            ->locale('en_US')
            ->min(0.01)
            ->required(),
    ]);
}
```

#### Multi-currency donation form

```php
CurrencyField::make('donation_amount')
    ->currencies(['USD', 'EUR', 'GBP'])
    ->currency('USD')
    ->variant('flat')
    ->size('lg');
```

---

### Playground

`/admin/flex-fields-playground/currency-field`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexTextInput](/docs/flextextinput) | For simple numeric inputs without currency formatting |
| [PriceRangeField](/docs/pricerangefield) | For selecting a range of prices (min/max) |
| [CountryField](/docs/countryfield) | For picking a country instead of a currency |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-currency-field` | Root wrapper |
| `fff-currency-field--{sm\|md\|lg}` | Size modifier |
| `fff-currency-field--{variant}` | Visual variant |
| `fff-currency-field__currency-trigger` | Currency picker chip |
| `fff-currency-field__digits` | Animated digit display |
| `fff-currency-field__symbol` | Trailing currency symbol |

---

### Performance

| Mechanism | What it does |
|-----------|--------------|
| **SSR Pre-render** | `getInitialDisplay()` renders segments server-side to prevent layout flash |
| **Memoized Metadata** | Currency metadata (symbols, decimals) is cached for Alpine |
| **Efficient Validation** | Normalizes state to minor units for consistent server-side validation |

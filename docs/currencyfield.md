# CurrencyField

[← Powrót do spisu treści](../README.md)


### Summary

Revolut-style currency input: locale-aware formatting, digit animations, optional currency picker, and **minor-unit** storage.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField` |
| **State type (internal)** | `int\|null` (single currency) or `array{amount: int\|null, currency: string}\|null` (multi-currency) |
| **Default DB format** | **Minor units** as integer — e.g. `66 666,60 PLN` → `6666660` |
| **FieldType** | `currency` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;

// Fixed currency (PLN only)
CurrencyField::make('price')
    ->label('Amount')
    ->currency('PLN')
    ->locale('pl_PL')
    ->required();

// Multi-currency with picker
CurrencyField::make('budget')
    ->currencies(['EUR', 'USD', 'GBP', 'PLN'])
    ->currency('EUR')
    ->min(0)
    ->max(99999.99);
```

---

### Storage format vs display format

**Important:** commas, spaces, and currency symbols (`zł`, `€`) are **display only**. They are controlled by `locale()` and never written to the database by default.

| Layer | Example (PLN) | Format |
|-------|---------------|--------|
| **UI** | `66 666,60 zł` | Locale grouping + symbol |
| **Form state (Alpine / Livewire)** | `6666660` | Integer, minor units |
| **Database (default dehydrate)** | `6666660` | Integer, minor units |

#### Minor units

Amounts are stored as the smallest currency unit (e.g. grosze, cents):

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

#### What the field accepts on load (`normalizeState`)

On hydrate, `afterStateHydrated` normalizes incoming values:

| Value from DB / model | Result in form state |
|-----------------------|----------------------|
| `6666660` (`int`) | Treated as **minor units** → `6666660` |
| `66666.60` (`float`) | Treated as **major units** → converted to `6666660` |
| `"12.50"` (`string` with `.`) | Major units → `1250` |
| `"66666,60"` (`string` with `,`) | **Not supported** out of the box |
| `"66 666,60"` | **Not supported** out of the box |
| `null` | `null` |

> `min()` / `max()` are defined in **major units** (e.g. `10.50`) but validated internally as minor units.

---

### Custom database formats

By default, `dehydrateStateUsing()` saves **minor units** as `int` (or `array` with multi-currency). There is **no** built-in `->storeAsMajor()` method — override dehydration (and optionally hydration) when your column uses a different format.

#### When to override `dehydrateStateUsing()`

Call **after** other field configuration. Your closure receives the **normalized minor-unit state** from the component (unless you also override hydration).

| Your DB column | Strategy |
|----------------|----------|
| `integer` minor units (recommended) | **Default** — no override needed |
| `decimal(12,2)` major units | `dehydrateStateUsing` → divide by `10^decimals` |
| `varchar` e.g. `"66666,60"` | `dehydrateStateUsing` → format string; `afterStateHydrated` → parse string |
| Legacy mixed data | Eloquent **cast** on the model (preferred) |

#### Example: store major units (`decimal`)

```php
CurrencyField::make('price')
    ->currency('PLN')
    ->dehydrateStateUsing(function (?int $state): ?float {
        if ($state === null) {
            return null;
        }

        return $state / 100; // 6666660 → 66666.60
    });
```

On load, floats with a dot are already converted to minor units by `normalizeState()` (e.g. `66666.60` → `6666660`), so no extra hydration hook is required for plain floats.

#### Example: store formatted string with comma

```php
CurrencyField::make('price')
    ->currency('PLN')
    ->afterStateHydrated(function (CurrencyField $component, mixed $state): void {
        if (! is_string($state)) {
            return;
        }

        // "66 666,60" → minor units
        $normalized = str_replace([' ', ','], ['', '.'], $state);
        $component->state((int) round((float) $normalized * 100));
    })
    ->dehydrateStateUsing(function (?int $state): ?string {
        if ($state === null) {
            return null;
        }

        return number_format($state / 100, 2, ',', ''); // 6666660 → "66666,60"
    });
```

#### Example: keep dehydration default, only transform on save

If you only need a one-off transform when persisting the parent form, you can still use `dehydrateStateUsing` on the field — it runs when Filament dehydrates form state to the model:

```php
CurrencyField::make('price')
    ->currency('PLN')
    ->dehydrateStateUsing(fn (CurrencyField $component, mixed $state) => $state === null
        ? null
        : $state / 100
    );
```

> **Note:** The built-in closure is `fn (CurrencyField $component, mixed $state) => $component->normalizeState($state)`. When overriding, you receive state that is already in the component’s internal shape (minor units). Use `normalizeState()` only if you need to re-normalize arbitrary input.

#### Recommended: Eloquent cast

Keep `CurrencyField` on minor units in the form and map at the model layer:

```php
// Model — illustrative custom cast
protected function casts(): array
{
    return [
        'price' => MinorUnitsCast::class, // DB decimal ↔ app int
    ];
}
```

This avoids duplicating conversion logic across forms and API resources.

#### Migration recommendation

For legacy projects, prefer a **one-time migration** to `integer` minor units or `decimal` major units rather than storing locale-specific strings long term.

---

### Configuration API

#### `variant(string|Closure $variant)`


Sets the styling variant of the currency field. Available: `primary` (default), `secondary`, `flat`.

```php
CurrencyField::make('price')
    ->variant('flat');
```
### Public helper methods

| Method | Description |
|--------|-------------|
| `normalizeState(mixed $state)` | Convert arbitrary input to minor-unit state shape |
| `extractAmount(int\|array\|null $state)` | Get amount in minor units |
| `extractCurrency(int\|array\|null $state)` | Get currency code |
| `getInitialDisplay(?mixed $state = null)` | Server-rendered segments for first paint (no layout flash) |
| `hasCurrencySelect()` | Whether `currencies()` is configured |
| `getCurrenciesMetadata()` | Metadata for Alpine (`code`, `symbol`, `decimals`, `locale`) |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `currency` | `currency()` |
| `locale` | `locale()` |
| `currencies` | `currencies()` |
| `min` | `min()` |
| `max` | `max()` |
| `allow_negative` | `allowNegative()` |
| `animated` | `animated()` |
| `commit_decimals_on_blur` | `commitDecimalsOnBlur()` |
| `searchable` | `searchable()` |
| `size` | `size()` |

### CSS classes

| Class | Meaning |
|-------|---------|
| `fff-currency-field` | Root wrapper |
| `fff-currency-field--{sm\|md\|lg}` | Size modifier |
| `fff-currency-field__currency-trigger` | Currency picker chip |
| `fff-currency-field__digits` | Animated digit display |
| `fff-currency-field__symbol` | Trailing currency symbol |

---

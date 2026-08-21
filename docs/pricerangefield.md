---
title: "PriceRangeField"
description: Dual-handle price range slider with histogram backdrop, min/max numeric inputs, and currency prefix.
---

![PriceRangeField](/art/sc-9.png)

[← Back to Table of Contents](/docs/index)

### Summary

Dual-handle **price range** slider with histogram backdrop, min/max numeric inputs, and currency prefix. Ideal for e-commerce filtering where users need to see data distribution while selecting a range.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField` |
| **State type** | `array<min: int|float, max: int|float>` |
| **Model cast** | `'price_range' => 'array'` or `'json'` |
| **FieldType** | `price_range` |
| **Playground** | `price-range` slug in Flex Fields playground |

Example state: `['min' => 100, 'max' => 1124]`.

---

### Basic usage

#### Standard Price Range
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField;

PriceRangeField::make('price_range')
    ->label('Price range')
    ->min(0)
    ->max(5000)
    ->step(1)
    ->prefix('$')
    ->histogram([30, 74, 85, 36, 98])
    ->showInputs();
```

#### Slider Only (No Inputs)
```php
PriceRangeField::make('budget')
    ->min(0)
    ->max(1000)
    ->showInputs(false)
    ->variant('flat');
```

---

### State & validation

#### Stored value
State is an associative array with `min` and `max` keys.

```php
$record->price_range; // ['min' => 100, 'max' => 1124]
```

#### Validation rules (built-in)
| Failure | Translation key |
|---------|-----------------|
| Non-numeric min/max | `price_range.invalid` |
| Values outside bounds | `price_range.out_of_bounds` |
| min > max | `price_range.min_greater_than_max` |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `min(int\|float $min)` | Setup | `0` | Lower bound |
| `max(int\|float $max)` | Setup | `1000` | Upper bound |
| `step(int\|float $step)` | Setup | `1` | Snap increment |
| `integer(bool $condition)` | Setup | `true` | Restrict to whole numbers |
| `decimalPlaces(int $places)` | Setup | `null` | Fixed decimal precision |
| `locale(string\|Closure\|null $locale)` | Setup | `null` | Locale for the displayed number (`pt_BR` → `1.234,50`). `null` keeps the plain unformatted output |
| `prefix(string $prefix)` | Setup | `'$'` | Currency prefix in inputs |
| `withoutPrefix()` | Setup | — | Remove currency prefix |
| `variant(string $variant)` | Setup | `'primary'` | `primary` (bordered), `secondary` (faded), `flat` |
| `showInputs(bool $condition)` | Setup | `true` | Show min/max numeric inputs |
| `minInputLabel(string $label)` | Setup | config | Label for min input |
| `maxInputLabel(string $label)` | Setup | config | Label for max input |
| `histogram(array $heights)` | Setup | default | Bar heights (8–100) for chart |
| `size(string $size)` | Setup | `'md'` | `sm`, `md`, `lg` |
| `rounding(string $rounding)` | Setup | config | Border radius token |

---

### Real-world examples

#### Custom Histogram Data
```php
PriceRangeField::make('price_range')
    ->histogram(fn () => Product::query()->pluck('price')->histogram(32));
```

#### Reactive Filtering
```php
PriceRangeField::make('price_range')
    ->live()
    ->afterStateUpdated(function ($state) {
        // Filter results based on $state['min'] and $state['max']
    });
```

---

### Playground

`/admin/flex-fields-playground/price-range`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexSlider](/docs/flexslider) | Generic dual-handle slider without histogram |
| [TrackSlider](/docs/trackslider) | Minimalist single-handle slider |
| [CurrencyField](/docs/currencyfield) | Single currency input |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-price-range-field` | Root wrapper |
| `fff-price-range-field--{sm\|md\|lg}` | Size variant |
| `fff-price-range-field--{variant}` | Visual variant |
| `fff-rounding-{rounding}` | Border radius utility |
| `fff-price-range-field__histogram` | Histogram container |
| `fff-price-range-field__bar` | Individual histogram bar |
| `fff-price-range-field__slider` | Dual-handle slider wrapper |
| `fff-price-range-field__inputs` | Min/max inputs container |

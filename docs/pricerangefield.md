# PriceRangeField

![PriceRangeField](/art/sc-9.png)

[← Back to Table of Contents](index.md)


### Summary

Dual-handle **price range** slider with histogram backdrop, min/max numeric inputs, and currency prefix.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField` |
| **State type** | `array<min: int|float, max: int|float>` |
| **Model cast** | `'price_range' => 'array'` or `'json'` |
| **FieldType** | `price_range` |

Example state: `['min' => 100, 'max' => 1124]`.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField;

PriceRangeField::make('price_range')
    ->label('Price range')
    ->min(0)
    ->max(5000)
    ->step(1)
    ->prefix('$')
    ->histogram([30, 74, 85, 36, 98])
    ->showInputs()
    ->variant('bordered')
    ->default(['min' => 100, 'max' => 1124]);
```

### Validation

Built-in rules via custom validator:

| Failure | Translation key |
|---------|-----------------|
| Non-numeric min/max | `price_range.invalid` |
| Values outside bounds | `price_range.out_of_bounds` |
| min > max | `price_range.min_greater_than_max` |

### Configuration API

#### `min(int|float|Closure $min = 0)` / `max(int|float|Closure $max = 1000)`


Range boundaries for slider and inputs.

```php
PriceRangeField::make('field_name')
    ->min(1)
    ->max(10);
```
#### `step(int|float|Closure $step = 1)`


Snap increment.

```php
PriceRangeField::make('field_name')
    ->step(1);
```
#### `integer(bool|Closure $condition = true)`


Restrict to whole numbers. **Default: true.**

```php
PriceRangeField::make('field_name')
    ->integer(true);
```
#### `decimalPlaces(int|Closure|null $places)`


Fixed decimal precision when not integer.

```php
PriceRangeField::make('field_name')
    ->decimalPlaces(2);
```
#### `prefix(string|Closure|null $prefix)` / `withoutPrefix()`


Currency or unit prefix shown in inputs. Default config: `$`.

```php
PriceRangeField::make('field_name')
    ->prefix('value')
    ->withoutPrefix();
```
#### `variant(string|Closure $variant)`


| Value | Description |
|-------|-------------|
| `bordered` | Bordered track. **Default.** |
| `flat` | Flat styling. |
| `faded` | Subtle faded track. |

```php
PriceRangeField::make('field_name')
    ->variant('primary');
```
#### `showInputs(bool|Closure $condition = true)`


Min/max numeric inputs below the slider. **Default: true.**

```php
PriceRangeField::make('field_name')
    ->showInputs(true);
```
#### `minInputLabel(string|Closure|null $label)` / `maxInputLabel(string|Closure|null $label)`


Accessible labels for the numeric inputs.

```php
PriceRangeField::make('field_name')
    ->minInputLabel('value')
    ->maxInputLabel('value');
```
#### `histogram(array|Closure $heights)`


Bar heights (8–100) for the background chart. Uses a built-in default pattern when omitted.

```php
PriceRangeField::make('field_name')
    ->histogram(['value1', 'value2']);
```
#### `size(string|ControlSize|Closure $size)`


See [Control size](shared-concepts.md).

```php
PriceRangeField::make('field_name')
    ->size('md');
```

### State normalization

On hydrate and dehydrate, values are clamped to `[min, max]`, stepped, and `min` is never greater than `max`.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `min` | `min()` |
| `max` | `max()` |
| `step` | `step()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `prefix` | `prefix()` |
| `histogram` | `histogram()` |
| `integer` | `integer()` |
| `decimal_places` | `decimalPlaces()` |
| `show_inputs` | `showInputs()` |
| `min_input_label` | `minInputLabel()` |
| `max_input_label` | `maxInputLabel()` |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `normalizeState(array $state)` | `array<min, max>` | Clamps min/max to bounds, applies step rounding, ensures `min ≤ max`. |
| `defaultHistogram()` | `list<float>` | Built-in 32-bar histogram heights (8–100) used when `histogram()` is empty. |
| `hasPrefix()` | `bool` | Whether a currency/unit prefix is configured (not `withoutPrefix()`). |

---

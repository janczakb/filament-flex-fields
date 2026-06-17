# NumberStepper

![NumberStepper](../art/sc-13.png)

[← Back to Table of Contents](index.md)


### Summary

Pill-shaped numeric control with **−** / **+** buttons and an animated NumberFlow display.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper` |
| **State type** | `int\|float\|null` when `nullable()` |
| **Model cast** | `'quantity' => 'integer'` or `'decimal:2'` |
| **FieldType** | `number_stepper` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;

NumberStepper::make('quantity')
    ->label('Quantity')
    ->minValue(0)
    ->maxValue(99)
    ->step(1)
    ->suffix('kg')
    ->variant('primary')
    ->size('lg');
```

### Validation

| Method | Effect |
|--------|--------|
| `integer()` | Adds `integer` rule. **Enabled by default.** |
| `minValue($n)` | Adds `min:$n` when not nullable |
| `maxValue($n)` | Adds `max:$n` |

### Configuration API

#### `minValue(scalar|Closure|null $value)` / `maxValue(scalar|Closure|null $value)`


Lower and upper bounds.

```php
NumberStepper::make('field_name')
    ->minValue()
    ->maxValue();
```
#### `step(int|float|Closure $step = 1)`


Increment / decrement step.

```php
NumberStepper::make('field_name')
    ->step(1);
```
#### `integer(bool|Closure $condition = true)`


Restrict to whole numbers.

```php
NumberStepper::make('field_name')
    ->integer(true);
```
#### `nullable(bool|Closure $condition = true)`


Allows `null`. Displays `nullLabel` or `—`.

```php
NumberStepper::make('field_name')
    ->nullable(true);
```
#### `variant(string|Closure $variant)`


| Value | Description |
|-------|-------------|
| `default` | White circular buttons on grey track. |
| `primary` | Filled primary buttons. |
| `secondary` | Primary-tinted buttons. |
| `tertiary` | Grey buttons. |
| `outline` | Outlined buttons. |

```php
NumberStepper::make('field_name')
    ->variant('primary');
```
#### `prefix(string|Closure|null $prefix)` / `suffix(string|Closure|null $suffix)`


Static text before / after the numeric value in the display.

```php
NumberStepper::make('field_name')
    ->prefix('value')
    ->suffix('zł');
```
#### `nullLabel(string|Closure|null $label)`


Text shown when the value is `null`.

```php
NumberStepper::make('field_name')
    ->nullLabel('value');
```
#### `decrementIcon(string|Closure|null $icon)` / `incrementIcon(string|Closure|null $icon)`


Custom Heroicon for − and + buttons.

```php
NumberStepper::make('field_name')
    ->decrementIcon('value')
    ->incrementIcon('value');
```
#### `icons(array|Closure $icons)`


Shorthand:

```php
->icons([
    'decrement' => 'heroicon-o-minus',
    'increment' => 'heroicon-o-plus',
])
```

#### `reversed(bool|Closure $condition = true)`


Swaps the visual order of decrement and increment buttons.

```php
NumberStepper::make('field_name')
    ->reversed(true);
```
#### `decimalPlaces(int|Closure|null $places)`


Fixed decimal places in the display.

```php
NumberStepper::make('field_name')
    ->decimalPlaces(2);
```
#### `wheelAnimated(bool|Closure $condition = true)`


NumberFlow wheel animation on value change. **Default: true.**

```php
NumberStepper::make('field_name')
    ->wheelAnimated(true);
```
#### `size(string|ControlSize|Closure $size)`


See [Control size](shared-concepts.md).

```php
NumberStepper::make('field_name')
    ->size('md');
```

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `min` | `minValue()` |
| `max` | `maxValue()` |
| `step` | `step()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `prefix` | `prefix()` |
| `suffix` | `suffix()` |
| `null_label` | `nullLabel()` |
| `icons` | `icons()` |
| `decrement_icon` | `decrementIcon()` |
| `increment_icon` | `incrementIcon()` |
| `reversed` | `reversed()` |
| `decimal_places` | `decimalPlaces()` |
| `wheel_animated` | `wheelAnimated()` |
| `integer` | `integer()` |
| `nullable` | `nullable()` |

---

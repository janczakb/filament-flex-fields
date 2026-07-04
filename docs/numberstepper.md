---
title: "NumberStepper"
description: Pill-shaped numeric control with − / + buttons and an animated NumberFlow display.
---

![NumberStepper](/art/sc-13.png)

[← Back to Table of Contents](/docs/index)

### Summary

Pill-shaped numeric control with **−** / **+** buttons and an animated **NumberFlow** display. Ideal for quantities, guests, or any numeric input where precise stepping is preferred over direct text entry.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper` |
| **State type** | `int|float|null` when `nullable()` |
| **Model cast** | `'quantity' => 'integer'` or `'decimal:2'` |
| **FieldType** | `number_stepper` |
| **Playground** | `number-stepper` slug in Flex Fields playground |

Works with all standard Filament field APIs: `required()`, `disabled()`, `hidden()`, `live()`, `afterStateUpdated()`, validation rules, etc.

---

### Basic usage

#### Standard Quantity
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;

NumberStepper::make('quantity')
    ->label('Quantity')
    ->minValue(0)
    ->maxValue(99)
    ->step(1)
    ->suffix('kg');
```

#### Currency Input
```php
NumberStepper::make('price')
    ->prefix('$')
    ->decimalPlaces(2)
    ->integer(false)
    ->step(0.5)
    ->minValue(0);
```

---

### State & validation

#### Stored value
State is a numeric value (`int` or `float`) or `null` if nullable.

```php
$record->quantity; // int|null — e.g. 5
```

#### Validation rules (built-in)
| Rule | When |
|------|------|
| `integer` | When `->integer()` (default: true) |
| `min:$n` | When `->minValue($n)` and not `nullable()` |
| `max:$n` | When `->maxValue($n)` |
| `required` | When `->required()` |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `minValue(scalar $value)` | Setup | `null` | Lower bound |
| `maxValue(scalar $value)` | Setup | `null` | Upper bound |
| `step(int\|float $step)` | Setup | `1` | Increment / decrement step |
| `integer(bool $condition)` | Setup | `true` | Restrict to whole numbers |
| `nullable(bool $condition)` | Setup | `false` | Allows `null` state |
| `variant(string $variant)` | Setup | `'default'` | `default`, `primary`, `secondary`, `tertiary`, `outline` |
| `prefix(string $prefix)` | Setup | `null` | Static text before the value |
| `suffix(string $suffix)` | Setup | `null` | Static text after the value |
| `nullLabel(string $label)` | Setup | `'—'` | Text shown when value is `null` |
| `decrementIcon(string $icon)` | Setup | config | Heroicon for minus button |
| `incrementIcon(string $icon)` | Setup | config | Heroicon for plus button |
| `icons(array $icons)` | Setup | — | Shorthand for `decrement` and `increment` keys |
| `reversed(bool $condition)` | Setup | `false` | Swaps button positions |
| `decimalPlaces(int $places)` | Setup | `null` | Fixed decimal precision |
| `wheelAnimated(bool $condition)` | Setup | `true` | Enable NumberFlow animation |
| `size(string $size)` | Setup | `'md'` | `sm`, `md`, `lg` |

---

### Real-world examples

#### Guest Selection (Wizard)
```php
NumberStepper::make('guests')
    ->label('Guests')
    ->helperText('Maximum 10 guests per reservation')
    ->minValue(1)
    ->maxValue(10)
    ->variant('primary')
    ->size('lg');
```

#### Reactive Price Calculator
```php
NumberStepper::make('quantity')
    ->minValue(1)
    ->live()
    ->afterStateUpdated(fn (Set $set, $state) => $set('total', $state * 10.5));
```

---

### Playground

`/admin/flex-fields-playground/number-stepper`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexSlider](/docs/flexslider) | Visual range selection with a track |
| [TrackSlider](/docs/trackslider) | Compact track-based slider |
| [FlexTextInput](/docs/flextextinput) | Standard text entry for large numbers |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-number-stepper` | Root wrapper |
| `fff-number-stepper--{sm\|md\|lg}` | Size variant |
| `fff-number-stepper--{variant}` | Visual variant |
| `fff-number-stepper__value` | Value container |
| `fff-number-stepper__value-sizer` | Hidden sizer for width stability |
| `fff-number-stepper__value-display` | Visible value wrapper |
| `fff-number-stepper__ssr-value` | Server-rendered initial value |
| `fff-number-stepper__null-label` | Label for null state |
| `fff-number-stepper__flow` | NumberFlow element |
| `is-disabled` | Disabled state |
| `is-reversed` | Reversed layout |

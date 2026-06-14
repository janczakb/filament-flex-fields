# TrackSlider

[← Powrót do spisu treści](../README.md)


### Summary

Track-based range slider with optional live value output.

Also used internally for `FieldType::Percentage` and `FieldType::RangeSlider`.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrackSlider` |
| **State type** | `int\|float` |
| **Model cast** | `'volume' => 'integer'` or `'float'` |
| **FieldType** | `range_slider`, `percentage` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrackSlider;

TrackSlider::make('volume')
    ->label('Volume')
    ->min(0)
    ->max(100)
    ->step(5)
    ->suffix('%')
    ->trackLabel('Volume level')
    ->variant('secondary')
    ->showOutput()
    ->size('md');
```

### Validation

No built-in min/max rules. Add manually:

```php
->rule('min:0')
->rule('max:100')
```

Or define rules in `FlexFieldDefinition`.

### Configuration API

#### `min(int|float|Closure $min = 0)` / `max(int|float|Closure $max = 100)`


Range boundaries.

```php
TrackSlider::make('field_name')
    ->min(1)
    ->max(10);
```
#### `step(int|float|Closure $step = 1)`


Slider step increment.

```php
TrackSlider::make('field_name')
    ->step(1);
```
#### `integer(bool|Closure $condition = true)`


Snap to integer values. **Default: true.**

```php
TrackSlider::make('field_name')
    ->integer(true);
```
#### `showOutput(bool|Closure $condition = true)`


Display the current value. **Default: true.**

```php
TrackSlider::make('field_name')
    ->showOutput(true);
```
#### `suffix(string|Closure|null $suffix)`


Text after the value, e.g. `%`, `px`.

```php
TrackSlider::make('field_name')
    ->suffix('zł');
```
#### `variant(string|Closure $variant)`


| Value | Description |
|-------|-------------|
| `default` | Standard filled track. |
| `secondary` | Subtle track styling. |

```php
TrackSlider::make('field_name')
    ->variant('primary');
```
#### `decimalPlaces(int|Closure|null $places)`


Decimal precision for displayed value.

```php
TrackSlider::make('field_name')
    ->decimalPlaces(2);
```
#### `trackLabel(string|Closure|null $label)`


Accessible label / ARIA description for the track.

```php
TrackSlider::make('field_name')
    ->trackLabel('Progress');
```
#### `size(string|ControlSize|Closure $size)`


See [Control size](shared-concepts.md).

```php
TrackSlider::make('field_name')
    ->size('md');
```

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `min` | `min()` |
| `max` | `max()` |
| `step` | `step()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `show_output` | `showOutput()` |
| `suffix` | `suffix()` |
| `decimal_places` | `decimalPlaces()` |
| `track_label` | `trackLabel()` |
| `integer` | `integer()` |

---

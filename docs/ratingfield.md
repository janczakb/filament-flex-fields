# RatingField

[← Back to Table of Contents](index.md)


### Summary

HeroUI-style star (or custom icon) rating input with hover preview, semantic colors, sizes, disabled/required states, and **fractional read-only display**.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField` |
| **State type** | `int|null` (interactive) · `float|null` (read-only display) |
| **FieldType** | `rating` |

Shares rating display configuration with [RatingColumn](ratingcolumn.md) via matching fluent API. Fill calculations live in the shared `CalculatesRatingFill` concern.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField;
use Filament\Support\Icons\Heroicon;

RatingField::make('score')
    ->label('How would you rate this product?')
    ->required();

RatingField::make('score')
    ->icon(Heroicon::Heart)
    ->color('danger')
    ->stars(5);

RatingField::make('average_score')
    ->readOnly()
    ->default(3.7);
```

### Validation

| Rule | When |
|------|------|
| `nullable` | Always (unless `required()`) |
| `numeric` | Always |
| `min:0` | Always |
| `max:{stars}` | Matches `stars()` / `max()` |
| `integer` | Interactive mode only |
| `required` | When `->required()` |

### Configuration API

#### `stars(int|Closure $count)` / `max(int|Closure $count)`


Number of rating items. Default: **5**. Minimum: **1**.

```php
RatingField::make('field_name')
    ->stars(5)
    ->max(10);
```
#### `size(string|ControlSize|Closure $size)`


See [Control size](shared-concepts.md). Default: `md`.

```php
RatingField::make('field_name')
    ->size('md');
```
#### `color(string|Closure|null $color)`


Semantic fill color for active icons.

| Value | Use case |
|-------|----------|
| `warning` | Default HeroUI amber stars |
| `primary` | Accent / blue |
| `danger` | Error / red hearts |
| `success` | Positive / green |

```php
RatingField::make('field_name')
    ->color('primary');
```
#### `icon(string|BackedEnum|Htmlable|Closure|null $icon)`


Custom icon for every item. Default: `Heroicon::Star`. Use `Heroicon::Heart` or `Heroicon::OutlinedHeart` for heart variants.

```php
RatingField::make('field_name')
    ->icon('heroicon-o-star');
```
#### `readOnly(bool|Closure $condition = true)`


Display-only mode. Supports **fractional values** (e.g. `3.7`) with partial icon fill.

```php
RatingField::make('field_name')
    ->readOnly(true);
```
#### `showValue(bool|Closure $condition = true)`


When read-only, show the numeric value (one decimal) beside the icons. Default: `true`.

```php
RatingField::make('field_name')
    ->showValue(true);
```
#### `extraAlpineAttributes(array|Closure $attributes)`


Extra Alpine bindings on the root element. From Filament `HasExtraAlpineAttributes` (same trait as `SwitchField`).

```php
RatingField::make('field_name')
    ->extraAlpineAttributes(['value1', 'value2']);
```
#### Inherited


`disabled()`, `required()`, `label()`, `helperText()`, `default()`, standard Filament field validation.

```php
RatingField::make('field_name')
    ->required()
    ->disabled();
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getFillPercentageForValue(float\|int\|null $value, int $index)` | `float` | Fill ratio `0.0–1.0` for icon at 1-based `$index`. Used for **read-only fractional display** (e.g. `3.7` fills star 4 to 70%). Returns `0` when value is `null`. |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `max` | `stars()` |
| `size` | `size()` |
| `color` | `color()` |
| `icon` | `icon()` |
| `show_value` | `showValue()` |
| `read_only` | `readOnly()` |

For **read-only rating display in tables**, use [RatingColumn](ratingcolumn.md) instead.

---

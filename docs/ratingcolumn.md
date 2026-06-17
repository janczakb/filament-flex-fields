---
title: "RatingColumn"
---

[← Back to Table of Contents](/docs/index)


### Summary

Read-only **table column** for displaying ratings with the same visual language as [RatingField](/docs/ratingfield). Supports fractional values with partial icon fill, custom icons, semantic colors, sizes, and optional numeric value display.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Tables\Columns\RatingColumn` |
| **Context** | Filament tables (`$table-&gt;columns([...])`) |
| **State type** | `int|float|string|null` (numeric values only) |
| **Parent** | `Filament\Tables\Columns\TextColumn` |

Shares rating display configuration with RatingField via matching fluent API. Column methods use a `rating*` prefix where they would conflict with `TextColumn` (`ratingSize()`, `ratingColor()`, `ratingIcon()`).

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\RatingColumn;
use Filament\Support\Icons\Heroicon;

RatingColumn::make('score')
    ->label('Rating');

RatingColumn::make('average_score')
    ->stars(10)
    ->ratingColor('success')
    ->ratingSize('lg');

RatingColumn::make('satisfaction')
    ->ratingIcon(Heroicon::Heart)
    ->ratingColor('danger')
    ->showValue(false);
```

Filament resolves `$record-&gt;score` as column state from the model attribute or relationship. Fractional values (e.g. `3.7`) render with partial star fill.

### Configuration API (RatingColumn-specific)

No column-only methods beyond inherited table APIs. All visual options come from the shared rating display API below.

### Shared rating display API

These methods are identical to [RatingField](/docs/ratingfield):

| Method | Description |
|--------|-------------|
| `stars(int\|Closure $count)` | Number of rating items. Default: **5** |
| `ratingSize(string\|ControlSize\|Closure $size)` | Icon control size (`sm`, `md`, `lg`). Default: `md` (table scale: 16 / 18 / 20 px) |
| `ratingColor(string\|Closure\|null $color)` | Semantic fill color (`warning`, `primary`, `danger`, `success`) |
| `ratingIcon(string\|BackedEnum\|Htmlable\|Closure\|null $icon)` | Custom icon. Default: `Heroicon::Star` |
| `showValue(bool\|Closure $condition = true)` | Show numeric value (one decimal) beside icons. Default: `true` |

### Inherited TextColumn API

All Filament `TextColumn` methods apply: `label()`, `sortable()`, `searchable()`, `toggleable()`, `alignStart()` / `alignCenter()`, `url()`, `tooltip()`, etc. The column uses `html()` internally — do not call `html(false)` unless you override `formatStateUsing()`.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `formatRatingDisplay(mixed $state)` | `string` | Rendered HTML for a state value |
| `normalizeRatingFromState(mixed $state)` | `?float` | Parsed numeric value clamped to `0…max`, or `null` |
| `getFillPercentageForValue(float\|int\|null $value, int $index)` | `float` | Fill ratio `0.0–1.0` for icon at 1-based `$index` |
| `getMax()` | `int` | Star count |
| `getRatingColor()` | `string` | Semantic color name |
| `getRatingIcon()` | `string\|BackedEnum\|Htmlable` | Icon reference |
| `getRatingDisplaySize()` | `string` | Icon control size (`sm`, `md`, `lg`) |
| `shouldShowValue()` | `bool` | Whether numeric value is shown |
| `getItemIndexes()` | `list&lt;int&gt;` | 1-based indexes for each star |

### CSS classes

| Class | Role |
|-------|------|
| `fff-rating-column` | Cell wrapper (inline flex) |
| `fff-rating` | Shared rating root (reused from RatingField) |
| `fff-rating--{size}` | Size variant (`sm`, `md`, `lg`) |
| `fff-rating--with-value` | Layout when numeric value is shown |
| `is-read-only` | Non-interactive display mode |
| `fff-rating__items` | Icon row |
| `fff-rating__icon-clip` | Partial fill clip for fractional values |
| `fff-rating__value` | Numeric value label |

### Performance

| Mechanism | What it does |
|-----------|----------------|
| **`RatingColumnRenderCache`** | Per-request cache of rendered rating HTML keyed by normalized value and column options. Identical ratings across rows reuse one Blade render. |
| **Lazy CSS** | `RatingColumn::setUp()` registers `rating-column` in `FlexFieldStylesheetQueue`; `queued-stylesheets` render hooks emit `flex-fields-rating-column.css` once per request (no `load-stylesheet` in cell blades) |

---

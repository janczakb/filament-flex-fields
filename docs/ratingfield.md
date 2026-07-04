---
title: "RatingField"
description: Star or custom icon rating input with hover preview, semantic colors, and fractional read-only display.
---

![RatingField](/art/sc-20.png)

[← Back to Table of Contents](/docs/index)

### Summary

Interactive rating input for scores, feedback, and reviews. Supports custom icons (stars, hearts, etc.), semantic colors, and various sizes. Includes a **read-only mode** that supports fractional values (e.g., 3.7) with partial icon fill.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField` |
| **State type** | `int\|null` (interactive) · `float\|null` (read-only display) |
| **Model cast** | `'rating' => 'integer'` · `'average_score' => 'float'` |
| **FieldType** | `rating` |
| **Playground** | `rating` slug in Flex Fields playground |

Shares rating display configuration with [RatingColumn](/docs/ratingcolumn) via matching fluent API.

---

### Basic usage

#### Standard 5-star rating

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField;

RatingField::make('score')
    ->label('How would you rate this product?')
    ->required();
```

#### Custom icon and color

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField;
use Filament\Support\Icons\Heroicon;

RatingField::make('score')
    ->icon(Heroicon::Heart)
    ->color('danger')
    ->stars(10);
```

#### Read-only fractional display

```php
RatingField::make('average_score')
    ->label('Average rating')
    ->readOnly()
    ->default(3.7);
```

---

### State & validation

#### Stored value

In interactive mode, the state is an **integer** representing the number of selected items. In read-only mode, it can be a **float**.

```php
$record->score; // int|null — e.g. 4
$record->average_score; // float|null — e.g. 4.2
```

#### Validation rules (built-in)

| Rule | When |
|------|------|
| `nullable` | Always (unless `required()`) |
| `numeric` | Always |
| `min:0` | Always |
| `max:{stars}` | Matches `stars()` / `max()` |
| `integer` | Interactive mode only |
| `required` | When `->required()` |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `stars(int\|Closure $count)` | Setup | `5` | Number of rating items. Alias: `max()`. |
| `icon(string\|BackedEnum\|Htmlable\|Closure\|null $icon)` | Setup | `Heroicon::Star` | Custom icon for every item. |
| `color(string\|Closure\|null $color)` | Setup | `warning` | Semantic fill color for active icons. |
| `size(string\|ControlSize\|Closure $size)` | Setup | `md` | Control size: `sm`, `md`, `lg`. |
| `readOnly(bool\|Closure $condition = true)` | Setup | `false` | Display-only mode with fractional support. |
| `showValue(bool\|Closure $condition = true)` | Setup | `true` | Show numeric value beside icons in read-only mode. |
| `extraAlpineAttributes(array\|Closure $attributes)` | Setup | `[]` | Extra Alpine bindings on the root element. |

#### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getFillPercentageForValue(float\|int\|null $value, int $index)` | `float` | Fill ratio `0.0–1.0` for icon at 1-based `$index`. |

---

### Real-world examples

#### Feedback form with required rating

```php
public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Customer Feedback')->schema([
            RatingField::make('satisfaction_score')
                ->label('Overall Satisfaction')
                ->stars(5)
                ->required(),
            
            Textarea::make('feedback_text')
                ->label('Comments')
                ->visible(fn (Get $get) => $get('satisfaction_score') <= 3),
        ]),
    ]);
}
```

#### Product list with average rating

```php
RatingField::make('avg_rating')
    ->readOnly()
    ->size('sm')
    ->color('primary')
    ->default(fn ($record) => $record->reviews()->avg('rating'));
```

---

### Playground

`/admin/flex-fields-playground/rating`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [RatingColumn](/docs/ratingcolumn) | Read-only rating display in tables. |
| [NpsField](/docs/nps-field) | Survey-specific scales (0–10) with color coding. |
| [SegmentControl](/docs/segmentcontrol) | Generic single-select segments. |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-rating-field` | Root wrapper |
| `fff-rating-field--{sm\|md\|lg}` | Size variant |
| `fff-rating-field__container` | Icons container |
| `fff-rating-field__item` | Individual icon wrapper |
| `fff-rating-field__value` | Numeric value (read-only) |

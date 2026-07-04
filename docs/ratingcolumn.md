---
title: "RatingColumn"
description: Visual star or icon rating display for Filament tables with fractional fill support.
---

[← Back to Table of Contents](/docs/index)

### Summary

A table column for displaying numeric ratings as a series of stars or custom icons. It supports fractional fills (e.g., 4.5 stars), custom icons, and color-coded states. Pair this with [RatingField](/docs/ratingfield) for a complete rating system.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Tables\Columns\RatingColumn` |
| **State type** | `float\|int\|null` |
| **Model cast** | `'rating' => 'float'` |
| **Playground** | `rating-column` slug in Flex Fields playground |
| **Default max** | `5` |
| **Default icon** | `heroicon-s-star` |

---

### Basic usage

#### Standard 5-star rating
```php
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\RatingColumn;

RatingColumn::make('average_rating')
    ->label('Score');
```

#### Custom icons and colors
```php
RatingColumn::make('satisfaction')
    ->ratingIcon('heroicon-s-face-smile')
    ->ratingColor('success')
    ->stars(10);
```

---

### State & formatting

#### Stored value
The column expects a numeric value (integer or float). Values exceeding the `stars()` count are capped at the maximum.

```php
$record->rating; // 4.5
```

#### Fractional Fills
`RatingColumn` automatically calculates fractional fills for non-integer values. A rating of `3.7` will show three full stars and one star filled to 70%.

#### Displaying the numeric value
By default, the numeric value is shown next to the icons. You can hide it if needed:

```php
RatingColumn::make('rating')
    ->showValue(false);
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `stars(int\|Closure $count)` | Setup | `5` | Maximum number of icons to show |
| `ratingColor(string\|Closure $color)` | Setup | `'warning'` | Icon fill color |
| `ratingIcon(string\|Closure $icon)` | Setup | `star` | Filament icon string |
| `showValue(bool\|Closure $cond)` | Setup | `true` | Show numeric value text |
| `ratingSize(string\|Closure $size)` | Setup | `'md'` | `sm`, `md`, `lg` |

---

### Real-world examples

#### Product Reviews Table
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title'),
            RatingColumn::make('rating')
                ->stars(5)
                ->ratingColor(fn ($state) => $state >= 4 ? 'success' : 'warning')
                ->sortable(),
        ]);
}
```

---

### Playground

`/admin/flex-fields-playground/rating-column`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [RatingField](/docs/ratingfield) | Form input for collecting ratings |
| [NpsField](/docs/nps-field) | Survey-style NPS or Likert scales |
| [ProgressBar](/docs/progressbar) | Linear progress instead of icon rating |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-rating-column` | Root wrapper |
| `fff-rating-column__icons` | Container for all icons |
| `fff-rating-column__icon-wrapper` | Individual icon container |
| `fff-rating-column__icon-bg` | Background (empty) icon |
| `fff-rating-column__icon-fill` | Foreground (filled) icon |
| `fff-rating-column__value` | Numeric value text |

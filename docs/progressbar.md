---
title: "ProgressBar"
description: Linear progress indicator with determinate, indeterminate, segmented delivery tracker, and pill-style variants.
---

![ProgressBar](/art/sc-4.png)

[← Back to Table of Contents](/docs/index)

### Summary

Versatile linear progress indicator for uploads, sync status, stepped delivery trackers, and multi-segment bars. Supports animations, gradients, and optional card shell wrapping.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressBar` |
| **State** | None — display-only schema component |
| **Playground** | `progress-bar` slug in Flex Fields playground |
| **Stylesheet** | Lazy `progress-bar` bundle |

---

### Basic usage

#### Simple determinate progress

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressBar;

ProgressBar::make()
    ->label('Upload progress')
    ->value(65)
    ->max(100)
    ->showValue()
    ->color('success');
```

#### Indeterminate loading state

```php
ProgressBar::make()
    ->label('Syncing data...')
    ->indeterminate()
    ->color('primary');
```

#### Stepped delivery tracker

```php
ProgressBar::make()
    ->segments([
        ['label' => 'Ordered', 'description' => 'Completed'],
        ['label' => 'Shipped', 'description' => 'In transit'],
        ['label' => 'Delivered', 'description' => 'Pending'],
    ])
    ->activeSegment(1)
    ->activeSegmentProgress(0.5)
    ->color('success');
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `value(float\|int\|Closure\|null $value)` | Setup | `0` | Current progress value. |
| `max(float\|int\|Closure $max)` | Setup | `100` | Maximum progress value. |
| `label(string\|Closure\|null $label)` | Setup | `null` | Header text shown above the track. |
| `displayValue(string\|Closure\|null $value)` | Setup | `null` | Custom formatted value string (e.g., "3 of 5 files"). |
| `showValue(bool\|Closure $condition = true)` | Setup | `true` | Show percentage or display value in the header. |
| `valueBadge(bool\|Closure $condition = true)` | Setup | `false` | Render the value as a pill badge in the header. |
| `color(string\|Closure\|null $color)` | Setup | `primary` | Semantic color: `primary`, `success`, `warning`, `danger`. |
| `indeterminate(bool\|Closure $condition = true)` | Setup | `false` | Animated loading bar for unknown progress. |
| `variant(string\|Closure $variant)` | Setup | `'track'` | `track` (continuous) or `pills` (segmented). |
| `pillCount(int\|Closure $count)` | Setup | `null` | Number of segments in `pills` variant. |
| `gradientFrom(string\|Closure\|null $color)` | Setup | `null` | Start color for gradient fill. |
| `gradientTo(string\|Closure\|null $color)` | Setup | `null` | End color for gradient fill. |
| `segments(array\|Closure\|null $segments)` | Setup | `null` | Array of step metadata for delivery-tracker mode. |
| `activeSegment(int\|Closure\|null $index)` | Setup | `null` | 0-based index of the active step. |
| `activeSegmentProgress(float\|Closure $progress)` | Setup | `0.62` | Partial fill (0–1) within the active step. |
| `segmentThumb(bool\|Closure $condition = true)` | Setup | `auto` | Show a thumb indicator on the active segment. |
| `startMarker(mixed $icon)` | Setup | `null` | Icon at the beginning of the track. |
| `currentMarker(mixed $icon)` | Setup | `null` | Icon at the current progress position. |
| `endMarker(mixed $icon)` | Setup | `null` | Icon at the end of the track. |
| `remainingTrackStyle(string $style)` | Setup | `'solid'` | Style of unfilled track: `solid` or `dashed`. |
| `shell(bool\|Closure $condition = true)` | Setup | `false` | Wrap in an elevated card container. |
| `description(string\|Closure $text)` | Setup | `null` | Subtitle text (requires `shell()`). |
| `footer(string\|Closure $text)` | Setup | `null` | Footer text (requires `shell()`). |
| `size(string\|Closure $size)` | Setup | `'md'` | Track height: `sm`, `md`, `lg`. |
| `animated(bool\|Closure $condition = true)` | Setup | `true` | Animate fill transitions. |
| `animationDuration(int\|Closure $ms)` | Setup | `400` | Transition duration in milliseconds. |

---

### Real-world examples

#### Animated file upload (Livewire)

```php
ProgressBar::make()
    ->label('Uploading photos')
    ->value(fn () => $this->uploadProgress)
    ->showValue()
    ->animated()
    ->color('primary');
```

#### Delivery route with markers

```php
ProgressBar::make()
    ->label('Delivery Status')
    ->value(45)
    ->startMarker('heroicon-o-map-pin')
    ->currentMarker('heroicon-o-truck')
    ->endMarker('heroicon-o-home')
    ->remainingTrackStyle('dashed')
    ->color('success');
```

---

### Playground

`/admin/flex-fields-playground/progress-bar`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [ProgressCircle](/docs/progresscircle) | Circular or semi-circular progress display. |
| [RatingField](/docs/ratingfield) | When the "progress" represents a user rating or score. |
| [SegmentControl](/docs/segmentcontrol) | For interactive selection between discrete options. |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-progress-bar` | Root container |
| `fff-progress-bar--{sm\|md\|lg}` | Size variant |
| `fff-progress-bar--indeterminate` | Indeterminate animation state |
| `fff-progress-bar--pills` | Pill variant modifier |
| `fff-progress-bar--segments` | Stepped tracker modifier |
| `fff-progress-bar--has-shell` | Card shell wrapper active |
| `fff-progress-bar__track` | Main track background |
| `fff-progress-bar__fill` | Animated fill element |
| `fff-progress-bar__marker` | Icon marker wrapper |
| `fff-progress-bar__segment` | Individual step in tracker mode |

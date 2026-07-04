---
title: "ProgressCircle"
description: SVG circular progress with full-circle and semi-circle variants, gradient strokes, and optional card shell.
---

![ProgressCircle](/art/sc-19.png)

[← Back to Table of Contents](/docs/index)

### Summary

High-performance SVG circular progress indicator. Supports full-circle and semi-circle variants, rounded stroke caps, centered or side-aligned content, and advanced gradient strokes for both fill and track.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressCircle` |
| **State** | None — display-only schema component |
| **Playground** | `progress-circle` slug in Flex Fields playground |
| **Stylesheet** | Lazy `progress-circle` bundle |

---

### Basic usage

#### Standard circle

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressCircle;

ProgressCircle::make()
    ->value(75)
    ->displayValue('75%')
    ->color('primary');
```

#### Semicircle with gradient

```php
ProgressCircle::make()
    ->variant('semicircle')
    ->value(69)
    ->gapAngle(24)
    ->gradientFrom('#6366f1')
    ->gradientTo('#ec4899')
    ->label('Completion rate');
```

#### Card layout with side content

```php
ProgressCircle::make()
    ->value(82)
    ->contentLayout('left')
    ->shell()
    ->heading('Storage used')
    ->description('Last 30 days')
    ->footer('Updated 5m ago');
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `value(float\|int\|Closure\|null $value)` | Setup | `0` | Arc fill ratio. |
| `max(float\|int\|Closure $max)` | Setup | `100` | Maximum progress value. |
| `displayValue(string\|Closure\|null $value)` | Setup | `null` | Primary center text. Auto-derived from percent when omitted. |
| `fraction(string\|Closure\|null $fraction)` | Setup | `null` | Secondary line (e.g., "124 / 200") shown below display value. |
| `label(string\|Closure\|null $label)` | Setup | `null` | Tertiary label shown at the bottom. |
| `variant(string\|Closure $variant)` | Setup | `'circle'` | `circle` (full ring) or `semicircle` (bottom arc). |
| `gapAngle(float\|int\|Closure $degrees)` | Setup | `0` | Degrees of empty gap at the bottom of the arc. |
| `paused(bool\|Closure $condition = true)` | Setup | `false` | Show paused state with center icon instead of text. |
| `pausedIcon(mixed $icon)` | Setup | `PauseFill` | Icon to show when `paused()` is true. |
| `color(string\|Closure\|null $color)` | Setup | `primary` | Semantic stroke color. |
| `size(string\|Closure $size)` | Setup | `'md'` | Diameter scale: `sm`, `md`, `lg`. |
| `gradientFrom(string\|Closure $color)` | Setup | `null` | Start color for progress stroke gradient. |
| `gradientTo(string\|Closure $color)` | Setup | `null` | End color for progress stroke gradient. |
| `gradientStroke(string\|Closure $css)` | Setup | `null` | Raw CSS gradient string for fill stroke. |
| `trackGradientFrom(string\|Closure $color)` | Setup | `null` | Start color for track (unfilled) gradient. |
| `trackGradientTo(string\|Closure $color)` | Setup | `null` | End color for track (unfilled) gradient. |
| `contentLayout(string\|Closure $layout)` | Setup | `'center'` | Text position: `center`, `left`, `right`, `above`. |
| `shell(bool\|Closure $condition = true)` | Setup | `false` | Wrap in an elevated card container. |
| `heading(string\|Closure $text)` | Setup | `null` | Card heading (requires `shell()`). |
| `description(string\|Closure $text)` | Setup | `null` | Card description (requires `shell()`). |
| `footer(string\|Closure $text)` | Setup | `null` | Card footer (requires `shell()`). |
| `animated(bool\|Closure $condition = true)` | Setup | `true` | Animate fill transitions. |
| `animationDuration(int\|Closure $ms)` | Setup | `400` | Transition duration in milliseconds. |

---

### Real-world examples

#### Dashboard Completion Rate

```php
ProgressCircle::make()
    ->value(fn () => $this->getCompletionRate())
    ->variant('semicircle')
    ->gapAngle(30)
    ->size('lg')
    ->color('success')
    ->label('Profile completion')
    ->shell();
```

#### Storage Usage with Fraction

```php
ProgressCircle::make()
    ->value(18.5)
    ->max(20)
    ->displayValue('92%')
    ->fraction('18.5GB / 20GB')
    ->color('warning')
    ->size('md');
```

---

### Playground

`/admin/flex-fields-playground/progress-circle`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [ProgressBar](/docs/progressbar) | Linear progress for uploads or stepped trackers. |
| [RatingField](/docs/ratingfield) | Star/icon ratings for user feedback. |
| [CoverCard](/docs/covercard) | Media-heavy cards with background images. |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-progress-circle` | Root wrapper |
| `fff-progress-circle--{sm\|md\|lg}` | Size variant |
| `fff-progress-circle--semicircle` | Semicircle variant modifier |
| `fff-progress-circle--has-shell` | Card shell active |
| `fff-progress-circle--layout-{center\|left\|right\|above}` | Content alignment |
| `fff-progress-circle__svg` | SVG element |
| `fff-progress-circle__track` | Unfilled background arc |
| `fff-progress-circle__fill` | Animated progress arc |
| `fff-progress-circle__content` | Centered text container |
| `fff-progress-circle__display-value` | Primary numeric text |
| `fff-progress-circle__fraction` | Secondary fraction text |
| `fff-progress-circle__label` | Bottom label text |

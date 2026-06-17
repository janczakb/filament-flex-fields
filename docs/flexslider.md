---
title: "FlexSlider"
---

[← Back to Table of Contents](/docs/index)


### Summary

Styled wrapper around Filament's `Slider` with SaaS-like track, step dots, server-rendered pips, fill segments, and formatted value display.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider` |
| **State type** | `int\|float` or `[min, max]` for range |
| **FieldType** | `flex_slider` |
| **Parent** | `Filament\Forms\Components\Slider` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;

FlexSlider::make('volume')
    ->range(0, 100)
    ->step(5)
    ->showValue()
    ->suffix('%')
    ->color('primary');

FlexSlider::make('price_range')
    ->range(0, 1000)
    ->step(10)
    ->fillTrack([false, true, false])
    ->prefix('$')
    ->trackLabel('Budget')
    ->decimalPlaces(0);
```

Range with auto-fill between handles:

```php
FlexSlider::make('age_range')
    ->range(18, 80)
    ->step(1)
    ->autoFill()
    ->showValue()
    ->valuePosition('start');
```

### State format

Same as Filament Slider: single numeric or array of two values for dual-handle range. Normalized via `normalizeNumeric()` respecting `step()` and `decimalPlaces()`.

### Validation

Inherits Filament Slider rules (`min`, `max`, `step`, etc.).

### Configuration API (FlexSlider-specific)

#### `showStepDots(bool|Closure $condition = true)`


Renders indicator dots on the slider track corresponding to each step interval. Requires a `step()` to be defined.

```php
FlexSlider::make('rating')
    ->min(1)
    ->max(5)
    ->step(1)
    ->showStepDots();
```
### Inherited Filament Slider API

Also configure via parent methods:

| Method | Purpose |
|--------|---------|
| `range($min, $max)` | Bounds |
| `step($step)` | Step increment |
| `fillTrack(array\|bool)` | Which segments are filled |
| `decimalPlaces(int)` | Fixed decimal display |
| `pips(PipsMode)` | Pip mode: Steps, Positions, Count, etc. |
| `pipsValues(...)` / `pipsDensity(...)` | Pip configuration |
| `vertical()` | Vertical orientation |
| `rangePadding(...)` | Padding beyond min/max |
| `default($value)` | Initial value |
| `disabled()` / `nullable()` | State modifiers |

See [Filament Slider documentation](https://filamentphp.com/docs/forms/fields/slider) for full parent API.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `shouldShowValue()` | `bool` | Value visible |
| `getDisplayPrefix()` / `getDisplaySuffix()` | `string\|null` | Affixes |
| `getVariant()` | `string` | Variant name |
| `getTrackLabel()` | `string\|null` | Header label |
| `shouldHideThumbUntilInteraction()` | `bool` | Thumb hidden initially |
| `getValuePosition()` | `string` | Label position |
| `shouldAutoFill()` | `bool` | Auto fill segments |
| `getColor()` / `getFillColor()` | `string\|null` | Colors |
| `getNormalizedStateValues()` | `list&lt;float&gt;` | Current handle values |
| `isRangeState()` | `bool` | Range mode |
| `valueToPercent()` / `valueToRatio()` | `float` | Position math |
| `getInitialValueRatios()` | `list&lt;float&gt;` | SSR thumb positions |
| `formatDisplayValue(?float $value)` | `string` | Formatted label |
| `shouldShowStepDots()` | `bool` | Step dots visible |
| `getStepDotRatios()` | `list&lt;float&gt;` | Dot positions |
| `shouldRenderServerPips()` | `bool` | SSR pips mode |
| `getServerRenderedPips()` | `list&lt;array&gt;` | Pip markup data |
| `resolveConnectForChrome()` | `array\|false` | Fill connection flags |
| `getInitialFillSegments()` | `list&lt;array&gt;` | SSR fill segments |
| `normalizeNumeric(float\|int $value)` | `float` | Step-aligned value |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `min` / `max` | `range()` |
| `step` | `step()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `show_value` | `showValue()` |
| `prefix` | `prefix()` |
| `suffix` | `suffix()` |
| `track_label` | `trackLabel()` |
| `hide_thumb_until_interaction` | `hideThumbUntilInteraction()` |
| `value_position` | `valuePosition()` |
| `fill_track` / `auto_fill` | `fillTrack()` / `autoFill()` |
| `color` | `color()` |
| `fill_color` | `fillColor()` |
| `decimal_places` | `decimalPlaces()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-flex-slider` | Root wrapper |
| `fff-flex-slider--{sm\|md\|lg}` | Size modifier |
| `fff-flex-slider--secondary` | Secondary variant |
| `fff-flex-slider__rail` | Track hit area |
| `fff-flex-slider__fill` | Filled segment |
| `fff-flex-slider__thumb` | Draggable handle |
| `fff-flex-slider__pip` | Scale pip |
| `fff-flex-slider__step-dot` | Step indicator dot |

Uses CSS variables `--fff-flex-slider-accent`, `--fff-flex-slider-value-ratio`, etc.

### Implementation notes

- Server-rendered pips and fill segments reduce layout shift before Alpine hydrates.
- Step dots capped at 11 visible dots (`MAX_STEP_DOTS`); excess steps are sampled.
- Vertical sliders disable step dots and server pips.

---

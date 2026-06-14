# ProgressBar

[← Powrót do spisu treści](../README.md)


Linear progress indicator for uploads, sync status, stepped delivery trackers, and pill-style multi-segment bars.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressBar` |
| **State** | None — display-only schema component |
| **Playground** | `progress-bar` |
| **Stylesheet** | Lazy `progress-bar` bundle |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressBar;

ProgressBar::make()
    ->label('Upload')
    ->value(60)
    ->max(100)
    ->showValue(true)
    ->size('md')
    ->color('primary');
```

### Configuration API

#### `value(float|int|Closure|null $value)` / `max(float|int|Closure $max)`


Numeric progress. Default `max`: `100`. Ratio = `value / max`.

```php
->value(42)->max(200) // 21%
```

#### `label(string|Closure|null $label)`


Header text above the track.

```php
ProgressBar::make('field_name')
    ->label('value');
```
#### `displayValue(string|Closure|null $value)` / `showValue(bool|Closure $condition = true)`


Custom formatted value string. When `showValue()` is true, shown beside the label (or as badge when `valueBadge()`).

```php
->displayValue('3 of 5 files')
->showValue()
```

#### `valueBadge(bool|Closure $condition = true)`


Render the value as a pill badge in the header instead of inline text.

```php
ProgressBar::make('field_name')
    ->valueBadge(true);
```
#### `color(string|Closure|null $color)`


Semantic track/fill color: `primary`, `success`, `warning`, `danger`. Default: `primary`.

```php
ProgressBar::make('field_name')
    ->color('primary');
```
#### `indeterminate(bool|Closure $condition = true)`


Animated loading bar when progress is unknown:

```php
ProgressBar::make()->label('Syncing')->indeterminate();
```

#### `variant('default'|'pills')` / `pillCount(int|Closure $count)`


| Variant | Description |
|---------|-------------|
| `default` | Single continuous track |
| `pills` | Segmented pill track; `value` = number of filled pills |

```php
ProgressBar::make()
    ->variant('pills')
    ->pillCount(5)
    ->value(3);
```

When `pillCount` is omitted in pills mode, count is derived from `max`.

#### `gradientFrom(string|Closure|null $color)` / `gradientTo(string|Closure|null $color)`


CSS color stops for gradient fill (pills and default track):

```php
->gradientFrom('rgb(99 102 241)')
->gradientTo('rgb(236 72 153)')
```

#### `segments(array|Closure|null $segments)` / `activeSegment(int|Closure|null $index)`


Stepped **delivery-tracker** mode. Each segment: `label`, optional `icon`, optional `color`.

```php
ProgressBar::make()
    ->segments([
        ['label' => 'Ordered', 'icon' => 'gravityui-check'],
        ['label' => 'Shipped'],
        ['label' => 'Delivered'],
    ])
    ->activeSegment(1)
    ->color('success');
```

#### `segmentThumb(bool|Closure $condition = true)` / `activeSegmentProgress(float|Closure $progress)`


Show a draggable-style thumb on the active segment. `activeSegmentProgress` (0–1) controls partial fill within the active step.

```php
ProgressBar::make('field_name')
    ->segmentThumb(true)
    ->activeSegmentProgress(10);
```
#### `startMarker()` / `currentMarker()` / `endMarker()`


Optional icons at track start, current position, and end (`string|BackedEnum|Htmlable`).

```php
->startMarker('gravityui-circle')
->endMarker('gravityui-check')
->currentMarker('gravityui-pin')
```

#### `remainingTrackStyle('solid'|'dashed')`


Style of the unfilled portion of the track. Default: `solid`.

```php
ProgressBar::make('field_name')
    ->remainingTrackStyle();
```
#### `shell(bool|Closure $condition = true)` / `description()` / `footer()`


Optional card chrome wrapping the bar:

```php
ProgressBar::make()
    ->label('Backup')
    ->value(80)
    ->shell()
    ->description('Daily snapshot in progress…')
    ->footer('Estimated 2 min remaining');
```

#### `size('sm'|'md'|'lg')`


Track height and typography scale. Default: `md`.

```php
ProgressBar::make('field_name')
    ->size('md');
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getProgressRatio()` | `float` | `value / max` clamped 0–1 |
| `getPercentage()` | `int` | Rounded percent |
| `getFormattedValue()` | `string` | Display string |
| `getNormalizedSegments()` | `array` | Segment metadata |
| `isIndeterminate()` | `bool` | Loading mode |
| `isPillsVariant()` | `bool` | Pills mode |
| `getActivePillCount()` | `int` | Filled pills |
| `hasShell()` / `hasCardChrome()` | `bool` | Card wrapper state |

### CSS classes

| Class | Role |
|-------|------|
| `fff-progress-bar` | Root |
| `fff-progress-bar--{sm\|md\|lg}` | Size |
| `fff-progress-bar--indeterminate` | Loading animation |
| `fff-progress-bar--pills` | Pill variant |
| `fff-progress-bar--segments` | Stepped tracker |
| `fff-progress-bar__track` | Track background |
| `fff-progress-bar__fill` | Filled portion |

---

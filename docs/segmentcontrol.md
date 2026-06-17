# SegmentControl

![SegmentControl](/art/sc-17.png)

[← Back to Table of Contents](index.md)


### Summary

iOS-style **single-select** segmented control.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl` |
| **State type** | `string\|int` — one option key |
| **Model cast** | `'alignment' => 'string'` |
| **FieldType** | `segment_control` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl;

SegmentControl::make('alignment')
    ->label('Alignment')
    ->options([
        'left' => 'Left',
        'center' => [
            'label' => 'Center',
            'icon' => 'heroicon-o-bars-3',
            'tooltip' => 'Centered text',
        ],
        'right' => 'Right',
    ])
    ->variant('ghost')
    ->fullWidth();
```

### Validation

Built-in `Rule::in(...)` against option keys.

### Configuration API

#### `options(array|Closure $options)`


| Key | Type | Description |
|-----|------|-------------|
| `label` | `string` | Segment label |
| `icon` | `string\|null` | Heroicon (or use `icons()`) |
| `tooltip` | `string\|null` | Hover tooltip |
| `disabled` | `bool` | Disables this segment |

Simple form: `'left' => 'Left'`.

```php
SegmentControl::make('field_name')
    ->options([
        'option_1' => 'Option 1',
        'option_2' => 'Option 2',
    ]);
```
#### `icons(array|Closure $icons)`


Map of option key → Heroicon name.

```php
SegmentControl::make('field_name')
    ->icons([
        'option_1' => 'heroicon-o-star',
        'option_2' => 'heroicon-o-heart',
    ]);
```
#### `disabledOptions(array|Closure $keys)`


Disable segments by key.

```php
SegmentControl::make('field_name')
    ->disabledOptions(['option_2']);
```
#### `variant(string|Closure $variant)`


| Value | Description |
|-------|-------------|
| `default` | Filled track background. **Default.** |
| `ghost` | Transparent track; uses `color()` for selection accent. |

```php
SegmentControl::make('field_name')
    ->variant('primary');
```
#### `color(string|Closure|null $color)`


Selection accent color. For `ghost`, defaults to `primary` when omitted.

```php
SegmentControl::make('field_name')
    ->color('primary');
```
#### `separators(bool|Closure $condition = true)`


Vertical dividers between segments. **Default: true.**

```php
SegmentControl::make('field_name')
    ->separators(true);
```
#### `fullWidth(bool|Closure $condition = true)`


Stretch the control to the full field width.

```php
SegmentControl::make('field_name')
    ->fullWidth(true);
```
#### `iconOnly(bool|Closure $condition = true)`


Hide labels; show icons only. Requires icons on options.

```php
SegmentControl::make('field_name')
    ->iconOnly(true);
```
#### `expandSelectedLabel(bool|Closure $condition = true)`


Animates the selected segment to a wider width (label expansion).

```php
SegmentControl::make('field_name')
    ->expandSelectedLabel(true);
```
#### `size(string|ControlSize|Closure $size)`


See [Control size](shared-concepts.md).

```php
SegmentControl::make('field_name')
    ->size('md');
```

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `full_width` | `fullWidth()` |
| `icons` | `icons()` |
| `disabled_options` | `disabledOptions()` |
| `color` | `color()` |
| `separators` | `separators()` |
| `icon_only` | `iconOnly()` |
| `expand_selected_label` | `expandSelectedLabel()` |

---

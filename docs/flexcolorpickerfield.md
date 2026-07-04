---
title: "FlexColorPickerField"
description: Full-featured color picker with advanced HSV panel, eyedropper, and preset grid layouts.
---

![FlexColorPickerField](/art/sc-11.png)

[← Back to Table of Contents](/docs/index)

### Summary

Full-featured color picker with two distinct layouts: **Advanced** (HSV square + hue/alpha sliders + eyedropper) and **Grid** (preset swatches). Supports multiple output formats (Hex, RGB, HSL, RGBA) and features a modern, compact UI.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexColorPickerField` |
| **State type** | `string\|null` — color in the configured output format |
| **FieldType** | `flex_color_picker` |
| **Playground** | `flex-color-picker` slug in Flex Fields playground |

---

### Basic usage

#### Advanced HSV Picker
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexColorPickerField;

FlexColorPickerField::make('brand_color')
    ->label('Brand color')
    ->alpha() // Enable opacity
    ->required();
```

#### Grid Palette
```php
FlexColorPickerField::make('accent')
    ->layout(FlexColorPickerField::LAYOUT_GRID)
    ->gridColumns(10)
    ->gridRows(5)
    ->rgba();
```

---

### State & validation

#### Stored value
State is a CSS color string in the format selected via `hex()`, `rgb()`, `hsl()`, or `rgba()`.

```php
$record->brand_color; // '#6366F1' or 'rgba(99, 102, 241, 0.8)'
```

#### Validation rules (built-in)
Ensures the input is a valid color string matching the expected format.

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `layout(string $layout)` | Setup | `'advanced'` | `advanced` or `grid` |
| `hex()` | Format | active | Set output format to Hex |
| `rgb()` | Format | — | Set output format to RGB |
| `hsl()` | Format | — | Set output format to HSL |
| `rgba()` | Format | — | Set output format to RGBA |
| `format(string $format)` | Setup | `'hex'` | `hex`, `rgb`, `hsl`, `rgba` |
| `alpha(bool $enabled)` | Setup | `false` | Enable opacity slider and inputs |
| `eyedropper(bool $enabled)` | Setup | `true` | Show browser EyeDropper button |
| `gridColumns(int $cols)` | Setup | `17` | Grid columns (generated palette) |
| `gridRows(int $rows)` | Setup | `11` | Grid rows (generated palette) |
| `gridColors(array $colors)` | Setup | `null` | Custom hex list for grid layout |
| `variant(string $variant)` | Setup | `'primary'` | `primary`, `secondary`, `flat` |
| `size(string $size)` | Setup | `'md'` | `sm`, `md`, `lg` |
| `rounding(string $rounding)` | Setup | config | Border radius token |

---

### Real-world examples

#### Transparent Background Color
```php
FlexColorPickerField::make('bg_color')
    ->rgba()
    ->alpha()
    ->label('Background Color')
    ->helperText('Select a color with transparency');
```

#### Fixed Palette Selection
```php
FlexColorPickerField::make('theme')
    ->layout('grid')
    ->gridColors([
        '#000000', '#ffffff', '#ff0000', '#00ff00', '#0000ff'
    ])
    ->withoutPrefix();
```

---

### Playground

`/admin/flex-fields-playground/flex-color-picker`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [ColorSwatchField](/docs/colorswatchfield) | Simple preset selection with keys |
| [FlexTextInput](/docs/flextextinput) | Standard text input for hex codes |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-flex-color-picker` | Root wrapper |
| `fff-flex-color-picker__shell` | Trigger + panel container |
| `fff-flex-color-picker__trigger` | Opens picker panel |
| `fff-flex-color-picker__preview` | Color swatch in trigger |
| `fff-flex-color-picker__panel` | Dropdown panel |
| `fff-flex-color-picker__saturation` | S/V square (advanced) |
| `fff-flex-color-picker__eyedropper` | Screen color picker button |
| `fff-flex-color-picker__hue` | Hue slider |
| `fff-flex-color-picker__alpha` | Opacity slider track |
| `fff-flex-color-picker__grid` | Grid swatch container |
| `fff-flex-color-picker__bottom-bar` | Format + value + opacity inputs |

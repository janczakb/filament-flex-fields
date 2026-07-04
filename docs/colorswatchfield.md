---
title: "ColorSwatchField"
description: Preset color picker: horizontal swatch pills with optional section header and tooltips.
---

![ColorSwatchField](/art/sc-24.png)

[← Back to Table of Contents](/docs/index)

### Summary

Preset color picker featuring horizontal swatch pills with optional section headers and hover tooltips. Ideal for theme selection, category coloring, or any case where users choose from a predefined palette.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\ColorSwatchField` |
| **State type** | `string\|null` — selected color **key** (not hex) |
| **FieldType** | `color_presets` |
| **Playground** | `color-swatch` slug in Flex Fields playground |

---

### Basic usage

#### Standard Theme Colors
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ColorSwatchField;

ColorSwatchField::make('theme_color')
    ->colors([
        'indigo' => '#6366f1',
        'rose' => '#f43f5e',
        'emerald' => '#10b981',
    ])
    ->sectionLabel('Brand colors')
    ->tooltips(true);
```

#### Large Swatches with Custom Tooltips
```php
ColorSwatchField::make('accent')
    ->colors(['primary' => '#3b82f6', 'white' => '#ffffff'])
    ->tooltips([
        'primary' => 'Primary Brand Blue',
        'white' => 'Pure White',
    ])
    ->size('lg');
```

---

### State & validation

#### Stored value
State is the **array key** from `colors()`, not the hex value. This allows you to change the hex values in your code without breaking existing database records.

```php
$record->theme_color; // 'indigo'
```

#### Validation rules (built-in)
| Rule | Detail |
|------|--------|
| `nullable` | No selection allowed (unless `required()`) |
| `Rule::in(...)` | Key must exist in the configured `colors()` array |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `colors(array $colors)` | Setup | `[]` | Map of `key => hex` (or CSS color) |
| `sectionLabel(string $label)` | Setup | `null` | Heading above swatches |
| `sectionIcon(string $icon)` | Setup | config | Icon next to section label |
| `tooltips(bool\|array $tooltips)` | Setup | `false` | `true` for auto-labels, or custom array |
| `size(string $size)` | Setup | `'md'` | `sm`, `md`, `lg` |

---

### Real-world examples

#### Status Color Picker
```php
ColorSwatchField::make('status_color')
    ->colors([
        'danger' => '#ef4444',
        'warning' => '#f59e0b',
        'success' => '#10b981',
        'info' => '#3b82f6',
    ])
    ->tooltips(true)
    ->required();
```

#### Compact Palette
```php
ColorSwatchField::make('tag_color')
    ->colors($this->getAvailableColors())
    ->size('sm')
    ->hiddenLabel();
```

---

### Playground

`/admin/flex-fields-playground/color-swatch`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexColorPickerField](/docs/flexcolorpickerfield) | Full HSV/Grid color picker for any color |
| [ChoiceCards](/docs/choicecards) | Large card-style selection |
| [NpsField](/docs/nps-field) | Survey-style color-coded scales |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-color-swatch` | Root wrapper |
| `fff-color-swatch--{sm\|md\|lg}` | Size variant |
| `fff-color-swatch__header` | Section label container |
| `fff-color-swatch__swatches` | Swatch pills container |
| `fff-color-swatch__pill` | Individual color button |
| `fff-color-swatch__pill--light` | Border for light colors (contrast) |
| `is-selected` | Selected state |
| `is-disabled` | Disabled state |

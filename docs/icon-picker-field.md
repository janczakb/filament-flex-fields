---
title: "IconPickerField"
description: Searchable blade-icons picker with lazy SVG rendering, set filters, and virtual scrolling.
---

![IconPickerField](/art/sc-32.webp)

[← Back to Table of Contents](/docs/index)

### Summary

Searchable **blade-icons** picker with lazy SVG rendering, set filters, whitelist/exclude controls, paginated server-side search, and virtual scrolling for large catalogs. Stores the full icon name string (e.g. `heroicon-o-star`, `gravityui-star`).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField` |
| **State type** | `string\|null` |
| **FieldType** | `icon_picker` |
| **Playground** | `icon-picker-field` slug in Flex Fields playground |

The stored value is a plain string, making it compatible with any standard Filament or Blade icon component.

---

### Basic usage

#### Standard Icon Picker
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;

IconPickerField::make('menu_icon')
    ->label('Menu icon')
    ->sets(['heroicons', 'gravity-icons'])
    ->preload()
    ->required();
```

#### Strict Whitelist
```php
IconPickerField::make('status_icon')
    ->sets(['heroicons'])
    ->icons([
        'heroicon-o-check-circle',
        'heroicon-o-exclamation-triangle',
        'heroicon-o-x-circle',
    ])
    ->gridColumns(4)
    ->size('sm');
```

---

### State & validation

#### Stored value
State is the full blade-icons name stored as a plain string.

```php
$record->menu_icon; // 'heroicon-o-star'
```

#### Validation rules (built-in)
Custom rule checks the value against the configured catalog (sets, whitelist, and exclude list).

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `sets(array\|string $sets)` | Setup | all | Limit available icon libraries |
| `icons(array $icons)` | Setup | — | Whitelist only these full icon names |
| `excludeIcons(array $icons)` | Setup | — | Remove icons from the catalog |
| `searchResultsLayout(string $layout)` | Setup | `icons` | `icons`, `grid`, or `list` |
| `gridColumns(int $columns)` | Setup | `8` | Column count for grid layouts (2–12) |
| `preload(bool $condition)` | Setup | `false` | Fetch first page on mount |
| `closeOnSelect(bool $condition)` | Setup | `true` | Close panel after selection |
| `perPage(int $perPage)` | Setup | `64` | Server page size (max 96) |
| `limitPerSet(int $limit)` | Setup | — | Cap icons indexed per set |
| `clearable(bool $condition)` | Setup | `true` | Show clear (×) button |
| `variant(string $variant)` | Setup | `bordered` | `bordered`, `secondary`, `flat`, `soft`, `faded` |
| `size(string $size)` | Setup | `'md'` | `sm`, `md`, `lg` |
| `placeholder(string $text)` | Setup | default | Trigger placeholder |

---

### Real-world examples

#### CMS Navigation Icon
```php
IconPickerField::make('icon')
    ->label('Navigation Icon')
    ->sets(['heroicons'])
    ->iconsOnly()
    ->gridColumns(10)
    ->preload();
```

#### Frontend Usage (Blade)
```blade
@if (filled($category->icon))
    <x-icon :name="$category->icon" class="h-6 w-6" />
@endif
```

---

### Playground

`/admin/flex-fields-playground/icon-picker-field`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [IconColumn](/docs/iconcolumn) | Read-only icon display in tables |
| [SelectField](/docs/selectfield) | Generic selection without icon previews |
| [RatingField](/docs/ratingfield) | Star-based rating input |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-icon-picker` | Alpine root inside trigger |
| `fff-icon-picker-field` | Field wrapper modifier |
| `fff-icon-picker__preview` | Selected icon SVG in trigger |
| `fff-icon-picker__panel` | Teleported dropdown panel |
| `fff-icon-picker__toolbar` | Search and set tabs container |
| `fff-icon-picker__results` | Scrollable results list |
| `fff-icon-picker__grid` | Grid cells container |
| `fff-icon-picker__option` | Individual icon cell |
| `fff-icon-picker__skeleton` | Loading placeholder |

---
title: "IconColumn"
description: Read-only table column for displaying blade-icons with optional labels, colors, and sizes.
---

[← Back to Table of Contents](/docs/index)

### Summary

Read-only **table column** for displaying blade-icons values stored by [IconPickerField](/docs/icon-picker-field). Renders the SVG icon with optional human label, technical name, semantic color, and size tokens shared with the flex-field design system.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Tables\Columns\IconColumn` |
| **Context** | Filament tables (`$table->columns([...])`) |
| **State type** | `string\|null` — full icon name (e.g. `heroicon-o-star`) |
| **Parent** | `Filament\Tables\Columns\TextColumn` |
| **Playground** | `icon-column` slug in Flex Fields playground |

> **Import alias:** Filament ships its own `IconColumn`. When you need both, alias this class:
> `use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\IconColumn as FlexIconColumn;`

---

### Basic usage

#### Standard Icon
```php
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\IconColumn;

IconColumn::make('menu_icon')
    ->label('Icon');
```

#### Labeled Status Icon
```php
IconColumn::make('status_icon')
    ->iconColor('success')
    ->iconSize('lg')
    ->showLabel();
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `iconSize(string $size)` | Setup | `'md'` | `sm`, `md`, `lg` |
| `iconColor(string\|array $color)` | Setup | `null` | Semantic Filament color |
| `showLabel(bool $condition)` | Setup | `false` | Show human-readable label |
| `showName(bool $condition)` | Setup | `false` | Show technical icon name |
| `labelUsing(Closure $callback)` | Setup | default | Custom label resolver |

---

### Real-world examples

#### Navigation Table
```php
IconColumn::make('icon')
    ->iconSize('md')
    ->iconColor('primary')
    ->showLabel();
```

#### Debug / Developer View
```php
IconColumn::make('debug_icon')
    ->iconSize('sm')
    ->showLabel()
    ->showName()
    ->tooltip(fn ($state) => $state);
```

---

### Performance
- **SVG Cache**: Server-side SVG HTML is cached to prevent repeated disk lookups.
- **Render Cache**: Per-request caching of rendered column HTML.
- **Lazy CSS**: Stylesheets are loaded only when the column renders.

---

### Playground

`/admin/flex-fields-playground/icon-column`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [IconPickerField](/docs/icon-picker-field) | Form field for selecting icons |
| [UserColumn](/docs/usercolumn) | Avatar and user display column |
| [RatingColumn](/docs/ratingcolumn) | Star rating display column |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-icon-column` | Column cell root |
| `fff-icon-column--{sm\|md\|lg}` | Size modifiers |
| `fff-icon-column--with-label` | Icon + text layout |
| `fff-icon-column__icon` | SVG wrapper |
| `fff-icon-column__text` | Label/name stack |
| `fff-icon-column__label` | Human-readable label |
| `fff-icon-column__name` | Technical icon name |

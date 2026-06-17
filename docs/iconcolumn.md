---
title: "IconColumn"
---

[← Back to Table of Contents](/docs/index)


### Summary

Read-only **table column** for displaying blade-icons values stored by [IconPickerField](/docs/icon-picker-field). Renders the SVG icon with optional human label, technical name, semantic color, and size tokens shared with the flex-field design system.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Tables\Columns\IconColumn` |
| **Context** | Filament tables (`$table->columns([...])`) |
| **State type** | `string|null` — full icon name (e.g. `heroicon-o-star`) |
| **Parent** | `Filament\Tables\Columns\TextColumn` |

> **Import alias:** Filament ships its own `Filament\Tables\Columns\IconColumn`. When you need both, alias this class:
>
> ```php
> use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\IconColumn as FlexIconColumn;
> ```

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\IconColumn;

IconColumn::make('menu_icon')
    ->label('Icon');

IconColumn::make('status_icon')
    ->iconColor('success')
    ->iconSize('lg')
    ->showLabel();

IconColumn::make('debug_icon')
    ->iconSize('sm')
    ->showLabel()
    ->showName()
    ->tooltip(fn (?string $state): ?string => $state);
```

Filament resolves `$record->menu_icon` as column state from the model attribute. Values must match the same blade-icons strings saved by `IconPickerField`.

### Usage on the frontend

`IconColumn` is for **Filament admin tables only**. The same database value powers your public site — render it with blade-icons:

```blade
@if (filled($menuItem->icon))
    <x-icon :name="$menuItem->icon" class="h-5 w-5" />
@endif
```

See [IconPickerField — Usage on the frontend](/docs/icon-picker-field#usage-on-the-frontend) for Filament `generate_icon_html()` and model examples.

### Configuration API (IconColumn-specific)

All methods accept `Closure` for dynamic configuration.

| Method | Description | Default |
|--------|-------------|---------|
| `iconSize(string\|ControlSize\|Closure $size)` | Icon control size (`sm`, `md`, `lg`) | `md` |
| `iconColor(string\|array\|Closure\|null $color)` | Semantic Filament color on the icon shell | `null` |
| `showLabel(bool\|Closure $condition = true)` | Show human-readable label beside the icon | `false` |
| `showName(bool\|Closure $condition = true)` | Show full technical icon name (monospace) | `false` |
| `labelUsing(?Closure $callback)` | Custom label resolver — receives `['icon' => string]` | catalog label |

#### `iconSize(string|ControlSize|Closure $size)`

```php
use Bjanczak\FilamentFlexFields\Enums\ControlSize;

IconColumn::make('menu_icon')->iconSize('sm');
IconColumn::make('menu_icon')->iconSize(ControlSize::Lg);
```

Maps to Filament `IconSize`: `sm` → Small, `md` → Medium, `lg` → Large.

#### `iconColor(string|array|Closure|null $color)`

```php
IconColumn::make('status_icon')->iconColor('warning');
IconColumn::make('status_icon')->iconColor('primary');
```

Adds `fi-color-{color}` on the column shell when set.

#### `showLabel()` / `showName()`

```php
IconColumn::make('menu_icon')
    ->showLabel(); // "Check Circle" from heroicon-o-check-circle

IconColumn::make('menu_icon')
    ->showLabel()
    ->showName(); // label + heroicon-o-check-circle
```

Default labels use `IconCatalogResolver::formatIconLabel()` — the same headline logic as `IconPickerField` search results.

#### `labelUsing(?Closure $callback)`

```php
IconColumn::make('menu_icon')
    ->showLabel()
    ->labelUsing(fn (array $arguments): string => match ($arguments['icon']) {
        'heroicon-o-home' => 'Homepage',
        default => 'Menu item',
    });
```

### Inherited TextColumn API

All Filament `TextColumn` methods apply: `label()`, `sortable()`, `searchable()`, `toggleable()`, `alignStart()` / `alignCenter()`, `url()`, `tooltip()`, `placeholder()`, etc. The column uses `html()` internally — do not call `html(false)` unless you override `formatStateUsing()`.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `formatIconDisplay(mixed $state)` | `string` | Rendered HTML for a state value |
| `normalizeIconFromState(mixed $state)` | `?string` | Trimmed icon name or `null` |
| `renderIconHtml(string $icon)` | `string` | Cached SVG HTML via `IconSvgCache` |
| `resolveIconLabel(string $icon)` | `string` | Human label from catalog or `labelUsing()` |
| `getIconDisplaySize()` | `string` | Resolved size (`sm`, `md`, `lg`) |
| `getIconDisplayColor()` | `string\|array\|null` | Resolved semantic color |
| `shouldShowLabel()` / `shouldShowName()` | `bool` | Text display flags |

### Recipe: navigation table

```php
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\IconColumn;

public function table(Table $table): Table
{
    return $table
        ->columns([
            IconColumn::make('icon')
                ->label('Icon')
                ->iconSize('md'),
            TextColumn::make('label'),
            TextColumn::make('route'),
        ]);
}
```

### Recipe: status column with color + label

```php
IconColumn::make('status_icon')
    ->label('Status')
    ->iconColor(fn (string $state): string => match ($state) {
        'heroicon-o-check-circle' => 'success',
        'heroicon-o-exclamation-triangle' => 'warning',
        'heroicon-o-x-circle' => 'danger',
        default => 'gray',
    })
    ->showLabel()
    ->sortable();
```

### Recipe: pair with IconPickerField on a Resource

```php
// Form
IconPickerField::make('menu_icon')
    ->sets(['heroicons'])
    ->required();

// Table
IconColumn::make('menu_icon')
    ->showLabel()
    ->iconSize('sm');
```

### CSS classes

| Class | Role |
|-------|------|
| `fff-icon-column` | Column cell root |
| `fff-icon-column--sm` / `--md` / `--lg` | Size modifiers |
| `fff-icon-column--with-label` | Icon + text layout |
| `fff-icon-column__icon` | SVG wrapper |
| `fff-icon-column__text` | Label/name stack |
| `fff-icon-column__label` | Human-readable label |
| `fff-icon-column__name` | Technical icon name |

### Performance

| Mechanism | Detail |
|-----------|--------|
| **`IconColumnRenderCache`** | Per-request cache of rendered HTML keyed by icon + column options |
| **`IconSvgCache`** | Server-side SVG HTML cache (shared with `IconPickerField`) |
| **Lazy CSS** | `IconColumn::setUp()` registers `icon-column` in `FlexFieldStylesheetQueue`; `queued-stylesheets` (render hooks at `STYLES_AFTER` / `BODY_END`) emits `flex-fields-icon-column.css` once per request |
| **Dedup** | `markStylesheetsEmitted()` + asset injector prevent duplicate `<link>` tags on repeat hook passes and SPA navigation |
| **Playground slug bundle** | `/flex-fields-playground/icon-column` loads `playground-icon-column.css`; `suppressForPlaygroundBundle()` skips redundant lazy injection |

### Playground

- Main playground section: **IconColumn** (mock table after RatingColumn)
- Dedicated page: `/admin/flex-fields-playground/icon-column` (when playground is enabled)

### See also

- [IconPickerField](/docs/icon-picker-field) — form field for selecting icons
- [RatingColumn](/docs/ratingcolumn) — another flex-field table column
- [UserColumn](/docs/usercolumn) — avatar + user display column

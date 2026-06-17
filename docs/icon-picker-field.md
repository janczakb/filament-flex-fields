---
title: "IconPickerField"
---

![IconPickerField](/art/sc-32.png)

[← Back to Table of Contents](/docs/index)


### Summary

Searchable **blade-icons** picker with lazy SVG rendering, set filters, whitelist/exclude controls, paginated server-side search, and virtual scrolling for large catalogs. Stores the full icon name string (for example `heroicon-o-star`, `gravityui-star`).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField` |
| **Extends** | `Filament\Forms\Components\Field` |
| **State type** | `string\|null` |
| **FieldType** | `icon_picker` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;

IconPickerField::make('menu_icon')
    ->label('Menu icon')
    ->sets(['heroicons', 'gravity-icons'])
    ->iconsOnly()
    ->gridColumns(8)
    ->preload()
    ->required();

IconPickerField::make('status_icon')
    ->label('Status icon')
    ->sets(['heroicons'])
    ->icons([
        'heroicon-o-check-circle',
        'heroicon-o-exclamation-triangle',
        'heroicon-o-x-circle',
    ])
    ->gridColumns(4)
    ->size('sm')
    ->variant('soft');
```

Limit the field to a single installed library:

```php
IconPickerField::make('gravity_icon')
    ->label('Gravity icons only')
    ->sets(['gravity-icons'])
    ->helperText('Limits the catalog to the gravity-icons set.');
```

### State format

| Value | Description | Example |
|-------|-------------|---------|
| Selected icon | Full blade-icons name stored as a plain string | `heroicon-o-star` |
| Empty | `null` when cleared or never selected | `null` |

The stored value is the same string you would pass to Filament `generate_icon_html()` or `<x-icon>` — no extra wrapper object.

Use the value anywhere Filament accepts an icon name:

```php
->icon(fn (Get $get): ?string => $get('menu_icon'))
```

### Usage on the frontend

The field stores the **full blade-icons name** in your database (for example `heroicon-o-star`, `gravityui-star`). On the frontend, treat it like any other static icon name — no JSON decoding or custom serializer.

Assuming the icon is saved on a `$category` model as `$category->icon`:

```blade
@if (filled($category->icon))
    <x-icon :name="$category->icon" class="h-6 w-6" />
@endif
```

In PHP / Filament views:

```php
use function Filament\Support\generate_icon_html;

echo generate_icon_html($category->icon)?->toHtml();
```

In Livewire or Inertia props, pass the string through unchanged. Your public site only needs the same blade-icons sets installed (Heroicons, Gravity UI, etc.) — the stored value is already the correct `<x-icon>` name.

For **read-only display in Filament admin tables**, use [IconColumn](/docs/iconcolumn) instead of rebuilding the cell yourself.

### Validation

| Behaviour | Detail |
|-----------|--------|
| Built-in | Custom rule checks the value against the configured catalog (sets, whitelist, exclude list) |
| `required()` | Empty / `null` state fails with the standard required message |
| Invalid icon | Fails with `filament-flex-fields::default.validation.icon_picker.invalid` |

Whitelist mode (`->icons([...])`) is strict: only listed names pass validation, even if they exist in a broader set.

### Setup

IconPickerField reads any **blade-icons** set registered in your Laravel app (Heroicons ship with Filament; Gravity UI icons ship with this plugin via `janczakb/blade-gravity-icons`).

**Set names vs prefixes** — `sets()` accepts either the blade-icons set key or an icon prefix:

| You pass | Resolves to |
|----------|-------------|
| `heroicons` | Heroicons set |
| `gravity-icons` | Gravity UI set |
| `gravityui` | Same Gravity UI set (matched by prefix) |
| `heroicon` | Heroicons set (matched by prefix) |

When `->sets()` is omitted, all installed sets are indexed. Restrict globally via config:

```php
// config/filament-flex-fields.php
'icon_picker_sets' => ['heroicons', 'gravity-icons'],
```

**Bundled manifest (recommended for production)** — speeds up cold catalog reads:

```bash
php artisan fff:icons:manifest
php artisan fff:icons:manifest --sets=heroicons --sets=gravity-icons
```

Writes `resources/dist/icon-catalog-manifest.json`. Disable with `icon_picker_use_bundled_manifest => false` if you prefer live blade-icons scanning.

### Configuration API

#### `sets(array|string|Closure|null $sets = null)`

Limit available icon libraries. `null` uses all installed sets, or `config('filament-flex-fields.ui.icon_picker_sets')` when that config is a non-empty array.

```php
IconPickerField::make('icon')->sets(['heroicons']);
IconPickerField::make('icon')->sets('gravityui');
```

#### `icons(array|Closure $icons)`

Whitelist only these full icon names. Set tabs and search operate inside the whitelist.

```php
IconPickerField::make('icon')->icons([
    'heroicon-o-star',
    'gravityui-star',
]);
```

#### `excludeIcons(array|Closure $icons)`

Remove icons from the catalog after sets are resolved.

```php
IconPickerField::make('icon')->excludeIcons(['heroicon-o-x-mark']);
```

#### `searchResultsLayout(string|Closure $layout)`

Dropdown result layout. Default: `icons`.

| Layout | Description |
|--------|-------------|
| `icons` | Icon-only grid cells (default) |
| `grid` | Grid with icon + human-readable label |
| `list` | Vertical list rows with icon + label |

Shorthand helpers:

```php
IconPickerField::make('icon')->iconsOnly(); // icons
IconPickerField::make('icon')->grid();    // grid
IconPickerField::make('icon')->list();    // list
```

#### `gridColumns(int|Closure $columns)`

Column count for `grid` / `icons` layouts. Clamped to 2–12. Default: `8`.

```php
IconPickerField::make('icon')->gridColumns(6);
```

#### `closeOnSelect(bool|Closure $condition = true)`

Close the teleported panel after picking an icon. Default: `true`.

```php
IconPickerField::make('icon')->closeOnSelect(false);
```

#### `preload(bool|Closure $condition = true)`

Fetch the first results page when the Alpine component mounts (before the panel opens). Default: `false`.

```php
IconPickerField::make('icon')->preload();
```

#### `limitPerSet(int|Closure|null $limit)`

Cap how many icons are indexed per set. Useful for demos or constrained pickers.

```php
IconPickerField::make('icon')->limitPerSet(100);
```

#### `perPage(int|Closure $perPage)`

Server page size for search / infinite scroll. Default: `64`, maximum: `96`.

```php
IconPickerField::make('icon')->perPage(32);
```

#### `clearable(bool|Closure $condition = true)`

Show the clear (×) control when a value is selected. Default: `true`.

```php
IconPickerField::make('icon')->clearable(false);
```

#### `variant(string|Closure $variant)`

Visual shell shared with `SelectField`. Values: `bordered` (default), `secondary`, `flat`, `soft`, `faded`, `underlined`. Legacy `primary` maps to `bordered`.

```php
IconPickerField::make('icon')->variant('soft');
```

#### `size(string|ControlSize|Closure $size)`

Control height. See [Control size](/docs/shared-concepts). Default: `md`, or `config('filament-flex-fields.ui.icon_picker_size')`.

```php
IconPickerField::make('icon')->size('lg');
```

#### `placeholder(string|Closure|null $placeholder)`

Trigger placeholder when no icon is selected. Default translation: `Select an icon`.

```php
IconPickerField::make('icon')->placeholder('Pick an icon…');
```

#### `readOnly(bool|Closure $condition = true)`

Inherited from Filament `CanBeReadOnly`. Prevents opening the panel and clearing the value.

```php
IconPickerField::make('icon')->readOnly();
```

#### `focusOutline(bool|Closure $condition = true)`

Inherited from `HasFieldFocusOutline`.

```php
IconPickerField::make('icon')->focusOutline();
```

#### `chevronIcon()` / `clearIcon()` / `selectedOptionCheckIcon()`

Inherited from `HasSelectFieldIcons`. Override trigger chevron, clear button, or selected checkmark. Defaults come from `config('filament-flex-fields.ui.select_*_icon')` or Gravity UI fallbacks.

```php
IconPickerField::make('icon')
    ->chevronIcon('heroicon-o-chevron-down')
    ->clearIcon('heroicon-o-x-mark');
```

#### Prefix / suffix affixes

Inherited from Filament `HasAffixes` — `prefixIcon()`, `suffixIcon()`, `prefixActions()`, `suffixActions()`, and related helpers work on the trigger shell.

```php
IconPickerField::make('icon')
    ->prefixIcon('heroicon-o-sparkles')
    ->suffixIcon('heroicon-o-information-circle');
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getConfiguredSets()` | `list<string>\|null` | Raw `sets()` config |
| `getResolvedSetNames()` | `list<string>` | Normalized blade-icons set keys |
| `getWhitelistedIcons()` | `list<string>` | Whitelist from `icons()` |
| `getExcludedIcons()` | `list<string>` | Exclusions from `excludeIcons()` |
| `getSearchResultsLayout()` | `string` | `grid`, `list`, or `icons` |
| `getGridColumns()` | `int` | Clamped column count |
| `shouldPreload()` | `bool` | Preload flag |
| `shouldCloseOnSelect()` | `bool` | Close-on-select flag |
| `getPerPage()` | `int` | Page size |
| `getLimitPerSet()` | `int\|null` | Per-set cap |
| `isClearable()` | `bool` | Clear button enabled |
| `getVariant()` | `string` | Resolved variant |
| `getSize()` | `string` | Resolved size |
| `isAllowedIcon(string $icon)` | `bool` | Whether the icon is in the effective catalog |
| `searchIcons(string $query, ?string $set, int $page)` | `array` | Ranked search payload |
| `renderIconHtml(?string $icon)` | `string` | SVG HTML for trigger / previews |
| `renderIconSvgs(array $icons)` | `list<array{name, html}>` | Batch SVG render with server cache |
| `getIconPickerSearchResults(...)` | `array` | Livewire `@Renderless` search endpoint |
| `getIconPickerSvgPreviews(array $icons)` | `list<array{name, html}>` | Livewire `@Renderless` SVG batch (max 48 icons) |
| `getWrapperClasses()` | `array<string, bool\|string>` | CSS class map for the trigger |
| `getAvailableSetsForJs()` | `list<array>` | Set summaries for Alpine (`key`, `prefix`, `label`, `count`) |

### FlexField schema config

When using `FieldType::IconPicker` / `FlexFieldFormBuilder`:

| Config key | Type | Maps to |
|------------|------|---------|
| `sets` | `array\|string\|null` | `sets()` |
| `icons` | `string[]` | `icons()` |
| `exclude_icons` | `string[]` | `excludeIcons()` |
| `search_results_layout` / `layout` | `grid\|list\|icons` | `searchResultsLayout()` |
| `close_on_select` | `bool` | `closeOnSelect()` |
| `grid_columns` | `int` | `gridColumns()` |
| `preload` | `bool` | `preload()` |
| `limit_per_set` | `int\|null` | `limitPerSet()` |
| `per_page` | `int` | `perPage()` |
| `size` | `sm\|md\|lg` | `size()` — default from `icon_picker_size` |
| `variant` | `string` | `variant()` — default from `icon_picker_variant` |

```php
new FlexFieldDefinition(
    slug: 'menu_icon',
    label: 'Menu icon',
    type: FieldType::IconPicker,
    config: [
        'sets' => ['heroicons'],
        'layout' => 'icons',
        'grid_columns' => 8,
        'preload' => true,
    ],
);
```

### Global config

| Key | Default | Purpose |
|-----|---------|---------|
| `icon_picker_sets` | `null` | Default `sets()` when not set on the field |
| `icon_picker_size` | `md` | Default size for form-builder fields |
| `icon_picker_variant` | `bordered` | Default variant for form-builder fields |
| `icon_picker_index_cache_days` | `7` | TTL for indexed catalog pools |
| `icon_picker_catalog_cache_days` | `7` | TTL for resolved set catalogs |
| `icon_picker_svg_cache_days` | `30` | TTL for rendered SVG HTML |
| `icon_picker_search_cache_minutes` | `60` | TTL for ranked search responses (`0` disables) |
| `icon_picker_use_bundled_manifest` | `true` | Prefer `icon-catalog-manifest.json` over live scanning |

Search result caching is skipped when a whitelist or exclude list is configured, so filtered catalogs always stay accurate.

### UX

- **Trigger** — Select-style pill with live SVG preview of the chosen icon, clear button, and chevron. Shares `SelectField` + `teleported-menu` styling.
- **Set filter** — Tab-style filter when multiple libraries are available, with per-set counts. Hidden when only one set is configured.
- **Search** — Debounced server search with term highlighting in labels and a clear-search control.
- **Keyboard** — Arrow keys move the active cell, Enter selects, Escape closes the panel and restores focus (`preventScroll` on focus return).
- **Infinite scroll** — Intersection Observer sentinel at the list bottom loads the next page; the following page is prefetched in the background.
- **Loading** — Skeleton cells while the first page or SVG previews load.
- **Accessibility** — Combobox-style trigger with `aria-expanded`, `aria-controls`, and an associated results list.

### Performance notes

- **Indexed catalog** — `IconCatalogIndex` precomputes labels and O(1) allowed-icon lookup.
- **Ranked search** — Exact name matches rank above partial / label matches.
- **Lean Livewire payloads** — Set summaries ship only on the first unfiltered page; pagination responses carry icon names only.
- **SVG cache** — Rendered SVG HTML is cached server-side and batched (max 48 icons per Livewire call).
- **Viewport SVG loading** — The browser fetches SVG previews only for icons entering the scroll viewport.
- **Virtual window** — Long lists mount only visible rows plus a small buffer (~48 cells) while preserving scroll height.
- **Client search cache** — Repeated queries are served from an in-memory LRU `Map` (max 32 entries) without extra round-trips.
- **Lazy assets** — `icon-picker-field`, `select-field`, and `teleported-menu` CSS/JS load through the flex-field asset injector.

### CSS classes

| Class | Role |
|-------|------|
| `fff-icon-picker` | Alpine root inside the trigger |
| `fff-icon-picker-field` | Field wrapper modifier on `fff-select-field` |
| `fff-icon-picker-field--layout-{grid\|list\|icons}` | Layout modifier on wrapper |
| `fff-icon-picker__preview` | Selected icon SVG in trigger |
| `fff-icon-picker__panel` | Teleported dropdown (`x-teleport="body"`) |
| `fff-icon-picker__toolbar` | Search + set tabs |
| `fff-icon-picker__set-tabs` / `fff-icon-picker__set-tab` | Library filter chips |
| `fff-icon-picker__results` | Scrollable results container |
| `fff-icon-picker__grid` / `fff-icon-picker__option` | Grid cells |
| `fff-icon-picker__track` / `fff-icon-picker__track--virtual` | Virtual scroll track |
| `fff-icon-picker__skeleton` | Loading placeholder cell |

Trigger shell classes come from `fff-select-field` (`fff-select-field--{size}`, `fff-select-field--{variant}`, `fff-select-field--clearable-has-value`).

### Model & persistence

```php
// Migration
$table->string('menu_icon')->nullable();

// Model — no special cast required
protected $fillable = ['menu_icon'];
```

Editing an existing record:

```php
IconPickerField::make('menu_icon')
    ->label('Menu icon')
    ->sets(['heroicons'])
    ->default(fn (?MenuItem $record): ?string => $record?->menu_icon);
```

Use the stored icon name in Blade or Filament actions:

```php
// Table column
TextColumn::make('menu_icon')
    ->icon(fn (?string $state): ?string => $state);

// Infolist
TextEntry::make('menu_icon')
    ->icon(fn (?string $state): ?string => $state);
```

---

### Recipes

#### CMS menu icon — multi-set catalog

```php
IconPickerField::make('menu_icon')
    ->label('Navigation icon')
    ->sets(['heroicons', 'gravity-icons'])
    ->iconsOnly()
    ->gridColumns(8)
    ->preload()
    ->closeOnSelect(true)
    ->required();
```

#### Strict whitelist — status icons only

```php
IconPickerField::make('status_icon')
    ->label('Status')
    ->sets(['heroicons'])
    ->icons([
        'heroicon-o-check-circle',
        'heroicon-o-exclamation-triangle',
        'heroicon-o-x-circle',
        'heroicon-o-information-circle',
    ])
    ->gridColumns(4)
    ->size('sm')
    ->variant('soft')
    ->clearable(false);
```

#### Read-only display on view / audit page

```php
IconPickerField::make('chosen_icon')
    ->label('Selected icon')
    ->default(fn ($record) => $record->chosen_icon)
    ->readOnly()
    ->sets(['heroicons']);
```

#### Large catalog — preload + virtual scroll tuning

```php
IconPickerField::make('feature_icon')
    ->label('Feature icon')
    ->sets(['heroicons', 'gravity-icons'])
    ->preload()
    ->perPage(48)
    ->grid()
    ->gridColumns(6)
    ->limitPerSet(200)
    ->helperText('Preloads the first page; scroll loads more icons on demand.');
```

#### Grid vs list layout comparison

```php
// Compact icon grid (default icons-only cells)
IconPickerField::make('icon_grid')
    ->iconsOnly()
    ->gridColumns(10);

// Labelled grid rows
IconPickerField::make('icon_grid_labels')
    ->grid()
    ->gridColumns(6);

// Vertical list with icon + searchable label
IconPickerField::make('icon_list')
    ->list()
    ->perPage(32);
```

#### Flex-field schema recipe

```php
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\FlexFieldDefinition;

new FlexFieldDefinition(
    slug: 'nav_icon',
    label: 'Navigation icon',
    type: FieldType::IconPicker,
    config: [
        'sets' => ['heroicons'],
        'layout' => 'icons',
        'grid_columns' => 8,
        'preload' => true,
        'size' => 'md',
        'variant' => 'bordered',
    ],
);
```

---

### See also

- [IconColumn](/docs/iconcolumn) — read-only table display for saved icon values

### Playground

Enable the playground (`FLEX_FIELDS_PLAYGROUND=true`) and open **Flex Fields Playground → Icon picker** (`icon-picker-field`) to compare:

- Heroicons-only vs Gravity-icons-only pickers
- Grid, whitelist, size, variant, and clearable demos

### Implementation notes

- Dropdown panel uses `x-teleport="body"` and `createSearchableSelectMenuMixin()` for positioning, same as `SelectField` / `CountryField`.
- Requires installed blade-icons sets; empty catalogs mean no icons are listed.
- `getIconPickerSvgPreviews()` silently drops unknown or disallowed icon names.
- Reopen flow resets virtual scroll and re-syncs viewport SVGs without calling Alpine `$watch` cleanup (Alpine magic `$watch` does not return an unwatcher).

---

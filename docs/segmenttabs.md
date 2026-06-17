# SegmentTabs

[← Back to Table of Contents](/docs/index)


### Summary

Schema layout component: **iOS-style segmented tabs** with per-tab form schemas (same visual language as [SegmentControl](/docs/segmentcontrol)). Used directly and as the base for [TranslatableFields](/docs/translatablefields).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs` |
| **Tab class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab` |
| **State** | Tab panels contain nested fields; tab selection is local UI state (optional query-string persistence) |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

SegmentTabs::make('Account')
    ->tabs([
        SegmentTab::make('General')
            ->icon(GravityIcon::Person)
            ->tooltip('General settings')
            ->schema([
                FlexTextInput::make('name'),
            ]),
        SegmentTab::make('Advanced')
            ->schema([
                FlexTextInput::make('api_key'),
            ]),
    ])
    ->variant('ghost')
    ->fullWidth();
```

### Configuration API

#### `tabs(array|Closure $tabs)`


Array of `SegmentTab` components. Each tab wraps a nested schema.

```php
SegmentTabs::make('field_name')
    ->tabs([
        'tab_1' => 'Tab 1',
        'tab_2' => 'Tab 2',
    ]);
```
#### `activeTab(int|Closure $activeTab)`


1-based index of the initially active tab. **Default:** `1`.

```php
SegmentTabs::make('field_name')
    ->activeTab(1);
```
#### `persistTabInQueryString(string|Closure|null $key = 'segment-tab')`


Persist the active tab in the URL query string. Pass `null` to disable.

```php
SegmentTabs::make('field_name')
    ->persistTabInQueryString('tab');
```
#### `variant(string|Closure $variant)`


| Value | Description |
|-------|-------------|
| `default` | Filled track background. **Default.** |
| `ghost` | Transparent track; uses `color()` for selection accent. |

```php
SegmentTabs::make('field_name')
    ->variant('primary');
```
#### `color(string|Closure|null $color)`


Selection accent color. For `ghost`, defaults to `primary` when omitted.

```php
SegmentTabs::make('field_name')
    ->color('primary');
```
#### `separators(bool|Closure $condition = true)`


Vertical dividers between tab segments. **Default:** `true`.

```php
SegmentTabs::make('field_name')
    ->separators(true);
```
#### `fullWidth(bool|Closure $condition = true)`


Stretch tabs to the full container width.

```php
SegmentTabs::make('field_name')
    ->fullWidth(true);
```
#### `iconOnly(bool|Closure $condition = true)`


Hide tab labels; show icons only (requires icons on tabs).

```php
SegmentTabs::make('field_name')
    ->iconOnly(true);
```
#### `expandSelectedLabel(bool|Closure $condition = true)`


Animate the selected tab to a wider width.

```php
SegmentTabs::make('field_name')
    ->expandSelectedLabel(true);
```
#### `size(string|ControlSize|Closure $size)`


See [Control size](/docs/shared-concepts).

```php
SegmentTabs::make('field_name')
    ->size('md');
```

### SegmentTab API

| Method | Description |
|--------|-------------|
| `make(string\|Htmlable\|Closure\|null $label)` | Create a tab |
| `icon(string\|BackedEnum\|Htmlable\|Closure\|null $icon)` | Tab icon |
| `tooltip(string\|Closure\|null $tooltip)` | Hover tooltip |
| `schema(array\|Closure $schema)` | Nested form/schema components |
| `badge(...)` | Inherited Filament badge API |
| `visible(...)` / `hidden(...)` | Inherited visibility API |

### Getters

| Method | Returns |
|--------|---------|
| `getVisibleTabs()` | `list&lt;SegmentTab&gt;` — visible tabs only |
| `getActiveTab()` | `int` — 1-based active index |
| `getActiveTabKey()` | `?string` — key of active tab |
| `isTabActive(SegmentTab $tab)` | `bool` |
| `isTabPersistedInQueryString()` | `bool` |
| `getTabQueryStringKey()` | `?string` |

---

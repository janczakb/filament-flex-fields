---
title: "SegmentTabs"
description: Schema layout component featuring iOS-style segmented tabs with per-tab form schemas and query-string persistence.
---

[← Back to Table of Contents](/docs/index)

### Summary

Schema layout component providing **iOS-style segmented tabs**. Each tab wraps a nested form schema, allowing for clean organization of complex forms. Shares the same visual language as [SegmentControl](/docs/segmentcontrol).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs` |
| **Tab class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab` |
| **State** | Local UI state (optional query-string persistence) |
| **Playground** | `segment-tabs` slug in Flex Fields playground |

---

### Basic usage

#### Standard segmented tabs

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;

SegmentTabs::make('Account Settings')
    ->tabs([
        SegmentTab::make('General')
            ->icon('heroicon-o-user')
            ->schema([
                FlexTextInput::make('name'),
                FlexTextInput::make('email'),
            ]),
        SegmentTab::make('Security')
            ->icon('heroicon-o-lock')
            ->schema([
                FlexTextInput::make('password'),
            ]),
    ]);
```

#### Ghost variant with query persistence

```php
SegmentTabs::make('Profile')
    ->variant('ghost')
    ->persistTabInQueryString('profile-tab')
    ->tabs([
        SegmentTab::make('Details')->schema([...]),
        SegmentTab::make('Billing')->schema([...]),
    ]);
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `tabs(array\|Closure $tabs)` | Setup | `[]` | Array of `SegmentTab` components. |
| `activeTab(int\|Closure $index)` | Setup | `1` | 1-based index of the initially active tab. |
| `persistTabInQueryString(string\|null $key)` | Setup | `null` | Persist active tab in URL. Default key: `'segment-tab'`. |
| `variant(string\|Closure $variant)` | Setup | `'default'` | `default` (filled track) or `ghost` (transparent track). |
| `color(string\|Closure\|null $color)` | Setup | `null` | Selection accent color. Defaults to `primary` for `ghost`. |
| `separators(bool\|Closure $on)` | Setup | `true` | Vertical dividers between tab segments. |
| `fullWidth(bool\|Closure $on)` | Setup | `false` | Stretch tabs to full container width. |
| `iconOnly(bool\|Closure $on)` | Setup | `false` | Hide tab labels; show icons only. |
| `expandSelectedLabel(bool\|Closure $on)` | Setup | `false` | Animate the selected tab to a wider width. |
| `size(string\|Closure $size)` | Setup | `'md'` | Control size: `sm`, `md`, `lg`. |

#### SegmentTab API

| Method | Description |
|--------|-------------|
| `make(string\|Htmlable\|Closure $label)` | Create a tab instance. |
| `icon(mixed $icon)` | Tab icon. |
| `tooltip(string\|Closure $text)` | Hover tooltip for the tab. |
| `schema(array\|Closure $schema)` | Nested form/schema components. |
| `badge(string\|Closure $text)` | Optional badge shown on the tab. |

---

### Real-world examples

#### Multi-step profile editor

```php
SegmentTabs::make('User Editor')
    ->variant('ghost')
    ->fullWidth()
    ->tabs([
        SegmentTab::make('Basic Info')
            ->icon('heroicon-o-identification')
            ->schema([
                FlexTextInput::make('first_name'),
                FlexTextInput::make('last_name'),
            ]),
        SegmentTab::make('Preferences')
            ->icon('heroicon-o-adjustments-horizontal')
            ->schema([
                Toggle::make('newsletter'),
                Select::make('theme')->options(['light' => 'Light', 'dark' => 'Dark']),
            ]),
    ]);
```

---

### Playground

`/admin/flex-fields-playground/segment-tabs`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [SegmentControl](/docs/segmentcontrol) | For simple single-select form fields without nested schemas. |
| [TranslatableFields](/docs/translatablefields) | For multi-language form fields (uses SegmentTabs internally). |
| [ItemCardGroup](/docs/itemcardgroup) | For grouping rows that don't need tab switching. |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-segment-tabs` | Root layout wrapper |
| `fff-segment-tabs__header` | Tab switcher container |
| `fff-segment-tabs__content` | Active tab panel container |
| `fff-segment-tab-panel` | Individual tab content wrapper |

Uses `fff-segment-control` classes for the tab switcher header.

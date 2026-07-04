---
title: "ItemCard"
description: List row for settings, navigation, and mixed action layouts with leading visuals and trailing controls.
---

[← Back to Table of Contents](/docs/index)

### Summary

Modern list row or standalone card for settings screens, navigation menus, and mixed action layouts. Renders a horizontal row with optional leading visuals (icon or image), title, description, and a trailing schema slot for switches, selects, or buttons.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard` |
| **State** | None — display/action component (nested fields use parent form state) |
| **Contexts** | `standalone` (card) · `group` (flat row) |
| **Extends** | `Filament\Schemas\Components\Component` |

---

### Basic usage

#### Standard navigation row

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Filament\Support\Icons\Heroicon;

ItemCard::make('Language')
    ->description('Choose your preferred language')
    ->icon(Heroicon::GlobeAlt)
    ->chevron()
    ->pressable();
```

#### Settings row with switch

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField;

ItemCard::make('Dark mode')
    ->description('Use dark theme across the app')
    ->icon(Heroicon::Moon)
    ->schema([
        SwitchField::make('dark_mode')->inline()->size('sm'),
    ]);
```

---

### Context: `standalone` vs `group`

Rendering mode is controlled by **context**. This changes surface styling (border, shadow, padding, chevron shape).

| Context | When | Visual behavior |
|---------|------|------------------|
| `auto` (default) | Parent is `ItemCardGroup` → `group`; otherwise → `standalone` | Detected automatically |
| `group` | Row inside a shared group surface | Flat row; no per-row border/shadow |
| `standalone` | Outside `ItemCardGroup` (or forced) | Self-contained card: border, radius, shadow |

```php
ItemCard::make('Profile')->standalone(); // force standalone surface
ItemCard::make('Profile')->inGroup();    // force flat group row
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `variant(string\|Closure $variant)` | Setup | `'default'` | `default`, `secondary`, `tertiary`, `outline`, `transparent`. |
| `icon(mixed $icon)` | Setup | `null` | Leading icon (Heroicon, Gravity, etc.). |
| `image(string\|Closure $url)` | Setup | `null` | Leading media image (overrides icon). |
| `imageShape(string\|Closure $shape)` | Setup | `'rounded'` | Crop shape: `circle` or `rounded`. |
| `imageAlt(string\|Closure $alt)` | Setup | `heading` | Alternative text for the leading image. |
| `chevron(bool\|Closure $on)` | Setup | `false` | Show a trailing chevron indicator. |
| `pressable(bool\|Closure $on)` | Setup | `auto` | Enable hover/ripple effects (auto-enabled if chevron/url set). |
| `pressableAction(Action\|Closure $action)` | Setup | `null` | Action to trigger when the entire row is clicked. |
| `url(string\|Closure $url)` | Setup | `null` | URL to navigate to on row click. |
| `schema(array\|Closure $components)` | Setup | `[]` | Trailing slot components (fields, actions). |

#### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getVariant()` | `string` | Resolved surface variant. |
| `getContext()` | `string` | `group` or `standalone`. |
| `isPressable()` | `bool` | Whether the row is interactive. |
| `hasInteractiveAction()` | `bool` | `true` if `schema()` contains components. |

---

### Real-world examples

#### Profile card with avatar

```php
ItemCard::make('Alex Rivera')
    ->description('Product Designer')
    ->image('https://i.pravatar.cc/128?img=12')
    ->imageShape('circle')
    ->chevron()
    ->url(route('profile.edit'));
```

#### Destructive action row

```php
ItemCard::make('Delete account')
    ->description('Permanently remove your account and all data')
    ->schema([
        Action::make('delete')
            ->label('Delete')
            ->color('danger')
            ->itemCard()
            ->requiresConfirmation(),
    ]);
```

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `item-card` | Base row wrapper |
| `item-card--{variant}` | Surface variant modifier |
| `item-card--context-{standalone\|group}` | Layout context modifier |
| `item-card--pressable` | Interactive state modifier |
| `item-card--form-panel` | Vertical form layout modifier |
| `item-card-icon` | Leading visual container |
| `item-card-content` | Title and description container |
| `item-card-action` | Trailing schema container |
| `item-card-chevron` | Chevron indicator wrapper |

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [ItemCardGroup](/docs/itemcardgroup) | To group multiple `ItemCard` rows in a shared container. |
| [ChoiceCards](/docs/choicecards) | For selectable cards that store a form state. |
| [CoverCard](/docs/covercard) | For media-heavy hero banners or product tiles. |

---

### Playground

Grouped layouts and hold-confirm actions: `/admin/flex-fields-playground/item-card-group`.

---

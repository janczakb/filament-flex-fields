---
title: "ItemCardStack"
description: Layout component for vertically stacking ItemCards with consistent spacing.
---

[← Back to Table of Contents](/docs/index)

### Summary

A layout component designed specifically for stacking [ItemCard](/docs/itemcard) or [ItemCardGroup](/docs/itemcardgroup) components vertically. It provides consistent spacing and alignment, ensuring a clean "stack" appearance for complex list items.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack` |
| **State type** | *(Layout component — no state)* |
| **FieldType** | `item-card-stack` |
| **Playground** | *(No dedicated playground — see ItemCardGroup)* |
| **Extends** | `Filament\Schemas\Components\Component` |

---

### Basic usage

#### Simple vertical stack
```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;

ItemCardStack::make()
    ->schema([
        ItemCard::make()->title('First Item'),
        ItemCard::make()->title('Second Item'),
        ItemCard::make()->title('Third Item'),
    ]);
```

#### Custom spacing
```php
ItemCardStack::make()
    ->stackGap('lg')
    ->schema([
        // ... cards ...
    ]);
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `stackGap(string\|Closure $gap)` | Setup | `'md'` | Spacing between items: `sm`, `md`, `lg` |
| `schema(array\|Closure $schema)` | Setup | — | Components to stack |

---

### Real-world examples

#### Dashboard Activity Feed
```php
ItemCardStack::make()
    ->stackGap('sm')
    ->schema([
        ItemCard::make()
            ->title('New Order #1234')
            ->description('Received 2 minutes ago')
            ->icon('heroicon-o-shopping-bag'),
        ItemCard::make()
            ->title('User Registered')
            ->description('John Doe joined the platform')
            ->icon('heroicon-o-user-plus'),
    ]);
```

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [ItemCardGroup](/docs/itemcardgroup) | Grid-based layout for cards |
| `Section` | When a title and collapsible behavior is needed |
| `Grid` | For multi-column layouts |

---

### Playground

Stacked layouts: `/admin/flex-fields-playground/item-card-group`.

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-item-card-stack` | Root wrapper |
| `fff-item-card-stack--gap-{sm\|md\|lg}` | Spacing variant |

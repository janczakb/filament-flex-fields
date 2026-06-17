# ItemCardStack

[← Back to Table of Contents](/docs/index)


Vertical stack wrapper for **standalone** `ItemCard` components. Adds consistent **gap** between sibling cards (SaaS vertical stack / pressable list).

**Class:** `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack`  
**Extends:** `Filament\Schemas\Components\Component`

Does not change card styling — children auto-detect `standalone` context when not inside `ItemCardGroup`.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack;

ItemCardStack::make()
    ->schema([
        ItemCard::make('Profile')
            ->description('Update your personal information')
            ->icon(Heroicon::OutlinedUser)
            ->chevron()
            ->pressableAction(fn () => $this->openProfile()),
        ItemCard::make('Security')
            ->variant('secondary')
            ->description('Manage passwords and 2FA')
            ->icon(Heroicon::OutlinedKey)
            ->chevron(),
    ]);
```

### Chainable configuration API

#### `make()`


Factory. No heading — use child cards for content.

```php
Component::make('field_name')
    ->make();
```
#### `stackGap(string|Closure $stackGap)`


Spacing between stacked cards.

| Value | Gap |
|-------|-----|
| `sm` | `0.5rem` |
| `md` (default) | `0.75rem` |
| `lg` | `1rem` |

```php
ItemCardStack::make()->stackGap('lg')->schema([...]);
```

> **Note:** Named `stackGap()` to avoid clashing with Filament's grid `gap()` method on `Component`.

#### `schema(array|Closure $components)`


Child `ItemCard` components.

```php
Component::make('field_name')
    ->schema([
        // ... schema components
    ]);
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getStackGap()` | `string` | `sm`, `md`, or `lg` |

### Inherited Filament schema component API

`key()`, `id()`, `hidden()`, `visible()`, `extraAttributes()`, `columnSpan()`, `columns()`, `gap()` (grid gap for children, default `0` in `setUp()`).

### CSS classes

| Class | Meaning |
|-------|---------|
| `fff-item-card-stack` | Stack wrapper |
| `fff-item-card-stack--{sm\|md\|lg}` | Gap size modifier |

---

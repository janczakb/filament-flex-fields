---
title: "Hold confirm action"
---

[← Back to Table of Contents](/docs/index)

### Summary

**Press-and-hold confirmation** for high-risk Filament actions (delete account, purge data, irreversible settings). The user must keep the button pressed until a fill animation completes — a single click does nothing. Accidental taps are blocked; intentional actions feel deliberate.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Actions\Action` |
| **Extends** | `Filament\Actions\Action` |
| **Trait** | `CanRequireHoldConfirm` |
| **Enabler** | `->holdConfirm()` on the package `Action` class |
| **Assets** | Lazy `hold-confirm-action.js` + `hold-confirm-action.css` per rendered action |

> **Important:** Import the package action class, not Filament’s base class:
>
> `use Bjanczak\FilamentFlexFields\Filament\Actions\Action;`

---

## When to use it

| Pattern | Good for |
|---------|----------|
| `->holdConfirm()` | Destructive or irreversible actions where a normal click is too easy to trigger by mistake |
| `->requiresConfirmation()` | Actions that need an explicit modal “Are you sure?” step |
| Both together | Rare — pick one UX pattern per action |

Hold confirm works in forms, infolists, tables, modals, and [ItemCard](/docs/itemcard) trailing slots. It supports Livewire `action()` callbacks and `url()` navigation.

---

## Quick start

```php
use Bjanczak\FilamentFlexFields\Filament\Actions\Action;

Action::make('delete')
    ->label('Hold to Delete')
    ->color('danger')
    ->icon(Heroicon::OutlinedTrash)
    ->holdConfirm()
    ->action(fn () => $this->deleteRecord());
```

Defaults: **2000 ms** hold, sweep from **right**, **200 ms** release animation, danger-themed palette when `color('danger')`.

---

## How it works

1. PHP renders the normal Filament button, then wraps label/icon in **base** and **overlay** layers (`renderHoldConfirmTriggerHtml()`).
2. The action view swaps to `filament-flex-fields::actions.hold-confirm` and disables the default Livewire click handler.
3. Alpine `holdConfirmActionFormComponent` listens for **pointer down** on the left mouse button / touch.
4. Progress fills over `duration` ms using a directional clip-path (`sweep`).
5. Releasing early reverses progress over `releaseDuration` ms.
6. At 100%, Alpine dispatches `fff-hold-complete` → `$wire.mountAction('…')` or navigates to `url()`.

Ordinary `click` events are suppressed so the action cannot fire without completing the hold.

---

## Configuration catalog

| Method | Type / values | Default | Section |
|--------|---------------|---------|---------|
| `holdConfirm()` | `int` ms, `string` sweep | `2000`, `'right'` | [Duration](#hold-duration) |
| `holdConfirmSweep()` | `right`, `left`, `up`, `down` | `right` | [Sweep direction](#sweep-direction) |
| `holdConfirmReleaseDuration()` | `int` ms | `200` | [Release animation](#release-animation) |
| `holdConfirmThemed()` | `bool` | `true` | [Theming](#theming) |
| `rounded()` | `none`, `sm`, `md`, `lg`, `xl`, `full` | auto `full` for danger | [Rounded corners](#rounded-corners) |
| `itemCard()` | `bool` | `false` | [Item card styling](#item-card-integration) |

Inherited Filament APIs (`label()`, `icon()`, `color()`, `size()`, `disabled()`, `url()`, `action()`, `requiresConfirmation()`, etc.) work as usual.

---

## Basic usage

### Default hold (2 seconds)

```php
Action::make('delete')
    ->label('Hold to Delete')
    ->color('danger')
    ->holdConfirm()
    ->action(fn () => $this->delete());
```

### Custom duration

```php
Action::make('purge')
    ->label('Hold to Purge')
    ->color('danger')
    ->holdConfirm(4000)
    ->action(fn () => $this->purgeCache());
```

### Fast confirmation (800 ms)

Useful for lower-risk actions that still benefit from intentional input:

```php
Action::make('apply')
    ->label('Hold to Apply')
    ->color('primary')
    ->holdConfirm(800)
    ->action(fn () => $this->applySettings());
```

---

## Sweep direction

The fill animation direction. Second argument to `holdConfirm()` or via `holdConfirmSweep()`.

### `right` (default)

```php
Action::make('delete')
    ->holdConfirm(2000, 'right')
    ->action(fn () => null);
```

### `left`

```php
Action::make('delete')
    ->holdConfirm(2000, 'left')
    ->action(fn () => null);
```

### `up`

```php
Action::make('delete')
    ->holdConfirm(2000, 'up')
    ->action(fn () => null);
```

### `down`

```php
Action::make('delete')
    ->holdConfirm(2000, 'down')
    ->action(fn () => null);
```

### Change sweep after `holdConfirm()`

```php
Action::make('delete')
    ->holdConfirm()
    ->holdConfirmSweep('left')
    ->action(fn () => null);
```

Invalid values (e.g. `'diagonal'`) throw `InvalidArgumentException`.

---

## Release animation

When the user releases before completion, progress animates back to zero.

```php
// Default: 200 ms rewind
Action::make('delete')->holdConfirm();

// Slower rewind (more visible feedback)
Action::make('delete')
    ->holdConfirm()
    ->holdConfirmReleaseDuration(500)
    ->action(fn () => null);

// Snappy rewind
Action::make('delete')
    ->holdConfirm()
    ->holdConfirmReleaseDuration(80)
    ->action(fn () => null);
```

---

## Theming

When `holdConfirmThemed()` is enabled (default), `color('danger')` applies the package danger track/fill palette (`fff-hold-confirm-action--palette-danger`) and defaults to **fully rounded** corners.

### Danger palette (default for destructive actions)

```php
Action::make('delete')
    ->color('danger')
    ->holdConfirm()
    ->action(fn () => null);
```

### Keep Filament’s native button colors

```php
Action::make('delete')
    ->color('danger')
    ->holdConfirm()
    ->holdConfirmThemed(false)
    ->action(fn () => null);
```

### Primary / success / warning

Themed palette applies only to `danger` today. Other colors use Filament styling with the hold overlay on top:

```php
Action::make('publish')
    ->color('success')
    ->holdConfirm(1500)
    ->action(fn () => $this->publish());
```

---

## Rounded corners

Use the package `rounded()` helper (`CanRoundAction`):

```php
Action::make('delete')
    ->color('danger')
    ->holdConfirm()
    ->rounded('full')   // default auto-applied for danger hold confirm
    ->action(fn () => null);

Action::make('archive')
    ->holdConfirm()
    ->rounded('lg')
    ->action(fn () => null);
```

Supported values: `none`, `sm`, `md`, `lg`, `xl`, `full`.

---

## Item card integration

Common pattern: destructive control in an [ItemCard](/docs/itemcard) trailing slot.

```php
use Bjanczak\FilamentFlexFields\Filament\Actions\Action;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

ItemCard::make('Delete account')
    ->description('Permanently remove your account and all data')
    ->icon(GravityIcon::TrashBin)
    ->schema([
        Action::make('deleteAccount')
            ->label('Hold to Delete')
            ->color('danger')
            ->icon(GravityIcon::TrashBin)
            ->itemCard()          // compact pill style for card rows
            ->holdConfirm(2000)
            ->action(fn () => $this->deleteAccount()),
    ]),
```

Combine `itemCard()` + `holdConfirm()` for settings-style rows. See the playground **Item card group** section for live demos (default, fast 800 ms, slow 4 s left sweep).

---

## Table actions

```php
use Bjanczak\FilamentFlexFields\Filament\Actions\Action;
use Filament\Tables\Table;

public function table(Table $table): Table
{
    return $table
        ->actions([
            Action::make('delete')
                ->label('Hold to Delete')
                ->color('danger')
                ->icon(Heroicon::OutlinedTrash)
                ->holdConfirm()
                ->action(fn (Post $record) => $record->delete()),
        ]);
}
```

Works with bulk actions, header actions, and relation managers — anywhere Filament accepts `Action` instances. Always use the **package** `Action` class.

---

## Resource page actions

```php
use Bjanczak\FilamentFlexFields\Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete')
                ->label('Hold to Delete')
                ->color('danger')
                ->holdConfirm(3000)
                ->action(fn () => $this->delete()),
        ];
    }
}
```

---

## URL actions

Hold confirm can navigate instead of calling Livewire:

```php
Action::make('export')
    ->label('Hold to Export')
    ->color('primary')
    ->holdConfirm(1500)
    ->url(fn (): string => route('exports.download', $this->record));

Action::make('docs')
    ->label('Hold to Open Docs')
    ->holdConfirm()
    ->url('https://example.com/docs')
    ->openUrlInNewTab();
```

---

## Disabled state

When the action is disabled, Alpine receives `disabled: true` and ignores pointer down:

```php
Action::make('delete')
    ->holdConfirm()
    ->disabled(fn (): bool => ! $this->record->canDelete())
    ->action(fn () => $this->delete());
```

---

## Comparison with modal confirmation

```php
// Modal — user clicks once, confirms in dialog
Action::make('delete')
    ->requiresConfirmation()
    ->action(fn () => $this->delete());

// Hold — user must press continuously; no extra modal
Action::make('delete')
    ->color('danger')
    ->holdConfirm()
    ->action(fn () => $this->delete());
```

Choose hold confirm when you want **inline, tactile** friction without a second step. Choose `requiresConfirmation()` when you need to show extra context, a checkbox, or typed confirmation in a modal.

---

## Assets & performance

| Asset | Loading |
|-------|---------|
| `hold-confirm-action.js` | Per-action `@push` `modulepreload` + Alpine `x-load` when action renders |
| `hold-confirm-action.css` | Lazy via `load-stylesheet` partial when action renders |

CSS is **not** in the global `core.css` bundle — only actions that call `holdConfirm()` pull the stylesheet. After upgrading the package, run:

```bash
php artisan filament:assets
```

---

## Accessibility & mobile

- Only **primary button** (`event.button === 0`) starts a hold; right-click is ignored.
- Progress is visual; pair with a clear `label()` (e.g. “Hold to Delete”).
- On supported devices, a short **vibration** fires when the hold completes.
- `wire:loading` indicators are preserved outside the duplicated label layers.

For critical destructive actions, consider adding helper text in the surrounding UI (ItemCard description, modal heading, etc.).

---

## Playground

Enable the Flex Fields playground and open **Item card group**:

```dotenv
FLEX_FIELDS_PLAYGROUND=true
```

Three hold-confirm demos:

| Card | Duration | Sweep | Color |
|------|----------|-------|-------|
| Hold confirm | 2000 ms | right | primary |
| Hold confirm (fast) | 800 ms | right | danger |
| Hold confirm (slow) | 4000 ms | left | danger |

---

## API reference

### `holdConfirm(int|Closure $duration = 2000, string|Closure $sweep = 'right'): static`

Enables hold-to-confirm, sets duration and sweep, disables Livewire click handler, switches view to `hold-confirm`. Auto-applies `rounded('full')` for danger actions when no explicit `rounded()` was set.

### `holdConfirmSweep(string|Closure $sweep): static`

Updates sweep direction: `right`, `left`, `up`, `down`.

### `holdConfirmReleaseDuration(int|Closure $duration): static`

Milliseconds to animate progress back to zero when the user releases early. Default: `200`.

### `holdConfirmThemed(bool|Closure $themed = true): static`

When `true`, applies package danger palette for `color('danger')`. When `false`, keeps Filament’s default button styling.

### Introspection (advanced)

| Method | Returns |
|--------|---------|
| `hasHoldConfirm()` | Whether hold confirm is active |
| `getHoldConfirmDuration()` | Hold duration in ms, or `null` |
| `getHoldConfirmSweep()` | Sweep direction string |
| `getHoldConfirmReleaseDuration()` | Release duration in ms |
| `isHoldConfirmThemed()` | Whether themed palette is enabled |
| `getHoldConfirmPalette()` | `'danger'` or `null` |
| `getHoldConfirmCompleteExpression()` | JS expression run on complete (testing / debugging) |

---

## Related

- [ItemCard](/docs/itemcard) — trailing `Action` slots and `itemCard()` styling
- [ItemCardGroup](/docs/itemcardgroup) — grouped settings rows with hold confirm demos
- [Shared concepts](/docs/shared-concepts) — lazy assets and design tokens

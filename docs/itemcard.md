---
title: "ItemCard"
---

[← Back to Table of Contents](/docs/index)


modern SaaS-inspired list row / card for settings screens, navigation rows, and mixed action layouts. Renders a horizontal row with optional leading icon, title, description, trailing schema slot (switch, select, actions), and optional chevron.

**Class:** `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard`  
**Extends:** `Filament\Schemas\Components\Component`  
**Traits:** `CanOpenUrl`, `HasDescription`, `HasHeading`, `HasActions` (Filament)

Use inside any Filament schema (`Form`, `Section`, `ItemCardGroup`, `ItemCardStack`, etc.).

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Filament\Support\Icons\Heroicon;

ItemCard::make('Language')
    ->description('Choose your preferred language')
    ->icon(Heroicon::GlobeAlt)
    ->chevron();
```

### Context: `standalone` vs `group`

Rendering mode is controlled by **context**. This changes surface styling (border, shadow, padding, chevron shape).

| Context | When | Visual behaviour |
|---------|------|------------------|
| `auto` (default) | Parent is `ItemCardGroup` → `group`; otherwise → `standalone` | Detected automatically |
| `group` | Row inside a shared group surface | Flat row; no per-row border/shadow |
| `standalone` | Outside `ItemCardGroup` (or forced) | Self-contained card: border, radius, shadow (variant-dependent), chevron in circle |

```php
ItemCard::make('Profile')->standalone(); // force standalone surface
ItemCard::make('Profile')->inGroup();    // force flat group row (even outside a group)
```

### Variants

Set on the **card** (standalone) or inherited visually from the group row context.

| Variant | Standalone appearance |
|---------|---------------------|
| `default` | White surface, border, shadow |
| `secondary` | Light gray surface, no shadow, transparent border |
| `tertiary` | Darker gray surface, no shadow |
| `outline` | Transparent background, visible border, no shadow |
| `transparent` | Transparent background, no border, no shadow |

```php
ItemCard::make('Billing')
    ->variant('outline')
    ->description('Payment methods')
    ->icon(Heroicon::OutlinedCreditCard)
    ->chevron();
```

Invalid variant throws `InvalidArgumentException`.

### Chainable configuration API

#### `image(string|Closure|null $url)`


Sets the leading media image URL for the item card.

```php
ItemCard::make('profile')
    ->image('/images/user.jpg');
```

#### `imageShape(string|Closure $shape)`


Sets the crop shape of the leading image (`circle`, `square`, or `rounded`). Default is `circle`.

```php
ItemCard::make('profile')
    ->image('/images/user.jpg')
    ->imageShape('square');
```

#### `imageAlt(string|Closure|null $alt)`


Sets the alternative text for the leading media image.

```php
ItemCard::make('profile')
    ->image('/images/user.jpg')
    ->imageAlt('User avatar');
```
### Pressable click priority

When the row is pressable:

1. `url()` → navigation link
2. `action()` / `pressableAction()` → `wire:click` → `mountAction(...)` with `schemaComponent` context
3. Chevron / group `pressable()` only → visual feedback (ripple + hover), no server action unless you add `pressableAction()`

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getVariant()` | `string` | Resolved variant name |
| `getIcon()` | `string\|BackedEnum\|Htmlable\|null` | Resolved icon |
| `hasChevron()` | `bool` | Whether chevron is shown |
| `getContext()` | `string` | `group` or `standalone` |
| `isPressable()` | `bool` | Whether row renders as button/link |
| `getPressableAction()` | `Action\|null` | Prepared action for row click |
| `hasInteractiveAction()` | `bool` | `true` when `schema()` has children |
| `getUrl()` | `string\|null` | Resolved URL |
| `shouldOpenUrlInNewTab()` | `bool` | New tab flag |

### Form integration

Fields inside `-&gt;schema()` are normal Filament form fields. They use the parent form `statePath` (e.g. `data.dark_mode`).

**Validation** runs on parent form submit (`$this-&gt;form-&gt;validate()`). Rules go on the **field**, not on `ItemCard`. Errors appear **below each field** in the trailing slot (standard Filament wrapper), not under `ItemCardGroup`.

**Autosave / on change:**

```php
SwitchField::make('dark_mode')
    ->live()
    ->afterStateUpdated(fn (bool $state) => $this->saveDarkMode($state)),
```

**Reading values on submit:**

```php
$data = $this->form->getState();
// ['dark_mode' => true, 'event_invites' => 'email', ...]
```

`ItemCard` / `ItemCardGroup` do not add their own state keys — only nested fields do.

### Trailing actions (Filament `Action`)

Use `Action` / `ActionGroup` in `schema()` for visible buttons (not whole-row click):

```php
use Bjanczak\FilamentFlexFields\Filament\Actions\Action;

ItemCard::make('Delete account')
    ->description('Permanently remove your account')
    ->schema([
        Action::make('delete')
            ->label('Delete')
            ->color('danger')
            ->itemCard()           // pill outlined small button style
            ->requiresConfirmation()
            ->action(fn () => $this->deleteAccount()),
    ]),
```

`-&gt;itemCard()` is provided by the package `Action` class (`CanStyleItemCardAction`).

### Select & switch in item cards

**Select** — use `SelectField` with `variant('item-card')`:

```php
SelectField::make('language')
    ->options(['en' => 'English', 'pl' => 'Polish'])
    ->variant('item-card')
    ->hiddenLabel(),
```

**Switch** — use `SwitchField` with `inline()` and optional `size('sm')`:

```php
SwitchField::make('dark_mode')
    ->inline()
    ->size('sm'),
```

### Form panel layout (`item-card--form-panel`)

Default `ItemCard` is a **horizontal row** (icon | title | trailing control). For **multi-field form sections**, add the `item-card--form-panel` class via `extraAttributes()`:

- Row 1: icon + title + description
- Row 2: child fields at **full width**

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

ItemCardStack::make()
    ->columns(['default' => 1, 'sm' => 2])
    ->extraAttributes(['class' => 'fff-form-layout fff-form-layout--grid'])
    ->schema([
        ItemCard::make('Profile')
            ->description('How you appear to guests')
            ->icon(GravityIcon::Person)
            ->variant('outline')
            ->standalone()
            ->extraAttributes(['class' => 'item-card--form-panel'])
            ->columns(1)
            ->schema([
                FlexTextInput::make('name')->label('Display name'),
                FlexTextInput::make('email')->label('Email')->email(),
            ]),
        ItemCard::make('Contact')
            ->description('Phone and country')
            ->icon(GravityIcon::Handset)
            ->variant('outline')
            ->standalone()
            ->extraAttributes(['class' => 'item-card--form-panel'])
            ->columns(1)
            ->schema([
                PhoneField::make('phone')->label('Phone'),
            ]),
    ]);
```

| Class | When to use |
|-------|-------------|
| `item-card--form-panel` | Vertical form section inside a card |
| `fff-form-layout--grid` | Two-column card grid on `ItemCardStack` (from `sm` / `640px`) |

> **Do not** nest full-width fields in a plain `ItemCard` without `item-card--form-panel` — controls render in the narrow trailing slot.

### Inherited Filament schema component API

`ItemCard` extends `Filament\Schemas\Components\Component`. These methods work as in core Filament:

| Method | Description |
|--------|-------------|
| `schema()` / `components()` | Child components in the trailing slot |
| `key()` | Explicit schema component key (optional; auto-set for `action()`) |
| `id()` | HTML `id` attribute |
| `hidden()` / `visible()` | Conditional rendering |
| `hiddenOn()` / `visibleOn()` | Hide/show per operation |
| `extraAttributes()` | Extra HTML attributes on root element |
| `columnSpan()` / `columnStart()` | Grid layout when parent uses columns |
| `columns()` / `gap()` | Child grid (default `gap(0)`, `columns(1)` in `setUp()`) |
| `registerActions()` | Register multiple named actions |
| `getAction()` / `getActions()` | Resolve registered actions |
| `actionSchemaModel()` | Model for action forms |
| `hasAction()` | Whether a named action exists |

All configuration methods accept `Closure` with Filament utility injection.

### HTML structure (data slots)

| Slot | Element |
|------|---------|
| `data-slot="item-card"` | Root (`div`, `button`, or `a`) |
| `item-card-icon` | Leading icon container |
| `item-card-content` | Title + description |
| `item-card-action` | Trailing schema (fields, actions) |
| `item-card-chevron` | Chevron indicator |

### CSS classes

| Class | Meaning |
|-------|---------|
| `item-card` | Base row |
| `item-card--{variant}` | Surface variant |
| `item-card--context-standalone` / `--context-group` | Layout context |
| `item-card--pressable` | Interactive row |
| `item-card--form-panel` | Vertical form layout (header row + full-width fields) |

---

# ItemCardGroup

![ItemCardGroup](/art/sc-7.png)

[← Back to Table of Contents](/docs/index)


Grouped list surface: multiple `ItemCard` rows share one bordered container (SaaS **item-card-group**). Optional group header, row separators, group-level variant, and group-wide pressable rows.

**Class:** `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardGroup`  
**Extends:** `Filament\Schemas\Components\Component`  
**Traits:** `HasDescription`, `HasHeading`

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardGroup;

ItemCardGroup::make('Settings')
    ->description('Manage your account preferences')
    ->separated()
    ->schema([
        ItemCard::make('Language')
            ->description('Enable automatic language detection')
            ->icon(Heroicon::GlobeAlt)
            ->schema([
                SwitchField::make('language_auto')->inline()->size('sm'),
            ]),
        ItemCard::make('Event Invites')
            ->icon(Heroicon::OutlinedEnvelope)
            ->schema([
                SelectField::make('event_invites')
                    ->options([...])
                    ->variant('item-card')
                    ->hiddenLabel(),
            ]),
    ]);
```

Child `ItemCard` rows automatically use **group** context (`item-card--context-group`).

### Chainable configuration API

#### `make(string|Closure|null $heading = null)`


Factory. Optional group title.

```php
Component::make('field_name')
    ->make('value');
```
#### `heading(string|Htmlable|Closure|null $heading)`


Group title. Rendered in `.item-card-group__title` (or outside header — see `headerStyle()`).

```php
Component::make('field_name')
    ->heading('value');
```
#### `description(string|Htmlable|Closure|null $description)`


Group subtitle in `.item-card-group__description`.

```php
Component::make('field_name')
    ->description('value');
```
#### `schema(array|Closure $components)`


Child rows — typically `ItemCard` instances. Aliases `components()`.

```php
Component::make('field_name')
    ->schema([
        // ... schema components
    ]);
```
#### `variant(string|Closure $variant)`


Surface style for the **group container** (not per-row when inside group).

| Variant | Appearance |
|---------|------------|
| `default` | White background, border, shadow |
| `secondary` | Light gray background |
| `tertiary` | Darker gray background |
| `outline` | Transparent background, border |
| `transparent` | No background, no border |

Default: `default`.

Per-row `ItemCard::variant()` still applies when cards are **standalone**; inside a group, rows are flat and the **group** variant colours the shared surface.

```php
Component::make('field_name')
    ->variant('primary');
```
#### `layout(string|Closure $layout)`


| Value | Description |
|-------|-------------|
| `list` (default) | Vertical list of rows |
| `grid` | Grid layout for child cards |

Invalid layout throws `InvalidArgumentException`.

```php
Component::make('field_name')
    ->layout('value');
```
#### `divided(bool|Closure $condition = true)`


Enables horizontal separators between rows. Adds class `item-card-group--divided`.

```php
Component::make('field_name')
    ->divided(true);
```
#### `separated(bool|Closure $condition = true)`


Alias for `divided()`.

```php
Component::make('field_name')
    ->separated(true);
```
#### `withoutSeparators()`


Shortcut for `divided(false)`. Default: separators **off**.

```php
Component::make('field_name')
    ->withoutSeparators();
```
#### `headerStyle(string|Closure $style)`


| Value | Description |
|-------|-------------|
| `embedded` (default) | Title and description inside the bordered group box |
| `outside` | Title and description **above** the box; only rows are inside the surface |

Outside header uses wrapper `.item-card-group-host` and renders header as sibling of `.item-card-group` surface.

```php
ItemCardGroup::make('Source Control')
    ->headerStyle('outside')
    ->separated()
    ->schema([...]);
```

#### `pressable(bool|Closure $condition = true)`


When enabled, **all child rows without interactive `schema()`** become pressable (hover + ripple). Rows with switch/select/action children stay non-pressable.

Does not add actions by itself — combine with `ItemCard::pressableAction()` or `-&gt;chevron()` + `-&gt;url()` per row.

```php
ItemCardGroup::make('Account')
    ->pressable()
    ->separated()
    ->schema([
        ItemCard::make('Profile')->chevron()->pressableAction(...),
    ]);
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getLayout()` | `string` | `list` or `grid` |
| `getVariant()` | `string` | Group variant |
| `isDivided()` | `bool` | Whether row separators are shown |
| `getHeaderStyle()` | `string` | `embedded` or `outside` |
| `areRowsPressable()` | `bool` | Group-level pressable flag |

### Form integration

Same as `ItemCard`: fields nested in child `ItemCard::schema()` participate in the parent form state and validation. `ItemCardGroup` has no form state of its own.

### Validation errors

Displayed **per field** under the control in the row's trailing slot. `ItemCardGroup` does not render a group-level error summary.

### Inherited Filament schema component API

| Method | Description |
|--------|-------------|
| `schema()` / `components()` | Child `ItemCard` rows |
| `key()`, `id()` | Schema identity |
| `hidden()`, `visible()`, `hiddenOn()`, `visibleOn()` | Conditional display |
| `extraAttributes()` | HTML attributes on group root |
| `columnSpan()`, `columns()`, `gap()` | Layout (defaults: `gap(0)`, `columns(1)`) |

### HTML structure (data slots)

| Slot | Element |
|------|---------|
| `data-slot="item-card-group"` | Root or host wrapper |
| `item-card-group-header` | Title + description |
| `item-card-group-surface` | Bordered box (outside header mode) |
| `item-card-group-content` | Child schema grid |

### CSS classes

| Class | Meaning |
|-------|---------|
| `item-card-group` | Group surface |
| `item-card-group-host` | Outside-header wrapper |
| `item-card-group--{layout}` | `list` / `grid` |
| `item-card-group--{variant}` | Surface variant |
| `item-card-group--divided` | Row separators enabled |

---

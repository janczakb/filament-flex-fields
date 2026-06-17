# ChoiceCards

![ChoiceCards](/art/sc-14.png)

[← Back to Table of Contents](index.md)


### Summary

SaaS-style **single-select** card group (radio behaviour).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards` |
| **State type** | `string\|null` — one option key |
| **Model cast** | `'plan' =&gt; 'string'` or backed enum |
| **FieldType** | `choice_cards` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;

ChoiceCards::make('plan')
    ->label('Select a plan')
    ->helperText('Choose the plan that suits your needs')
    ->options([
        'starter' => [
            'label' => 'Starter',
            'description' => 'For individuals',
            'price' => '$5',
            'price_suffix' => '/mo',
        ],
        'pro' => 'Pro',
    ])
    ->layout('stack')
    ->default('pro');
```

### Validation

| Behaviour | Detail |
|-----------|--------|
| Built-in | `Rule::in(...)` — value must be a key from `options()` |
| `required()` | At least one option must be selected |

### Configuration API

#### `options(array|Closure $options)`


Option list. See [Rich card option shape](shared-concepts.md).

```php
ChoiceCards::make('field_name')
    ->options([
        'option_1' => 'Option 1',
        'option_2' => 'Option 2',
    ]);
```
#### `disabledOptions(array|Closure $keys)`


Disables options by key. Merged with per-option `disabled`.

```php
ChoiceCards::make('field_name')
    ->disabledOptions(['option_2']);
```
#### `layout(string|Closure $layout)`


| Value | Description |
|-------|-------------|
| `stack` | Vertical list of cards. **Default.** |
| `grid` | Responsive column grid. Use with `gridColumns()`. |
| `media` | Horizontal row: icon + text. Best for icon-led options. |
| `featured` | Plan-style cards: icon box, badge, large price. |

```php
ChoiceCards::make('field_name')
    ->layout('value');
```
#### `gridColumns(int|array|Closure $columns)`


Column count for `grid` and multi-column `media` layouts.

```php
->gridColumns(3)

->gridColumns([
    'default' => 1,
    'sm' => 2,
    'md' => 3,
    'lg' => 4,
])
```

Breakpoints cascade upward (`sm` → `md` → `lg`). Maximum: **4 columns**.

#### `indicator(string|Closure|null $indicator)`


Selection marker in the top-right corner.

| Value | Description |
|-------|-------------|
| `radio` | Radio dot. Default for `stack`. |
| `check` | Filled circle with checkmark. Default for `featured`. |
| `none` | Border-only selection. Default for `media`. |

When omitted, the default is resolved from `layout()`.

```php
ChoiceCards::make('field_name')
    ->indicator('value');
```
#### `variant(string|Closure $variant)`


| Value | Description |
|-------|-------------|
| `default` | Standard grey card background. |
| `primary` | Stronger selected state (featured plans). |
| `secondary` | Subtle grid styling. |

```php
ChoiceCards::make('field_name')
    ->variant('primary');
```
#### `color(string|Closure|null $color)`


Accent color for the selected border. Default: `primary`. Supports Filament color tokens (`success`, `danger`, etc.).

```php
ChoiceCards::make('field_name')
    ->color('primary');
```
#### `size(string|ControlSize|Closure $size)`


Scales padding, typography, indicators, and icons. See [Control size](shared-concepts.md).

```php
ChoiceCards::make('field_name')
    ->size('md');
```
#### `ripple(bool|Closure $condition = true)`


Enables a Material-style click ripple on each card.

```php
ChoiceCards::make('field_name')
    ->ripple(true);
```

### Animations

- Smooth border and background transitions on select/deselect
- Indicator scale animation for `check` / `radio` states
- Respects `prefers-reduced-motion`

### FlexField schema config

When built via `FlexFieldFormBuilder`:

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `layout` | `layout()` |
| `grid_columns` / `columns` | `gridColumns()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `color` | `color()` |
| `ripple` | `ripple()` |
| `indicator` | `indicator()` |
| `disabled_options` | `disabledOptions()` |

---

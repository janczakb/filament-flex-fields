---
title: "ChoiceCards"
description: Single-select card group with rich options, layouts, and Material-style ripple effects.
---

![ChoiceCards](/art/sc-14.png)

[← Back to Table of Contents](/docs/index)

### Summary

Premium **single-select** card group (radio behavior). Ideal for pricing plans, delivery methods, and feature selection. Supports rich option metadata (descriptions, prices, icons, badges) and multiple responsive layouts.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards` |
| **State type** | `string\|int\|null` — one option key |
| **Model cast** | `'plan' => 'string'` · `'type' => 'integer'` |
| **FieldType** | `choice_cards` |
| **Playground** | `choice-cards` slug in Flex Fields playground |

---

### Basic usage

#### Pricing plans (Stack layout)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;

ChoiceCards::make('plan')
    ->options([
        'starter' => [
            'label' => 'Starter',
            'description' => 'For individuals',
            'price' => '$5',
            'price_suffix' => '/mo',
        ],
        'pro' => [
            'label' => 'Pro',
            'description' => 'For teams',
            'price' => '$15',
            'price_suffix' => '/mo',
        ],
    ])
    ->default('pro');
```

#### Delivery methods (Media layout)

```php
ChoiceCards::make('delivery')
    ->layout('media')
    ->gridColumns(['default' => 1, 'sm' => 3])
    ->options([
        'standard' => ['label' => 'Standard', 'icon' => 'heroicon-o-truck'],
        'express' => ['label' => 'Express', 'icon' => 'heroicon-o-bolt'],
        'rocket' => ['label' => 'Rocket', 'icon' => 'heroicon-o-rocket'],
    ]);
```

---

### State & validation

#### Stored value

State is the **option key** from `options()`.

```php
$record->plan; // string|null — e.g. 'pro'
```

#### Validation rules (built-in)

| Rule | When |
|------|------|
| `nullable` | Always (unless `required()`) |
| `Rule::in(...)` | Value must match a configured option key |
| `required` | When `->required()` |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `options(array\|Closure $options)` | Setup | `[]` | Rich option map. Keys: `label`, `description`, `price`, `price_suffix`, `icon`, `badge`, `badge_color`, `disabled`. |
| `disabledOptions(array\|Closure $keys)` | Setup | `[]` | Keys that cannot be selected. |
| `layout(string\|Closure $layout)` | Setup | `'stack'` | `stack`, `grid`, `media`, `featured`. |
| `gridColumns(int\|array\|Closure $cols)` | Setup | `1` | Responsive columns for `grid`/`media` (max 4). |
| `indicator(string\|Closure\|null $type)` | Setup | `auto` | Selection marker: `radio`, `check`, `none`. |
| `variant(string\|Closure $variant)` | Setup | `'default'` | `default`, `primary` (stronger selection), `secondary`. |
| `color(string\|Closure\|null $color)` | Setup | `'primary'` | Accent color for selection borders/indicators. |
| `size(string\|Closure $size)` | Setup | `'md'` | Padding and typography scale: `sm`, `md`, `lg`. |
| `ripple(bool\|Closure $on)` | Setup | `false` | Enable Material-style click ripple. |

---

### Real-world examples

#### Featured plan selector

```php
ChoiceCards::make('tier')
    ->layout('featured')
    ->variant('primary')
    ->indicator('check')
    ->options([
        'basic' => ['label' => 'Basic', 'price' => '$0'],
        'pro' => [
            'label' => 'Pro', 
            'price' => '$29', 
            'badge' => 'Recommended',
            'badge_color' => 'success'
        ],
    ]);
```

#### Payment method selector

```php
ChoiceCards::make('payment_method')
    ->layout('media')
    ->gridColumns(2)
    ->options([
        'visa' => ['label' => '**** 1234', 'icon' => 'heroicon-o-credit-card'],
        'paypal' => ['label' => 'PayPal', 'icon' => 'heroicon-o-circle-stack'],
    ]);
```

---

### Playground

`/admin/flex-fields-playground/choice-cards`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [ChoiceCheckboxCards](/docs/choicecheckboxcards) | For multi-select card groups. |
| [FlexChecklist](/docs/flexchecklist) | For a more compact multi-select list. |
| [ItemCardGroup](/docs/itemcardgroup) | For grouping rows that aren't necessarily selectable form fields. |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-choice-cards` | Root wrapper |
| `fff-choice-cards--layout-{stack\|grid\|media\|featured}` | Layout modifier |
| `fff-choice-cards--variant-{default\|primary\|secondary}` | Visual variant |
| `fff-choice-cards--{sm\|md\|lg}` | Size modifier |
| `fff-choice-card` | Individual card element |
| `fff-choice-card--selected` | Active card state |
| `fff-choice-card--disabled` | Disabled card state |
| `fff-choice-card__indicator` | Selection marker wrapper |
| `fff-choice-card__ripple` | Ripple animation element |

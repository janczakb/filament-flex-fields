---
title: "ChoiceCheckboxCards"
description: Multi-select card group with rich options, selection limits, and animated checkmark indicators.
---

[← Back to Table of Contents](/docs/index)

### Summary

Premium **multi-select** card group (checkbox behavior). Features rich option metadata, Material-style ripples, and built-in validation for minimum, maximum, or exact selection counts.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCheckboxCards` |
| **State type** | `array<int\|string>` — list of selected option keys |
| **Model cast** | `'tags' => 'array'` · `'features' => 'json'` |
| **FieldType** | `choice_checkbox_cards` |
| **Playground** | `choice-checkbox-cards` slug in Flex Fields playground |

---

### Basic usage

#### Feature selection (Stack layout)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCheckboxCards;

ChoiceCheckboxCards::make('features')
    ->options([
        'security' => ['label' => 'Security', 'description' => 'Real-time threat detection'],
        'storage' => ['label' => 'Storage', 'description' => 'Unlimited cloud storage'],
        'analytics' => ['label' => 'Analytics', 'description' => 'Advanced reporting'],
    ])
    ->minSelections(1)
    ->default(['security']);
```

#### Add-ons grid (Check indicator)

```php
ChoiceCheckboxCards::make('addons')
    ->layout('grid')
    ->gridColumns(['default' => 1, 'sm' => 3])
    ->indicator('check')
    ->options([
        'backups' => ['label' => 'Backups', 'price' => '$5/mo'],
        'monitoring' => ['label' => 'Monitoring', 'price' => '$9/mo'],
        'support' => ['label' => 'Support', 'price' => '$15/mo'],
    ]);
```

---

### State & validation

#### Stored value

State is an **array** of unique option keys.

```php
$record->features; // ['security', 'analytics']
```

#### Validation rules (built-in)

| Rule | Description |
|------|-------------|
| `array` | Always enforced. |
| Option keys | Selected keys must exist in `options()`. |
| `minSelections(n)` | Minimum number of items (default 1 if `required()`). |
| `maxSelections(n)` | Maximum number; UI blocks further selection. |
| `exactSelections(n)` | Requires exactly `n` items; overrides min/max. |

---

### Configuration API

Shares the core card API with [ChoiceCards](/docs/choicecards).

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `options(array\|Closure $options)` | Setup | `[]` | Rich option map (label, description, price, icon, etc.). |
| `minSelections(int\|Closure $n)` | Validation | `null` | Minimum required selections. |
| `maxSelections(int\|Closure $n)` | Validation | `null` | Maximum allowed selections. |
| `exactSelections(int\|Closure $n)` | Validation | `null` | Requires exactly `n` selections. |
| `layout(string\|Closure $layout)` | Setup | `'stack'` | `stack`, `grid`, `media`, `featured`. |
| `gridColumns(int\|array\|Closure $cols)` | Setup | `1` | Responsive columns (max 4). |
| `indicator(string\|Closure $type)` | Setup | `auto` | `checkbox`, `check`, `none`. |
| `variant(string\|Closure $variant)` | Setup | `'default'` | `default`, `primary`, `secondary`. |
| `color(string\|Closure $color)` | Setup | `'primary'` | Accent color for selection. |
| `ripple(bool\|Closure $on)` | Setup | `false` | Enable Material-style click ripple. |

---

### Real-world examples

#### Pizza toppings with limits

```php
ChoiceCheckboxCards::make('toppings')
    ->label('Select up to 3 toppings')
    ->options([
        'cheese' => 'Extra Cheese',
        'pepperoni' => 'Pepperoni',
        'mushrooms' => 'Mushrooms',
        'olives' => 'Olives',
    ])
    ->minSelections(1)
    ->maxSelections(3);
```

#### Integration selector with icons

```php
ChoiceCheckboxCards::make('integrations')
    ->layout('media')
    ->gridColumns(3)
    ->indicator('none')
    ->options([
        'github' => ['label' => 'GitHub', 'icon' => 'gravityui-github'],
        'slack' => ['label' => 'Slack', 'icon' => 'gravityui-slack'],
        'linear' => ['label' => 'Linear', 'icon' => 'gravityui-linear'],
    ]);
```

---

### Playground

`/admin/flex-fields-playground/choice-checkbox-cards`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [ChoiceCards](/docs/choicecards) | For single-select card groups. |
| [FlexChecklist](/docs/flexchecklist) | For a more compact multi-select list. |
| [MatrixChoiceField](/docs/matrixchoicefield) | For complex grid-based multi-selection. |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-choice-cards` | Root wrapper (shared with ChoiceCards) |
| `fff-choice-card` | Individual card element |
| `fff-choice-card--selected` | Active card state |
| `fff-choice-card__indicator` | Checkbox/check marker wrapper |

Uses the same BEM structure as [ChoiceCards](/docs/choicecards).

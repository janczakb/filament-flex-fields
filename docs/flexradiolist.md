---
title: "FlexRadiolist"
description: Enhanced radio list with icons, descriptions, and custom sizing.
---

[← Back to Table of Contents](/docs/index)

### Summary

A modern replacement for Filament's native Radio component. `FlexRadiolist` adds support for per-option icons, rich descriptions, and custom sizing via CSS variables. It also features a "label-only" variant for compact layouts.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist` |
| **State type** | `string\|int\|null` |
| **Model cast** | `'plan_type' => 'string'` · `'category_id' => 'integer'` |
| **FieldType** | `radiolist` |
| **Playground** | `flex-radiolist` slug in Flex Fields playground |
| **Default variant** | `default` |

---

### Basic usage

#### Standard list with descriptions
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist;

FlexRadiolist::make('plan')
    ->options([
        'basic' => 'Basic Plan',
        'pro' => 'Professional',
        'ent' => 'Enterprise',
    ])
    ->descriptions([
        'basic' => 'Great for individuals starting out.',
        'pro' => 'Advanced features for growing teams.',
        'ent' => 'Custom solutions for large organizations.',
    ]);
```

#### List with icons and colors
```php
FlexRadiolist::make('status')
    ->options([
        'active' => 'Active',
        'pending' => 'Pending',
        'closed' => 'Closed',
    ])
    ->icons([
        'active' => 'heroicon-o-check-circle',
        'pending' => 'heroicon-o-clock',
        'closed' => 'heroicon-o-x-circle',
    ])
    ->color('success');
```

---

### State & validation

#### Stored value
State is the **option key** from `options()`.

```php
$record->plan; // string|null — e.g. 'pro'
```

#### Validation
The field automatically validates that the selected value exists in the configured options.

```php
FlexRadiolist::make('category_id')
    ->options(Category::pluck('name', 'id'))
    ->required();
```

---

### Variants

#### Default
Shows labels, descriptions (if set), and icons (if set) in a vertical list of cards.

#### Label-only
A more compact version that hides icons and descriptions, focusing only on the labels.

```php
FlexRadiolist::make('size')
    ->options(['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'])
    ->variant('label-only');
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `options(array\|Closure $options)` | Setup | `[]` | Key => Label mapping |
| `icons(array\|Closure $icons)` | Setup | `[]` | Key => Icon string |
| `descriptions(array\|Closure $desc)` | Setup | `[]` | Key => Description string |
| `disabledOptions(array\|Closure $keys)` | Setup | `[]` | Keys to disable |
| `color(string\|Closure\|null $color)` | Setup | `'primary'` | Active option color |
| `variant(string\|Closure $variant)` | Setup | `'default'` | `default` or `label-only` |
| `size(string\|Closure $size)` | Setup | `'md'` | `sm`, `md`, `lg` |

---

### Real-world examples

#### Plan Selection Card
```php
FlexRadiolist::make('subscription')
    ->label('Choose your plan')
    ->options([
        'monthly' => 'Monthly Billing',
        'yearly' => 'Yearly Billing (Save 20%)',
    ])
    ->descriptions([
        'monthly' => 'Billed every 30 days.',
        'yearly' => 'One annual payment.',
    ])
    ->icons([
        'monthly' => 'heroicon-o-calendar',
        'yearly' => 'heroicon-o-sparkles',
    ])
    ->size('lg')
    ->required();
```

---

### Playground

`/admin/flex-fields-playground/flex-radiolist`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexChecklist](/docs/flexchecklist) | Multiple selection instead of single |
| [ChoiceCards](/docs/choicecards) | Large visual cards with images |
| [SegmentControl](/docs/segmentcontrol) | Horizontal sliding selection |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-flex-radiolist` | Root wrapper |
| `fff-flex-radiolist--{sm\|md\|lg}` | Size variant |
| `fff-flex-radiolist--{variant}` | Variant class |
| `fff-flex-radiolist__item` | Individual option card |
| `fff-flex-radiolist__label` | Option label |
| `fff-flex-radiolist__description` | Option description |
| `fff-flex-radiolist__icon` | Option icon wrapper |
| `fff-flex-radiolist__indicator` | Radio dot indicator |

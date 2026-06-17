---
title: "ChoiceCheckboxCards"
---

[← Back to Table of Contents](/docs/index)


### Summary

SaaS-style **multi-select** card group (checkbox behaviour).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCheckboxCards` |
| **State type** | `array&lt;int\|string&gt;` — list of selected option keys |
| **Model cast** | `'toppings' =&gt; 'array'` or `'json'` |
| **FieldType** | `choice_checkbox_cards` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCheckboxCards;

ChoiceCheckboxCards::make('toppings')
    ->label('Pizza toppings')
    ->helperText('Select 1 to 3 toppings')
    ->options([
        'cheese' => ['label' => 'Extra cheese', 'description' => 'Mozzarella blend'],
        'mushrooms' => ['label' => 'Mushrooms', 'description' => 'Fresh button mushrooms'],
    ])
    ->minSelections(1)
    ->maxSelections(3)
    ->layout('stack')
    ->default(['cheese']);
```

### Validation

| Rule / method | Description |
|---------------|-------------|
| `array` | Built-in — state must be an array |
| Option keys | Unknown keys fail with `choice_checkbox_cards.invalid_option` |
| `required()` | If `minSelections` is not set, requires at least **1** selection |
| `minSelections(n)` | Minimum number of selected options |
| `maxSelections(n)` | Maximum number; UI blocks further selection when limit is reached |
| `exactSelections(n)` | Exactly `n` selections; overrides min/max validation |

Translation keys: `filament-flex-fields::default.validation.choice_checkbox_cards.*`

### Comparison with Filament `Checkbox`

| Filament `Checkbox` | ChoiceCheckboxCards |
|---------------------|---------------------|
| Boolean state | Array of option keys |
| `accepted()` | `required()` or `minSelections(1)` |
| `declined()` | Not applicable |
| `inline()` | Not applicable — label uses field wrapper above cards |

Use native `Checkbox` or `SwitchField` for a single boolean. Use `ChoiceCheckboxCards` for multi-select card UI.

### Configuration API

Shares the same card API as ChoiceCards:

- `options()` — see [Rich card option shape](/docs/shared-concepts)
- `disabledOptions()`
- `layout()`
- `gridColumns()`
- `variant()`
- `color()`
- `size()`
- `ripple()`

#### `indicator(string|Closure|null $indicator)`


| Value | Description |
|-------|-------------|
| `checkbox` | Square checkbox with animated checkmark. Default for `stack`. |
| `check` | Circle checkmark. Default for `featured`. |
| `none` | Border-only. Default for `media`. |

```php
ChoiceCheckboxCards::make('field_name')
    ->indicator('value');
```
#### `minSelections(int|Closure|null $count)`


Minimum selected options.

```php
ChoiceCheckboxCards::make('field_name')
    ->minSelections(10);
```
#### `maxSelections(int|Closure|null $count)`


Maximum selected options. Unselected cards become non-interactive when the limit is reached; selected cards can still be unchecked.

```php
ChoiceCheckboxCards::make('field_name')
    ->maxSelections(10);
```
#### `exactSelections(int|Closure|null $count)`


Requires exactly this many selections.

```php
ChoiceCheckboxCards::make('field_name')
    ->exactSelections(10);
```

### Animations

Same card transitions as ChoiceCards, plus:

- Checkbox box fill animation on select
- Checkmark scale-in with slight delay
- Empty ring → filled check crossfade for `check` indicator

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| All ChoiceCards keys | Same mapping |
| `min_selections` | `minSelections()` |
| `max_selections` | `maxSelections()` |
| `exact_selections` | `exactSelections()` |

---

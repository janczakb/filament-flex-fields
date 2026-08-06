---
title: "SelectField"
description: Styled Filament Select with pill trigger, rich option rows, grid layout, and multi-select chips.
---

[← Back to Table of Contents](/docs/index)

### Summary

Styled Filament **Select** with pill trigger, rich option rows, grid layout, and multi-select chips. Extends Filament `Select` — **all native Select APIs remain available**.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField` |
| **Extends** | `Filament\Forms\Components\Select` |
| **State type** | `string\|int\|null` (single) · `array` (multiple) |
| **Model cast** | `'category_id' => 'integer'` · `'tags' => 'json'` (multiple) |
| **FieldType** | `select` (use `multiple` in config for multi-select) |
| **Playground** | `select-field` slug in Flex Fields playground |

Works with all standard Filament field APIs: `required()`, `disabled()`, `hidden()`, `live()`, `afterStateUpdated()`, validation rules, etc.

---

### Basic usage

#### Standard select with rich options

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;

SelectField::make('framework')
    ->label('Framework')
    ->options([
        'laravel' => [
            'label' => 'Laravel',
            'description' => 'The PHP Framework for Web Artisans',
            'icon' => 'heroicon-o-bolt',
            'badge' => 'v11',
        ],
        'livewire' => [
            'label' => 'Livewire',
            'description' => 'Full-stack framework for Laravel',
            'icon' => 'heroicon-o-sparkles',
        ],
    ])
    ->searchable()
    ->variant('bordered')
    ->size('md');
```

#### Multi-select with chips

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;

SelectField::make('tags')
    ->multiple()
    ->options([
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ])
    ->chipColor('primary')
    ->required();
```

---

### State & validation

#### Stored value

State is the **option key** from `options()`.

```php
// Single select
$record->category_id; // int|string|null — e.g. 1

// Multi-select
$record->tags; // array — e.g. ['draft', 'published']
```

#### Default state

The field defaults to **`null`** (single) or **`[]`** (multiple).

```php
SelectField::make('category_id')
    ->default(1);
```

#### Validation rules

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
| `variant(string\|Closure $variant)` | Setup | `'bordered'` | Visual style: `bordered`, `secondary`, `flat`, `faded`, `soft`, `underlined`, `item-card` |
| `color(string\|Closure\|null $color)` | Setup | `null` | Accent color for the field |
| `chipColor(string\|Closure $chipColor)` | Setup | `'neutral'` | Color for multi-select chips |
| `richOptions(bool\|Closure $condition = true)` | Setup | auto | Force rich option rendering |
| `optionLayout(string\|Closure $layout)` | Setup | `'list'` | Dropdown layout: `list`, `grid` |
| `inlineFieldLabel(bool\|Closure $condition = true)` | Setup | `false` | Render label inside the field track |
| `inlineSearch(bool\|Closure $condition = true)` | Setup | `false` | Render search input inside the dropdown |
| `clearable(bool\|Closure $condition = true)` | Setup | auto | Show clear button (×) |
| `dropdownAlign(string\|Closure $align)` | Setup | auto | Align dropdown: `start`, `end` |
| `size(string\|ControlSize\|Closure $size)` | Setup | `'md'` | Control size: `sm`, `md`, `lg` |
| `rounding(string\|Closure\|null $rounding)` | Setup | config | Border radius token |

#### `variant()`

```php
SelectField::make('category_id')->variant('soft');
SelectField::make('category_id')->variant('underlined');
```

#### `optionLayout()`

Use `grid` for a multi-column visual picker:

```php
SelectField::make('theme')
    ->options([
        'light' => ['label' => 'Light', 'icon' => 'heroicon-o-sun'],
        'dark' => ['label' => 'Dark', 'icon' => 'heroicon-o-moon'],
    ])
    ->optionLayout('grid');
```

#### `inlineSearch()`

Recommended for searchable selects to keep the UI compact:

```php
SelectField::make('user_id')
    ->searchable()
    ->inlineSearch();
```

---

### Real-world examples

#### User select with avatars

```php
SelectField::make('user_id')
    ->label('Assignee')
    ->options(User::all()->mapWithKeys(fn ($user) => [
        $user->id => [
            'label' => $user->name,
            'description' => $user->email,
            'image' => $user->avatar_url,
        ],
    ]))
    ->searchable()
    ->inlineSearch()
    ->variant('soft');
```

#### Multi-select tags in a Section

```php
Section::make('Metadata')
    ->schema([
        SelectField::make('tags')
            ->multiple()
            ->relationship('tags', 'name')
            ->chipColor('success')
            ->preload(),
    ])
```

---

### Playground

`/admin/flex-fields-playground/select-field`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexRadiolist](/docs/flexradiolist) | When all options should be visible at once |
| [ChoiceCards](/docs/choicecards) | Large card-style selection with more detail |
| [DualListboxField](/docs/duallistboxfield) | When managing large sets of selected items |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-select-field` | Root wrapper |
| `fff-select-field--{sm\|md\|lg}` | Size modifier |
| `fff-select-field--{variant}` | Visual variant |
| `fff-select-field--layout-{list\|grid}` | Option layout |
| `fff-select-field--chips-{color}` | Multi-select chip color |
| `fff-select-field--inline-field-label` | Inline label active |
| `fff-select-field--inline-search` | Inline search active |

---

### Performance

| Mechanism | What it does |
|-----------|--------------|
| **Lazy CSS** | Loads `select-field` styles only when the field renders |
| **JS Transformation** | Efficiently prepares rich options for the frontend component |
| **Search Cache** | Memoizes search results to reduce server round-trips |

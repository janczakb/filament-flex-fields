---
title: "DualListboxField"
description: Dual-pane selection tool with search, reordering, and virtual scrolling.
---

![DualListboxField](/art/sc-8.png)

[← Back to Table of Contents](/docs/index)

### Summary

Multi-select with "Available" and "Selected" panes. Supports real-time search, drag-and-drop reordering, double-click transfer, and virtual scrolling for high-performance handling of thousands of options.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\DualListboxField` |
| **State type** | `array<string\|int>` — list of selected keys |
| **Model cast** | `'roles' => 'array'` · `'tags' => 'array'` |
| **FieldType** | `dual-listbox` |
| **Playground** | `dual-listbox` slug in Flex Fields playground |
| **Default variant** | `bordered` |

---

### Basic usage

#### Standard role selection
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\DualListboxField;

DualListboxField::make('roles')
    ->options([
        'admin' => 'Administrator',
        'editor' => 'Editor',
        'viewer' => 'Viewer',
    ])
    ->searchable();
```

#### Custom labels and height
```php
DualListboxField::make('permissions')
    ->availableLabel('Unassigned Permissions')
    ->selectedLabel('Active Permissions')
    ->listHeight('24rem')
    ->reorderable(false);
```

---

### State & validation

#### Stored value
The field stores an array of keys from the `options()` list. The order of keys in the array matches the order in the "Selected" pane if `reorderable()` is enabled.

```php
$record->roles; // ['admin', 'editor']
```

#### Validation Rules
You can enforce minimum or maximum selection counts:

| Rule | Method | Description |
|------|--------|-------------|
| `min` | `minItems(5)` | Must select at least 5 items |
| `max` | `maxItems(10)` | Cannot select more than 10 items |
| `exact` | `exactItems(3)` | Must select exactly 3 items |

---

### Advanced Features

#### Virtual Scrolling
When the number of options exceeds **100**, the component automatically enables virtual scrolling. This allows it to handle 10,000+ options without slowing down the browser.

#### Double-click Transfer
Users can double-click any item to instantly move it to the other pane. This can be disabled via `moveOnDoubleClick(false)`.

#### Custom Icons
Every button and search icon can be customized to match your UI.

```php
DualListboxField::make('items')
    ->icons([
        'search' => 'heroicon-o-magnifying-glass',
        'move_right' => 'heroicon-o-arrow-right-circle',
        'move_left' => 'heroicon-o-arrow-left-circle',
    ]);
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `options(array\|Closure $options)` | Setup | `[]` | Key => Label mapping |
| `disabledOptions(array\|Closure $keys)` | Setup | `[]` | Keys that cannot be moved |
| `variant(string\|Closure $variant)` | Setup | `'bordered'` | `bordered`, `flat`, `faded` |
| `listHeight(string\|Closure $h)` | Setup | `'16rem'` | Height of the list panes |
| `searchable(bool\|Closure $cond)` | Setup | `true` | Enable search inputs |
| `reorderable(bool\|Closure $cond)` | Setup | `true` | Enable drag-and-drop reordering |
| `moveOnDoubleClick(bool\|Closure $c)`| Setup | `true` | Transfer on double-click |
| `showTransferButtons(bool\|Closure $c)`| Setup | `true` | Show central action buttons |
| `availableLabel(string\|Closure $l)` | Setup | (trans) | Label for the left pane |
| `selectedLabel(string\|Closure $l)`  | Setup | (trans) | Label for the right pane |
| `minItems(int\|Closure $count)`      | Setup | `null` | Minimum selection count |
| `maxItems(int\|Closure $count)`      | Setup | `null` | Maximum selection count |
| `size(string\|Closure $size)`         | Setup | `'md'` | `sm`, `md`, `lg` |
| `rounding(string\|Closure $round)`    | Setup | config | Border radius token |

---

### Real-world examples

#### User Permissions Management
```php
DualListboxField::make('permissions')
    ->label('System Permissions')
    ->options(Permission::pluck('name', 'id'))
    ->searchable()
    ->availableLabel('Available')
    ->selectedLabel('Granted')
    ->listHeight('30rem')
    ->required();
```

---

### Playground

`/admin/flex-fields-playground/dual-listbox`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexChecklist](/docs/flexchecklist) | Simple multi-select for small lists |
| [SelectField](/docs/selectfield) | Standard dropdown with multi-select support |
| [ChoiceCards](/docs/choicecards) | Visual card-based multi-selection |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-dual-listbox-field` | Root wrapper |
| `fff-dual-listbox-field__pane` | Individual list container |
| `fff-dual-listbox-field__search` | Search input wrapper |
| `fff-dual-listbox-field__list` | The actual scrollable list |
| `fff-dual-listbox-field__item` | Individual option element |
| `fff-dual-listbox-field__actions` | Central button group |
| `fff-dual-listbox-field--{variant}` | Visual variant class |

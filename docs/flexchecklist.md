---
title: "FlexChecklist"
description: Multi-select checklist with row animations, icons, descriptions, and locked disabled options.
---

[← Back to Table of Contents](/docs/index)

### Summary

**Multi-select** checklist with row layout, optional icons and descriptions, and animated checkbox indicators.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexChecklist` |
| **State type** | `list<string\|int>` — selected option keys |
| **FieldType** | `flex_checklist` |
| **Playground** | `flex-checklist` slug in Flex Fields playground |
| **State cast** | `OptionsArrayStateCast` |

---

### Basic usage

#### Standard checklist

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexChecklist;

FlexChecklist::make('features')
    ->label('Included features')
    ->options([
        'wifi' => 'Wi‑Fi',
        'parking' => 'Parking',
        'breakfast' => 'Breakfast',
    ]);
```

#### Rich options with icons and descriptions

```php
FlexChecklist::make('permissions')
    ->options([
        'read' => [
            'label' => 'Read',
            'description' => 'Can view records',
            'icon' => 'heroicon-o-eye',
        ],
        'write' => [
            'label' => 'Write',
            'description' => 'Can edit records',
            'icon' => 'heroicon-o-pencil',
        ],
    ])
    ->color('success');
```

---

### State & validation

#### Stored value

State is an **array** of unique string or integer keys.

```php
$record->features; // ['wifi', 'breakfast']
```

#### Validation rules (built-in)

| Rule | Description |
|------|-------------|
| `array` | Always enforced. |
| Option keys | Selected keys must exist in `options()`. |
| `minSelections(n)` | Minimum items (default 1 if `required()`). |
| `maxSelections(n)` | Maximum items allowed. |
| `exactSelections(n)` | Requires exactly `n` items. |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `options(array\|Closure $options)` | Setup | `[]` | Option map: `key => label` or rich array (`label`, `description`, `icon`, `disabled`). |
| `icons(array\|Closure $icons)` | Setup | `[]` | Per-key icon map (overrides `options`). |
| `descriptions(array\|Closure $desc)` | Setup | `[]` | Per-key description map (overrides `options`). |
| `disabledOptions(array\|Closure $keys)` | Setup | `[]` | Keys rendered locked with a lock icon. |
| `color(string\|Closure\|null $color)` | Setup | `'primary'` | Accent color for selected rows. |
| `size(string\|Closure $size)` | Setup | `'md'` | Row scale: `sm`, `md`, `lg`. |
| `minSelections(int\|Closure $n)` | Validation | `null` | Minimum required selections. |
| `maxSelections(int\|Closure $n)` | Validation | `null` | Maximum allowed selections. |
| `exactSelections(int\|Closure $n)` | Validation | `null` | Requires exactly `n` selections. |

#### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getLockIcon()` | `mixed` | Resolved icon for disabled rows. |
| `getNormalizedOptions()` | `array` | Merged metadata for all options. |
| `getWrapperClasses()` | `array` | List of CSS classes for the root element. |

---

### Real-world examples

#### Project file manager

```php
FlexChecklist::make('files')
    ->label('Project files')
    ->options([
        'budget' => 'Budget.xlsx',
        'reports' => 'Reports.pdf',
        'archive' => 'Archive.zip',
    ])
    ->icons([
        'budget' => 'heroicon-o-table-cells',
        'reports' => 'heroicon-o-document-text',
        'archive' => 'heroicon-o-archive-box',
    ])
    ->disabledOptions(['archive']);
```

---

### Playground

`/admin/flex-fields-playground/flex-checklist`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexRadiolist](/docs/flexradiolist) | For single-select lists with the same row layout. |
| [ChoiceCheckboxCards](/docs/choicecheckboxcards) | For larger, more prominent multi-select cards. |
| [ItemCardGroup](/docs/itemcardgroup) | For grouping rows that aren't form inputs. |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-flex-checklist` | Root wrapper |
| `fff-flex-checklist--{sm\|md\|lg}` | Size modifier |
| `fff-flex-checklist__row` | Individual option row |
| `fff-flex-checklist__row--selected` | Active row state |
| `fff-flex-checklist__row--disabled` | Disabled/locked state |
| `fff-flex-checklist__indicator` | Checkbox container |
| `fff-flex-checklist__content` | Text and icon container |

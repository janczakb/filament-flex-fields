---
title: "FlexMatrixTable"
description: High-performance matrix input for bulk data entry in a structured grid.
---

[← Back to Table of Contents](/docs/index)

### Summary

A specialized grid input based on Filament's Repeater, optimized for bulk data entry across fixed rows and columns. Unlike a standard repeater, `FlexMatrixTable` is non-addable and non-deletable, providing a stable spreadsheet-like interface for complex configurations.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMatrixTable` |
| **State type** | `array<string, array<string, mixed>>` |
| **Model cast** | `'matrix_data' => 'array'` |
| **FieldType** | `flex-matrix-table` |
| **Playground** | `flex-matrix-table` slug in Flex Fields playground |
| **Extends** | `Filament\Forms\Components\Repeater` |

---

### Basic usage

#### Permission Matrix
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMatrixTable;
use Filament\Forms\Components\Toggle;

FlexMatrixTable::make('permissions')
    ->rows([
        'users' => 'User Management',
        'posts' => 'Blog Posts',
        'settings' => 'System Settings',
    ])
    ->schema([
        Toggle::make('view')->label('View'),
        Toggle::make('create')->label('Create'),
        Toggle::make('edit')->label('Edit'),
        Toggle::make('delete')->label('Delete'),
    ]);
```

#### Custom Column Widths
```php
FlexMatrixTable::make('pricing')
    ->rows(['basic' => 'Basic', 'pro' => 'Pro'])
    ->schema([
        TextInput::make('price')->numeric()->prefix('$'),
        TextInput::make('limit')->numeric(),
    ])
    ->columnWidths([
        'price' => '150px',
        'limit' => '100px',
    ]);
```

---

### State & validation

#### Stored value
The state is an associative array where keys are row IDs and values are arrays of field data from the `schema()`.

```php
[
    'users' => [
        'view' => true,
        'create' => false,
        'edit' => true,
    ],
    'posts' => [
        'view' => true,
        'create' => true,
        'edit' => true,
    ],
]
```

#### Fixed Structure
By default, `FlexMatrixTable` disables `addable()`, `deletable()`, and `reorderable()`. This ensures the matrix always matches your defined `rows()`.

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `rows(array\|Closure $rows)` | Setup | `[]` | Row keys => labels |
| `columnWidths(array\|Closure $w)` | Setup | `[]` | Field name => CSS width |
| `size(string\|Closure $size)` | Setup | `'md'` | `sm`, `md`, `lg` |
| `rounding(string\|Closure $round)` | Setup | config | Border radius token |
| `schema(array\|Closure $schema)` | Setup | — | Fields to show in each row |

---

### Real-world examples

#### Feature Availability Matrix
```php
FlexMatrixTable::make('features')
    ->label('Plan Features')
    ->rows([
        'api' => ['label' => 'API Access', 'desc' => 'REST & GraphQL'],
        'sso' => ['label' => 'Single Sign-On', 'desc' => 'SAML & OAuth'],
        'support' => ['label' => 'Priority Support', 'desc' => '24/7 coverage'],
    ])
    ->schema([
        Checkbox::make('starter')->label('Starter'),
        Checkbox::make('business')->label('Business'),
        Checkbox::make('enterprise')->label('Enterprise'),
    ])
    ->columnWidths([
        'starter' => '100px',
        'business' => '100px',
        'enterprise' => '100px',
    ]);
```

---

### Playground

`/admin/flex-fields-playground/flex-matrix-table`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [MatrixChoiceField](/docs/matrixchoicefield) | Simple radio/checkbox grids without custom schemas |
| `Repeater` | When users need to add/remove rows dynamically |
| [ItemCardGroup](/docs/itemcardgroup) | Visual card-based bulk selection |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-matrix-choice` | Root wrapper (shared with MatrixChoice) |
| `fff-matrix-choice--{sm\|md\|lg}` | Size variant |
| `fff-matrix-choice__header` | Table header row |
| `fff-matrix-choice__row` | Individual data row |
| `fff-matrix-choice__cell` | Data cell |
| `fff-matrix-choice__label` | Row label wrapper |
| `fff-matrix-choice__description` | Row description text |

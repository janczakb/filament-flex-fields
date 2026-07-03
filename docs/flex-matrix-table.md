---
title: "FlexMatrixTable"
---

[← Back to Table of Contents](/docs/index)

### Summary

**FlexMatrixTable** is an advanced grid layout component based on `MatrixChoiceField`. Instead of hardcoding simple radio buttons or checkboxes in every cell, it allows you to inject **any** standard Filament or Flex Fields components into the columns (such as `SelectField`, `FlexTextInput`, `SwitchField`, etc.).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMatrixTable` |
| **State type** | `array<string, array<string, mixed>>` |
| **FieldType** | `flex_matrix_table` |
| **Playground** | `flex-matrix-table` |
| **Stylesheet** | Shared with `matrix-choice-field` |
| **Model cast** | `'data' => 'array'` or `'data' => 'json'` |

### Full example

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMatrixTable;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField;
use Filament\Forms\Components\Component;

FlexMatrixTable::make('car_config')
    ->label('Car Configuration')
    ->rows([
        'engine_power' => ['label' => 'Engine Power', 'suffix' => 'HP'],
        'top_speed' => ['label' => 'Top Speed', 'suffix' => 'km/h'],
        'acceleration' => ['label' => '0-100 km/h', 'suffix' => 's'],
    ])
    ->columnWidths([
        'importance' => '1.5fr',
        'value' => '1fr',
        'enabled' => 'max-content',
    ])
    ->schema([
        SelectField::make('importance')
            ->label('Importance')
            ->options([
                'high' => 'High',
                'medium' => 'Medium',
                'low' => 'Low',
            ])
            ->size('sm')
            ->inlineFieldLabel(false)
            ->hiddenLabel(),

        FlexTextInput::make('value')
            ->label('Value')
            ->numeric()
            ->size('sm')
            ->hiddenLabel()
            ->suffix(function (Component $component) {
                // Read row definition to assign dynamic suffixes per row
                $statePathParts = explode('.', $component->getStatePath());
                $rowKey = $statePathParts[count($statePathParts) - 2] ?? null;
                
                $matrix = $component->getContainer()->getParentComponent();
                $rows = $matrix->getNormalizedRows();
                
                return $rows[$rowKey]['suffix'] ?? null;
            }),

        SwitchField::make('enabled')
            ->label('Enabled')
            ->size('sm')
            ->inline()
            ->hiddenLabel(),
    ]);
```

### Configuration API

#### `rows(array|Closure $rows)`

Defines the vertical rows shown in the table. Each key maps to a state entry.

#### `schema(array|Closure $schema)`

Defines the horizontal columns of the table as full-fledged Filament components.

#### `columnWidths(array|Closure $widths)`

Dynamic CSS grid template control. Pass an associative array matching column names to their CSS width value (e.g., `1fr`, `max-content`). 
This allows elements like small switches to snap to the right perfectly instead of stretching:

```php
->columnWidths([
    'priority' => '1.5fr',
    'story_points' => '1fr',
    'enabled' => 'max-content',
])
```

#### `size('sm'|'md'|'lg')`

Controls the overall scale for row labels and matrix borders. Default: `md`. Inherits CSS from `MatrixChoiceField`.

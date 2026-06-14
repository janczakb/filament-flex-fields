# DualListboxField

[← Back to Table of Contents](index.md)


### Summary

Two-panel **multi-select** listbox with search, transfer buttons, drag reorder, and double-click to move items.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\DualListboxField` |
| **State type** | `list<string>` — ordered selected option keys |
| **Model cast** | `'permissions' => 'array'` or `'json'` |
| **FieldType** | `dual_listbox` |

Example state: `['read', 'write', 'delete']`.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\DualListboxField;

DualListboxField::make('permissions')
    ->label('Permissions')
    ->options([
        'read' => ['label' => 'Read', 'description' => 'View records'],
        'write' => 'Write',
        'delete' => 'Delete',
    ])
    ->minItems(1)
    ->maxItems(5)
    ->searchable()
    ->reorderable()
    ->listHeight('16rem')
    ->variant('bordered');
```

### Validation

| Rule / method | Description |
|---------------|-------------|
| `array` | Built-in — state must be an array |
| Option keys | Unknown keys fail with `dual_listbox.invalid_option` |
| `minItems(n)` | Minimum selected items |
| `maxItems(n)` | Maximum selected items |
| `exactItems(n)` | Exactly `n` items (sets both min and max) |

Translation keys: `filament-flex-fields::default.validation.dual_listbox.*`

### Configuration API

#### `icons(array|Closure $icons)`


Overrides the default icons for all action buttons. Expects an associative array matching action keys to icon strings.

```php
DualListboxField::make('permissions')
    ->icons([
        'search' => 'heroicon-o-magnifying-glass',
        'moveRight' => 'heroicon-o-chevron-right',
        'moveLeft' => 'heroicon-o-chevron-left',
        'moveAllRight' => 'heroicon-o-chevron-double-right',
        'moveAllLeft' => 'heroicon-o-chevron-double-left',
        'moveUp' => 'heroicon-o-chevron-up',
        'moveDown' => 'heroicon-o-chevron-down',
        'swap' => 'heroicon-o-arrows-right-left',
    ]);
```

#### Custom action icon overrides


Configure specific action icons individually using fluent methods:

```php
DualListboxField::make('permissions')
    ->searchIcon('heroicon-o-magnifying-glass')
    ->moveRightIcon('heroicon-o-chevron-right')
    ->moveLeftIcon('heroicon-o-chevron-left')
    ->moveAllRightIcon('heroicon-o-chevron-double-right')
    ->moveAllLeftIcon('heroicon-o-chevron-double-left')
    ->moveUpIcon('heroicon-o-arrow-up')
    ->moveDownIcon('heroicon-o-arrow-down')
    ->swapIcon('heroicon-o-arrows-right-left');
```
### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `list_height` | `listHeight()` |
| `searchable` | `searchable()` |
| `reorderable` | `reorderable()` |
| `move_on_double_click` | `moveOnDoubleClick()` |
| `show_transfer_buttons` | `showTransferButtons()` |
| `available_label` | `availableLabel()` |
| `selected_label` | `selectedLabel()` |
| `disabled_options` | `disabledOptions()` |
| `min_items` | `minItems()` |
| `max_items` | `maxItems()` |
| `exact_items` | `exactItems()` |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `normalizeState(array $state)` | `list<string>` | Filters state to allowed, non-disabled option keys; preserves order and deduplicates. |
| `getNormalizedOptions()` | `array<string, array{label, description, disabled}>` | Flattened option map from simple strings or rich arrays plus `disabledOptions()`. |
| `getOptionsForJs()` | `list<array{value, label, description, disabled}>` | Option list shape passed to the Alpine/JS layer. |

---

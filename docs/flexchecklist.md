# FlexChecklist

[← Back to Table of Contents](index.md)


### Summary

SaaS-style **multi-select** checklist. Same row layout as [FlexRadiolist](flexradiolist.md) but stores an array of selected keys.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexChecklist` |
| **State type** | `list&lt;string\|int&gt;` — selected option keys |
| **FieldType** | `flex_checklist` |
| **State cast** | `OptionsArrayStateCast` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexChecklist;

FlexChecklist::make('features')
    ->label('Included features')
    ->options([
        'wifi' => 'Wi‑Fi',
        'parking' => [
            'label' => 'Parking',
            'description' => 'On-site parking included',
            'icon' => 'heroicon-o-truck',
        ],
    ])
    ->minSelections(1)
    ->maxSelections(3)
    ->color('primary');

FlexChecklist::make('permissions')
    ->options(['read' => 'Read', 'write' => 'Write', 'admin' => 'Admin'])
    ->exactSelections(2)
    ->disabledOptions(['admin']);
```

### State format

Array of unique string keys. Default: `[]`. Duplicate values are deduplicated on validation.

### Validation

| Rule | Detail |
|------|--------|
| `array` | State must be an array |
| Option keys | Each value must exist in `options()` |
| `exactSelections(n)` | Exactly `n` items selected |
| `minSelections(n)` | At least `n` items |
| `maxSelections(n)` | At most `n` items |
| `required()` | Implies `minSelections(1)` when no explicit min |

### Configuration API

#### `options(array|Closure $options)`


`key =&gt; label` or rich array (`label`, `description`, `desc`, `icon`, `disabled`). From `HasChecklistOptions`.

```php
FlexChecklist::make('field_name')
    ->options([
        'option_1' => 'Option 1',
        'option_2' => 'Option 2',
    ]);
```
#### `icons(array|Closure $icons)`


Per-key icon map merged into options.

```php
FlexChecklist::make('field_name')
    ->icons([
        'option_1' => 'heroicon-o-star',
        'option_2' => 'heroicon-o-heart',
    ]);
```
#### `descriptions(array|Closure $descriptions)`


Per-key description map. Config key `desc` also supported.

```php
FlexChecklist::make('field_name')
    ->descriptions(['value1', 'value2']);
```
#### `disabledOptions(array|Closure $keys)`


Keys rendered locked with lock icon.

```php
FlexChecklist::make('field_name')
    ->disabledOptions(['option_2']);
```
#### `size(string|ControlSize|Closure $size)`


`sm`, `md` (default), `lg`.

```php
FlexChecklist::make('field_name')
    ->size('md');
```
#### `color(string|Closure|null $color)`


Filament color for selected rows. Default: `primary`.

```php
FlexChecklist::make('field_name')
    ->color('primary');
```
#### `minSelections(int|Closure|null $count)`


Minimum selections. `null` = no minimum (unless `required()`).

```php
FlexChecklist::make('field_name')
    ->minSelections(10);
```
#### `maxSelections(int|Closure|null $count)`


Maximum selections.

```php
FlexChecklist::make('field_name')
    ->maxSelections(10);
```
#### `exactSelections(int|Closure|null $count)`


Exact count; overrides min/max semantics when set.

```php
FlexChecklist::make('field_name')
    ->exactSelections(10);
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getColor()` | `string\|null` | Accent color |
| `getLockIcon()` | `string\|BackedEnum\|Htmlable` | Lock icon for disabled rows |
| `getMinSelections()` | `int\|null` | Min count |
| `getMaxSelections()` | `int\|null` | Max count |
| `getExactSelections()` | `int\|null` | Exact count |
| `getOptionKeys()` | `list` | Valid keys |
| `getNormalizedOptions()` | `array` | Merged option metadata |
| `getDisabledOptions()` | `array` | Disabled keys |
| `isOptionDisabled(string\|int $key)` | `bool` | Row disabled |
| `getChecklistSizeStyles()` | `array` | CSS custom properties |
| `getWrapperClasses()` | `list&lt;string&gt;` | `fff-flex-checklist` |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `icons` | `icons()` |
| `descriptions` / `desc` | `descriptions()` |
| `disabled_options` | `disabledOptions()` |
| `size` | `size()` |
| `color` | `color()` |
| `min_selections` | `minSelections()` |
| `max_selections` | `maxSelections()` |
| `exact_selections` | `exactSelections()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-flex-checklist` | Root wrapper |
| `fff-flex-checklist--{sm\|md\|lg}` | Size modifier |
| `fff-flex-checklist__row` | Option row |
| `fff-flex-checklist__indicator` | Checkbox indicator |

### Implementation notes

- Pair with `live()` + `afterStateUpdated()` for autosave patterns (same as FlexRadiolist).
- Locked options show `getLockIcon()` and cannot be toggled.

---

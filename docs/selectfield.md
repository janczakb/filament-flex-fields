# SelectField

[← Powrót do spisu treści](../README.md)


### Summary

Styled Filament **Select** with pill trigger, rich option rows, grid layout, and multi-select chips. Extends Filament `Select`.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField` |
| **Extends** | `Filament\Forms\Components\Select` |
| **State type** | `string\|int\|null` (single) · `array` (multiple) |
| **FieldType** | `select`, `multi_select` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;

SelectField::make('framework')
    ->label('Framework')
    ->options([
        'laravel' => [
            'label' => 'Laravel',
            'description' => 'PHP web framework',
            'icon' => 'heroicon-o-bolt',
        ],
        'livewire' => 'Livewire',
    ])
    ->searchable()
    ->variant('bordered')
    ->size('md');

SelectField::make('tags')
    ->multiple()
    ->options(['draft' => 'Draft', 'published' => 'Published'])
    ->chipColor('primary');
```

### Custom configuration API

#### `clearable(bool|Closure $condition = true)`


Renders a clear button (×) allowing the user to reset the select value to null.

```php
SelectField::make('category_id')
    ->clearable();
```

#### `canSelectPlaceholder(bool|Closure $condition = true)`


Enables the placeholder option to be selected as an active choice inside the dropdown list.

```php
SelectField::make('category_id')
    ->canSelectPlaceholder();
```

#### `dropdownAlign(string|Closure $align)`


Aligns the dropdown overlay container relative to the field input trigger (`left`, `right`, or `center`).

```php
SelectField::make('category_id')
    ->dropdownAlign('right');
```

#### `inlineSearch(bool|Closure $condition = true)`


Renders the search input box inline directly inside the select dropdown panel instead of as a separate search overlay.

```php
SelectField::make('category_id')
    ->searchable()
    ->inlineSearch();
```

#### `transformRichOptionsForJs(array $options)`


Pre-formats rich option arrays containing avatars, labels, and badges into JS-compatible structures for the frontend component.

```php
SelectField::make('category_id')
    ->transformRichOptionsForJs([
        'laravel' => ['label' => 'Laravel', 'icon' => 'heroicon-o-bolt']
    ]);
```
### Inherited Select API

All Filament `Select` methods work, including:

| Method | Description |
|--------|-------------|
| `options()` | Static or dynamic option list |
| `searchable()` | Client-side or server-side search |
| `multiple()` | Multi-select with chips |
| `preload()` / `optionsLimit()` | Async option loading |
| `relationship()` | Eloquent relationship binding |
| `native()` | Native HTML select (overrides custom UI when `true`) |
| `allowHtml()` | Allow HTML in option labels |

Rich option shape: see [Rich select option shape](shared-concepts.md).

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `color` | `color()` |
| `chip_color` | `chipColor()` |
| `rich_options` | `richOptions()` |
| `option_layout` | `optionLayout()` |
| `searchable` | `searchable()` |
| `native` | `native()` |
| `inline_field_label` | `inlineFieldLabel()` |

---

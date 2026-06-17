# TagsField

[← Back to Table of Contents](index.md)


### Summary

Tag input with pill chips below the field, inline remove buttons, optional suggestions, and duplicate-insensitive matching.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField` |
| **State type** | `list&lt;string&gt;` |
| **FieldType** | `tags` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;

TagsField::make('skills')
    ->label('Skills')
    ->placeholder('Add a skill and press Tab')
    ->splitKeys(['Tab', 'Enter'])
    ->maxTags(10)
    ->suggestions(['PHP', 'Laravel', 'Filament'])
    ->required();

TagsField::make('labels')
    ->variant('secondary')
    ->size('sm')
    ->suggestionsOnly()
    ->duplicateInsensitive();
```

### State format

| Value | Description |
|-------|-------------|
| Tags | Ordered list of trimmed strings |
| Empty | `[]` when cleared |

### Validation

| Behaviour | Detail |
|-----------|--------|
| `required()` | At least one tag must be present |
| `maxTags()` | Caps the number of tags |
| `suggestionsOnly()` | Only values from `suggestions()` are accepted |
| `duplicateInsensitive()` | Compares tags case-insensitively when checking duplicates |

### Configuration API

#### `variant(string|Closure $variant)`

Visual style shared with FlexTextInput. Values: `primary` (default), `secondary`, `flat`, `soft`.

```php
TagsField::make('field_name')
    ->variant('primary');
```

#### `size(string|ControlSize|Closure $size)`

Control height. See [Control size](shared-concepts.md). Default: `md`.

```php
TagsField::make('field_name')
    ->size('md');
```

#### `maxTags(int|Closure|null $max)`

Maximum number of tags. `null` = unlimited.

```php
TagsField::make('field_name')
    ->maxTags(5);
```

#### `suggestions(array|Closure $suggestions)`

Preset values shown in the suggestion dropdown.

```php
TagsField::make('field_name')
    ->suggestions(['PHP', 'JavaScript']);
```

#### `suggestionsOnly(bool|Closure $condition = true)`

Restrict input to suggestion values only.

```php
TagsField::make('field_name')
    ->suggestionsOnly();
```

#### `duplicateInsensitive(bool|Closure $condition = true)`

Treat tags that differ only by case as duplicates.

```php
TagsField::make('field_name')
    ->duplicateInsensitive();
```

#### `showTagCount(bool|Closure $condition = true)`

Show a live tag count below the field.

```php
TagsField::make('field_name')
    ->showTagCount();
```

#### `searchSuggestions(bool|Closure $condition = true)`

Enable server-side suggestion search via `getSearchResultsForJs()` (used by `UserSelect` patterns).

```php
TagsField::make('field_name')
    ->searchSuggestions()
    ->minSearchLength(2);
```

### Spatie Tags integration

Use `FlexSpatieTagsField` when the model uses `spatie/laravel-tags`:

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieTagsField;

FlexSpatieTagsField::make('tags')
    ->type('skills');
```

Set `use_spatie_tags` in flex-field schema config to wire through `FlexFieldFormBuilder`.

### Assets & Livewire

- Uses `wire:ignore` on the Alpine root — state syncs through `$entangle`.
- Lazy-loads `tags-field.css` + `tag-chips.css` + `flex-text-input.css`.
- Alpine entry: `tags-field.js` via `x-load`.

### Playground

Preview: `/admin/flex-fields-playground/tags-field` (when playground is enabled).

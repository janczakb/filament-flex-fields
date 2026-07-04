---
title: "TagsField"
description: Tag input with pill chips, inline remove buttons, and optional server-side suggestions.
---

[← Back to Table of Contents](/docs/index)

### Summary

Tag input featuring removable pill chips, optional static or server-side suggestions, and duplicate-insensitive matching. Extends Filament's standard **TagsInput** with FlexTextInput styling, size/variant tokens, and optional Spatie Tags integration.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField` |
| **Spatie class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieTagsField` |
| **State type** | `list<string>` (array) or `string` (delimited) |
| **FieldType** | `tags` |
| **Playground** | `tags-field` slug in Flex Fields playground |

---

### Basic usage

#### Standard Skills Input
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;

TagsField::make('skills')
    ->label('Skills')
    ->suggestions(['PHP', 'Laravel', 'Filament', 'Livewire'])
    ->maxTags(10)
    ->required();
```

#### Comma-Separated Labels
```php
TagsField::make('labels')
    ->separator(',')
    ->placeholder('Add labels...')
    ->variant('secondary')
    ->size('sm');
```

---

### State & validation

#### Stored value
By default, state is an ordered list of trimmed strings (JSON array). If `separator()` is set, it dehydrates as a delimited string.

```php
$record->skills; // ['laravel', 'filament']
$record->labels; // 'php,pest,livewire' (with separator)
```

#### Validation rules (built-in)
| Behaviour | Detail |
|-----------|--------|
| `required()` | At least one tag must be present |
| `maxTags()` | Caps the total number of tags |
| `suggestionsOnly()` | Restricts input to suggestion values only |
| `duplicateInsensitive()` | Case-insensitive duplicate check |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `variant(string $variant)` | Setup | `primary` | `primary`, `secondary`, `flat`, `soft` |
| `size(string $size)` | Setup | `'md'` | `sm`, `md`, `lg` |
| `maxTags(int $max)` | Setup | `null` | Maximum number of tags |
| `suggestionsOnly(bool $cond)` | Setup | `false` | Restrict to suggestions only |
| `duplicateInsensitive(bool $c)` | Setup | `false` | Case-insensitive duplicates |
| `showTagCount(bool $cond)` | Setup | `false` | Show live counter below field |
| `getSearchResultsUsing(Closure $cb)`| Setup | — | Async suggestion search |
| `minSearchLength(int $len)` | Setup | `2` | Min chars for server search |
| `type(string $type)` | Spatie | — | Spatie tag type (Spatie class only) |

---

### Real-world examples

#### Server-Side Suggestion Search
```php
TagsField::make('technologies')
    ->getSearchResultsUsing(function (string $search): array {
        return Technology::query()
            ->where('name', 'like', "%{$search}%")
            ->limit(20)
            ->pluck('name')
            ->all();
    });
```

#### Spatie Tags Integration
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieTagsField;

FlexSpatieTagsField::make('tags')
    ->type('categories')
    ->label('Categories');
```

---

### Playground

`/admin/flex-fields-playground/tags-field`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [SelectField](/docs/selectfield) | Multi-select with fixed options |
| [FlexChecklist](/docs/flexchecklist) | Grid of checkbox choices |
| [UserSelect](/docs/userselect) | Specifically for selecting users |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-tags-field` | Root field wrapper |
| `fff-tags-field--{sm\|md\|lg}` | Size variant |
| `fff-tags-field--{variant}` | Visual variant |
| `fff-flex-text-input` | Shared input shell |
| `fi-color-{color}` | Pill color modifier |
| `has-focus-outline` | Focus ring active |

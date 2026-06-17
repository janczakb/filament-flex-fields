---
title: "TagsField"
---

[← Back to Table of Contents](/docs/index)


### Summary

Tag input with pill chips below the field, inline remove buttons, optional static or server-side suggestions, and duplicate-insensitive matching. Extends Filament `TagsInput` with FlexTextInput styling, size/variant tokens, and optional Spatie Tags relationship integration via `FlexSpatieTagsField`.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField` |
| **Spatie class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieTagsField` |
| **Extends** | `Filament\Forms\Components\TagsInput` |
| **State type** | `list<string>` — or `string` when `separator()` is set |
| **Model cast** | `'skills' => 'array'` (JSON) or `'labels' => 'string'` (comma-separated) |
| **FieldType** | `tags` |
| **Playground** | `tags-field` slug in Flex Fields playground |

---

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

Filament resource example:

```php
use Filament\Forms\Form;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;

public static function form(Form $form): Form
{
    return $form->schema([
        TagsField::make('skills')
            ->label('Skills')
            ->suggestions(['PHP', 'JavaScript', 'Laravel'])
            ->maxTags(8)
            ->columnSpanFull(),
    ]);
}
```

---

### State format

| Value | Description | Example |
|-------|-------------|---------|
| Tags (default) | Ordered list of trimmed strings | `['laravel', 'filament']` |
| Comma-separated | When `separator(',')` is set | `'php,pest,livewire'` |
| Empty | `[]` or `null` when cleared | `[]` |

Whitespace is trimmed per tag. With `duplicateInsensitive()`, comparison is case-insensitive but stored values keep the user's casing.

---

### Validation

| Behaviour | Detail |
|-----------|--------|
| `required()` | At least one tag must be present |
| `maxTags()` | Caps the number of tags client- and server-side |
| `suggestionsOnly()` | Only values from suggestions (static or server search) are accepted |
| `duplicateInsensitive()` | Treats tags that differ only by case as duplicates |
| Inherited `TagsInput` | Standard Filament `rule()`, `rules()`, `validationMessages()` |

Custom validation messages:

```php
TagsField::make('skills')
    ->required()
    ->maxTags(5)
    ->validationMessages([
        'required' => 'Add at least one skill.',
    ]);
```

---

### Configuration API — TagsField

Each fluent method accepts a `Closure` for dynamic configuration.

#### `variant(string|Closure $variant)`

Visual style shared with FlexTextInput. Values: `primary` (default), `secondary`, `flat`, `soft`.

```php
TagsField::make('skills')->variant('soft');
```

#### `size(string|ControlSize|Closure $size)`

Control height. See [Control size](/docs/shared-concepts). Default: `md`.

```php
TagsField::make('skills')->size('sm');
```

#### `maxTags(int|Closure|null $max)`

Maximum number of tags. `null` = unlimited.

```php
TagsField::make('skills')->maxTags(5);
```

#### `suggestionsOnly(bool|Closure $condition = true)`

Restrict input to suggestion values only (static `suggestions()` or server search results).

```php
TagsField::make('category')
    ->suggestions(['News', 'Blog', 'Docs'])
    ->suggestionsOnly();
```

#### `duplicateInsensitive(bool|Closure $condition = true)`

Treat tags that differ only by case as duplicates.

```php
TagsField::make('labels')->duplicateInsensitive();
```

#### `showTagCount(bool|Closure $condition = true)`

Show a live tag count below the field (e.g. `2 / 5 tags`).

```php
TagsField::make('skills')
    ->maxTags(5)
    ->showTagCount();
```

#### `focusOutline(bool|Closure $condition = true)`

Inherited from `HasFieldFocusOutline`. Default: **`false`**. When `true`, shows the shared `--fff-field-focus-*` ring on the input shell.

```php
TagsField::make('skills')->focusOutline();
```

---

### Configuration API — inherited TagsInput

`TagsField` inherits the full Filament `TagsInput` API. Common options:

| Method | Default | Description |
|--------|---------|-------------|
| `suggestions(array\|Closure $suggestions)` | `[]` | Static suggestion list for the dropdown |
| `splitKeys(array\|Closure $keys)` | `['Tab']` | Keys that commit the current input as a tag |
| `separator(string\|Closure\|null $separator)` | `null` | Dehydrate as delimited string instead of JSON array |
| `tagPrefix(string\|Closure\|null $prefix)` | `null` | Display-only prefix on each pill |
| `tagSuffix(string\|Closure\|null $suffix)` | `null` | Display-only suffix on each pill |
| `reorderable(bool\|Closure $condition = true)` | `false` | Drag-and-drop tag reordering |
| `nested(bool\|Closure $condition = true)` | `false` | Allow nested tag paths (e.g. `foo/bar`) |
| `placeholder(string\|Closure\|null $placeholder)` | — | Input placeholder |
| `trim(bool\|Closure $condition = true)` | — | Trim whitespace from tags |
| `color(string\|Closure\|null $color)` | `primary` | Filament color token for pills |
| `disabled(bool\|Closure $condition = true)` | — | Disable input and tag actions |
| `readOnly(bool\|Closure $condition = true)` | — | Read-only display (no new tags) |

```php
TagsField::make('percentages')
    ->tagSuffix('%')
    ->placeholder('Add percentage')
    ->splitKeys(['Tab', 'Enter', ' ']);

TagsField::make('ordered')
    ->reorderable()
    ->splitKeys(['Tab', ' ']);

TagsField::make('keywords')
    ->separator(',')
    ->placeholder('Comma-separated keywords');
```

---

### Server-side suggestion search

Enable async suggestion lookup with `getSearchResultsUsing()`. There is **no** `searchSuggestions()` method — server search is configured exclusively through this callback.

When a callback is registered, static `suggestions()` are **not** sent to the client; the dropdown fetches results via Livewire instead.

#### `getSearchResultsUsing(?Closure $callback)`

Callback signature: `function (TagsField $component, string $search): array`. Return a list of suggestion strings.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;
use Illuminate\Support\Facades\DB;

TagsField::make('technologies')
    ->getSearchResultsUsing(function (TagsField $component, string $search): array {
        return DB::table('technologies')
            ->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
            ->orderBy('name')
            ->limit(20)
            ->pluck('name')
            ->all();
    })
    ->minSearchLength(2);
```

#### `minSearchLength(int|Closure $length)`

Minimum characters before the server search fires. Default: **`2`**.

```php
TagsField::make('technologies')
    ->getSearchResultsUsing(fn ($component, string $search) => /* ... */)
    ->minSearchLength(3);
```

#### Livewire endpoint

The Alpine component calls the exposed Livewire method **`getTagSearchResults(string $search)`**, which delegates to `searchTagSuggestions()`. Do not reference `getSearchResultsForJs()` — that method does not exist on this field.

| Method | Returns | Description |
|--------|---------|-------------|
| `shouldSearchSuggestions()` | `bool` | Whether `getSearchResultsUsing()` is configured |
| `getMinSearchLength()` | `int` | Resolved minimum search length |
| `getSuggestionsForJs()` | `list<string>` | Static suggestions only (empty when server search is on) |
| `searchTagSuggestions(string $search)` | `list<string>` | Server-side search (PHP) |
| `getTagSearchResults(string $search)` | `list<string>` | Livewire `@Renderless` endpoint for Alpine |

---

### Spatie Tags integration (`FlexSpatieTagsField`)

Use when the Eloquent model implements `Spatie\Tags\HasTags`. Requires **`composer require spatie/laravel-tags`**.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieTagsField;

FlexSpatieTagsField::make('tags')
    ->type('skills');
```

#### Model setup

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class Candidate extends Model
{
    use HasTags;

    protected $fillable = ['name'];
}
```

Tags are **not** dehydrated through form state — the field calls `dehydrated(false)` and persists via Eloquent relationships on save.

| Save path | When |
|-----------|------|
| `syncTagsWithType($state, $type)` | `type('skills')` — typed collection |
| `syncTagsWithType($state, null)` | `type('')` with `allowsAnySpatieTagType() === false` — untyped tags only |
| `tags()->sync()` via `findFromStringOfAnyType` | `type(null)` — any tag type |

#### `type(string|Closure|null $type)`

| Value | Behaviour |
|-------|-------------|
| `'skills'` (string) | Load/save only tags of that Spatie type; search filters by `type` |
| `null` | Allow tags of **any** type; creates missing tags on save |

```php
// Typed skills collection
FlexSpatieTagsField::make('skills')
    ->type('skills')
    ->label('Skills');

// Any tag type (default when type omitted)
FlexSpatieTagsField::make('tags')
    ->type(null);

// Untyped tags only (type column IS NULL)
FlexSpatieTagsField::make('labels')
    ->type('');
```

#### Auto suggestion search

`FlexSpatieTagsField` registers `getSearchResultsUsing()` automatically — it queries the Spatie `tags` table (filtered by `type()` when set). Override with your own callback if needed.

#### `getTagClassName()`

Resolves the tag model class from the bound Eloquent model (`$model::getTagClassName()`) or `config('tags.tag_model', Spatie\Tags\Tag::class)`. Throws `RuntimeException` if `spatie/laravel-tags` is not installed.

#### Edit form — loading existing tags

Relationship hydration is automatic when the form is bound to a record:

```php
FlexSpatieTagsField::make('skills')
    ->type('skills')
    ->label('Skills');

// On edit: loads $record->tagsWithType('skills')->pluck('name')
// On save: $record->syncTagsWithType($state, 'skills')
```

#### FlexField schema

Set `use_spatie_tags: true` and `spatie_tag_type` in flex-field schema config to wire through `FlexFieldFormBuilder`:

```php
new FlexFieldDefinition(
    slug: 'skills',
    label: 'Skills',
    type: FieldType::Tags,
    config: [
        'use_spatie_tags' => true,
        'spatie_tag_type' => 'skills',
        'max_tags' => 10,
        'suggestions_only' => false,
    ],
);
```

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `variant` | `variant()` |
| `separator` | `separator()` |
| `split_keys` | `splitKeys()` |
| `suggestions` | `suggestions()` |
| `tag_prefix` / `tag_suffix` | `tagPrefix()` / `tagSuffix()` |
| `reorderable` | `reorderable()` |
| `color` | `color()` |
| `trim` | `trim()` |
| `max_tags` | `maxTags()` |
| `suggestions_only` | `suggestionsOnly()` |
| `duplicate_insensitive` | `duplicateInsensitive()` |
| `show_tag_count` | `showTagCount()` |
| `spatie_tag_type` | `type()` (FlexSpatieTagsField only) |

---

### Model & persistence

#### JSON array column (default)

```php
// Migration
$table->json('skills')->nullable();

// Model
protected $casts = [
    'skills' => 'array',
];

protected $fillable = ['skills'];
```

```php
TagsField::make('skills')
    ->default(fn (?Article $record): array => $record?->skills ?? []);
```

#### Comma-separated string column

```php
// Migration
$table->string('labels')->nullable();

// Model — no array cast
protected $fillable = ['labels'];
```

```php
TagsField::make('labels')
    ->separator(',')
    ->default(fn (?Article $record): ?string => $record?->labels);
```

---

### Recipes

#### Taxonomy — suggestions-only with static list

```php
TagsField::make('content_type')
    ->label('Content type')
    ->suggestions(['Article', 'Tutorial', 'News', 'Release'])
    ->suggestionsOnly()
    ->maxTags(1)
    ->required();
```

#### Skills cap — maxTags + duplicateInsensitive + counter

```php
TagsField::make('skills')
    ->label('Skills')
    ->maxTags(8)
    ->duplicateInsensitive()
    ->showTagCount()
    ->suggestions(['PHP', 'Laravel', 'Livewire', 'Filament', 'JavaScript']);
```

#### Server search — custom lookup table

```php
TagsField::make('frameworks')
    ->label('Frameworks')
    ->getSearchResultsUsing(function (TagsField $component, string $search): array {
        return Framework::query()
            ->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
            ->orderBy('name')
            ->limit(15)
            ->pluck('name')
            ->all();
    })
    ->minSearchLength(2)
    ->placeholder('Search frameworks…');
```

#### Spatie typed tags on a Filament Resource

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieTagsField;

FlexSpatieTagsField::make('skills')
    ->type('skills')
    ->label('Skills')
    ->maxTags(12)
    ->helperText('Synced to the Spatie tags relationship on save.');
```

#### Flex-field schema — comma-separated labels

```php
new FlexFieldDefinition(
    slug: 'labels',
    label: 'Labels',
    type: FieldType::Tags,
    config: [
        'separator' => ',',
        'split_keys' => ['Tab', 'Enter'],
        'max_tags' => 5,
        'show_tag_count' => true,
        'variant' => 'secondary',
    ],
);
```

---

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getVariant()` | `string` | Resolved variant |
| `getSize()` | `string` | Resolved size |
| `getMaxTags()` | `int\|null` | Tag cap |
| `isSuggestionsOnly()` | `bool` | Suggestions-only mode |
| `isDuplicateInsensitive()` | `bool` | Case-insensitive duplicates |
| `shouldShowTagCount()` | `bool` | Tag counter visible |
| `getTagDisplayLabel(string $tag)` | `string` | Tag with prefix/suffix for display |
| `getWrapperClasses()` | `array<string, string>` | CSS class list for wrapper |
| `shouldShowFocusOutline()` | `bool` | Focus ring enabled |

---

### CSS classes

| Class | Role |
|-------|------|
| `fff-tags-field` | Root field wrapper |
| `fff-tags-field--{sm\|md\|lg}` | Size modifier |
| `fff-tags-field--{variant}` | Variant modifier (`primary`, `secondary`, `flat`, `soft`) |
| `fff-flex-text-input` | Shared FlexTextInput shell |
| `fff-flex-text-input--{size}` | Size on input shell |
| `fff-flex-text-input--{variant}` | Variant on input shell |
| `fi-color-{color}` | Filament color token for pills |
| `has-focus-outline` | Focus ring when `focusOutline()` is enabled |

Tag chip styles come from `tag-chips.css` (lazy-loaded).

---

### Assets & Livewire

- Uses `wire:ignore` on the Alpine root — state syncs through `$entangle`.
- Lazy-loads `tags-field.css` + `tag-chips.css` + `flex-text-input.css`.
- Alpine entry: `tags-field.js` via `x-load`.
- Server search debounces input and calls `getTagSearchResults()` on the Livewire component.

---

### Playground

Slug: **`tags-field`**

| Demo field | Shows |
|------------|-------|
| `tags_field__basic` | Default tags with pills |
| `tags_field__suggestions` | Static autocomplete |
| `tags_field__comma` | `separator(',')` storage |
| `tags_field__suffix` | `tagSuffix('%')` display |
| `tags_field__reorderable` | Drag reorder + split keys |
| `tags_field__max` | `maxTags(3)` + `showTagCount()` |
| `tags_field__sm` / `tags_field__lg` | Size variants |
| `tags_field__secondary` / `tags_field__soft` | Variant tokens |

Preview: `/admin/flex-fields-playground/tags-field` (when playground is enabled).

---

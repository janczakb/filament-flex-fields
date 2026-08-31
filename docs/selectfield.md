---
title: "SelectField"
description: Styled Filament Select with pill trigger, rich option rows, grid layout, and multi-select chips.
---

[← Back to Table of Contents](/docs/index)

### Summary

Styled Filament **Select** with pill trigger, rich option rows, grid layout, and multi-select chips. Extends Filament `Select` — **all native Select APIs remain available**.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField` |
| **Extends** | `Filament\Forms\Components\Select` |
| **State type** | `string\|int\|null` (single) · `array` (multiple) |
| **Model cast** | `'category_id' => 'integer'` · `'tags' => 'json'` (multiple) |
| **FieldType** | `select` (use `multiple` in config for multi-select) |
| **Playground** | `select-field` slug in Flex Fields playground |

Works with all standard Filament field APIs: `required()`, `disabled()`, `hidden()`, `live()`, `afterStateUpdated()`, validation rules, etc.

---

### Basic usage

#### Standard select with rich options

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;

SelectField::make('framework')
    ->label('Framework')
    ->options([
        'laravel' => [
            'label' => 'Laravel',
            'description' => 'The PHP Framework for Web Artisans',
            'icon' => 'heroicon-o-bolt',
            'badge' => 'v11',
        ],
        'livewire' => [
            'label' => 'Livewire',
            'description' => 'Full-stack framework for Laravel',
            'icon' => 'heroicon-o-sparkles',
        ],
    ])
    ->searchable()
    ->variant('bordered')
    ->size('md');
```

#### Multi-select with chips

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;

SelectField::make('tags')
    ->multiple()
    ->options([
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ])
    ->chipColor('primary')
    ->required();
```

By default, multi-select **removes** chosen values from the dropdown list (pick-from-remaining / chip mode).

#### Email recipients (two-line options + chip labels)

Use `chipLabel` (or `chip_label`) on rich options when the dropdown should show name + email, but selected chips should show only the email:

```php
SelectField::make('recipients')
    ->multiple()
    ->searchable()
    ->keepSelectedOptionsInDropdown()
    ->richOptions()
    ->options([
        'jane' => [
            'label' => 'Jane Cooper',
            'description' => 'jane.cooper@example.com',
            'chipLabel' => 'jane.cooper@example.com',
        ],
        'john' => [
            'label' => 'John Smith',
            'description' => 'john.smith@example.com',
            'chipLabel' => 'john.smith@example.com',
        ],
    ]);
```

The dropdown row renders the full two-line layout; chips and `triggerLabel` use the compact `chipLabel` text.

#### Custom value (`optionView()`)

Use `optionView()` when you need full control over option HTML — custom Blade per row and trigger (server-side equivalent of render props). Pair with `optionTriggerView()` when the closed trigger should differ from the dropdown row.

```php
SelectField::make('user_id')
    ->label('User')
    ->searchable()
    ->options([
        'fred' => [
            'label' => 'Fred',
            'description' => 'fred@example.com',
        ],
        'bob' => [
            'label' => 'Bob',
            'description' => 'bob@example.com',
        ],
    ])
    ->optionView('my-app.select.user-option')
    ->optionTriggerView('my-app.select.user-trigger'); // optional
```

Each view receives `$option`, `$layout` (`list`, `trigger`, `grid`, `chip`), `$field`, `$value`, `$label`, `$description`, and any data from `optionViewData()`.

Use `richListTriggerDisplay()` to keep the full list row (avatar + name + email) in the closed trigger instead of the compact trigger layout.

```php
SelectField::make('assignee_id')
    ->optionView('my-app.select.user-option')
    ->richListTriggerDisplay();
```

Closure form — return HTML, `Htmlable`, `View`, or a nested view name:

```php
->optionView(fn (array $option, string $layout): string => view('my-app.select.user-option', [
    'option' => $option,
    'layout' => $layout,
])->render())
```

`optionView()` automatically enables `allowHtml()` and sanitizes output through the package HTML sanitizer.

#### Multi-select checklist (keep options visible)

Use `keepSelectedOptionsInDropdown()` when selected options should stay in the list with checkmarks (dropdown stays open while toggling):

```php
SelectField::make('states')
    ->multiple()
    ->searchable()
    ->keepSelectedOptionsInDropdown()
    ->options([
        'california' => 'California',
        'texas' => 'Texas',
        'delaware' => 'Delaware',
    ]);
```

---

### State & validation

#### Stored value

State is the **option key** from `options()`.

```php
// Single select
$record->category_id; // int|string|null — e.g. 1

// Multi-select
$record->tags; // array — e.g. ['draft', 'published']
```

#### Default state

The field defaults to **`null`** (single) or **`[]`** (multiple).

```php
SelectField::make('category_id')
    ->default(1);
```

#### Validation rules

| Rule | When |
|------|------|
| `nullable` | Always (unless `required()`) |
| `Rule::in(...)` | Value must match a configured option key |
| `required` | When `->required()` |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `variant(string\|Closure $variant)` | Setup | `'bordered'` | Visual style: `bordered`, `secondary`, `flat`, `faded`, `soft`, `underlined`, `item-card` |
| `color(string\|Closure\|null $color)` | Setup | `null` | Accent color for the field |
| `chipColor(string\|Closure $chipColor)` | Setup | `'neutral'` | Color for multi-select chips |
| `keepSelectedOptionsInDropdown(bool\|Closure $condition = true)` | Setup | `false` | Multi-select: keep selected options in the dropdown with checkmarks (checklist) instead of removing them |
| `richOptions(bool\|Closure $condition = true)` | Setup | auto | Force rich option rendering |
| `optionView(string\|Closure $view)` | Setup | — | Custom Blade (or Closure) per option row and trigger |
| `optionTriggerView(string\|Closure\|null $view)` | Setup | — | Optional separate view for closed trigger / chips |
| `optionViewData(array\|Closure $data)` | Setup | `[]` | Extra data passed into every option view |
| `richListTriggerDisplay(bool\|Closure $condition = true)` | Setup | `false` | Keep full list HTML in the closed trigger |
| `optionLayout(string\|Closure $layout)` | Setup | `'list'` | Dropdown layout: `list`, `grid` |
| `inlineFieldLabel(bool\|Closure $condition = true)` | Setup | `false` | Render label inside the field track |
| `inlineSearch(bool\|Closure $condition = true)` | Setup | `false` | Render search input inside the trigger (dropdown header search hidden) |
| `recentOptions(array\|Closure $values)` | Setup | `[]` | Pin recent option keys at the top of the dropdown (smart suggest) |
| `suggestedOptions(array\|Closure $values)` | Setup | `[]` | Pin suggested option keys below recent (smart suggest) |
| `allowCreateOption(bool\|Closure $condition = true)` | Setup | `false` | Show inline “Create …” row when search has no exact match |
| `entityMentions(bool\|Closure $condition = true, string\|Closure $trigger = '@')` | Setup | `false` | Async people/entity picker; type the trigger in search or on the closed trigger |
| `clearable(bool\|Closure $condition = true)` | Setup | auto | Show clear button (×) |
| `dropdownAlign(string\|Closure $align)` | Setup | auto | Align dropdown: `start`, `end` |
| `size(string\|ControlSize\|Closure $size)` | Setup | `'md'` | Control size: `sm`, `md`, `lg` |
| `rounding(string\|Closure\|null $rounding)` | Setup | config | Border radius token |

#### `variant()`

```php
SelectField::make('category_id')->variant('soft');
SelectField::make('category_id')->variant('underlined');
```

#### `optionLayout()`

Use `grid` for a multi-column visual picker:

```php
SelectField::make('theme')
    ->options([
        'light' => ['label' => 'Light', 'icon' => 'heroicon-o-sun'],
        'dark' => ['label' => 'Dark', 'icon' => 'heroicon-o-moon'],
    ])
    ->optionLayout('grid');
```

#### `inlineSearch()`

Recommended for **single-select** searchable fields to keep the UI compact — the search field is the trigger, not a separate label swap:

```php
SelectField::make('user_id')
    ->searchable()
    ->inlineSearch();
```

When closed, the trigger input shows the selected label. When focused or open, the same input stays editable and keeps that label until you type or clear it; clearing the input clears the selection. Use the default field clear (×) next to the chevron to reset the value — inline mode does not render a separate search-query × (that control exists only in the dropdown search header). Multi-select fields should use dropdown search instead (`searchable()` without `inlineSearch()`).

For RTL layouts, set `extraAttributes(['dir' => 'rtl'])` on the field — the teleported menu mirrors from the trigger, and the inline input inherits the same direction for Arabic/Hebrew labels.

Works with `entityMentions()` — type `@` in the inline search input or press `@` on the closed trigger to start a mention query.

#### Smart suggest (`recentOptions`, `suggestedOptions`, `allowCreateOption`)

Pin curated rows at the top of the dropdown and optionally allow creating a new value from the current search:

```php
SelectField::make('project_id')
    ->searchable()
    ->recentOptions(['inbox', 'drafts'])
    ->suggestedOptions(['archived'])
    ->allowCreateOption();
```

#### Entity mentions (`entityMentions()`)

Async people/entity picker triggered with `@` in the closed trigger, inline search input, or dropdown search:

```php
SelectField::make('assignees')
    ->multiple()
    ->searchable()
    ->inlineSearch()
    ->entityMentions(trigger: '@')
    ->getSearchResultsUsing(fn (string $search): array => User::query()
        ->where('name', 'like', "%{$search}%")
        ->limit(20)
        ->pluck('name', 'id')
        ->all());
```

Selected mention chips render with the `@` prefix and `fff-select-entity-mention-chip` styling. Playground: **Entity mentions** on `/select-field`. See also [Upgrade v2 → v3](/docs/upgrade-v2-to-v3).

---

### Real-world examples

#### User select with avatars

```php
SelectField::make('user_id')
    ->label('Assignee')
    ->options(User::all()->mapWithKeys(fn ($user) => [
        $user->id => [
            'label' => $user->name,
            'description' => $user->email,
            'image' => $user->avatar_url,
        ],
    ]))
    ->searchable()
    ->inlineSearch()
    ->variant('soft');
```

#### Multi-select tags in a Section

```php
Section::make('Metadata')
    ->schema([
        SelectField::make('tags')
            ->multiple()
            ->relationship('tags', 'name')
            ->chipColor('success')
            ->preload(),
    ])
```

---

### Grouped sections

Nest option groups in `options()` to render section headers in the dropdown. Enable explicit dividers between groups (default on):

```php
SelectField::make('country')
    ->searchable()
    ->optionGroupSeparators()
    ->options([
        'North America' => [
            'usa' => 'United States',
            'canada' => 'Canada',
        ],
        'Europe' => [
            'france' => 'France',
            'germany' => 'Germany',
        ],
    ]);
```

Use `optionGroupSeparators(false)` to fall back to header-only dividers.

---

### Disabled options

Disable specific keys while keeping them visible in the list:

```php
SelectField::make('animal')
    ->searchable()
    ->disabledOptions(['cat', 'kangaroo'])
    ->options([
        'dog' => 'Dog',
        'cat' => 'Cat',
        'bird' => 'Bird',
    ]);
```

`disableOptionWhen()` remains available for dynamic rules.

---

### Async search with load more

For large remote datasets, paginate search results and append rows as the user scrolls:

```php
SelectField::make('character')
    ->searchable()
    ->paginatedSearchResults()
    ->searchResultsPageSize(20)
    ->getSearchResultsPageUsing(function (string $search, ?string $cursor, int $pageSize): array {
        [$items, $nextCursor, $hasMore] = CharacterRepository::search($search, $cursor, $pageSize);

        return [
            'items' => $items,
            'cursor' => $nextCursor,
            'hasMore' => $hasMore,
        ];
    });
```

The trigger shows a loading indicator while the first page loads or while a new query is debounced. A footer spinner appears while additional pages load.

Closure-based `options()` still lazy-load on first open (`select__dynamic_options` in the playground).

---

### Playground

`/admin/flex-fields-playground/select-field`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexRadiolist](/docs/flexradiolist) | When all options should be visible at once |
| [ChoiceCards](/docs/choicecards) | Large card-style selection with more detail |
| [DualListboxField](/docs/duallistboxfield) | When managing large sets of selected items |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-select-field` | Root wrapper |
| `fff-select-field--{sm\|md\|lg}` | Size modifier |
| `fff-select-field--{variant}` | Visual variant |
| `fff-select-field--layout-{list\|grid}` | Option layout |
| `fff-select-field--chips-{color}` | Multi-select chip color |
| `fff-select-field--inline-field-label` | Inline label active |
| `fff-select-field--inline-search` | Inline search active |
| `fff-select-entity-mention-chip` | Mention chip styling (multi-select) |
| `fff-select-entity-mention__highlight` | Mention match highlight in dropdown |

---

### Performance

| Mechanism | What it does |
|-----------|--------------|
| **Lazy CSS** | Loads `select-field` styles only when the field renders |
| **JS Transformation** | Efficiently prepares rich options for the frontend component |
| **Search Cache** | Memoizes search results to reduce server round-trips |
| **Virtualized rows** | Flat and grouped lists virtualize from 100 visible rows (~50-row window) |
| **Paginated search** | `paginatedSearchResults()` appends pages via scroll sentinel |

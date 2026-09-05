---
title: "SelectField"
description: Styled Filament Select with pill trigger, rich option rows, grid layout, and multi-select chips.
---

![SelectField mobile bottom sheet](/art/drawer-mobile.webp)

[← Back to Table of Contents](/docs/index)

### Summary

Styled Filament **Select** with pill trigger, rich option rows, grid layout, and multi-select chips. Extends Filament `Select` — **all native Select APIs remain available**. On phones and tablets, searchable menus open as a **bottom sheet** (drag handle, search in the sheet header, checkmark selection) instead of a desktop dropdown.

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
| `Rule::in(...)` | Value must match a configured option key (Filament “valid option” / label check) |
| `required` | When `->required()` |
| `array` + `min:N` | When `->minItems(N)` on a multi-select |
| `array` + `max:N` | When `->maxItems(N)` on a multi-select |

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
| `allowCreateOption(bool\|Closure $condition = true)` | Setup | `false` | Inline “Create …” row from search (sets state to the string; **no** PHP create callback — see [Smart suggest](#smart-suggest-recentoptions-suggestedoptions-allowcreateoption) / [Create option: inline vs modal](#create-option-inline-vs-modal-which-api)) |
| `entityMentions(bool\|Closure $condition = true, string\|Closure $trigger = '@')` | Setup | `false` | Async people/entity picker; type the trigger in search or on the closed trigger |
| `clearable(bool\|Closure $condition = true)` | Setup | auto | Show clear button (×); also updates `selectablePlaceholder()` |
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

When closed, the trigger input shows the selected label. When focused or open on **desktop**, the same input stays editable and keeps that label until you type or clear it; clearing the input clears the selection. On **mobile** (bottom sheet / drawer), search moves into the sheet header — the trigger cannot accept keyboard input while the drawer covers it. Use the default field clear (×) next to the chevron to reset the value — inline mode does not render a separate search-query × on desktop (that control exists in the dropdown/sheet search header). Multi-select fields should use dropdown search instead (`searchable()` without `inlineSearch()`).

For RTL layouts, set `extraAttributes(['dir' => 'rtl'])` on the field — the teleported **panel** and mobile **sheet** (bottom drawer) both copy the trigger writing direction onto the menu (`dir="rtl"`), so search icons, checkmarks (`inset-inline-end`), and option text mirror correctly. Search inputs use `dir="auto"` so Hebrew/Arabic queries get an RTL caret while Latin queries keep a normal LTR caret.

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

Requires `searchable()`. When the query has **no exact label match**, a **Create “…”** row appears at the top of the list (label from `filament-flex-fields::default.select_field.smart_suggest.create`). Choosing that row commits the trimmed search string as the field value — **same string for both value and label**. No modal opens and **no PHP callback runs at click time**.

| | Single (`allowCreateOption()`) | Multiple (`->multiple()->allowCreateOption()`) |
|--|--|--|
| State after create | `string` (the typed text) | `array` of strings (each create adds a chip) |
| Dropdown | Create row when search ≠ any option label | Same; created values become chips |
| Server / DB | Not created automatically | Same |

Persist or insert in the database yourself — typically on form save, or immediately with `live()` + `afterStateUpdated()`:

```php
// Single — state is the new label string (or an existing option key).
SelectField::make('tag')
    ->searchable()
    ->options(fn (): array => Tag::query()->pluck('name', 'name')->all())
    ->allowCreateOption()
    ->live()
    ->afterStateUpdated(function (?string $state): void {
        if (blank($state)) {
            return;
        }

        Tag::query()->firstOrCreate(['name' => $state]);
    });

// Multiple — state is a list of strings; create any missing tags.
SelectField::make('tags')
    ->multiple()
    ->searchable()
    ->options(fn (): array => Tag::query()->pluck('name', 'name')->all())
    ->allowCreateOption()
    ->live()
    ->afterStateUpdated(function (?array $state): void {
        foreach ($state ?? [] as $name) {
            if (filled($name)) {
                Tag::query()->firstOrCreate(['name' => $name]);
            }
        }
    });
```

If you need a **numeric id** in state (e.g. `tag_id`) after insert, create the row in `afterStateUpdated` and `$set` the new key — or prefer the modal path below when the new record needs more than one field.

#### Create option: inline vs modal (which API?)

Two different create flows exist. Do not mix their mental models:

| | Inline smart suggest | Filament modal create |
|--|--|--|
| API | `allowCreateOption()` | `createOptionForm()` + `createOptionUsing()` (+ optional `createOptionAction()`) |
| UI | “Create …” row inside the dropdown | Suffix / manage action opens a form modal |
| Input | Current search string only | Full form schema (name, email, …) |
| On confirm | Sets state to that string (client) | Runs `createOptionUsing` on the server; return value becomes the selected option key |
| Best for | Free-text tags, one-field labels, quick add | Eloquent / relationship records with validation and multiple attributes |

Modal example (also works without `relationship()` when you maintain `options()` yourself):

```php
SelectField::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable()
    ->preload()
    ->createOptionForm([
        TextInput::make('name')->required(),
        TextInput::make('email')->email()->required(),
    ])
    ->createOptionUsing(fn (array $data): int => Author::create($data)->getKey())
    ->createOptionAction(fn (Action $action) => $action->modalWidth('3xl'));
```

Playground: **Smart suggest · create option** on `/select-field` shows inline single + multiple next to the modal form demo.

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

### Filament Select parity

`SelectField` **extends** `Filament\Forms\Components\Select`. Every public Select API from the Filament docs is available with the same method names. The headless Alpine/Livewire UI wires behaviour that Filament’s JS select would otherwise handle.

**Out of scope for this field (separate Filament components):** `MorphToSelect`, `ModalTableSelect`. Use those classes when you need morph type pickers or table-in-modal selection.

#### Coverage matrix (Filament Select docs → SelectField)

| Filament API | Support | Notes |
|--------------|---------|-------|
| `options([...])` / `options(fn)` | Yes | Static payload or `getOptionsForJs` on open (closures deferred until open) |
| `native(false)` | Always on | Headless JS select; `setUp()` forces non-native |
| `searchable()` / `searchable(bool\|Closure)` / `searchable(['col', …])` | Yes | Client filter or relationship SQL columns |
| `getSearchResultsUsing()` + `getOptionLabelUsing()` | Yes | Async search + label hydrate; required for valid-option validation |
| `getOptionLabelsUsing()` (multiple) | Yes | Same as Filament multi-select |
| `loadingMessage()` | Yes | Skeleton / loading empty state |
| `searchingMessage()` | Yes | While debounced search is in flight |
| `searchPrompt()` | Yes | Before the user types a query |
| `noSearchResultsMessage()` | Yes | Including static searchable lists |
| `noOptionsMessage()` | Yes | Empty list / preload with no rows |
| `searchDebounce()` | Yes | Default 1000 ms (same as Filament) |
| `optionsLimit()` | Yes | Caps rendered options (default 50) |
| `multiple()` | Yes | Array state; cast on the model |
| `reorderable()` | Yes | Drag chips when multiple |
| `minItems()` | Yes | PHP `array` + `min:N` validation (same as Filament — no client gate) |
| `maxItems()` / `maxItemsMessage()` | Yes | PHP `max:N` **and** client block + in-menu banner |
| Grouped `options(['Group' => [...]])` | Yes | Flat dropdown rows + group headers |
| `relationship()` | Yes | BelongsTo / BelongsToMany; query restrict wrapper |
| `preload()` | Yes | |
| `ignoreRecord` (relationship arg) | Yes | Inherited |
| `modifyQueryUsing` (relationship arg) | Yes | Inherited |
| `getOptionLabelFromRecordUsing()` | Yes | Inherited |
| `pivotData()` | Yes | Inherited |
| `createOptionForm()` / `createOptionUsing()` | Yes | Filament **modal** create (server callback returns option key). Not the same as `allowCreateOption()` — see [Create option: inline vs modal](#create-option-inline-vs-modal-which-api) |
| `editOptionForm()` / `updateOptionUsing()` | Yes | Same action path |
| `createOptionAction()` / `editOptionAction()` / `manageOptionActions()` | Yes | Inherited action customizers |
| `allowHtml()` | Yes | Sanitized when using package option views |
| `wrapOptionLabels(false)` | Yes | Truncate overflowing labels |
| `selectablePlaceholder(false)` | Yes | Disables clear / null re-select (`isClearableInUi()`) |
| `clearable()` | Yes | Flex Fields alias that also sets `selectablePlaceholder()` |
| `disableOptionWhen()` / `disabledOptions()` | Yes | Filament callback + Flex Fields key list |
| `prefix()` / `suffix()` / `prefixIcon()` / `suffixIcon()` / icon colors | Yes | Filament input wrapper / inline affixes |
| `boolean()` / true/false/placeholder labels | Yes | Inherited |
| `position('top'\|'bottom')` | Yes | Forces menu above/below (auto-flip when unset) |
| `forceSearchCaseInsensitive()` | Yes | Inherited search behaviour |
| `dependsOn()` | Yes | Flex Fields helper; parent must be `->live()`; reopen dropdown after parent change |

#### Basic options & JS select

```php
SelectField::make('status')
    ->options([
        'draft' => 'Draft',
        'reviewing' => 'Reviewing',
        'published' => 'Published',
    ]);
// Always a JavaScript select (native HTML5 select is not used).
```

#### Searching & custom messages

```php
SelectField::make('author_id')
    ->searchable()
    ->getSearchResultsUsing(fn (string $search): array => User::query()
        ->where('name', 'like', "%{$search}%")
        ->limit(50)
        ->pluck('name', 'id')
        ->all())
    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
    ->loadingMessage('Loading authors...')
    ->searchingMessage('Searching authors...')
    ->noSearchResultsMessage('No authors found.')
    ->noOptionsMessage('No authors available.')
    ->searchPrompt('Search authors by name')
    ->searchDebounce(500)
    ->optionsLimit(20);
```

#### Multi-select, reorder, min/max items

```php
SelectField::make('technologies')
    ->multiple()
    ->reorderable()
    ->options([
        'tailwind' => 'Tailwind CSS',
        'alpine' => 'Alpine.js',
        'laravel' => 'Laravel',
        'livewire' => 'Laravel Livewire',
    ])
    ->minItems(1)   // form validation when fewer than 1 selected
    ->maxItems(3)   // validation + blocks further picks in the UI
    ->maxItemsMessage('Remove an item before adding another.');
```

For custom async multi-select labels use `getOptionLabelsUsing()` (plural), same as Filament.

#### Grouped options

```php
SelectField::make('status')
    ->searchable()
    ->options([
        'In Process' => [
            'draft' => 'Draft',
            'reviewing' => 'Reviewing',
        ],
        'Reviewed' => [
            'published' => 'Published',
            'rejected' => 'Rejected',
        ],
    ]);
```

#### Relationship (+ preload, create/edit modals, pivot)

```php
SelectField::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable(['name', 'email'])
    ->preload()
    ->createOptionForm([
        TextInput::make('name')->required(),
        TextInput::make('email')->email()->required(),
    ])
    ->createOptionUsing(fn (array $data): int => Author::create($data)->getKey())
    ->createOptionAction(fn (Action $action) => $action->modalWidth('3xl'))
    ->editOptionForm([
        TextInput::make('name')->required(),
        TextInput::make('email')->email()->required(),
    ])
    ->updateOptionUsing(function (array $data, Schema $schema): void {
        $schema->getRecord()?->update($data);
    });
```

Inline smart-suggest create (`allowCreateOption()`) is documented under [Create option: inline vs modal](#create-option-inline-vs-modal-which-api): one-field create from the search string, no modal, no automatic DB insert. Use `createOptionForm()` / `createOptionUsing()` when the new record needs multiple fields or a server-side primary key.

Call `disabled()` **before** `relationship()` on multi relationship selects (Filament requirement).

#### HTML labels, wrap, placeholder, disabled options, affixes, boolean, position

```php
SelectField::make('technology')
    ->options([
        'tailwind' => '<span class="text-blue-500">Tailwind</span>',
    ])
    ->searchable()
    ->allowHtml()
    ->wrapOptionLabels(false)
    ->selectablePlaceholder(false)
    ->disableOptionWhen(fn (string $value): bool => $value === 'published')
    ->prefix('https://')
    ->suffix('.com')
    ->suffixIcon(Heroicon::GlobeAlt)
    ->suffixIconColor('success')
    ->position('bottom'); // or 'top'

SelectField::make('feedback')
    ->boolean(trueLabel: 'Absolutely!', falseLabel: 'Not at all!', placeholder: 'Make your mind up...');
```

#### Cascading options (`dependsOn`)

```php
SelectField::make('country')
    ->live()
    // Avoid remorphing the whole form (freezes sibling fields for seconds on large pages).
    ->skipRenderAfterStateUpdated()
    ->options([...])
    ->afterStateUpdated(fn (Set $set) => $set('region', null));

SelectField::make('region')
    ->dependsOn('country', fn (?string $country): array => match ($country) {
        'pl' => ['mz' => 'Mazowieckie'],
        default => [],
    })
    ->searchable()
    ->placeholder('Pick a country first');
```

Until the parent has a value, the region list is correctly empty. Options refetch when the dropdown opens after the parent changes.

On large schemas, prefer `skipRenderAfterStateUpdated()` or `partiallyRenderComponentsAfterStateUpdated([...])` on the parent — a full Livewire morph is what makes Region feel “blocked”, not the Region trigger itself.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexRadiolist](/docs/flexradiolist) | When all options should be visible at once |
| [ChoiceCards](/docs/choicecards) | Large card-style selection with more detail |
| [DualListboxField](/docs/duallistboxfield) | When managing large sets of selected items |
| `Filament\Forms\Components\MorphToSelect` | MorphTo type + record pickers |
| `Filament\Forms\Components\ModalTableSelect` | Select records from a Filament table modal |

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

---
title: "UserColumn"
---

[← Back to Table of Contents](/docs/index)


### Summary

Read-only **table column** for displaying users with the same visual language as [UserSelect](/docs/userselect). Automatically picks the layout based on how many users are in the cell state:

| Users in state | Display |
|----------------|---------|
| **0** | Empty cell |
| **1** | Rich row: avatar + name + email + verified badge |
| **2+** | Overlapping circular avatar stack with `+N` overflow badge |

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Tables\Columns\UserColumn` |
| **Context** | Filament tables (`$table-&gt;columns([...])`) |
| **State type** | `Model`, `Collection` of models, or `list&lt;Model&gt;` |
| **Parent** | `Filament\Tables\Columns\TextColumn` |

Shares user field configuration with UserSelect via the `ResolvesUserDisplay` concern (`nameColumn`, `emailColumn`, `avatarColumn`, custom resolvers).

### Basic usage

Single user (BelongsTo / HasOne):

```php
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\UserColumn;

UserColumn::make('author')
    ->label('Author')
    ->nameColumn('name')
    ->emailColumn('email')
    ->avatarColumn('avatar_url')
    ->verificationColumn('email_verified_at');
```

Multiple users (BelongsToMany / HasMany collection):

```php
UserColumn::make('members')
    ->label('Members')
    ->maxVisibleAvatars(4)
    ->stackedRing(2)
    ->stackedOverlap(10);
```

Filament resolves `$record-&gt;author` or `$record-&gt;members` as column state. `UserColumn` automatically calls `with()` for direct relationship column names (for example `members`, `author`) and any extra relations declared via `eagerLoad()`.

Custom `getStateUsing()` that returns the same users on every row should use `sharedStackUsing()` (preferred) or resolve models once per request on the page/resource.

Custom avatar via Filament convention:

```php
UserColumn::make('owner')
    ->getAvatarUrlUsing(fn (User $record): ?string => $record->getFilamentAvatarUrl());
```

### Avatar resolution order

1. `getAvatarUrlUsing()` callback, if set
2. `getFilamentAvatarUrl()` on the model, when the method exists
3. `avatarColumn()` value on the model
4. Initials on a gradient surface when no URL is available

### Configuration API (UserColumn-specific)

#### `maxVisibleAvatars(int|Closure $limit)`


Maximum avatars shown in stack mode before the `+N` overflow badge. Default: `4`, minimum `1`.

```php
UserColumn::make('field_name')
    ->maxVisibleAvatars(5);
```
#### `stackedRing(int|Closure $ring)`


White ring width (px) around each stacked avatar. Default: `2`.

```php
UserColumn::make('field_name')
    ->stackedRing(2);
```
#### `stackedOverlap(int|Closure $overlap)`


Horizontal overlap (px) between stacked avatars. Default: `10`.

```php
UserColumn::make('field_name')
    ->stackedOverlap(4);
```
#### `stackTooltips(bool|Closure $condition = true)`


Show each user's name in a native `title` tooltip on stack avatars. Default: `true`.

```php
UserColumn::make('field_name')
    ->stackTooltips(true);
```
#### `eagerLoad(string|array|Closure $relationships)`


Register extra relationships to eager-load for this column. Direct relationship column names such as `members` or `author` are eager-loaded automatically when they match an Eloquent relation on the table model.

```php
UserColumn::make('team_preview')
    ->eagerLoad('assignees');
```
#### `sharedStackUsing(Closure $resolver)`


Resolve the same multi-user stack **once per table page** instead of querying or building state on every row. Use for preview/demo columns that show identical members on each record.

```php
UserColumn::make('team_preview')
    ->sharedStackUsing(fn () => User::query()->orderBy('id')->limit(8)->get());
```

### Performance

| Mechanism | What it does |
|-----------|----------------|
| **`applyEagerLoading()`** | During table query build, adds `with()` for the column name when it matches an Eloquent relation (`members`, `author`, …) plus any `eagerLoad()` relations. |
| **`UserColumnStackState`** | Wraps multi-user state so Filament `TextColumn` treats it as one HTML cell (avoids comma-joined rich rows). |
| **`UserColumnRenderCache`** | Per-request cache of rendered rich/stack HTML keyed by normalized display data and column options. Identical stacks across rows reuse one Blade render. |
| **`sharedStackUsing()`** | Per-page cache of shared multi-user state — resolver runs once per Livewire table, not once per row. |
| **Lazy CSS** | Loads `flex-fields-user-display.css` + `flex-fields-user-column.css` only when a `UserColumn` cell renders (via `load-stylesheet` partial). Request-scoped queue + `emit-assets` deduplication prevent duplicate `&lt;link&gt;` tags; `data-navigate-track` + `flex-field-asset-injector` keep SPA navigation clean. |

### Shared user display API

These methods are identical to [UserSelect](/docs/userselect):

| Method | Description |
|--------|-------------|
| `nameColumn(string\|Closure $column)` | Name attribute. Default: `name` |
| `emailColumn(string\|Closure\|null $column)` | Email / subtitle column |
| `avatarColumn(string\|Closure\|null $column)` | Avatar URL column |
| `verificationColumn(string\|Closure\|null $column)` | Verified badge column |
| `getAvatarUrlUsing(?Closure $callback)` | Custom avatar resolver |
| `getNameUsing(?Closure $callback)` | Custom name resolver |
| `getEmailUsing(?Closure $callback)` | Custom email resolver |
| `isVerifiedUsing(?Closure $callback)` | Custom verified resolver |

### Inherited TextColumn API

All Filament `TextColumn` methods apply: `label()`, `sortable()`, `searchable()`, `toggleable()`, `alignStart()` / `alignCenter()`, `url()`, `tooltip()`, etc. The column uses `html()` internally — do not call `html(false)` unless you override `formatStateUsing()`.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `formatUserDisplay(mixed $state)` | `string` | Rendered HTML for a state value |
| `normalizeUsersFromState(mixed $state)` | `list&lt;array&gt;` | Normalized user display arrays |
| `recordToDisplayArray(Model $record)` | `array` | `label`, `description`, `image`, `verified`, `initials` |
| `renderRichUser(array $user)` | `string` | Single-user rich HTML |
| `renderAvatarStack(array $users)` | `string` | Multi-user stack HTML |
| `getMaxVisibleAvatars()` | `int` | Stack visibility limit |
| `getStackedRing()` | `int` | Stack ring width (px) |
| `getStackedOverlap()` | `int` | Stack overlap (px) |
| `shouldShowStackTooltips()` | `bool` | Whether stack items show name tooltips |
| `resolveUserDisplayInitials(string $name)` | `string` | Initials fallback |

### CSS classes

| Class | Role |
|-------|------|
| `fff-user-column` | Cell wrapper |
| `fff-user-column--rich` | Single-user layout |
| `fff-user-column--stacked` | Multi-user stack layout |
| `fff-user-column__avatar-stack` | Overlapping avatar row |
| `fff-user-column__avatar-stack-item` | Individual stack avatar |
| `fff-user-column__avatar-stack-overflow` | `+N` overflow badge |
| `fff-user-select__avatar--stack` | Stack avatar size modifier |

### Implementation notes

- Lazy-loads **`user-display`** + **`user-column`** CSS bundles when the column renders (not part of `flex-fields-core.css`).
- Multi-user state is wrapped internally so Filament does not comma-join multiple rich user rows.
- Stack mode hides the verified badge on individual avatars (names are available via tooltip when `stackTooltips()` is enabled).
- Only Eloquent `Model` instances in state are rendered; scalar IDs are not resolved automatically — use a relationship column with auto eager-load, or a custom `getStateUsing()` that returns models (cached when shared across rows).

---

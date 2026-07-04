---
title: "UserSelect"
description: Rich user picker with avatars, email secondary line, verification badge, and names/tags multi-trigger.
---

[← Back to Table of Contents](/docs/index)

### Summary

Rich user picker extending [SelectField](/docs/selectfield) with a design optimized for user selection. Features avatars, names, emails, and verified badges in both the dropdown and trigger. Supports single selection with a rich trigger and multiple selection with removable avatar tags.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect` |
| **State type** | `string\|int\|null` (single) or `list<string\|int>` (multiple) |
| **FieldType** | `user_select` |
| **Parent** | `SelectField` — inherits select API |
| **Playground** | `user-select` slug in Flex Fields playground |

For **read-only user display in tables**, use [UserColumn](/docs/usercolumn) instead.

---

### Basic usage

#### Eloquent Model Search
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use App\Models\User;

UserSelect::make('assignee_id')
    ->label('Assignee')
    ->optionModel(User::class)
    ->nameColumn('name')
    ->emailColumn('email')
    ->avatarColumn('avatar_url')
    ->verificationColumn('email_verified_at')
    ->searchable();
```

#### Multi-Select Relationship
```php
UserSelect::make('team_members')
    ->relationship('members', 'name')
    ->multiple()
    ->searchable()
    ->maxVisibleAvatars(5);
```

---

### State & validation

#### Stored value
State is a scalar ID (single) or an array of IDs (multiple). Values must match model primary keys when using `optionModel()` or `relationship()`.

```php
$record->assignee_id; // 1
$record->team_members; // [1, 2, 3]
```

#### Validation rules
Inherits standard Filament Select validation. Option keys must exist in search results or static `options()`.

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `optionModel(string $model)` | Setup | `null` | Eloquent model for search/options |
| `nameColumn(string $column)` | Setup | `'name'` | Model attribute for user name |
| `emailColumn(string $column)` | Setup | `null` | Model attribute for email/subtitle |
| `avatarColumn(string $column)` | Setup | `null` | Model attribute for avatar URL |
| `verificationColumn(string $col)` | Setup | `null` | Model attribute for verified status |
| `getAvatarUrlUsing(Closure $cb)` | Resolver | — | Custom avatar URL resolver |
| `getNameUsing(Closure $cb)` | Resolver | — | Custom name resolver |
| `isVerifiedUsing(Closure $cb)` | Resolver | — | Custom verified status resolver |
| `multiple(bool $condition)` | Setup | `false` | Enable multi-select mode |
| `searchable(bool $condition)` | Setup | `false` | Enable AJAX/local search |
| `maxVisibleAvatars(int $limit)` | Setup | `5` | Max avatars shown in multi-trigger |

---

### Real-world examples

#### Custom Avatar Integration
```php
UserSelect::make('owner_id')
    ->optionModel(User::class)
    ->getAvatarUrlUsing(fn (User $record) => $record->getFilamentAvatarUrl())
    ->isVerifiedUsing(fn (User $record) => $record->hasVerifiedEmail());
```

#### Static Rich Options
```php
UserSelect::make('reviewer_id')
    ->options([
        1 => [
            'label' => 'Jane Doe',
            'description' => 'jane@example.com',
            'image' => '/avatars/jane.jpg',
            'verified' => true,
        ],
    ]);
```

---

### Playground

`/admin/flex-fields-playground/user-select`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [UserColumn](/docs/usercolumn) | Read-only user display in tables |
| [SelectField](/docs/selectfield) | Generic selection without user semantics |
| [ChoiceCards](/docs/choicecards) | Large card-style selection |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-user-select` | Root wrapper |
| `fff-user-select--single` | Single-select modifier |
| `fff-user-select--multiple` | Multi-select modifier |
| `fff-user-select-option--list` | Dropdown option row |
| `fff-user-select-option--trigger` | Selection trigger row |
| `fff-user-select-option--tag` | Multi-select tag chip |
| `fff-user-select__selected-tags` | Tag container below field |
| `fff-user-select__avatar` | Avatar image/initials wrapper |
| `fff-user-select__verified-badge` | Verified seal icon |

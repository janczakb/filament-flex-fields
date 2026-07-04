---
title: "UserColumn"
description: Read-only table column for displaying users with avatars, names, and overlapping stacks.
---

[← Back to Table of Contents](/docs/index)

### Summary

Read-only **table column** for displaying users with the same visual language as [UserSelect](/docs/userselect). Automatically switches between a rich single-user layout and an overlapping avatar stack for multiple users.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Tables\Columns\UserColumn` |
| **Context** | Filament tables (`$table->columns([...])`) |
| **State type** | `Model`, `Collection` of models, or `list<Model>` |
| **Parent** | `Filament\Tables\Columns\TextColumn` |
| **Playground** | `user-column` slug in Flex Fields playground |

---

### Basic usage

#### Single User (BelongsTo)
```php
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\UserColumn;

UserColumn::make('author')
    ->label('Author')
    ->nameColumn('name')
    ->emailColumn('email')
    ->avatarColumn('avatar_url')
    ->verificationColumn('email_verified_at');
```

#### Multiple Users (BelongsToMany)
```php
UserColumn::make('members')
    ->label('Team Members')
    ->maxVisibleAvatars(4)
    ->stackedOverlap(10);
```

---

### State format
State must be an Eloquent `Model` or a collection/array of models. `UserColumn` automatically handles eager-loading for direct relationship columns.

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `nameColumn(string $column)` | Setup | `'name'` | Name attribute |
| `emailColumn(string $column)` | Setup | `null` | Email / subtitle attribute |
| `avatarColumn(string $column)` | Setup | `null` | Avatar URL attribute |
| `verificationColumn(string $col)` | Setup | `null` | Verified badge attribute |
| `maxVisibleAvatars(int $limit)` | Setup | `4` | Max avatars in stack before `+N` |
| `stackedRing(int $px)` | Setup | `2` | Ring width around stacked avatars |
| `stackedOverlap(int $px)` | Setup | `10` | Overlap between stacked avatars |
| `stackTooltips(bool $cond)` | Setup | `true` | Show names in tooltips on stack |
| `eagerLoad(string\|array $rels)` | Setup | — | Extra relationships to eager-load |
| `sharedStackUsing(Closure $cb)` | Setup | — | Cache identical stacks per page |

---

### Real-world examples

#### Project Members Stack
```php
UserColumn::make('members')
    ->maxVisibleAvatars(5)
    ->stackTooltips()
    ->getAvatarUrlUsing(fn ($user) => $user->getFilamentAvatarUrl());
```

#### Shared Preview Column
```php
UserColumn::make('team_preview')
    ->sharedStackUsing(fn () => User::query()->limit(10)->get());
```

---

### Performance
- **Auto Eager-Loading**: Automatically calls `with()` for relationship columns.
- **Render Cache**: Per-request caching of rendered HTML for identical stacks.
- **Lazy CSS**: Stylesheets are loaded only when the column is rendered.

---

### Playground

`/admin/flex-fields-playground/user-column`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [UserSelect](/docs/userselect) | Form field for selecting users |
| [IconColumn](/docs/iconcolumn) | Displaying icons in tables |
| [RatingColumn](/docs/ratingcolumn) | Displaying star ratings in tables |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-user-column` | Cell wrapper |
| `fff-user-column--rich` | Single-user layout |
| `fff-user-column--stacked` | Multi-user stack layout |
| `fff-user-column__avatar-stack` | Overlapping avatar row |
| `fff-user-column__avatar-stack-item` | Individual stack avatar |
| `fff-user-column__avatar-stack-overflow` | `+N` overflow badge |

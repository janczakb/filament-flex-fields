---
title: "TodoListField"
description: Animated todo list with check/strike motion, celebrations, sub-stacks, undo toast, create/edit/delete, reorder, search, virtualize, and infinite scroll.
---

![TodoListField](/art/todolist.webp)

[← Back to Table of Contents](/docs/index)

### Summary

An enterprise animated checklist. Tasks dehydrate as structured items (`id`, `label`, `done`, …). Checking a task draws an SVG checkmark with spark lines, nudges the title, writes a hand-scribble strikethrough **through the title**, then settles into a muted “done” look after ~500ms. Completing the list can fire fullscreen fireworks (or other celebrations).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TodoListField` |
| **State type** | `list<array{id, label, done, …}>` |
| **Model cast** | `'tasks' => 'array'` |
| **FieldType** | `todo_list` |
| **Playground** | `todo-list-field` |

---

### Basic usage

```php
TodoListField::make('tasks')
    ->options([
        'water' => ['label' => 'Water', 'description' => 'Stay hydrated'],
        'inbox' => 'Clear inbox',
    ])
    ->celebration('fireworks')
    ->celebrationFullscreen()
    ->strikethroughStyle('hand')
    ->doneSettleMs(500)
    ->sounds();
```

### Create · delete · edit · reorder · lock

```php
TodoListField::make('tasks')
    ->options([
        'water' => 'Water',
        'policy' => [
            'label' => 'Safety briefing',
            'locked' => true, // or disabled: true — no toggle / delete / edit / drag
            'done' => true,
        ],
    ])
    ->allowCreate()
    ->createWithDescription() // optional second field on create
    ->createDescriptionPlaceholder('Notes…')
    ->allowDelete()
    ->deletable('all') // 'created' | 'all' | 'none'
    ->allowEdit()
    ->editable('all') // 'created' | 'all' | 'none'
    ->editWithDescription() // modal: title + description (defaults to createWithDescription)
    ->editIcon(\Bjanczak\FilamentFlexFields\Support\GravityIcon::Pencil) // optional
    ->editUsing(function (array $item, array $data): ?array {
        // Persist title/description; return updated item or null to cancel
        return [...$item, 'label' => $data['label'], 'description' => $data['description'] ?? null];
    })
    ->deleteUsing(function (array $item): bool {
        // Persist hard delete; return false to cancel removal from the list
        // Parent deletes include nested children on $item['children'].
        // Task::query()->whereKey($item['id'])->delete();

        return true;
    })
    ->reorderable() // drag parent groups (parent + children move together); not with virtualizing/infiniteScroll
    ->reorderUsing(function (array $items): bool {
        // Persist new order; return false to revert the drag
        foreach ($items as $position => $row) {
            // Task::query()->whereKey($row['id'])->update(['position' => $position]);
        }

        return true;
    });
```

Hover a row to reveal edit (before delete) when enabled. Action slots stay reserved on the right so labels do not shift. Use `deletable('created')` / `editable('created')` if only user-added rows may change. With `createWithDescription()`, Enter on the title focuses description; Enter on description (or blur with a title) creates the item.

### Sub-stack · completion undo

Nest one level of `children` under an option. Checking the parent completes every child; checking every actionable child completes the parent. Parents with children show a Gravity `CodeTrunk` meta chip (`done/total`). Optional `date` shows a calendar chip. With `reorderable()`, Sortable moves each **parent group** (parent row + its sub-stack) as one unit — children are not dragged on their own.

```php
TodoListField::make('tasks')
    ->options([
        'pack' => [
            'label' => 'Pack bag',
            'description' => 'Before departure',
            'date' => '12 Sep', // optional
            'children' => [
                'passport' => 'Passport',
                'charger' => ['label' => 'Charger', 'done' => true],
            ],
        ],
    ])
    ->undoCompletionNotifications(); // default true — Filament toast + Undo
```

Undo restores **only** the items that completion changed, so later checks/creates are kept. Use `->undoCompletionNotifications(false)` to disable the toast. Icons in this field are Gravity only (no Heroicons).

### Persist create · edit · delete · reorder · toggle (PHP / DB)

Hook create, edit, delete, reorder, and selection so you can write to your models without waiting for form save:

```php
TodoListField::make('tasks')
    ->allowCreate()
    ->createUsing(function (array $item): ?array {
        $task = Task::create(['title' => $item['label']]);

        return [...$item, 'id' => (string) $task->id];
    })
    ->allowEdit()
    ->editUsing(function (array $item, array $data, array $children): ?array {
        // Editing a parent keeps `$children` nested; only title/description change here.
        Task::query()->whereKey($item['id'])->update([
            'title' => $data['label'],
            'notes' => $data['description'] ?? null,
        ]);

        return [...$item, 'label' => $data['label'], 'description' => $data['description'] ?? null];
    })
    ->allowDelete()
    ->deletable('all')
    ->deleteUsing(function (array $item, array $children, array $deletedIds): bool {
        // Parent delete: `$children` is the nested stack; `$deletedIds` is parent + every child id.
        // Child delete: `$children` is [] and `$deletedIds` is just that row.
        Task::query()->whereIn('id', $deletedIds)->delete();

        return true; // return false to keep the row(s) in the list
    })
    ->reorderable()
    ->reorderUsing(function (array $items): bool {
        // `$items` is the full list in the new order (parent groups; children stay nested).
        foreach ($items as $position => $row) {
            Task::query()->whereKey($row['id'])->update([
                'position' => $position,
            ]);
        }

        return true; // return false to revert the drag on the client
    })
    ->afterToggled(function (array $changed, array $items): void {
        // One call per click — $changed is the full cascade set (parent + kids when applicable).
        foreach ($changed as $row) {
            Task::query()->whereKey($row['id'])->update([
                'done' => (bool) $row['done'],
            ]);
        }
    });
```

`createUsing` / `editUsing` may return `null` to cancel. Editing a parent never drops its `children` (they stay nested). `deleteUsing` / `reorderUsing` may return `false` to cancel (reorder reverts the previous order). When a parent is deleted, `deleteUsing` receives the parent `$item`, its `$children`, and `$deletedIds` (parent + child ids) so you can cascade in the DB. Without those hooks, mutations stay client-side only until form save. Checking a parent with five children fires **one** `afterToggled` with all affected rows in `$changed` (not five separate Livewire calls).

Per-item celebrations can opt into fullscreen with `'celebrationFullscreen' => true` on the option row (field-level `celebrationFullscreen()` still applies to complete-all celebrations).

**Undo** in the Filament toast uses the same `afterToggled` hook — restoring a completion is another done-state change (typically `done: false` on the patched ids).

You can also use Filament’s full-state hook:

```php
TodoListField::make('tasks')
    ->live()
    ->afterStateUpdated(function (?array $state): void {
        // Persist the whole list
    });
```

### Search · virtualizing

```php
TodoListField::make('tasks')
    ->options($many)
    ->searchable()
    ->virtualizing();
```

Virtualizing measures each visible group’s **natural** height (title wrap, description, sub-stacks). Nothing forces a fixed row height — unmeasured rows temporarily use a content-based estimate, then snap to the live measured size while you scroll.

### Infinite scroll · AJAX pages

```php
TodoListField::make('tasks')
    ->options($firstPage)
    ->virtualizing()
    ->infiniteScroll(20)
    ->remoteLoader('loadTodoPage'); // Livewire method: (int $page, string $search) => items|{items,hasMore}
```

`paginated(n)` is an alias that enables infinite scroll with page size `n`.

### Celebrations

Built-ins: `fireworks`, `confetti`, `sparkles`, `streamers` (meteors), `bloom` (check cascade). Use `celebrationFullscreen()` for a viewport-covering canvas. In-box fireworks stop around 1.6s; fullscreen runs ~6s.

Bundled audio: fireworks launch + burst, confetti pop, sparkles chime, checks success chime (and check / create / accent clicks). Override with `celebrationStartSound()` / `celebrationSound()` / `createSound()` or per-key packs via the field defaults.

Custom FX: register in the browser, then pass the key to `celebration()` / per-item `celebration`:

```js
window.FilamentFlexFieldsTodoList.registerCelebration('my-logo-burst', {
    durationMs: 3200,
    start({ canvas, durationMs, playSound, fullscreen, reducedMotion }) {
        // rAF / canvas particles…
        return { stop() { /* cleanup */ } }
    },
})
```

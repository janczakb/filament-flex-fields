<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TodoListField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Playground\TodoListFieldPlayground;

it('exposes todo list api and alpine config', function () {
    $field = TodoListField::make('tasks')
        ->options([
            'water' => 'Water',
            'inbox' => [
                'label' => 'Clear inbox',
                'description' => 'Zero unread',
                'celebration' => 'sparkles',
            ],
            'locked' => [
                'label' => 'Locked row',
                'locked' => true,
            ],
        ])
        ->allowCreate()
        ->allowDelete()
        ->deletable('all')
        ->allowEdit()
        ->editable('all')
        ->editWithDescription()
        ->editIcon(GravityIcon::Pencil)
        ->reorderable()
        ->searchable()
        ->celebration('fireworks')
        ->celebrationDurationMs(5500)
        ->strikethroughStyle('hand')
        ->doneSettleMs(500)
        ->sounds()
        ->minDone(1)
        ->maxItems(10)
        ->color('primary')
        ->size('md');

    $items = $field->seedItemsFromOptions();

    expect($field->canCreate())->toBeTrue()
        ->and($field->canDelete())->toBeTrue()
        ->and($field->canEdit())->toBeTrue()
        ->and($field->canEditWithDescription())->toBeTrue()
        ->and($field->getEditableMode())->toBe('all')
        ->and($field->getEditIcon())->toBe(GravityIcon::Pencil)
        ->and($field->getDeletableMode())->toBe('all')
        ->and($field->isReorderable())->toBeTrue()
        ->and($field->isSearchable())->toBeTrue()
        ->and($field->getWrapperClasses())->toContain('fff-todo-list-field--reorderable')
        ->and($field->getAction('editTodoItem'))->not->toBeNull()
        ->and(collect($items)->firstWhere('id', 'locked')['disabled'] ?? null)->toBeTrue();
});

it('exposes edit api and replaces nested items by id', function () {
    $field = TodoListField::make('tasks')
        ->options([
            'pack' => [
                'label' => 'Pack',
                'children' => [
                    'a' => 'Passport',
                ],
            ],
            'locked' => [
                'label' => 'Locked',
                'locked' => true,
            ],
        ])
        ->allowEdit()
        ->editable('all')
        ->editWithDescription()
        ->editIcon(GravityIcon::Pencil)
        ->editUsing(fn (array $item, array $data): array => [
            'label' => strtoupper((string) $data['label']),
            'description' => $data['description'] ?? null,
        ]);

    $present = new ReflectionMethod(TodoListField::class, 'presentItem');
    $present->setAccessible(true);
    $seed = $field->seedItemsFromOptions();
    $pack = $present->invoke($field, $seed[0]);
    $locked = $present->invoke($field, $seed[1]);

    expect($field->canEdit())->toBeTrue()
        ->and($field->canEditWithDescription())->toBeTrue()
        ->and($field->hasEditUsing())->toBeTrue()
        ->and($field->getEditIcon())->toBe(GravityIcon::Pencil)
        ->and($field->getAction('editTodoItem'))->not->toBeNull()
        ->and($pack['editable'])->toBeTrue()
        ->and($pack['children'][0]['editable'])->toBeTrue()
        ->and($locked['editable'])->toBeFalse()
        ->and($field->itemIsEditable($locked))->toBeFalse();

    $replace = new ReflectionMethod(TodoListField::class, 'mapReplaceItemById');
    $replace->setAccessible(true);
    $next = $replace->invoke($field, $seed, 'a', ['label' => 'Travel passport']);

    expect($next[0]['children'][0]['label'])->toBe('Travel passport')
        ->and($next[0]['label'])->toBe('Pack');
});

it('disables reorder when virtualizing', function () {
    $field = TodoListField::make('tasks')
        ->options(['a' => 'A'])
        ->reorderable()
        ->virtualizing();

    expect($field->isReorderable())->toBeFalse();
});

it('exposes celebration audio and size wrappers', function () {
    $field = TodoListField::make('tasks')
        ->options([
            'water' => 'Water',
            'inbox' => [
                'label' => 'Clear inbox',
                'celebration' => 'sparkles',
            ],
        ])
        ->celebration('fireworks')
        ->celebrationDurationMs(5500)
        ->strikethroughStyle('hand')
        ->doneSettleMs(500)
        ->minDone(1)
        ->maxItems(10)
        ->color('primary')
        ->size('md');

    $items = $field->seedItemsFromOptions();

    expect($field->getCelebration())->toBe('fireworks')
        ->and($field->getCelebrationDurationMs())->toBe(5500)
        ->and($field->getCelebrationStartSoundUrl())->toContain('todo-fireworks-start.mp3')
        ->and($field->getCelebrationSoundUrl())->toContain('todo-fireworks-burst.mp3')
        ->and($field->getCelebrationAudio()['confetti']['start'])->toContain('todo-confetti.mp3')
        ->and($field->getCelebrationAudio()['sparkles']['start'])->toContain('todo-sparkles.mp3')
        ->and($field->getCelebrationAudio()['bloom']['start'])->toContain('todo-checks.mp3')
        ->and($field->getCreateSoundUrl())->toContain('todo-create.mp3')
        ->and($field->canCreateWithDescription())->toBeFalse()
        ->and($field->getStrikethroughStyle())->toBe('hand')
        ->and($field->getDoneSettleMs())->toBe(500)
        ->and($field->getMinDone())->toBe(1)
        ->and($field->getMaxItems())->toBe(10)
        ->and($field->getPlusIcon())->toBe(GravityIcon::CirclePlusFill)
        ->and($field->getWrapperClasses())->toContain('fff-todo-list-field', 'fff-todo-list-field--md', 'fi-color-primary')
        ->and($items)->toHaveCount(2)
        ->and($items[0]['id'])->toBe('water')
        ->and($items[1]['celebration'])->toBe('sparkles');
});

it('keeps virtualizing available with infinite scroll page size', function () {
    $field = TodoListField::make('tasks')
        ->options(['a' => 'A'])
        ->virtualizing()
        ->infiniteScroll(8);

    expect($field->getPageSize())->toBe(8)
        ->and($field->isInfiniteScroll())->toBeTrue()
        ->and($field->isVirtualizing())->toBeTrue()
        ->and($field->isCelebrationFullscreen())->toBeFalse();

    $fullscreen = TodoListField::make('tasks')->celebrationFullscreen();
    expect($fullscreen->isCelebrationFullscreen())->toBeTrue();
});

it('can enable create with description', function () {
    $field = TodoListField::make('tasks')
        ->options(['a' => 'A'])
        ->allowCreate()
        ->createWithDescription()
        ->createDescriptionPlaceholder('Notes…');

    expect($field->canCreate())->toBeTrue()
        ->and($field->canCreateWithDescription())->toBeTrue()
        ->and($field->getCreateDescriptionPlaceholder())->toBe('Notes…');

    $off = TodoListField::make('tasks')->allowCreate();
    expect($off->canCreateWithDescription())->toBeFalse();
});

it('normalizes seed options and legacy key lists', function () {
    $field = TodoListField::make('tasks')
        ->options([
            'water' => 'Water',
            'inbox' => 'Inbox',
        ]);

    $seeded = $field->normalizeState([]);

    expect($seeded)->toHaveCount(2)
        ->and($seeded[0])->toMatchArray([
            'id' => 'water',
            'label' => 'Water',
            'done' => false,
            'created' => false,
        ]);

    $fromKeys = $field->normalizeState(['water']);

    expect($fromKeys[0]['done'])->toBeTrue()
        ->and($fromKeys[1]['done'])->toBeFalse();
});

it('marks created items and preserves structured state', function () {
    $field = TodoListField::make('tasks')
        ->options(['water' => 'Water']);

    $state = $field->normalizeState([
        [
            'id' => 'water',
            'label' => 'Water',
            'done' => true,
        ],
        [
            'id' => 'custom-1',
            'label' => 'Custom',
            'done' => false,
            'created' => true,
        ],
    ]);

    expect($state[0]['created'])->toBeFalse()
        ->and($state[1]['created'])->toBeTrue()
        ->and($state[0]['done'])->toBeTrue();
});

it('validates done counts', function () {
    $field = TodoListField::make('tasks')
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->minDone(2);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);
    $message = null;
    $rule('tasks', [
        ['id' => 'a', 'label' => 'A', 'done' => true],
        ['id' => 'b', 'label' => 'B', 'done' => false],
    ], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.todo_list.min_done', ['count' => 2]));
});

it('seeds children and exposes undo completion notifications', function () {
    $field = TodoListField::make('tasks')
        ->options([
            'pack' => [
                'label' => 'Pack',
                'date' => '12 Sep',
                'children' => [
                    'a' => 'Passport',
                    'b' => ['label' => 'Charger', 'done' => true],
                ],
            ],
        ]);

    $items = $field->seedItemsFromOptions();

    expect($items[0]['children'])->toHaveCount(2)
        ->and($items[0]['children'][1]['done'])->toBeTrue()
        ->and($items[0]['date'])->toBe('12 Sep')
        ->and($items[0])->not->toHaveKey('comments')
        ->and($items[0])->not->toHaveKey('tags')
        ->and($field->hasUndoCompletionNotifications())->toBeTrue()
        ->and($field->getChildrenIcon())->toBe(GravityIcon::CodeTrunk)
        ->and($field->getReorderIcon())->toBe(GravityIcon::Grip)
        ->and($field->getDateIcon())->toBe(GravityIcon::Calendar);

    $field->undoCompletionNotifications(false);

    expect($field->hasUndoCompletionNotifications())->toBeFalse();
});

it('runs createUsing, deleteUsing, reorderUsing and afterToggled callbacks', function () {
    $created = null;
    $deleted = null;
    $deletedChildren = null;
    $cascadeDeletedIds = null;
    $reordered = null;
    $toggled = null;

    $field = TodoListField::make('tasks')
        ->options([
            'a' => [
                'label' => 'Pack',
                'children' => [
                    'a1' => 'Passport',
                    'a2' => 'Charger',
                ],
            ],
            'b' => 'B',
        ])
        ->allowDelete()
        ->deletable('all')
        ->reorderable()
        ->createUsing(function (array $item) use (&$created): array {
            $created = $item;

            return [...$item, 'id' => 'db-1'];
        })
        ->deleteUsing(function (array $item, array $children, array $deletedIds) use (&$deleted, &$deletedChildren, &$cascadeDeletedIds): bool {
            $deleted = $item;
            $deletedChildren = $children;
            $cascadeDeletedIds = $deletedIds;

            return true;
        })
        ->reorderUsing(function (array $items) use (&$reordered): bool {
            $reordered = $items;

            return true;
        })
        ->afterToggled(function (array $changed, array $items) use (&$toggled): void {
            $toggled = compact('changed', 'items');
        });

    $parent = $field->seedItemsFromOptions()[0];

    expect($field->hasCreateUsing())->toBeTrue()
        ->and($field->hasDeleteUsing())->toBeTrue()
        ->and($field->hasReorderUsing())->toBeTrue()
        ->and($field->hasAfterToggled())->toBeTrue()
        ->and($field->createTodoItem(['item' => ['id' => 'tmp', 'label' => 'New']]))->toMatchArray([
            'id' => 'db-1',
            'label' => 'New',
        ])
        ->and($field->deleteTodoItem(['item' => $parent]))->toBeTrue()
        ->and($field->reorderTodoItems([
            'items' => [
                ['id' => 'b', 'label' => 'B'],
                ['id' => 'a', 'label' => 'A'],
            ],
        ]))->toBeTrue();

    $field->todoItemsToggled([
        'changed' => [['id' => 'b', 'label' => 'B', 'done' => true]],
        'items' => [['id' => 'b', 'label' => 'B', 'done' => true]],
    ]);

    expect($created['label'])->toBe('New')
        ->and($deleted['id'])->toBe('a')
        ->and($deletedChildren)->toHaveCount(2)
        ->and($cascadeDeletedIds)->toBe(['a', 'a1', 'a2'])
        ->and($reordered[0]['id'])->toBe('b')
        ->and($toggled['changed'][0]['done'])->toBeTrue();

    $blocked = TodoListField::make('blocked')
        ->options(['b' => 'B'])
        ->allowDelete()
        ->deletable('all')
        ->deleteUsing(fn (): bool => false);

    expect($blocked->deleteTodoItem(['item' => ['id' => 'b', 'label' => 'B']]))->toBeFalse();

    $blockedReorder = TodoListField::make('blocked_reorder')
        ->options(['a' => 'A', 'b' => 'B'])
        ->reorderable()
        ->reorderUsing(fn (): bool => false);

    expect($blockedReorder->reorderTodoItems([
        'items' => [
            ['id' => 'b', 'label' => 'B'],
            ['id' => 'a', 'label' => 'A'],
        ],
    ]))->toBeFalse();
});

it('keeps children when replacing a parent by id', function () {
    $field = TodoListField::make('tasks')
        ->options([
            'pack' => [
                'label' => 'Pack',
                'children' => [
                    'passport' => 'Passport',
                    'charger' => 'Charger',
                ],
            ],
        ]);

    $seed = $field->seedItemsFromOptions();
    $replace = new ReflectionMethod(TodoListField::class, 'mapReplaceItemById');
    $replace->setAccessible(true);
    $next = $replace->invoke($field, $seed, 'pack', ['label' => 'Packed bag']);

    expect($next[0]['label'])->toBe('Packed bag')
        ->and($next[0]['children'])->toHaveCount(2)
        ->and($next[0]['children'][0]['id'])->toBe('passport')
        ->and($next[0]['children'][1]['label'])->toBe('Charger');
});

it('registers playground hub', function () {
    $playground = new TodoListFieldPlayground;

    expect($playground->components())->not->toBeEmpty();
    expect(file_get_contents((new ReflectionClass($playground))->getFileName()))
        ->toContain('todo_list__long_copy')
        ->toContain('todo_list__variable_virtual')
        ->toContain('todo_list__edit_all')
        ->toContain('todo_list__celebration_gallery')
        ->toContain('todo_list__deletable_all')
        ->toContain('todo_list__reorder_locked')
        ->toContain('todo_list__substack');
});

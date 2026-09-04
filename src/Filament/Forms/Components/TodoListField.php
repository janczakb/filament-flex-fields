<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\TodoListFieldAudio;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TodoListField extends Field
{
    use HasControlSize;

    protected string $view = 'filament-flex-fields::forms.components.todo-list-field';

    /**
     * @var array<string | int, string | array<string, mixed>> | Closure
     */
    protected array|Closure $options = [];

    protected string|Closure|null $color = 'primary';

    protected bool|Closure $sounds = true;

    protected string|Closure|null $checkSound = null;

    protected string|Closure|null $accentSound = null;

    protected string|Closure|null $createSound = null;

    protected string|Closure|false|null $celebration = 'fireworks';

    protected int|Closure $celebrationDurationMs = 5500;

    protected string|Closure|null $celebrationSound = null;

    protected string|Closure|null $celebrationStartSound = null;

    protected bool|Closure $celebrationFullscreen = false;

    protected string|Closure $strikethroughStyle = 'hand';

    protected int|Closure $doneSettleMs = 500;

    protected bool|Closure $allowCreate = false;

    protected bool|Closure $createWithDescription = false;

    protected bool|Closure $allowDelete = false;

    protected string|Closure $deletable = 'created';

    protected bool|Closure $allowEdit = false;

    protected string|Closure $editable = 'all';

    protected bool|Closure|null $editWithDescription = null;

    protected string|BackedEnum|Htmlable|Closure|null $editIcon = null;

    protected bool|Closure $reorderable = false;

    protected int|Closure $reorderAnimationDuration = 200;

    protected bool|Closure $undoCompletionNotifications = true;

    /**
     * Persist a newly created item (DB etc.). Return the item (e.g. with real id) or null to cancel.
     */
    protected ?Closure $createUsing = null;

    /**
     * Persist an edited item. Return the updated item row or null to cancel.
     * Signature: `function (array $item, array $data, TodoListField $component): ?array`
     */
    protected ?Closure $editUsing = null;

    /**
     * Persist a deleted item. Return false to cancel removal from the list.
     * Signature: `function (array $item, TodoListField $component): bool|null`
     */
    protected ?Closure $deleteUsing = null;

    /**
     * Persist a reordered list. Return false to revert the client order.
     * Signature: `function (array $items, TodoListField $component): bool|null`
     */
    protected ?Closure $reorderUsing = null;

    /**
     * React when one or more items are toggled (done / undone), including cascade.
     */
    protected ?Closure $afterToggled = null;

    protected string|Closure|null $createLabel = null;

    protected string|Closure|null $createPlaceholder = null;

    protected string|Closure|null $createDescriptionPlaceholder = null;

    protected bool|Closure $searchable = false;

    protected string|Closure|null $searchPrompt = null;

    protected bool|Closure $virtualizing = false;

    protected int|Closure $virtualItemHeight = 52;

    protected int|bool|Closure $paginated = false;

    protected bool|Closure $infiniteScroll = false;

    protected string|Closure|null $remoteLoader = null;

    protected bool|Closure $persistCompletedOrder = false;

    protected int|Closure|null $maxItems = null;

    protected int|Closure|null $minDone = null;

    protected int|Closure|null $maxDone = null;

    protected int|Closure|null $exactDone = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(function (TodoListField $component, mixed $state): void {
            $component->state($component->normalizeState(is_array($state) ? $state : []));
        });

        $this->dehydrateStateUsing(fn (TodoListField $component, mixed $state): array => $component->normalizeState(is_array($state) ? $state : []));

        $this->rule('array');

        $this->rule(function (TodoListField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (! is_array($value)) {
                    return;
                }

                $items = $component->normalizeState($value);
                $doneCount = collect($items)->where('done', true)->count();
                $exact = $component->getExactDone();

                if ($exact !== null) {
                    if ($doneCount !== $exact) {
                        $fail(__('filament-flex-fields::default.validation.todo_list.exact_done', ['count' => $exact]));
                    }

                    return;
                }

                $min = $component->getMinDone();

                if ($component->isRequired() && $min === null) {
                    $min = 1;
                }

                if ($min !== null && $doneCount < $min) {
                    $fail(__('filament-flex-fields::default.validation.todo_list.min_done', ['count' => $min]));

                    return;
                }

                $max = $component->getMaxDone();

                if ($max !== null && $doneCount > $max) {
                    $fail(__('filament-flex-fields::default.validation.todo_list.max_done', ['count' => $max]));
                }

                $maxItems = $component->getMaxItems();

                if ($maxItems !== null && count($items) > $maxItems) {
                    $fail(__('filament-flex-fields::default.validation.todo_list.max_items', ['count' => $maxItems]));
                }
            };
        });
    }

    /**
     * @param  array<string | int, string | array<string, mixed>> | Closure  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function sounds(bool|Closure $sounds = true): static
    {
        $this->sounds = $sounds;

        return $this;
    }

    public function checkSound(string|Closure|null $url): static
    {
        $this->checkSound = $url;

        return $this;
    }

    public function accentSound(string|Closure|null $url): static
    {
        $this->accentSound = $url;

        return $this;
    }

    public function createSound(string|Closure|null $url): static
    {
        $this->createSound = $url;

        return $this;
    }

    public function celebration(string|Closure|false|null $celebration): static
    {
        $this->celebration = $celebration;

        return $this;
    }

    public function celebrationDurationMs(int|Closure $ms): static
    {
        $this->celebrationDurationMs = $ms;

        return $this;
    }

    public function celebrationSound(string|Closure|null $url): static
    {
        $this->celebrationSound = $url;

        return $this;
    }

    public function celebrationStartSound(string|Closure|null $url): static
    {
        $this->celebrationStartSound = $url;

        return $this;
    }

    public function celebrationFullscreen(bool|Closure $fullscreen = true): static
    {
        $this->celebrationFullscreen = $fullscreen;

        return $this;
    }

    public function strikethroughStyle(string|Closure $style): static
    {
        $this->strikethroughStyle = $style;

        return $this;
    }

    public function doneSettleMs(int|Closure $ms): static
    {
        $this->doneSettleMs = $ms;

        return $this;
    }

    public function allowCreate(bool|Closure $allow = true): static
    {
        $this->allowCreate = $allow;

        return $this;
    }

    public function createWithDescription(bool|Closure $allow = true): static
    {
        $this->createWithDescription = $allow;

        return $this;
    }

    public function allowDelete(bool|Closure $allow = true): static
    {
        $this->allowDelete = $allow;

        return $this;
    }

    public function deletable(string|Closure $mode): static
    {
        $this->deletable = $mode;

        return $this;
    }

    public function allowEdit(bool|Closure $allow = true): static
    {
        $this->allowEdit = $allow;

        return $this;
    }

    /**
     * Which rows may be edited: `all` | `created` | `none`.
     */
    public function editable(string|Closure $mode): static
    {
        $this->editable = $mode;

        return $this;
    }

    /**
     * Include a description field in the edit modal.
     * When null, follows `createWithDescription()`.
     */
    public function editWithDescription(bool|Closure|null $allow = true): static
    {
        $this->editWithDescription = $allow;

        return $this;
    }

    public function editIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->editIcon = $icon;

        return $this;
    }

    public function reorderable(bool|Closure $enabled = true): static
    {
        $this->reorderable = $enabled;

        return $this;
    }

    public function reorderAnimationDuration(int|Closure $ms): static
    {
        $this->reorderAnimationDuration = $ms;

        return $this;
    }

    /**
     * Show Filament toast with Undo when completing tasks (default: on).
     */
    public function undoCompletionNotifications(bool|Closure $enabled = true): static
    {
        $this->undoCompletionNotifications = $enabled;

        return $this;
    }

    /**
     * Called when the user creates an item. Use to insert into your DB and return the item
     * (optionally with a real primary key as `id`). Return null to cancel create.
     *
     * Signature: `function (array $item, TodoListField $component): ?array`
     */
    public function createUsing(?Closure $callback): static
    {
        $this->createUsing = $callback;

        return $this;
    }

    /**
     * Called when the user saves the edit modal. Return the updated item or null to cancel.
     *
     * Signature: `function (array $item, array $data, TodoListField $component): ?array`
     */
    public function editUsing(?Closure $callback): static
    {
        $this->editUsing = $callback;

        return $this;
    }

    /**
     * Called when the user deletes an item. Return `false` to cancel.
     *
     * Signature: `function (array $item, array $children, array $deletedIds, TodoListField $component): bool|null`
     * - `$item`: the deleted row (parent or child)
     * - `$children`: nested rows when deleting a parent (empty for a leaf / child delete)
     * - `$deletedIds`: every id removed from the list (`[$item['id'], ...$children ids]`)
     */
    public function deleteUsing(?Closure $callback): static
    {
        $this->deleteUsing = $callback;

        return $this;
    }

    /**
     * Called after the user reorders parent groups. Return `false` to revert.
     *
     * Signature: `function (array $items, TodoListField $component): bool|null`
     * - `$items`: full list in the new order (each row may include `children`)
     */
    public function reorderUsing(?Closure $callback): static
    {
        $this->reorderUsing = $callback;

        return $this;
    }

    /**
     * Called after check/uncheck (including parent↔children cascade). Use to sync `done` to your DB.
     *
     * Signature: `function (array $changed, array $items, TodoListField $component): void`
     * - `$changed`: list of affected item rows with the new `done` value
     * - `$items`: full list after the toggle
     *
     * Also works with `->live()->afterStateUpdated()` for the full entangled state.
     */
    public function afterToggled(?Closure $callback): static
    {
        $this->afterToggled = $callback;

        return $this;
    }

    public function createLabel(string|Closure|null $label): static
    {
        $this->createLabel = $label;

        return $this;
    }

    public function createPlaceholder(string|Closure|null $placeholder): static
    {
        $this->createPlaceholder = $placeholder;

        return $this;
    }

    public function createDescriptionPlaceholder(string|Closure|null $placeholder): static
    {
        $this->createDescriptionPlaceholder = $placeholder;

        return $this;
    }

    public function searchable(bool|Closure $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function searchPrompt(string|Closure|null $prompt): static
    {
        $this->searchPrompt = $prompt;

        return $this;
    }

    public function virtualizing(bool|Closure $virtualizing = true): static
    {
        $this->virtualizing = $virtualizing;

        return $this;
    }

    /**
     * @deprecated No longer used — virtual rows measure their natural content height.
     * Kept for backwards compatibility; calling it is a no-op for layout.
     */
    public function virtualItemHeight(int|Closure $height): static
    {
        $this->virtualItemHeight = $height;

        return $this;
    }

    /**
     * Page size for infinite scroll (load-more). Prefer infiniteScroll() + page size.
     */
    public function paginated(int|bool|Closure $paginated = 10): static
    {
        $this->paginated = $paginated;

        if ($paginated !== false && $paginated !== null) {
            $this->infiniteScroll = true;
        }

        return $this;
    }

    /**
     * Infinite scroll / load-more. Works with virtualizing() and optional remoteLoader().
     *
     * @param  bool|int|Closure  $enabled  true, or page size int (enables infinite + size)
     */
    public function infiniteScroll(bool|int|Closure $enabled = true): static
    {
        if (is_int($enabled)) {
            $this->paginated = $enabled;
            $this->infiniteScroll = true;

            return $this;
        }

        $this->infiniteScroll = $enabled;

        return $this;
    }

    /**
     * Alpine/Livewire loader name for AJAX pages: fn(page, search) => items|{items,hasMore}.
     * Livewire: method on the parent component; browser: window[name].
     */
    public function remoteLoader(string|Closure|null $loader): static
    {
        $this->remoteLoader = $loader;

        return $this;
    }

    public function persistCompletedOrder(bool|Closure $persist = true): static
    {
        $this->persistCompletedOrder = $persist;

        return $this;
    }

    public function maxItems(int|Closure|null $count): static
    {
        $this->maxItems = $count;

        return $this;
    }

    public function minDone(int|Closure|null $count): static
    {
        $this->minDone = $count;

        return $this;
    }

    public function maxDone(int|Closure|null $count): static
    {
        $this->maxDone = $count;

        return $this;
    }

    public function exactDone(int|Closure|null $count): static
    {
        $this->exactDone = $count;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->evaluate($this->color);
    }

    public function soundsEnabled(): bool
    {
        return (bool) $this->evaluate($this->sounds);
    }

    public function getCheckSoundUrl(): string
    {
        return (string) ($this->evaluate($this->checkSound) ?: TodoListFieldAudio::url('todo-check.mp3'));
    }

    public function getAccentSoundUrl(): string
    {
        return (string) ($this->evaluate($this->accentSound) ?: TodoListFieldAudio::url('todo-accent.mp3'));
    }

    public function getCreateSoundUrl(): string
    {
        return (string) ($this->evaluate($this->createSound) ?: TodoListFieldAudio::url('todo-create.mp3'));
    }

    public function getCelebration(): string|false|null
    {
        $value = $this->evaluate($this->celebration);

        if ($value === false || $value === null || $value === '') {
            return $value === false ? false : null;
        }

        return (string) $value;
    }

    public function getCelebrationDurationMs(): int
    {
        return max(500, (int) $this->evaluate($this->celebrationDurationMs));
    }

    public function getCelebrationSoundUrl(): string
    {
        return (string) ($this->evaluate($this->celebrationSound) ?: TodoListFieldAudio::url('todo-fireworks-burst.mp3'));
    }

    public function getCelebrationStartSoundUrl(): string
    {
        return (string) ($this->evaluate($this->celebrationStartSound) ?: TodoListFieldAudio::url('todo-fireworks-start.mp3'));
    }

    /**
     * Per-celebration audio packs (start / burst). Field-level celebrationStartSound /
     * celebrationSound still override the fireworks pack used for complete-all.
     *
     * @return array<string, array{start?: string, burst?: string}>
     */
    public function getCelebrationAudio(): array
    {
        $fireworksStart = $this->getCelebrationStartSoundUrl();
        $fireworksBurst = $this->getCelebrationSoundUrl();

        return [
            'fireworks' => [
                'start' => $fireworksStart,
                'burst' => $fireworksBurst,
            ],
            'confetti' => [
                'start' => TodoListFieldAudio::url('todo-confetti.mp3'),
            ],
            'sparkles' => [
                'start' => TodoListFieldAudio::url('todo-sparkles.mp3'),
            ],
            'bloom' => [
                'start' => TodoListFieldAudio::url('todo-checks.mp3'),
            ],
        ];
    }

    public function isCelebrationFullscreen(): bool
    {
        return (bool) $this->evaluate($this->celebrationFullscreen);
    }

    public function getStrikethroughStyle(): string
    {
        $style = (string) $this->evaluate($this->strikethroughStyle);

        return in_array($style, ['hand', 'straight'], true) ? $style : 'hand';
    }

    public function getDoneSettleMs(): int
    {
        return max(0, (int) $this->evaluate($this->doneSettleMs));
    }

    public function canCreate(): bool
    {
        return (bool) $this->evaluate($this->allowCreate);
    }

    public function canCreateWithDescription(): bool
    {
        return $this->canCreate() && (bool) $this->evaluate($this->createWithDescription);
    }

    public function canDelete(): bool
    {
        return (bool) $this->evaluate($this->allowDelete);
    }

    public function getDeletableMode(): string
    {
        $mode = (string) $this->evaluate($this->deletable);

        if (! in_array($mode, ['created', 'all', 'none'], true)) {
            throw new InvalidArgumentException('TodoListField deletable mode must be created, all, or none.');
        }

        if ($mode === 'none' && $this->canCreate() && $this->canDelete()) {
            return 'created';
        }

        return $mode;
    }

    public function canEdit(): bool
    {
        return (bool) $this->evaluate($this->allowEdit);
    }

    public function getEditableMode(): string
    {
        $mode = (string) $this->evaluate($this->editable);

        if (! in_array($mode, ['created', 'all', 'none'], true)) {
            throw new InvalidArgumentException('TodoListField editable mode must be created, all, or none.');
        }

        return $mode;
    }

    public function canEditWithDescription(): bool
    {
        if ($this->editWithDescription === null) {
            return (bool) $this->evaluate($this->createWithDescription);
        }

        return (bool) $this->evaluate($this->editWithDescription);
    }

    public function hasEditUsing(): bool
    {
        return $this->editUsing instanceof Closure;
    }

    public function hasDeleteUsing(): bool
    {
        return $this->deleteUsing instanceof Closure;
    }

    public function hasReorderUsing(): bool
    {
        return $this->reorderUsing instanceof Closure;
    }

    public function isReorderable(): bool
    {
        if (! (bool) $this->evaluate($this->reorderable)) {
            return false;
        }

        // Virtual / infinite lists cannot safely own Sortable indices.
        if ($this->isVirtualizing() || $this->isInfiniteScroll()) {
            return false;
        }

        return true;
    }

    public function getReorderAnimationDuration(): int
    {
        return max(0, (int) $this->evaluate($this->reorderAnimationDuration));
    }

    public function hasUndoCompletionNotifications(): bool
    {
        return (bool) $this->evaluate($this->undoCompletionNotifications);
    }

    public function hasCreateUsing(): bool
    {
        return $this->createUsing instanceof Closure;
    }

    public function hasAfterToggled(): bool
    {
        return $this->afterToggled instanceof Closure;
    }

    /**
     * Livewire: create via Alpine → callSchemaComponentMethod(..., 'createTodoItem', ...).
     *
     * @param  array{item?: array<string, mixed>}  $arguments
     * @return array<string, mixed>|null
     */
    public function createTodoItem(array $arguments): ?array
    {
        $item = $arguments['item'] ?? null;

        if (! is_array($item) || ! isset($item['label'])) {
            return null;
        }

        if (! $this->hasCreateUsing()) {
            return $item;
        }

        $result = $this->evaluate($this->createUsing, [
            'item' => $item,
            'component' => $this,
        ]);

        return is_array($result) ? $result : null;
    }

    /**
     * Livewire: delete via Alpine → callSchemaComponentMethod(..., 'deleteTodoItem', ...).
     *
     * @param  array{id?: mixed, item?: array<string, mixed>}  $arguments
     */
    public function deleteTodoItem(array $arguments): bool
    {
        $id = $arguments['id'] ?? null;
        $item = is_array($arguments['item'] ?? null)
            ? $arguments['item']
            : $this->findItemInState($id);

        if (! is_array($item) || ! isset($item['id'])) {
            return false;
        }

        if (! $this->itemIsDeletable($item)) {
            return false;
        }

        $children = [];

        foreach ($item['children'] ?? [] as $child) {
            if (is_array($child) && isset($child['id'])) {
                $children[] = $child;
            }
        }

        $deletedIds = array_values(array_unique([
            (string) $item['id'],
            ...array_map(static fn (array $child): string => (string) $child['id'], $children),
        ]));

        if (! $this->hasDeleteUsing()) {
            return true;
        }

        $result = $this->evaluate($this->deleteUsing, [
            'item' => $item,
            'children' => $children,
            'deletedIds' => $deletedIds,
            'id' => (string) $item['id'],
            'component' => $this,
        ]);

        return $result !== false;
    }

    /**
     * Livewire: reorder via Alpine → callSchemaComponentMethod(..., 'reorderTodoItems', ...).
     *
     * @param  array{items?: list<array<string, mixed>>}  $arguments
     */
    public function reorderTodoItems(array $arguments): bool
    {
        $items = $arguments['items'] ?? null;

        if (! is_array($items)) {
            return false;
        }

        if (! $this->isReorderable()) {
            return false;
        }

        if (! $this->hasReorderUsing()) {
            return true;
        }

        $result = $this->evaluate($this->reorderUsing, [
            'items' => $items,
            'component' => $this,
        ]);

        return $result !== false;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function itemIsDeletable(array $item): bool
    {
        if (! $this->canDelete()) {
            return false;
        }

        if ((bool) ($item['disabled'] ?? $item['locked'] ?? false)) {
            return false;
        }

        return match ($this->getDeletableMode()) {
            'all' => true,
            'created' => (bool) ($item['created'] ?? false),
            default => false,
        };
    }

    /**
     * Livewire: toggle via Alpine → callSchemaComponentMethod(..., 'todoItemsToggled', ...).
     *
     * @param  array{changed?: list<array<string, mixed>>, items?: list<array<string, mixed>>}  $arguments
     */
    public function todoItemsToggled(array $arguments): void
    {
        if (! $this->hasAfterToggled()) {
            return;
        }

        $changed = is_array($arguments['changed'] ?? null) ? $arguments['changed'] : [];
        $items = is_array($arguments['items'] ?? null) ? $arguments['items'] : [];

        $this->evaluate($this->afterToggled, [
            'changed' => $changed,
            'items' => $items,
            'component' => $this,
        ]);
    }

    /**
     * @return list<Action>
     */
    public function getDefaultActions(): array
    {
        return [
            $this->makeEditTodoItemAction(),
        ];
    }

    protected function makeEditTodoItemAction(): Action
    {
        return Action::make('editTodoItem')
            ->label(__('filament-flex-fields::default.todo_list.edit_item'))
            ->modalHeading(__('filament-flex-fields::default.todo_list.edit_modal_heading'))
            ->modalSubmitActionLabel(__('filament-flex-fields::default.todo_list.edit_save'))
            ->modalWidth(Width::Medium)
            ->fillForm(function (array $arguments, TodoListField $component): array {
                $item = is_array($arguments['item'] ?? null)
                    ? $arguments['item']
                    : $component->findItemInState($arguments['id'] ?? null);

                return [
                    'label' => (string) ($item['label'] ?? ''),
                    'description' => (string) ($item['description'] ?? ''),
                ];
            })
            ->schema(function (TodoListField $component): array {
                $fields = [
                    FlexTextInput::make('label')
                        ->label(__('filament-flex-fields::default.todo_list.edit_label'))
                        ->placeholder($component->getCreatePlaceholder())
                        ->required()
                        ->maxLength(500)
                        ->autofocus(),
                ];

                if ($component->canEditWithDescription()) {
                    $fields[] = FlexTextInput::make('description')
                        ->label(__('filament-flex-fields::default.todo_list.edit_description'))
                        ->placeholder($component->getCreateDescriptionPlaceholder())
                        ->maxLength(2000);
                }

                return $fields;
            })
            ->action(function (array $data, array $arguments, TodoListField $component): void {
                $updated = $component->applyEditedTodoItem(
                    (string) ($arguments['id'] ?? ''),
                    $data,
                    is_array($arguments['item'] ?? null) ? $arguments['item'] : null,
                );

                if (! is_array($updated)) {
                    return;
                }

                // Alpine lives under wire:ignore — push the patched row so the list label updates immediately.
                $component->getLivewire()->dispatch($component->getEditSyncedEvent(), item: $updated);
            })
            ->visible(fn (TodoListField $component): bool => $component->canEdit());
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $clientItem  Alpine row (preferred under wire:ignore / deferred entangle)
     * @return array<string, mixed>|null
     */
    public function applyEditedTodoItem(string $id, array $data, ?array $clientItem = null): ?array
    {
        if ($id === '' || ! $this->canEdit()) {
            return null;
        }

        $needle = (string) $id;
        $label = trim((string) ($data['label'] ?? ''));

        if ($label === '') {
            return null;
        }

        $current = is_array($clientItem) && isset($clientItem['id'])
            ? $clientItem
            : $this->findItemInState($needle);

        if ($current === null || ! $this->itemIsEditable($current)) {
            return null;
        }

        $patch = [
            'label' => $label,
        ];

        if ($this->canEditWithDescription()) {
            $description = trim((string) ($data['description'] ?? ''));
            $patch['description'] = $description !== '' ? $description : null;
        }

        $candidate = array_merge($current, $patch);

        if ($this->hasEditUsing()) {
            $result = $this->evaluate($this->editUsing, [
                'item' => $current,
                'data' => array_merge($data, $patch),
                'children' => is_array($current['children'] ?? null) ? $current['children'] : [],
                'component' => $this,
            ]);

            if (! is_array($result)) {
                return null;
            }

            $candidate = array_merge($current, $result, [
                'id' => $current['id'],
            ]);

            if (! isset($candidate['label']) || trim((string) $candidate['label']) === '') {
                return null;
            }
        }

        // Keep nested children when editing a parent (editUsing must not wipe them).
        if (array_key_exists('children', $current) && ! array_key_exists('children', $candidate)) {
            $candidate['children'] = $current['children'];
        }

        $state = $this->normalizeState(is_array($this->getState()) ? $this->getState() : []);
        $this->state($this->mapReplaceItemById($state, $needle, $candidate));

        return $candidate;
    }

    public function getEditSyncedEvent(): string
    {
        return 'fff-todo-list-edited-'.preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $this->getStatePath());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findItemInState(mixed $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        $needle = (string) $id;
        $state = $this->normalizeState(is_array($this->getState()) ? $this->getState() : []);

        foreach ($state as $item) {
            if ((string) ($item['id'] ?? '') === $needle) {
                return $item;
            }

            foreach ($item['children'] ?? [] as $child) {
                if (! is_array($child)) {
                    continue;
                }

                if ((string) ($child['id'] ?? '') === $needle) {
                    return $child;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function itemIsEditable(array $item): bool
    {
        if (! $this->canEdit()) {
            return false;
        }

        if ((bool) ($item['disabled'] ?? $item['locked'] ?? false)) {
            return false;
        }

        return match ($this->getEditableMode()) {
            'all' => true,
            'created' => (bool) ($item['created'] ?? false),
            default => false,
        };
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $replacement
     * @return list<array<string, mixed>>
     */
    protected function mapReplaceItemById(array $items, string $needle, array $replacement): array
    {
        $next = [];

        foreach ($items as $item) {
            if ((string) ($item['id'] ?? '') === $needle) {
                $next[] = array_merge($item, $replacement, [
                    'id' => $item['id'],
                    'children' => $item['children'] ?? [],
                ]);

                continue;
            }

            $children = [];

            foreach ($item['children'] ?? [] as $child) {
                if (! is_array($child)) {
                    continue;
                }

                if ((string) ($child['id'] ?? '') === $needle) {
                    $children[] = array_merge($child, $replacement, [
                        'id' => $child['id'],
                    ]);

                    continue;
                }

                $children[] = $child;
            }

            $item['children'] = $children;
            $next[] = $item;
        }

        return $next;
    }

    public function getCreateLabel(): string
    {
        return (string) ($this->evaluate($this->createLabel) ?: __('filament-flex-fields::default.todo_list.create_option'));
    }

    public function getCreatePlaceholder(): string
    {
        return (string) ($this->evaluate($this->createPlaceholder) ?: __('filament-flex-fields::default.todo_list.create_placeholder'));
    }

    public function getCreateDescriptionPlaceholder(): string
    {
        return (string) ($this->evaluate($this->createDescriptionPlaceholder) ?: __('filament-flex-fields::default.todo_list.create_description_placeholder'));
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->searchable);
    }

    public function getSearchPrompt(): string
    {
        return (string) ($this->evaluate($this->searchPrompt) ?: __('filament-flex-fields::default.todo_list.search_prompt'));
    }

    public function isVirtualizing(): bool
    {
        return (bool) $this->evaluate($this->virtualizing);
    }

    public function getVirtualItemHeight(): int
    {
        return max(32, (int) $this->evaluate($this->virtualItemHeight));
    }

    public function getPageSize(): ?int
    {
        $value = $this->evaluate($this->paginated);

        if ($value === false || $value === null) {
            $infinite = $this->evaluate($this->infiniteScroll);

            if (is_int($infinite)) {
                return max(1, $infinite);
            }

            return null;
        }

        if ($value === true) {
            return 10;
        }

        return max(1, (int) $value);
    }

    public function isInfiniteScroll(): bool
    {
        if ($this->getPageSize() !== null) {
            return true;
        }

        return (bool) $this->evaluate($this->infiniteScroll);
    }

    public function getRemoteLoader(): ?string
    {
        $loader = $this->evaluate($this->remoteLoader);

        return filled($loader) ? (string) $loader : null;
    }

    public function shouldPersistCompletedOrder(): bool
    {
        return (bool) $this->evaluate($this->persistCompletedOrder);
    }

    public function getMaxItems(): ?int
    {
        $count = $this->evaluate($this->maxItems);

        return $count === null ? null : (int) $count;
    }

    public function getMinDone(): ?int
    {
        $count = $this->evaluate($this->minDone);

        return $count === null ? null : (int) $count;
    }

    public function getMaxDone(): ?int
    {
        $count = $this->evaluate($this->maxDone);

        return $count === null ? null : (int) $count;
    }

    public function getExactDone(): ?int
    {
        $count = $this->evaluate($this->exactDone);

        return $count === null ? null : (int) $count;
    }

    public function getPlusIcon(): string
    {
        return GravityIcon::CirclePlusFill;
    }

    public function getDeleteIcon(): string
    {
        return GravityIcon::Xmark;
    }

    public function getEditIcon(): string|BackedEnum|Htmlable
    {
        return $this->evaluate($this->editIcon) ?: GravityIcon::Pencil;
    }

    public function getReorderIcon(): string
    {
        return GravityIcon::Grip;
    }

    public function getChildrenIcon(): string
    {
        return GravityIcon::CodeTrunk;
    }

    public function getDateIcon(): string
    {
        return GravityIcon::Calendar;
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-todo-list-field',
            'fff-todo-list-field--'.$this->getSize(),
            'fff-todo-list-field--strike-'.$this->getStrikethroughStyle(),
        ];

        if ($this->isReorderable()) {
            $classes[] = 'fff-todo-list-field--reorderable';
        }

        if ($color = $this->getColor()) {
            $classes[] = 'fi-color-'.$color;
        }

        return $classes;
    }

    /**
     * @return array<string, string>
     */
    public function getSizeStyles(): array
    {
        return match ($this->getSize()) {
            'sm' => [
                '--fff-todo-list-radius' => '0.625rem',
                '--fff-todo-list-row-py' => '0.5rem',
                '--fff-todo-list-row-px' => '0.625rem',
                '--fff-todo-list-gap' => '0.625rem',
                '--fff-todo-list-label-size' => '0.8125rem',
                '--fff-todo-list-body-size' => '0.6875rem',
                '--fff-todo-list-check-size' => '1.125rem',
            ],
            'lg' => [
                '--fff-todo-list-radius' => '0.875rem',
                '--fff-todo-list-row-py' => '0.875rem',
                '--fff-todo-list-row-px' => '1rem',
                '--fff-todo-list-gap' => '0.875rem',
                '--fff-todo-list-label-size' => '1rem',
                '--fff-todo-list-body-size' => '0.8125rem',
                '--fff-todo-list-check-size' => '1.375rem',
            ],
            default => [
                '--fff-todo-list-radius' => '0.75rem',
                '--fff-todo-list-row-py' => '0.625rem',
                '--fff-todo-list-row-px' => '0.75rem',
                '--fff-todo-list-gap' => '0.75rem',
                '--fff-todo-list-label-size' => '0.875rem',
                '--fff-todo-list-body-size' => '0.75rem',
                '--fff-todo-list-check-size' => '1.25rem',
            ],
        };
    }

    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     description: ?string,
     *     done: bool,
     *     disabled: bool,
     *     created: bool,
     *     sound: ?string,
     *     celebration: string|false|null,
     *     deletable: bool
     * }>
     */
    public function getItemsForJs(): array
    {
        $state = $this->normalizeState(is_array($this->getState()) ? $this->getState() : []);

        if ($state !== []) {
            return array_map(fn (array $item): array => $this->presentItem($item), $state);
        }

        return array_map(
            fn (array $item): array => $this->presentItem($item),
            $this->seedItemsFromOptions(),
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{
     *     id: string,
     *     label: string,
     *     description: ?string,
     *     done: bool,
     *     disabled: bool,
     *     created: bool,
     *     sound: ?string,
     *     celebration: string|false|null,
     *     deletable: bool
     * }
     */
    protected function presentItem(array $item): array
    {
        $deleteMode = $this->getDeletableMode();
        $editMode = $this->getEditableMode();
        $created = (bool) ($item['created'] ?? false);
        $disabled = (bool) ($item['disabled'] ?? $item['locked'] ?? false);
        $deletable = $this->canDelete() && ! $disabled && match ($deleteMode) {
            'all' => true,
            'created' => $created,
            default => false,
        };
        $editable = $this->canEdit() && ! $disabled && match ($editMode) {
            'all' => true,
            'created' => $created,
            default => false,
        };

        $children = [];

        foreach ($item['children'] ?? [] as $child) {
            if (! is_array($child) || ! isset($child['id'], $child['label'])) {
                continue;
            }

            $childCreated = (bool) ($child['created'] ?? false);
            $childDisabled = (bool) ($child['disabled'] ?? $child['locked'] ?? false);
            $children[] = [
                'id' => (string) $child['id'],
                'label' => (string) $child['label'],
                'description' => $child['description'] ?? null,
                'done' => (bool) ($child['done'] ?? false),
                'disabled' => $childDisabled,
                'created' => $childCreated,
                'sound' => $child['sound'] ?? null,
                'celebration' => array_key_exists('celebration', $child) ? $child['celebration'] : null,
                'celebrationFullscreen' => (bool) ($child['celebrationFullscreen'] ?? $child['celebration_fullscreen'] ?? false),
                'deletable' => $this->canDelete() && ! $childDisabled && match ($deleteMode) {
                    'all' => true,
                    'created' => $childCreated,
                    default => false,
                },
                'editable' => $this->canEdit() && ! $childDisabled && match ($editMode) {
                    'all' => true,
                    'created' => $childCreated,
                    default => false,
                },
            ];
        }

        return [
            'id' => (string) $item['id'],
            'label' => (string) $item['label'],
            'description' => $item['description'] ?? null,
            'done' => (bool) ($item['done'] ?? false),
            'disabled' => $disabled,
            'created' => $created,
            'sound' => $item['sound'] ?? null,
            'celebration' => array_key_exists('celebration', $item) ? $item['celebration'] : null,
            'celebrationFullscreen' => (bool) ($item['celebrationFullscreen'] ?? $item['celebration_fullscreen'] ?? false),
            'deletable' => $deletable,
            'editable' => $editable,
            'children' => $children,
            'date' => isset($item['date']) ? (string) $item['date'] : null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function seedItemsFromOptions(): array
    {
        $options = $this->evaluate($this->options) ?? [];
        $items = [];

        foreach ($options as $key => $option) {
            if (is_string($option)) {
                $items[] = [
                    'id' => (string) $key,
                    'label' => $option,
                    'description' => null,
                    'done' => false,
                    'disabled' => false,
                    'created' => false,
                    'sound' => null,
                    'celebration' => null,
                    'celebrationFullscreen' => false,
                    'children' => [],
                    'date' => null,
                ];

                continue;
            }

            if (! is_array($option)) {
                continue;
            }

            $items[] = [
                'id' => (string) ($option['id'] ?? $key),
                'label' => (string) ($option['label'] ?? $key),
                'description' => isset($option['description']) ? (string) $option['description'] : (isset($option['desc']) ? (string) $option['desc'] : null),
                'done' => (bool) ($option['done'] ?? false),
                'disabled' => (bool) ($option['disabled'] ?? $option['locked'] ?? false),
                'created' => (bool) ($option['created'] ?? false),
                'sound' => isset($option['sound']) ? (string) $option['sound'] : null,
                'celebration' => array_key_exists('celebration', $option) ? $option['celebration'] : null,
                'celebrationFullscreen' => (bool) ($option['celebrationFullscreen'] ?? $option['celebration_fullscreen'] ?? false),
                'children' => $this->normalizeChildren($option['children'] ?? []),
                'date' => isset($option['date']) ? (string) $option['date'] : null,
            ];
        }

        return $items;
    }

    /**
     * @param  array<string|int, mixed>  $children
     * @return list<array<string, mixed>>
     */
    protected function normalizeChildren(array $children): array
    {
        $normalized = [];

        foreach ($children as $key => $child) {
            if (is_string($child)) {
                $normalized[] = [
                    'id' => (string) $key,
                    'label' => $child,
                    'description' => null,
                    'done' => false,
                    'disabled' => false,
                    'created' => false,
                    'sound' => null,
                    'celebration' => null,
                    'celebrationFullscreen' => false,
                ];

                continue;
            }

            if (! is_array($child)) {
                continue;
            }

            $normalized[] = [
                'id' => (string) ($child['id'] ?? $key),
                'label' => (string) ($child['label'] ?? $key),
                'description' => isset($child['description']) ? (string) $child['description'] : null,
                'done' => (bool) ($child['done'] ?? false),
                'disabled' => (bool) ($child['disabled'] ?? $child['locked'] ?? false),
                'created' => (bool) ($child['created'] ?? false),
                'sound' => isset($child['sound']) ? (string) $child['sound'] : null,
                'celebration' => array_key_exists('celebration', $child) ? $child['celebration'] : null,
                'celebrationFullscreen' => (bool) ($child['celebrationFullscreen'] ?? $child['celebration_fullscreen'] ?? false),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, mixed>  $state
     * @return list<array<string, mixed>>
     */
    public function normalizeState(array $state): array
    {
        if ($state === []) {
            return $this->seedItemsFromOptions();
        }

        // Legacy: bare selected keys → merge onto seeded options
        if ($this->looksLikeKeyList($state)) {
            $selected = collect($state)->map(fn (mixed $key): string => (string) $key)->all();
            $items = $this->seedItemsFromOptions();

            foreach ($items as &$item) {
                $item['done'] = in_array((string) $item['id'], $selected, true);
            }

            return array_values($items);
        }

        $normalized = [];

        foreach ($state as $row) {
            if (! is_array($row) || ! isset($row['id'], $row['label'])) {
                continue;
            }

            $normalized[] = [
                'id' => (string) $row['id'],
                'label' => (string) $row['label'],
                'description' => isset($row['description']) ? (string) $row['description'] : null,
                'done' => (bool) ($row['done'] ?? false),
                'disabled' => (bool) ($row['disabled'] ?? $row['locked'] ?? false),
                'created' => (bool) ($row['created'] ?? ! $this->isSeededOptionKey((string) $row['id'])),
                'sound' => isset($row['sound']) ? (string) $row['sound'] : null,
                'celebration' => array_key_exists('celebration', $row) ? $row['celebration'] : null,
                'celebrationFullscreen' => (bool) ($row['celebrationFullscreen'] ?? $row['celebration_fullscreen'] ?? false),
                'children' => $this->normalizeChildren(is_array($row['children'] ?? null) ? $row['children'] : []),
                'date' => isset($row['date']) ? (string) $row['date'] : null,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, mixed>  $state
     */
    protected function looksLikeKeyList(array $state): bool
    {
        if ($state === []) {
            return false;
        }

        foreach ($state as $row) {
            if (is_array($row)) {
                return false;
            }
        }

        return true;
    }

    protected function isSeededOptionKey(string $id): bool
    {
        foreach ($this->seedItemsFromOptions() as $item) {
            if ((string) $item['id'] === $id && ! ($item['created'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAlpineConfig(): array
    {
        return [
            'items' => $this->getItemsForJs(),
            'disabled' => $this->resolveDisabledForAlpine(),
            'sounds' => $this->soundsEnabled(),
            'checkSound' => $this->getCheckSoundUrl(),
            'accentSound' => $this->getAccentSoundUrl(),
            'createSound' => $this->getCreateSoundUrl(),
            'celebration' => $this->getCelebration(),
            'celebrationDurationMs' => $this->getCelebrationDurationMs(),
            'celebrationSound' => $this->getCelebrationSoundUrl(),
            'celebrationStartSound' => $this->getCelebrationStartSoundUrl(),
            'celebrationAudio' => $this->getCelebrationAudio(),
            'celebrationFullscreen' => $this->isCelebrationFullscreen(),
            'strikethroughStyle' => $this->getStrikethroughStyle(),
            'doneSettleMs' => $this->getDoneSettleMs(),
            'allowCreate' => $this->canCreate(),
            'createWithDescription' => $this->canCreateWithDescription(),
            'allowDelete' => $this->canDelete(),
            'deletableMode' => $this->getDeletableMode(),
            'allowEdit' => $this->canEdit(),
            'editableMode' => $this->getEditableMode(),
            'editWithDescription' => $this->canEditWithDescription(),
            'reorderable' => $this->isReorderable(),
            'reorderAnimationDuration' => $this->getReorderAnimationDuration(),
            'undoCompletionNotifications' => $this->hasUndoCompletionNotifications(),
            'undoEvent' => 'fff-todo-list-undo-'.preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $this->getStatePath()),
            'editSyncedEvent' => $this->getEditSyncedEvent(),
            'componentKey' => $this->getKey(),
            'createUsingEnabled' => $this->hasCreateUsing(),
            'editUsingEnabled' => $this->hasEditUsing(),
            'deleteUsingEnabled' => $this->hasDeleteUsing(),
            'reorderUsingEnabled' => $this->hasReorderUsing(),
            'afterToggledEnabled' => $this->hasAfterToggled(),
            'labels' => [
                'taskCompleted' => __('filament-flex-fields::default.todo_list.task_completed'),
                'tasksCompleted' => __('filament-flex-fields::default.todo_list.tasks_completed'),
                'undo' => __('filament-flex-fields::default.todo_list.undo'),
                'editItem' => __('filament-flex-fields::default.todo_list.edit_item'),
            ],
            'createLabel' => $this->getCreateLabel(),
            'createPlaceholder' => $this->getCreatePlaceholder(),
            'createDescriptionPlaceholder' => $this->getCreateDescriptionPlaceholder(),
            'searchable' => $this->isSearchable(),
            'searchPrompt' => $this->getSearchPrompt(),
            'virtualizing' => $this->isVirtualizing(),
            'pageSize' => $this->getPageSize(),
            'infiniteScroll' => $this->isInfiniteScroll(),
            'remoteLoader' => $this->getRemoteLoader(),
            'persistCompletedOrder' => $this->shouldPersistCompletedOrder(),
            'maxItems' => $this->getMaxItems(),
        ];
    }

    protected function resolveDisabledForAlpine(): bool
    {
        try {
            return $this->isDisabled();
        } catch (\Error) {
            return false;
        }
    }

    public static function makeNewItemId(): string
    {
        return (string) Str::uuid();
    }
}

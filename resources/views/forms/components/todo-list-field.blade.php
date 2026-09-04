@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Filament\Support\Facades\FilamentAsset;
    use Illuminate\Support\Js;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $wrapperClasses = $getWrapperClasses();
    $alpineConfig = $getAlpineConfig();
    $ssrItems = $getItemsForJs();
    $livewireKey = $getLivewireKey();
    $todoAssetSrc = FilamentAsset::getAlpineComponentSrc('todo-list-field', FilamentFlexFieldsPlugin::PACKAGE_NAME);
    $instanceId = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($getId() ?: $statePath));
    $isReorderable = $isReorderable();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'todo-list-field',
        'livewireKey' => $livewireKey,
    ])

    @once
        @if (filled($todoAssetSrc))
            <link
                rel="modulepreload"
                href="{{ $todoAssetSrc }}"
                as="script"
                crossorigin
            />
        @endif
    @endonce

    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $getSize(), $isSearchable(), $canCreate(), $isReorderable])), 0, 64) }}"
    >
        <div
            x-load
            x-load-src="{{ $todoAssetSrc }}"
            x-data="todoListFieldFormComponent({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                ...{{ Js::from($alpineConfig) }},
            })"
            @class([
                ...$wrapperClasses,
                'is-disabled' => $isDisabled,
            ])
            @style($getSizeStyles())
            role="group"
            aria-label="{{ $getLabel() }}"
        >
            @if ($isSearchable())
                <div class="fff-todo-list-field__search">
                    <input
                        type="search"
                        class="fff-todo-list-field__search-input"
                        x-model.debounce.150ms="search"
                        placeholder="{{ $getSearchPrompt() }}"
                        @disabled($isDisabled)
                        x-bind:disabled="disabled"
                        autocomplete="off"
                    />
                </div>
            @endif

            <div class="fff-todo-list-field__body">
            @include('filament-flex-fields::forms.components.partials.todo-list-field-ssr', [
                'ssrItems' => $ssrItems,
                'allowCreate' => $canCreate(),
                'createLabel' => $getCreateLabel(),
                'createIcon' => $getPlusIcon(),
                'childrenIcon' => $getChildrenIcon(),
                'dateIcon' => $getDateIcon(),
                'reorderIcon' => $getReorderIcon(),
                'isDisabled' => $isDisabled,
                'virtualizing' => $isVirtualizing(),
                'infiniteScroll' => $isInfiniteScroll(),
                'isReorderable' => $isReorderable,
                'canEdit' => $canEdit(),
                'canDelete' => $canDelete(),
            ])

            <div class="fff-todo-list-field__live">
                <div class="fff-todo-list-field__shell">
                    <canvas
                        class="fff-todo-list-field__celebration"
                        x-ref="celebration"
                        aria-hidden="true"
                    ></canvas>

                    <div class="fff-todo-list-field__scroll-pane">
                    <div
                        class="fff-todo-list-field__viewport"
                        :class="{
                            'is-virtual': virtualizing && ! infiniteScroll,
                            'is-infinite': infiniteScroll,
                        }"
                        x-ref="viewport"
                        @scroll="onScroll($event)"
                    >
                        <div
                            class="fff-todo-list-field__list"
                            role="list"
                            :style="listStyle()"
                            @if ($isReorderable)
                                x-sortable
                                data-sortable-animation-duration="{{ $getReorderAnimationDuration() }}"
                                x-on:end.stop="reorderItems($event)"
                            @endif
                        >
                            <template x-for="(item, index) in renderedItems().items" :key="item.id">
                                <div
                                    class="fff-todo-list-field__group"
                                    :class="{ 'has-children': hasChildren(item) }"
                                    :data-todo-group-id="item.id"
                                    role="listitem"
                                    @if ($isReorderable)
                                        x-bind:x-sortable-item="index"
                                    @endif
                                >
                                    <div
                                        class="fff-todo-list-field__row"
                                        :data-todo-id="item.id"
                                        :class="{
                                            'is-done': isTodoDone(item.id),
                                            'is-settled': isTodoDone(item.id) && isSettledId(item.id),
                                            'is-disabled': disabled || item.disabled,
                                            'is-reorderable': canReorderItem(item),
                                            'is-entering': Boolean(enteringIds[String(item.id)]),
                                            'is-exiting': Boolean(exitingIds[String(item.id)]),
                                        }"
                                        :aria-disabled="disabled || item.disabled ? 'true' : null"
                                        :tabindex="disabled || item.disabled ? -1 : 0"
                                        @click="toggle(item.id, $event)"
                                        @keydown.enter.prevent="toggle(item.id, $event)"
                                        @keydown.space.prevent="toggle(item.id, $event)"
                                    >
                                        @if ($isReorderable)
                                            <button
                                                type="button"
                                                class="fff-todo-list-field__drag"
                                                :class="{ 'is-locked': ! canReorderItem(item) }"
                                                x-sortable-handle
                                                @click.stop.prevent
                                                tabindex="-1"
                                                :aria-hidden="canReorderItem(item) ? 'false' : 'true'"
                                                :disabled="! canReorderItem(item)"
                                                aria-label="{{ __('filament-flex-fields::default.todo_list.reorder_item') }}"
                                            >
                                                <x-filament::icon
                                                    :icon="$getReorderIcon()"
                                                    class="fff-todo-list-field__drag-icon h-4 w-4"
                                                />
                                            </button>
                                        @endif

                                        @include('filament-flex-fields::forms.components.partials.todo-list-field-checkbox', [
                                            'todoVar' => 'item',
                                            'instanceId' => $instanceId,
                                        ])

                                        <div class="fff-todo-list-field__content">
                                            <span class="fff-todo-list-field__title-wrap">
                                                <span class="fff-todo-list-field__label" x-text="item.label"></span>
                                                <span class="fff-todo-list-field__strikes" aria-hidden="true">
                                                    <svg
                                                        class="fff-todo-list-field__strike"
                                                        viewBox="0 0 100 8"
                                                        preserveAspectRatio="none"
                                                    >
                                                        @include('filament-flex-fields::forms.components.partials.todo-list-strike-paths')
                                                    </svg>
                                                </span>
                                            </span>
                                            <template x-if="item.description">
                                                <span class="fff-todo-list-field__description" x-text="item.description"></span>
                                            </template>
                                            @include('filament-flex-fields::forms.components.partials.todo-list-field-meta', [
                                                'childrenIcon' => $getChildrenIcon(),
                                                'dateIcon' => $getDateIcon(),
                                            ])
                                        </div>

                                        <div
                                            class="fff-todo-list-field__actions"
                                            x-show="actionSlotCount() > 0"
                                            x-cloak
                                            @click.stop
                                        >
                                            <template x-if="allowEdit">
                                                <span class="fff-todo-list-field__action-slot">
                                                    <button
                                                        type="button"
                                                        class="fff-todo-list-field__action fff-todo-list-field__edit"
                                                        x-show="canEditItem(item)"
                                                        x-cloak
                                                        @click="editItem(item.id, $event)"
                                                        :aria-label="labels.editItem || '{{ __('filament-flex-fields::default.todo_list.edit_item') }}'"
                                                    >
                                                        <x-filament::icon
                                                            :icon="$getEditIcon()"
                                                            class="fff-todo-list-field__action-icon h-4 w-4"
                                                        />
                                                    </button>
                                                </span>
                                            </template>
                                            <template x-if="allowDelete">
                                                <span class="fff-todo-list-field__action-slot">
                                                    <button
                                                        type="button"
                                                        class="fff-todo-list-field__action fff-todo-list-field__delete"
                                                        x-show="canDeleteItem(item)"
                                                        x-cloak
                                                        @click="removeItem(item.id, $event)"
                                                        :aria-label="'{{ __('filament-flex-fields::default.todo_list.delete_item') }}'"
                                                    >
                                                        <x-filament::icon
                                                            :icon="$getDeleteIcon()"
                                                            class="fff-todo-list-field__action-icon h-4 w-4"
                                                        />
                                                    </button>
                                                </span>
                                            </template>
                                        </div>
                                    </div>

                                    <div
                                        class="fff-todo-list-field__substack"
                                        x-show="hasChildren(item)"
                                    >
                                        <template x-for="child in (item.children || [])" :key="child.id">
                                            <div
                                                class="fff-todo-list-field__row fff-todo-list-field__row--child"
                                                :data-todo-id="child.id"
                                                :class="{
                                                    'is-done': isTodoDone(child.id),
                                                    'is-settled': isTodoDone(child.id) && isSettledId(child.id),
                                                    'is-disabled': disabled || child.disabled,
                                                    'is-entering': Boolean(enteringIds[String(child.id)]),
                                                    'is-exiting': Boolean(exitingIds[String(child.id)]),
                                                }"
                                                :aria-disabled="disabled || child.disabled ? 'true' : null"
                                                :tabindex="disabled || child.disabled ? -1 : 0"
                                                @click="toggle(child.id, $event)"
                                                @keydown.enter.prevent="toggle(child.id, $event)"
                                                @keydown.space.prevent="toggle(child.id, $event)"
                                            >
                                                @include('filament-flex-fields::forms.components.partials.todo-list-field-checkbox', [
                                                    'todoVar' => 'child',
                                                    'instanceId' => $instanceId,
                                                ])

                                                <div class="fff-todo-list-field__content">
                                                    <span class="fff-todo-list-field__title-wrap">
                                                        <span class="fff-todo-list-field__label" x-text="child.label"></span>
                                                        <span class="fff-todo-list-field__strikes" aria-hidden="true">
                                                            <svg
                                                                class="fff-todo-list-field__strike"
                                                                viewBox="0 0 100 8"
                                                                preserveAspectRatio="none"
                                                            >
                                                                @include('filament-flex-fields::forms.components.partials.todo-list-strike-paths')
                                                            </svg>
                                                        </span>
                                                    </span>
                                                    <template x-if="child.description">
                                                        <span class="fff-todo-list-field__description" x-text="child.description"></span>
                                                    </template>
                                                </div>

                                                <div
                                                    class="fff-todo-list-field__actions"
                                                    x-show="actionSlotCount() > 0"
                                                    x-cloak
                                                    @click.stop
                                                >
                                                    <template x-if="allowEdit">
                                                        <span class="fff-todo-list-field__action-slot">
                                                            <button
                                                                type="button"
                                                                class="fff-todo-list-field__action fff-todo-list-field__edit"
                                                                x-show="canEditItem(child)"
                                                                x-cloak
                                                                @click="editItem(child.id, $event)"
                                                                :aria-label="labels.editItem || '{{ __('filament-flex-fields::default.todo_list.edit_item') }}'"
                                                            >
                                                                <x-filament::icon
                                                                    :icon="$getEditIcon()"
                                                                    class="fff-todo-list-field__action-icon h-4 w-4"
                                                                />
                                                            </button>
                                                        </span>
                                                    </template>
                                                    <template x-if="allowDelete">
                                                        <span class="fff-todo-list-field__action-slot">
                                                            <button
                                                                type="button"
                                                                class="fff-todo-list-field__action fff-todo-list-field__delete"
                                                                x-show="canDeleteItem(child)"
                                                                x-cloak
                                                                @click="removeItem(child.id, $event)"
                                                                :aria-label="'{{ __('filament-flex-fields::default.todo_list.delete_item') }}'"
                                                            >
                                                                <x-filament::icon
                                                                    :icon="$getDeleteIcon()"
                                                                    class="fff-todo-list-field__action-icon h-4 w-4"
                                                                />
                                                            </button>
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <template x-if="infiniteScroll && hasMore && ! loadingMore">
                            <div class="fff-todo-list-field__load-more" x-ref="sentinel">
                                <span x-show="! remoteLoader">{{ __('filament-flex-fields::default.todo_list.scroll_for_more') }}</span>
                            </div>
                        </template>
                        <template x-if="infiniteScroll && loadingMore">
                            <div class="fff-todo-list-field__load-more">{{ __('filament-flex-fields::default.todo_list.loading_more') }}</div>
                        </template>
                    </div>

                    <div
                        class="fff-todo-list-field__scrollbar"
                        x-ref="scrollbar"
                        data-visible="false"
                        data-active="false"
                        aria-hidden="true"
                    >
                        <div
                            class="fff-todo-list-field__scrollbar-thumb"
                            x-ref="scrollbarThumb"
                        ></div>
                    </div>
                    </div>

                    <template x-if="allowCreate">
                        <div
                            class="fff-todo-list-field__create-dock"
                            x-ref="createDock"
                            @focusout="onCreateDockFocusOut($event)"
                        >
                            <div
                                class="fff-todo-list-field__row fff-todo-list-field__row--create"
                                :class="{
                                    'is-creating': creating,
                                    'is-create-with-description': createWithDescription,
                                    'is-create-busy': createBusy,
                                    'is-create-disabled': ! canAddMore() || disabled || createBusy,
                                    'is-disabled': disabled,
                                }"
                                role="listitem"
                                :aria-busy="createBusy ? 'true' : null"
                                :tabindex="canAddMore() && ! disabled && ! createBusy ? 0 : -1"
                                @click="onCreateRowClick($event)"
                                @keydown.enter.prevent="! creating && startCreate()"
                            >
                                <span
                                    class="fff-todo-list-field__create-icon"
                                    aria-hidden="true"
                                    @mousedown="onCreateIconMouseDown($event)"
                                    @click.stop="onCreateIconClick($event)"
                                >
                                    <x-filament::icon
                                        :icon="$getPlusIcon()"
                                        class="fff-todo-list-field__plus"
                                    />
                                </span>

                                <div class="fff-todo-list-field__content">
                                    <template x-if="! creating">
                                        <span class="fff-todo-list-field__label" x-text="createLabel"></span>
                                    </template>
                                    <template x-if="creating">
                                        <div class="fff-todo-list-field__create-fields">
                                            <input
                                                type="text"
                                                class="fff-todo-list-field__create-input"
                                                x-ref="createInput"
                                                x-model="createDraft"
                                                x-bind:placeholder="createPlaceholder"
                                                x-bind:disabled="createBusy"
                                                @click.stop
                                                @keydown.enter.prevent="onCreateLabelEnter()"
                                                @keydown.escape.prevent="cancelCreate()"
                                            />
                                            <template x-if="createWithDescription">
                                                <input
                                                    type="text"
                                                    class="fff-todo-list-field__create-description"
                                                    x-ref="createDescriptionInput"
                                                    x-model="createDescriptionDraft"
                                                    x-bind:placeholder="createDescriptionPlaceholder"
                                                    x-bind:disabled="createBusy"
                                                    @click.stop
                                                    @keydown.enter.prevent="confirmCreate()"
                                                    @keydown.escape.prevent="cancelCreate()"
                                                />
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

{{--
    SSR list shell for TodoListField — painted until Alpine adds is-hydrated (TagsField pattern).
    Search sits outside this shell so it does not remount on hydrate.
--}}
@php
    /** @var list<array<string, mixed>> $ssrItems */
    $ssrItems = $ssrItems ?? [];
    $allowCreate = (bool) ($allowCreate ?? false);
    $createLabel = $createLabel ?? '';
    $createIcon = $createIcon ?? null;
    $childrenIcon = $childrenIcon ?? null;
    $dateIcon = $dateIcon ?? null;
    $reorderIcon = $reorderIcon ?? null;
    $isDisabled = (bool) ($isDisabled ?? false);
    $virtualizing = (bool) ($virtualizing ?? false);
    $infiniteScroll = (bool) ($infiniteScroll ?? false);
    $isReorderable = (bool) ($isReorderable ?? false);
    $canEdit = (bool) ($canEdit ?? false);
    $canDelete = (bool) ($canDelete ?? false);
@endphp

<div class="fff-todo-list-field__ssr" aria-hidden="true">
    <div class="fff-todo-list-field__shell">
        <div class="fff-todo-list-field__scroll-pane">
        <div
            @class([
                'fff-todo-list-field__viewport',
                'is-virtual' => $virtualizing && ! $infiniteScroll,
                'is-infinite' => $infiniteScroll,
            ])
        >
            <div class="fff-todo-list-field__list" role="list">
                @foreach ($ssrItems as $item)
                    @php
                        $done = (bool) ($item['done'] ?? false);
                        $children = is_array($item['children'] ?? null) ? $item['children'] : [];
                        $hasChildren = $children !== [];
                        $doneChildren = collect($children)->where('done', true)->count();
                        $hasMeta = $hasChildren
                            || filled($item['date'] ?? null);
                    @endphp
                    <div @class([
                        'fff-todo-list-field__group',
                        'has-children' => $hasChildren,
                    ]) role="listitem">
                        <div
                            @class([
                                'fff-todo-list-field__row',
                                'is-done' => $done,
                                'is-settled' => $done,
                                'is-disabled' => $isDisabled || ($item['disabled'] ?? false),
                            ])
                            @if ($done)
                                style="--text-line-scale: 1; --text-x: 0px; --checkbox-tick-offset: 0;"
                            @endif
                        >
                            @if ($isReorderable)
                                <span
                                    @class([
                                        'fff-todo-list-field__drag',
                                        'is-locked' => $item['disabled'] ?? false,
                                    ])
                                    aria-hidden="true"
                                >
                                    @if (! ($item['disabled'] ?? false) && filled($reorderIcon))
                                        <x-filament::icon
                                            :icon="$reorderIcon"
                                            class="fff-todo-list-field__drag-icon h-4 w-4"
                                        />
                                    @endif
                                </span>
                            @endif
                            @if ($done)
                                @include('filament-flex-fields::forms.components.partials.todo-list-field-checkbox-done-ssr', [
                                    'tickKey' => (string) ($item['id'] ?? uniqid('p', false)),
                                ])
                            @else
                                <span class="fff-todo-list-field__checkbox" aria-hidden="true">
                                    <span class="fff-todo-list-field__checkbox-input"></span>
                                    <svg class="fff-todo-list-field__checkbox-svg" viewBox="0 0 21 18">
                                        <path
                                            class="fff-todo-list-field__shape"
                                            d="M1.08722 4.13374C1.29101 2.53185 2.53185 1.29101 4.13374 1.08722C5.50224 0.913124 7.25112 0.75 9 0.75C10.7489 0.75 12.4978 0.913124 13.8663 1.08722C15.4681 1.29101 16.709 2.53185 16.9128 4.13374C17.0869 5.50224 17.25 7.25112 17.25 9C17.25 10.7489 17.0869 12.4978 16.9128 13.8663C16.709 15.4681 15.4682 16.709 13.8663 16.9128C12.4978 17.0869 10.7489 17.25 9 17.25C7.25112 17.25 5.50224 17.0869 4.13374 16.9128C2.53185 16.709 1.29101 15.4681 1.08722 13.8663C0.913124 12.4978 0.75 10.7489 0.75 9C0.75 7.25112 0.913124 5.50224 1.08722 4.13374Z"
                                        />
                                    </svg>
                                </span>
                            @endif

                            <div class="fff-todo-list-field__content">
                                <span class="fff-todo-list-field__title-wrap">
                                    <span class="fff-todo-list-field__label">{{ $item['label'] }}</span>
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
                                @if (filled($item['description'] ?? null))
                                    <span
                                        @class([
                                            'fff-todo-list-field__description',
                                            'is-complete' => $done,
                                        ])
                                    >{{ $item['description'] }}</span>
                                @endif
                                @if ($hasMeta)
                                    <div class="fff-todo-list-field__meta">
                                        @if ($hasChildren)
                                            <span class="fff-todo-list-field__meta-chip">
                                                @if (filled($childrenIcon))
                                                    <x-filament::icon
                                                        :icon="$childrenIcon"
                                                        class="fff-todo-list-field__meta-icon h-3.5 w-3.5"
                                                    />
                                                @endif
                                                <span>{{ $doneChildren }}/{{ count($children) }}</span>
                                            </span>
                                        @endif
                                        @if (filled($item['date'] ?? null))
                                            <span class="fff-todo-list-field__meta-chip">
                                                @if (filled($dateIcon))
                                                    <x-filament::icon
                                                        :icon="$dateIcon"
                                                        class="fff-todo-list-field__meta-icon h-3.5 w-3.5"
                                                    />
                                                @endif
                                                <span>{{ $item['date'] }}</span>
                                            </span>
                                        @endif
                                    </div>
                                @endif
                        </div>

                        @if ($canEdit || $canDelete)
                            <div class="fff-todo-list-field__actions" aria-hidden="true">
                                @if ($canEdit)
                                    <span class="fff-todo-list-field__action-slot"></span>
                                @endif
                                @if ($canDelete)
                                    <span class="fff-todo-list-field__action-slot"></span>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($hasChildren)
                        <div class="fff-todo-list-field__substack">
                            @foreach ($children as $child)
                                @php
                                    $childDone = (bool) ($child['done'] ?? false);
                                @endphp
                                <div
                                    @class([
                                        'fff-todo-list-field__row',
                                        'fff-todo-list-field__row--child',
                                        'is-done' => $childDone,
                                        'is-settled' => $childDone,
                                        'is-disabled' => $isDisabled || ($child['disabled'] ?? false),
                                    ])
                                    @if ($childDone)
                                        style="--text-line-scale: 1; --text-x: 0px; --checkbox-tick-offset: 0;"
                                    @endif
                                >
                                    @if ($childDone)
                                        @include('filament-flex-fields::forms.components.partials.todo-list-field-checkbox-done-ssr', [
                                            'tickKey' => (string) ($child['id'] ?? uniqid('c', false)),
                                        ])
                                    @else
                                        <span class="fff-todo-list-field__checkbox" aria-hidden="true">
                                            <span class="fff-todo-list-field__checkbox-input"></span>
                                            <svg class="fff-todo-list-field__checkbox-svg" viewBox="0 0 21 18">
                                                <path
                                                    class="fff-todo-list-field__shape"
                                                    d="M1.08722 4.13374C1.29101 2.53185 2.53185 1.29101 4.13374 1.08722C5.50224 0.913124 7.25112 0.75 9 0.75C10.7489 0.75 12.4978 0.913124 13.8663 1.08722C15.4681 1.29101 16.709 2.53185 16.9128 4.13374C17.0869 5.50224 17.25 7.25112 17.25 9C17.25 10.7489 17.0869 12.4978 16.9128 13.8663C16.709 15.4681 15.4682 16.709 13.8663 16.9128C12.4978 17.0869 10.7489 17.25 9 17.25C7.25112 17.25 5.50224 17.0869 4.13374 16.9128C2.53185 16.709 1.29101 15.4681 1.08722 13.8663C0.913124 12.4978 0.75 10.7489 0.75 9C0.75 7.25112 0.913124 5.50224 1.08722 4.13374Z"
                                                />
                                            </svg>
                                        </span>
                                    @endif
                                    <div class="fff-todo-list-field__content">
                                        <span class="fff-todo-list-field__title-wrap">
                                            <span class="fff-todo-list-field__label">{{ $child['label'] }}</span>
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
                                        @if (filled($child['description'] ?? null))
                                            <span
                                                @class([
                                                    'fff-todo-list-field__description',
                                                    'is-complete' => $childDone,
                                                ])
                                            >{{ $child['description'] }}</span>
                                        @endif
                                    </div>
                                    @if ($canEdit || $canDelete)
                                        <div class="fff-todo-list-field__actions" aria-hidden="true">
                                            @if ($canEdit)
                                                <span class="fff-todo-list-field__action-slot"></span>
                                            @endif
                                            @if ($canDelete)
                                                <span class="fff-todo-list-field__action-slot"></span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    </div>

    @if ($allowCreate)
        <div class="fff-todo-list-field__create-dock">
            <div
                @class([
                    'fff-todo-list-field__row',
                    'fff-todo-list-field__row--create',
                    'is-disabled' => $isDisabled,
                ])
                role="listitem"
            >
                <span class="fff-todo-list-field__create-icon" aria-hidden="true">
                    @if (filled($createIcon))
                        <x-filament::icon
                            :icon="$createIcon"
                            class="fff-todo-list-field__plus"
                        />
                    @endif
                </span>
                <div class="fff-todo-list-field__content">
                    <span class="fff-todo-list-field__label">{{ $createLabel }}</span>
                </div>
            </div>
        </div>
    @endif
</div>
</div>

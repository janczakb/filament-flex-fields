@php
    use Filament\Support\Enums\IconSize;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $wrapperClasses = $getWrapperClasses();
    $listHeight = $getListHeight();
    $toolbarIconSize = IconSize::Small;

    $searchIconHtml = \Filament\Support\generate_icon_html($field->getSearchIcon(), size: $toolbarIconSize)?->toHtml() ?? '';
    $moveAllRightIconHtml = \Filament\Support\generate_icon_html($field->getMoveAllRightIcon(), size: $toolbarIconSize)?->toHtml() ?? '';
    $moveRightIconHtml = \Filament\Support\generate_icon_html($field->getMoveRightIcon(), size: $toolbarIconSize)?->toHtml() ?? '';
    $swapIconHtml = \Filament\Support\generate_icon_html($field->getSwapIcon(), size: $toolbarIconSize)?->toHtml() ?? '';
    $moveLeftIconHtml = \Filament\Support\generate_icon_html($field->getMoveLeftIcon(), size: $toolbarIconSize)?->toHtml() ?? '';
    $moveAllLeftIconHtml = \Filament\Support\generate_icon_html($field->getMoveAllLeftIcon(), size: $toolbarIconSize)?->toHtml() ?? '';
    $moveAllDownIconHtml = \Filament\Support\generate_icon_html(\Bjanczak\FilamentFlexFields\Support\GravityIcon::ArrowChevronDown, size: $toolbarIconSize)?->toHtml() ?? '';
    $moveDownTransferIconHtml = \Filament\Support\generate_icon_html(\Bjanczak\FilamentFlexFields\Support\GravityIcon::ArrowDown, size: $toolbarIconSize)?->toHtml() ?? '';
    $swapStackedIconHtml = \Filament\Support\generate_icon_html(\Bjanczak\FilamentFlexFields\Support\GravityIcon::ArrowUpArrowDown, size: $toolbarIconSize)?->toHtml() ?? '';
    $moveUpTransferIconHtml = \Filament\Support\generate_icon_html(\Bjanczak\FilamentFlexFields\Support\GravityIcon::ArrowUp, size: $toolbarIconSize)?->toHtml() ?? '';
    $moveAllUpIconHtml = \Filament\Support\generate_icon_html(\Bjanczak\FilamentFlexFields\Support\GravityIcon::ArrowChevronUp, size: $toolbarIconSize)?->toHtml() ?? '';
    $moveUpIconHtml = \Filament\Support\generate_icon_html($field->getMoveUpIcon(), size: IconSize::ExtraSmall)?->toHtml() ?? '';
    $moveDownIconHtml = \Filament\Support\generate_icon_html($field->getMoveDownIcon(), size: IconSize::ExtraSmall)?->toHtml() ?? '';
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'dual-listbox',
        'livewireKey' => $getLivewireKey(),
    ])
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('dual-listbox', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="dualListboxFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            options: @js($getOptionsForJs()),
            hasDeferredOptions: @js($field->hasDeferredOptions()),
            initialOptions: @js($field->getInitialOptionsForJs()),
            searchable: @js($isSearchable()),
            reorderable: @js($isReorderable()),
            moveOnDoubleClick: @js($isMoveOnDoubleClick()),
            showTransferButtons: @js($showsTransferButtons()),
            disabled: @js($isDisabled),
            maxItems: @js($getMaxItems()),
            virtualThreshold: @js($getVirtualScrollThreshold()),
        })"
        x-init="init()"
        @class([
            'fff-dual-listbox',
            'is-disabled' => $isDisabled,
        ])
        :class="{ 'is-pointer-dragging': pointerDragActive }"
        style="--fff-dual-listbox-height: {{ $listHeight }}"
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div class="fff-dual-listbox__layout">
            {{-- Available panel --}}
            <div
                class="fff-dual-listbox__panel"
                :class="{ 'is-drop-pane': pointerDragActive && dropPane === 'available' && touchHoldPane === 'selected' }"
            >
                <div class="fff-dual-listbox__panel-header">
                    <span class="fff-dual-listbox__panel-title">{{ $getAvailableLabel() }}</span>
                    <span class="fff-dual-listbox__panel-count" x-text="availableItems.length"></span>
                </div>

                @if ($isSearchable())
                    <div class="fff-dual-listbox__search">
                        <span class="fff-dual-listbox__search-icon" aria-hidden="true">
                            {!! $searchIconHtml !!}
                        </span>
                        <input
                            type="search"
                            class="fff-dual-listbox__search-input"
                            placeholder="{{ __('filament-flex-fields::default.dual_listbox.search_available') }}"
                            x-model="availableQuery"
                            :disabled="disabled"
                            autocomplete="off"
                        />
                    </div>
                @endif

                <ul
                    class="fff-dual-listbox__list"
                    data-fff-dual-listbox-pane="available"
                    role="listbox"
                    aria-multiselectable="true"
                    :aria-label="@js($getAvailableLabel())"
                    x-ref="availableList"
                    x-init="measureAvailableList($event)"
                    @scroll="onAvailableListScroll($event)"
                >
                    <li
                        class="fff-dual-listbox__virtual-spacer-top"
                        x-show="availableVirtualWindow.useVirtual"
                        :style="{ height: `${availableVirtualWindow.spacerTop}px` }"
                        aria-hidden="true"
                    ></li>

                    <template x-for="item in availableVirtualWindow.items" :key="item.value">
                        <li
                            role="option"
                            :aria-selected="isAvailableSelected(item.value)"
                            :aria-disabled="item.disabled"
                            @click="toggleAvailableSelection(item.value, $event)"
                            @pointerdown="onAvailablePointerDown(item.value, $event)"
                            @pointerup="onPointerUp($event)"
                            @pointercancel="onPointerUp($event)"
                            @dblclick.prevent="handleAvailableDoubleClick(item.value)"
                            :class="{
                                'is-selected': isAvailableSelected(item.value),
                                'is-disabled': item.disabled,
                                'is-dragging': isPointerDraggingValue(item.value),
                            }"
                            class="fff-dual-listbox__item"
                            data-fff-dual-listbox-item="true"
                            :data-fff-value="item.value"
                        >
                            <span class="fff-dual-listbox__item-content">
                                <span class="fff-dual-listbox__item-label" x-text="item.label"></span>
                                <span
                                    class="fff-dual-listbox__item-description"
                                    x-show="item.description"
                                    x-text="item.description"
                                ></span>
                            </span>
                        </li>
                    </template>

                    <li
                        class="fff-dual-listbox__virtual-spacer-bottom"
                        x-show="availableVirtualWindow.useVirtual"
                        :style="{ height: `${availableVirtualWindow.spacerBottom}px` }"
                        aria-hidden="true"
                    ></li>

                    <li
                        class="fff-dual-listbox__empty"
                        x-show="availableItems.length === 0"
                        x-cloak
                    >
                        {{ __('filament-flex-fields::default.dual_listbox.empty_available') }}
                    </li>
                </ul>
            </div>

            @if ($showsTransferButtons())
                <div class="fff-dual-listbox__toolbar" aria-hidden="false">
                    <button
                        type="button"
                        class="fff-dual-listbox__transfer-btn"
                        @click="moveAllToSelected()"
                        :disabled="disabled || ! canMoveAllToSelected()"
                        title="{{ __('filament-flex-fields::default.dual_listbox.move_all_right') }}"
                    >
                        <span class="fff-dual-listbox__transfer-icon fff-dual-listbox__transfer-icon--inline">{!! $moveAllRightIconHtml !!}</span>
                        <span class="fff-dual-listbox__transfer-icon fff-dual-listbox__transfer-icon--stacked">{!! $moveAllDownIconHtml !!}</span>
                    </button>
                    <button
                        type="button"
                        class="fff-dual-listbox__transfer-btn"
                        @click="moveSelectionToSelected()"
                        :disabled="disabled || availableSelection.length === 0"
                        title="{{ __('filament-flex-fields::default.dual_listbox.move_selected_right') }}"
                    >
                        <span class="fff-dual-listbox__transfer-icon fff-dual-listbox__transfer-icon--inline">{!! $moveRightIconHtml !!}</span>
                        <span class="fff-dual-listbox__transfer-icon fff-dual-listbox__transfer-icon--stacked">{!! $moveDownTransferIconHtml !!}</span>
                    </button>
                    <button
                        type="button"
                        class="fff-dual-listbox__transfer-btn"
                        @click="swapLists()"
                        :disabled="disabled || ! canSwapLists()"
                        title="{{ __('filament-flex-fields::default.dual_listbox.swap_lists') }}"
                    >
                        <span class="fff-dual-listbox__transfer-icon fff-dual-listbox__transfer-icon--inline">{!! $swapIconHtml !!}</span>
                        <span class="fff-dual-listbox__transfer-icon fff-dual-listbox__transfer-icon--stacked">{!! $swapStackedIconHtml !!}</span>
                    </button>
                    <button
                        type="button"
                        class="fff-dual-listbox__transfer-btn"
                        @click="moveSelectionToAvailable()"
                        :disabled="disabled || selectedSelection.length === 0"
                        title="{{ __('filament-flex-fields::default.dual_listbox.move_selected_left') }}"
                    >
                        <span class="fff-dual-listbox__transfer-icon fff-dual-listbox__transfer-icon--inline">{!! $moveLeftIconHtml !!}</span>
                        <span class="fff-dual-listbox__transfer-icon fff-dual-listbox__transfer-icon--stacked">{!! $moveUpTransferIconHtml !!}</span>
                    </button>
                    <button
                        type="button"
                        class="fff-dual-listbox__transfer-btn"
                        @click="moveAllToAvailable()"
                        :disabled="disabled || (state?.length ?? 0) === 0"
                        title="{{ __('filament-flex-fields::default.dual_listbox.move_all_left') }}"
                    >
                        <span class="fff-dual-listbox__transfer-icon fff-dual-listbox__transfer-icon--inline">{!! $moveAllLeftIconHtml !!}</span>
                        <span class="fff-dual-listbox__transfer-icon fff-dual-listbox__transfer-icon--stacked">{!! $moveAllUpIconHtml !!}</span>
                    </button>
                </div>
            @endif

            {{-- Selected panel --}}
            <div
                class="fff-dual-listbox__panel"
                :class="{ 'is-drop-pane': pointerDragActive && dropPane === 'selected' }"
            >
                <div class="fff-dual-listbox__panel-header">
                    <span class="fff-dual-listbox__panel-title">{{ $getSelectedLabel() }}</span>
                    <span class="fff-dual-listbox__panel-count" x-text="(state?.length ?? 0)"></span>
                </div>

                @if ($isSearchable())
                    <div class="fff-dual-listbox__search">
                        <span class="fff-dual-listbox__search-icon" aria-hidden="true">
                            {!! $searchIconHtml !!}
                        </span>
                        <input
                            type="search"
                            class="fff-dual-listbox__search-input"
                            placeholder="{{ __('filament-flex-fields::default.dual_listbox.search_selected') }}"
                            x-model="selectedQuery"
                            :disabled="disabled"
                            autocomplete="off"
                        />
                    </div>
                @endif

                <ul
                    class="fff-dual-listbox__list"
                    data-fff-dual-listbox-pane="selected"
                    role="listbox"
                    aria-multiselectable="true"
                    :aria-label="@js($getSelectedLabel())"
                    x-ref="selectedList"
                    x-init="measureSelectedList($event)"
                    @scroll="onSelectedListScroll($event)"
                >
                    <li
                        class="fff-dual-listbox__virtual-spacer-top"
                        x-show="selectedVirtualWindow.useVirtual"
                        :style="{ height: `${selectedVirtualWindow.spacerTop}px` }"
                        aria-hidden="true"
                    ></li>

                    <template x-for="item in selectedVirtualWindow.items" :key="item.value">
                        <li
                            role="option"
                            :aria-selected="isSelectedSelected(item.value)"
                            @click="toggleSelectedSelection(item.value, $event)"
                            @pointerdown="onSelectedPointerDown(item.value, $event)"
                            @pointerup="onPointerUp($event)"
                            @pointercancel="onPointerUp($event)"
                            @dblclick.prevent="handleSelectedDoubleClick(item.value)"
                            @if ($isReorderable())
                                :draggable="canReorderSelected() && html5DragEnabled"
                                @dragstart="startSelectedDrag(item.value, $event)"
                                @dragover.prevent="markSelectedDropTarget(item.value)"
                                @drop.prevent="dropSelectedAt(item.value, $event)"
                                @dragend="clearSelectedDrag()"
                            @endif
                            :class="{
                                'is-selected': isSelectedSelected(item.value),
                                'is-disabled': item.disabled,
                                'is-dragging': isPointerDraggingValue(item.value) || draggingSelectedValues.includes(item.value) || draggingSelectedValue === item.value,
                                'is-drop-target': isSelectedDropTarget(item.value) && ! dropAfter,
                                'is-drop-after': isSelectedDropTarget(item.value) && dropAfter,
                                'is-reorderable': canReorderSelected(),
                            }"
                            class="fff-dual-listbox__item"
                            data-fff-dual-listbox-item="true"
                            :data-fff-value="item.value"
                            @if ($isReorderable())
                                :title="canReorderSelected() ? @js(__('filament-flex-fields::default.dual_listbox.reorder')) : null"
                            @endif
                        >
                            <span class="fff-dual-listbox__item-content">
                                <span class="fff-dual-listbox__item-label" x-text="item.label"></span>
                                <span
                                    class="fff-dual-listbox__item-description"
                                    x-show="item.description"
                                    x-text="item.description"
                                ></span>
                            </span>

                            @if ($isReorderable())
                                <span
                                    class="fff-dual-listbox__reorder"
                                    @click.stop
                                    @pointerdown.stop
                                >
                                    <button
                                        type="button"
                                        class="fff-dual-listbox__reorder-btn"
                                        draggable="false"
                                        @click="moveSelectedUp(item.value)"
                                        :disabled="disabled"
                                        title="{{ __('filament-flex-fields::default.dual_listbox.move_up') }}"
                                    >
                                        {!! $moveUpIconHtml !!}
                                    </button>
                                    <button
                                        type="button"
                                        class="fff-dual-listbox__reorder-btn"
                                        draggable="false"
                                        @click="moveSelectedDown(item.value)"
                                        :disabled="disabled"
                                        title="{{ __('filament-flex-fields::default.dual_listbox.move_down') }}"
                                    >
                                        {!! $moveDownIconHtml !!}
                                    </button>
                                </span>
                            @endif
                        </li>
                    </template>

                    <li
                        class="fff-dual-listbox__virtual-spacer-bottom"
                        x-show="selectedVirtualWindow.useVirtual"
                        :style="{ height: `${selectedVirtualWindow.spacerBottom}px` }"
                        aria-hidden="true"
                    ></li>

                    <li
                        class="fff-dual-listbox__empty"
                        x-show="selectedItems.length === 0"
                        x-cloak
                    >
                        {{ __('filament-flex-fields::default.dual_listbox.empty_selected') }}
                    </li>
                </ul>
            </div>
        </div>

        <template x-teleport="body">
            <div
                class="fff-dual-listbox__ghost"
                x-show="pointerDragActive"
                x-cloak
                :style="ghostStyle"
                data-fff-dual-listbox-ghost="true"
                aria-hidden="true"
            >
                <div class="fff-dual-listbox__ghost-stack">
                    <template x-for="card in ghostStack" :key="card.value">
                        <div
                            class="fff-dual-listbox__ghost-card"
                            :style="ghostCardStyle(card.depth)"
                        >
                            <span class="fff-dual-listbox__ghost-label" x-text="card.label"></span>
                        </div>
                    </template>
                </div>
                <span
                    class="fff-dual-listbox__ghost-badge"
                    x-show="ghostCount > 1"
                    x-text="ghostCount"
                ></span>
            </div>
        </template>
    </div>
</x-dynamic-component>

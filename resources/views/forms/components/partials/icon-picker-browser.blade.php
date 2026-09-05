<div class="fff-icon-picker__toolbar" x-bind:class="{ 'has-set-tabs': availableSets.length > 1 }">
    @include('filament-flex-fields::forms.components.partials.headless-overlay-search', [
        'queryModel' => 'searchQuery',
        'searchRef' => 'iconSearch',
        'openKey' => 'panelOpen',
        'listboxId' => 'componentKey + \'-listbox\'',
        'keydownHandler' => 'onIconSearchKeydown',
        'inputHandler' => 'onSearchInput',
        'disabledBinding' => 'readOnly',
        'placeholderBinding' => 'labels.search',
        'searchLabel' => 'Icon search',
        'activeIndexKey' => 'activeIconIndex',
        'optionIdPrefix' => 'componentKey + \'-option-\'',
    ])

    <div
        class="fff-icon-picker__set-tabs"
        x-show="availableSets.length > 1"
        x-cloak
    >
        <button
            type="button"
            class="fff-icon-picker__set-tab"
            x-bind:class="{ 'is-active': activeSet === null }"
            x-on:click="selectSet(null)"
        >
            <span x-text="labels.allSets"></span>
        </button>

        <template x-for="set in availableSets" x-bind:key="set.key">
            <button
                type="button"
                class="fff-icon-picker__set-tab"
                x-bind:class="{ 'is-active': activeSet === set.key }"
                x-on:click="selectSet(set.key)"
            >
                <span x-text="set.label"></span>
                <span class="fff-icon-picker__set-tab-count" x-text="set.count"></span>
            </button>
        </template>
    </div>
</div>

<div class="fff-icon-picker__divider" aria-hidden="true"></div>

<div
    x-ref="iconResults"
    class="fff-headless-overlay__list fff-icon-picker__results fi-select-input-options-ctn"
    x-bind:class="{
        'fff-icon-picker__results--grid': layout === 'grid' || layout === 'icons',
        'fff-icon-picker__results--list': layout === 'list',
        'fff-icon-picker__results--icons-only': layout === 'icons',
        'is-loading': showInitialSkeleton || showScrollLoadSkeleton,
    }"
    x-on:scroll.passive="onIconResultsScroll($event)"
    x-on:keydown="onIconResultsKeydown($event)"
    tabindex="-1"
    role="listbox"
    x-bind:id="componentKey + '-listbox'"
    x-bind:aria-hidden="! panelOpen"
    x-bind:aria-busy="showInitialSkeleton || loadingMore"
>
    <div
        class="fff-icon-picker__status"
        x-show="! showInitialSkeleton && ! searchPending && ! initialLoadPending && loadedIconItems.length === 0"
        x-cloak
        x-text="labels.noResults"
    ></div>

    <div
        class="fff-icon-picker__initial-skeleton"
        x-show="showInitialSkeleton"
        x-cloak
        x-bind:class="{ 'is-fading': iconSkeletonFading }"
        role="status"
        aria-live="polite"
    >
        {{-- Static cells (no Alpine x-for) so bones paint on the first open frame. --}}
        <div
            class="fff-icon-picker__grid fff-icon-picker__skeleton-grid"
            x-bind:style="iconResultsGridStyle"
        >
            @foreach (range(1, 48) as $skeletonSlot)
                @include('filament-flex-fields::forms.components.partials.icon-picker-skeleton-cell')
            @endforeach
        </div>
    </div>

    <div
        class="fff-icon-picker__track"
        x-show="loadedIconItems.length > 0 && ! showInitialSkeleton"
        x-cloak
        x-bind:class="{ 'fff-icon-picker__track--virtual': usesIconVirtualScroll }"
        x-bind:style="iconTrackStyle"
    >
        <div
            class="fff-icon-picker__virtual-spacer-top"
            x-show="usesIconVirtualScroll && iconVirtualWindow.paddingTop > 0"
            x-bind:style="virtualTopSpacerStyle"
            aria-hidden="true"
        ></div>

        <div
            x-ref="iconGrid"
            class="fff-icon-picker__grid"
            x-bind:style="iconResultsGridStyle"
        >
            <template x-for="entry in visibleIconEntries" x-bind:key="'icon-option-' + entry.item.name + '-' + entry.index">
                @include('filament-flex-fields::forms.components.partials.icon-picker-option')
            </template>
        </div>

        <div
            class="fff-icon-picker__virtual-spacer-bottom"
            x-show="usesIconVirtualScroll && iconVirtualWindow.paddingBottom > 0"
            x-bind:style="virtualBottomSpacerStyle"
            aria-hidden="true"
        ></div>

        <div
            x-ref="iconLoadMoreSentinel"
            class="fff-icon-picker__load-more-sentinel"
            x-show="hasMore && ! loadingMore"
            x-cloak
            aria-hidden="true"
        ></div>

        <div
            class="fff-icon-picker__load-more-tail"
            x-show="showLoadMoreTailSkeleton"
            x-cloak
            role="status"
            aria-live="polite"
            x-bind:aria-busy="true"
            x-bind:style="loadMoreTailStyle"
        >
            <div
                class="fff-icon-picker__grid fff-icon-picker__load-more-tail-grid"
                x-bind:style="iconResultsGridStyle"
            >
                <template x-for="slot in loadMoreSkeletonSlots" x-bind:key="'load-more-tail-' + slot">
                    @include('filament-flex-fields::forms.components.partials.icon-picker-skeleton-cell')
                </template>
            </div>
        </div>
    </div>
</div>

<div class="fff-icon-picker__footer" x-show="showLoadMoreListHint" x-cloak>
    <span class="fff-icon-picker__status" x-text="labels.loadMore + '…'"></span>
</div>

<div class="fff-icon-picker__toolbar">
    <div class="fi-select-input-search-ctn fff-teleported-menu__search-wrap">
        <label class="sr-only" x-text="labels.search"></label>
        <input
            type="search"
            x-ref="iconSearch"
            class="fi-input fff-teleported-menu__search"
            x-model="searchQuery"
            x-on:input.debounce.300ms="onSearchInput()"
            x-on:keydown="onIconSearchKeydown($event)"
            x-bind:disabled="readOnly"
            x-bind:placeholder="labels.search"
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            x-bind:aria-expanded="panelOpen"
            x-bind:aria-controls="componentKey + '-listbox'"
            x-bind:aria-activedescendant="activeIconIndex >= 0 ? componentKey + '-option-' + activeIconIndex : null"
        />
    </div>

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
    class="fff-icon-picker__results fi-select-input-options-ctn"
    x-bind:class="{
        'fff-icon-picker__results--grid': layout === 'grid' || layout === 'icons',
        'fff-icon-picker__results--list': layout === 'list',
        'fff-icon-picker__results--icons-only': layout === 'icons',
        'is-loading': searchPending || initialLoadPending,
    }"
    x-bind:style="layout === 'grid' || layout === 'icons'
        ? { '--fff-icon-picker-grid-columns': gridColumns }
        : null"
    x-on:scroll.passive="onIconResultsScroll($event)"
    x-on:keydown="onIconResultsKeydown($event)"
    tabindex="-1"
    role="listbox"
    x-bind:id="componentKey + '-listbox'"
>
    <template x-if="(searchPending || initialLoadPending) && loadedIconItems.length === 0">
        <div class="fff-icon-picker__skeleton-grid" aria-hidden="true">
            <template x-for="slot in skeletonSlots" x-bind:key="slot">
                <div class="fff-icon-picker__skeleton"></div>
            </template>
        </div>
    </template>

    <template x-if="! searchPending && ! initialLoadPending && loadedIconItems.length === 0">
        <div class="fff-icon-picker__status" x-text="labels.noResults"></div>
    </template>

    <template x-if="loadedIconItems.length > 0">
        <div
            class="fff-icon-picker__track"
            x-bind:class="{ 'fff-icon-picker__track--virtual': usesIconVirtualScroll }"
            x-bind:style="usesIconVirtualScroll ? { height: `${iconTrackHeight}px` } : null"
        >
            <div
                class="fff-icon-picker__grid"
                x-bind:class="{ 'fff-icon-picker__grid--virtual': usesIconVirtualScroll }"
                x-bind:style="usesIconVirtualScroll
                    ? {
                        top: `${iconWindowOffsetTop}px`,
                        '--fff-icon-picker-grid-columns': gridColumns,
                    }
                    : (layout === 'grid' || layout === 'icons'
                        ? { '--fff-icon-picker-grid-columns': gridColumns }
                        : null)"
            >
                <template x-for="entry in visibleIconEntries" x-bind:key="entry.item.name">
                    <button
                        type="button"
                        class="fff-icon-picker__option"
                        x-bind:class="iconOptionClasses(entry.index)"
                        x-bind:data-icon-index="entry.index"
                        x-on:click="selectIcon(entry.item.name)"
                        x-on:mouseenter="activeIconIndex = entry.index"
                        x-bind:disabled="readOnly"
                        x-bind:aria-label="entry.item.name"
                        x-bind:title="layout === 'icons' ? entry.item.name : null"
                        role="option"
                        x-bind:id="componentKey + '-option-' + entry.index"
                        x-bind:aria-selected="activeIconIndex === entry.index"
                    >
                    <span class="fff-icon-picker__option-icon" x-bind:data-icon-name="entry.item.name">
                        <span x-show="svgFor(entry.item.name)" x-html="svgFor(entry.item.name)"></span>
                        <span
                            class="fff-icon-picker__option-icon-skeleton"
                            x-show="! svgFor(entry.item.name)"
                            aria-hidden="true"
                        ></span>
                    </span>
                        <span
                            class="fff-icon-picker__option-label"
                            x-show="layout !== 'icons'"
                            x-html="highlightedLabel(entry.item.label)"
                        ></span>
                    </button>
                </template>
            </div>

            <div
                x-ref="iconScrollSentinel"
                class="fff-icon-picker__scroll-sentinel"
                x-show="hasMore && ! loadingMore"
                x-cloak
                x-bind:style="usesIconVirtualScroll ? { top: `${Math.max(iconTrackHeight - 1, 0)}px` } : null"
                aria-hidden="true"
            ></div>
        </div>
    </template>
</div>

<div class="fff-icon-picker__footer" x-show="(searchPending || loadingMore) && loadedIconItems.length > 0" x-cloak>
    <span class="fff-icon-picker__status" x-text="labels.search + '…'"></span>
</div>

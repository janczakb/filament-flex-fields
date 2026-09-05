@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\Enums\IconSize;

    $noResultsIconHtml = \Filament\Support\generate_icon_html(GravityIcon::Magnifier, size: IconSize::Large)?->toHtml() ?? '';
    $minCharsIconHtml = \Filament\Support\generate_icon_html(GravityIcon::Magnifier, size: IconSize::Large)?->toHtml() ?? '';
    $recentIconHtml = \Filament\Support\generate_icon_html(GravityIcon::Clock, size: IconSize::Small)?->toHtml() ?? '';
    $sheetSearchClearIconHtml = \Filament\Support\generate_icon_html(GravityIcon::CircleXmarkFill, size: IconSize::Small)?->toHtml() ?? '';
    $sheetSearchClearLabel = __('filament-flex-fields::default.select_field.clear_search');
@endphp

<div
    @class([
        'fff-geocoding-dropdown-panel',
        'fff-geocoding-dropdown-panel--map-context' => $mapContext ?? false,
        'fi-dropdown-panel',
        'fff-select-dropdown-panel',
        'fff-select-dropdown-panel--below',
        'fff-select-dropdown-panel--dropdown-fixed',
        'fff-teleported-menu',
        'fff-select-dropdown-panel--layout-plain',
        'fi-width-none',
    ])
    x-ref="searchDropdown"
    x-show="{{ $showExpression ?? 'searchable && searchOpen && ! readOnly' }}"
    x-cloak
    x-bind:class="{
        'is-positioned': searchDropdownReady,
        'is-open': searchable && searchOpen && searchDropdownReady && ! readOnly,
    }"
    x-on:mousedown="
        const target = $event.target;
        if (! (target instanceof Element)) {
            return;
        }
        if (target.closest('input, textarea, button, [role=\'option\'], a, label')) {
            return;
        }
        // Keep sheet search focused when tapping empty drawer chrome (blur must not close).
        $event.preventDefault();
    "
>
    <div
        class="fi-select-input-search-ctn fff-select-input-search-ctn"
        x-show="shouldShowGeocodingSheetSearch()"
        x-cloak
    >
        <label class="sr-only" x-text="labels.search"></label>
        <input
            type="search"
            class="fi-input fi-select-input-search-input"
            x-ref="geocodingSheetSearchInput"
            x-model="searchQuery"
            x-on:input="onSearchInput()"
            x-on:keydown="onSearchKeydown($event)"
            x-on:blur="onSearchBlur()"
            x-bind:placeholder="labels.search"
            x-bind:disabled="readOnly"
            autocomplete="off"
            dir="auto"
            role="combobox"
            aria-autocomplete="list"
            x-bind:aria-expanded="searchOpen"
            aria-controls="{{ $listboxId }}"
            x-bind:aria-activedescendant="highlightedIndex >= 0 ? geocodingOptionId(highlightedIndex) : null"
        />
        <button
            type="button"
            class="fff-select-search-clear-btn"
            x-cloak
            x-show="String(searchQuery ?? '').length > 0"
            x-on:mousedown.prevent="clearGeocodingSheetSearch()"
            x-bind:aria-label="@js($sheetSearchClearLabel)"
        >
            {!! $sheetSearchClearIconHtml !!}
        </button>
    </div>

    <div
        id="{{ $listboxId }}"
        class="fi-select-input-options-ctn"
        role="listbox"
    >
        <div
            x-show="searchOpen && ! searchHasMinQuery && geocodingRecentResults.length > 0 && ! searchLoading"
            x-cloak
            class="fff-geocoding-dropdown-recent"
        >
            <div class="fi-dropdown-header fff-geocoding-dropdown-recent__header" x-text="labels.searchRecent"></div>

            <template x-for="(result, index) in geocodingRecentResults" :key="'recent-' + result.id">
                <button
                    type="button"
                    role="option"
                    class="fi-select-input-option fff-geocoding-dropdown-recent__option"
                    x-bind:id="geocodingOptionId('recent-' + index)"
                    x-bind:class="{ 'fi-selected': selectedResultId === result.id }"
                    x-on:mousedown.prevent="selectSearchResult(result)"
                    x-on:mouseenter="highlightedIndex = -1"
                >
                    <span class="fff-geocoding-dropdown-recent__icon" aria-hidden="true">{!! $recentIconHtml !!}</span>
                    <span x-text="result.label"></span>
                </button>
            </template>
        </div>

        <div
            x-show="searchOpen && ! searchHasMinQuery && geocodingRecentResults.length === 0 && ! searchLoading"
            x-cloak
            class="fff-select-dropdown-empty"
            role="status"
        >
            <span class="fff-select-dropdown-empty-icon" aria-hidden="true">{!! $minCharsIconHtml !!}</span>
            <span class="fff-select-dropdown-empty-title" x-text="labels.searchMinChars"></span>
        </div>

        <div
            x-show="searchOpen && searchLoading && searchHasMinQuery && searchResults.length === 0"
            x-cloak
            class="fff-select-dropdown-loading"
            role="status"
            aria-live="polite"
            x-bind:aria-busy="searchLoading && searchHasMinQuery ? 'true' : 'false'"
            x-bind:aria-label="labels.searchLoading"
        >
            <x-filament::loading-indicator class="fff-select-dropdown-loading__spinner" />
            <span class="fff-select-dropdown-loading__label" x-text="labels.searchLoading"></span>
        </div>

        <div
            x-show="searchOpen && searchHasMinQuery && ! searchLoading && ! searchRefreshing && searchResults.length === 0"
            x-cloak
            class="fff-select-dropdown-empty"
            role="status"
        >
            <span class="fff-select-dropdown-empty-icon" aria-hidden="true">{!! $noResultsIconHtml !!}</span>
            <span class="fff-select-dropdown-empty-title" x-text="labels.searchNoResults"></span>
        </div>

        <div
            x-show="searchOpen && searchRefreshing && searchResults.length > 0"
            x-cloak
            class="fff-geocoding-dropdown-refresh"
            role="status"
            aria-live="polite"
        >
            <x-filament::loading-indicator class="fff-geocoding-dropdown-refresh__spinner" />
            <span class="fff-geocoding-dropdown-refresh__label" x-text="labels.searchRefreshing"></span>
        </div>

        <template x-for="(result, index) in searchResults" :key="result.id">
            <button
                type="button"
                role="option"
                class="fi-select-input-option"
                x-show="searchOpen && searchHasMinQuery"
                x-cloak
                x-bind:id="geocodingOptionId(index)"
                x-bind:class="{ 'fi-selected': highlightedIndex === index || selectedResultId === result.id }"
                x-bind:aria-selected="highlightedIndex === index ? 'true' : 'false'"
                x-on:mousedown.prevent="selectSearchResult(result)"
                x-on:mouseenter="highlightedIndex = index"
            >
                <span x-text="result.label"></span>
            </button>
        </template>
    </div>
</div>

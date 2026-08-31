@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\Enums\IconSize;

    $noResultsIconHtml = \Filament\Support\generate_icon_html(GravityIcon::Magnifier, size: IconSize::Large)?->toHtml() ?? '';
    $minCharsIconHtml = \Filament\Support\generate_icon_html(GravityIcon::Magnifier, size: IconSize::Large)?->toHtml() ?? '';
    $recentIconHtml = \Filament\Support\generate_icon_html(GravityIcon::Clock, size: IconSize::Small)?->toHtml() ?? '';
@endphp

<div
    id="{{ $listboxId }}"
    role="listbox"
    @class([
        'fff-geocoding-dropdown-panel',
        'fff-geocoding-dropdown-panel--map-context' => $mapContext ?? false,
        'fi-dropdown-panel',
        'fff-select-dropdown-panel',
        'fff-select-dropdown-panel--below',
        'fff-teleported-menu',
        'fff-select-dropdown-panel--layout-plain',
    ])
    x-ref="searchDropdown"
    x-show="{{ $showExpression ?? 'searchable && searchOpen && ! readOnly' }}"
    x-cloak
>
    <div class="fi-select-input-options-ctn">
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
                x-show="searchOpen"
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

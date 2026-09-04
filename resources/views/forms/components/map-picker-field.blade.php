@php
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $defaultCenter = $field->getDefaultCenter();
    $accessToken = $field->getMapboxToken();
    $livewireKey = $getLivewireKey();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($field->getWrapperClasses())
    "
>
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $field->getFields(), $field->getStoreFormat(), filled($accessToken)])), 0, 64) }}"
        @class([
            'fff-map-picker',
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'map-picker',
        'livewireKey' => $getLivewireKey(),
    ])
        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('map-picker', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
            x-data="mapPickerFormComponent({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                accessToken: @js($accessToken),
                geocodeSearchUrl: @js($field->getGeocodeSearchUrl()),
                geocodeReverseUrl: @js($field->getGeocodeReverseUrl()),
                defaultCenter: @js($defaultCenter),
                defaultZoom: @js($field->getDefaultZoom()),
                searchable: @js($field->isSearchable() && ! $isDisabled && ! $isReadOnly),
                countries: @js($field->getCountries()),
                language: @js($field->getLanguage()),
                streetAddressesOnly: @js($field->isStreetAddressesOnly()),
                searchTypes: @js($field->getSearchTypes()),
                minSearchLength: @js($field->getMinSearchLength()),
                searchDebounce: @js($field->getSearchDebounce()),
                readOnly: @js($isDisabled || $isReadOnly),
                labels: {
                    search: @js(__('filament-flex-fields::default.map_picker.search_placeholder')),
                    summaryEmpty: @js(__('filament-flex-fields::default.map_picker.summary_empty')),
                    missingToken: @js(__('filament-flex-fields::default.map_picker.missing_token')),
                    loadFailed: @js(__('filament-flex-fields::default.map_picker.load_failed')),
                    loadingMap: @js(__('filament-flex-fields::default.map_picker.loading_map')),
                    searchLoading: @js(__('filament-flex-fields::default.map_picker.search_loading')),
                    searchRefreshing: @js(__('filament-flex-fields::default.map_picker.search_refreshing')),
                    searchMinChars: @js(__('filament-flex-fields::default.map_picker.search_min_chars')),
                    searchNoResults: @js(__('filament-flex-fields::default.map_picker.search_no_results')),
                    searchRecent: @js(__('filament-flex-fields::default.map_picker.search_recent')),
                    streetAddressRequired: @js(__('filament-flex-fields::default.map_picker.street_address_required')),
                    geocodeFailed: @js(__('filament-flex-fields::default.geocoding.failed')),
                },
            })"
            x-init="init()"
            class="fff-map-picker__root"
        >
            <div class="fff-map-picker__box">
                @if ($field->isSearchable() && ! $isDisabled && ! $isReadOnly)
                    <div class="fff-map-picker__search-wrap" x-ref="searchWrap">
                        <div
                            @class([
                                'fff-flex-text-input',
                                'fff-map-picker__search',
                                'fff-flex-text-input--'.$field->getSize(),
                                'has-focus-outline' => $field->shouldShowFocusOutline(),
                            ])
                        >
                            <div x-ref="searchShell" class="fff-flex-text-input__shell">
                                <div class="fff-flex-text-input__row">
                                    <div class="fff-flex-text-input__control">
                                        <label class="sr-only" for="{{ $statePath }}__search">{{ __('filament-flex-fields::default.map_picker.search_placeholder') }}</label>
                                        <input
                                            id="{{ $statePath }}__search"
                                            type="text"
                                            role="combobox"
                                            x-ref="searchInput"
                                            class="fff-flex-text-input__input"
                                            x-model="searchQuery"
                                            x-on:input="onSearchInput()"
                                            x-on:focus="onSearchFocus()"
                                            x-on:blur="onSearchBlur()"
                                            x-on:keydown="onSearchKeydown($event)"
                                            x-bind:placeholder="labels.search"
                                            x-bind:aria-expanded="searchOpen"
                                            x-bind:aria-activedescendant="highlightedIndex >= 0 ? geocodingOptionId(highlightedIndex) : null"
                                            aria-autocomplete="list"
                                            aria-controls="{{ $statePath }}__search-listbox"
                                            autocomplete="off"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <template x-teleport="body">
                            @include('filament-flex-fields::forms.components.partials.geocoding-search-dropdown-panel', [
                                'listboxId' => $statePath.'__search-listbox',
                                'mapContext' => true,
                            ])
                        </template>

                        <p
                            class="fff-map-picker__selection-error"
                            x-show="selectionError"
                            x-text="selectionError"
                            x-cloak
                        ></p>
                        <p
                            class="fff-map-picker__selection-error"
                            x-show="geocodeError"
                            x-text="geocodeError"
                            x-cloak
                        ></p>
                    </div>
                @endif

                <div class="fff-map-picker__map-shell">
                    <div class="fff-map-picker__gradient" aria-hidden="true"></div>

                    <div
                        class="fff-map-picker__loading"
                        x-show="mapLoading && ! mapError"
                        x-transition:leave="transition ease-in duration-500"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        x-cloak
                    >
                        <div class="fff-map-picker__loading-scene" aria-hidden="true">
                            <div class="fff-map-picker__loading-sky"></div>
                            <div class="fff-map-picker__loading-land"></div>

                            <div class="fff-map-picker__loading-grid">
                                @foreach (range(0, 23) as $tileIndex)
                                    <span
                                        class="fff-map-picker__loading-tile"
                                        style="--fff-map-picker-tile-i: {{ $tileIndex }}"
                                    ></span>
                                @endforeach
                            </div>

                            <svg
                                class="fff-map-picker__loading-roads"
                                viewBox="0 0 400 280"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    class="fff-map-picker__loading-road is-primary"
                                    d="M-20 196 C 72 168, 128 188, 204 164 S 332 150, 420 132"
                                />
                                <path
                                    class="fff-map-picker__loading-road is-secondary"
                                    d="M 88 -16 C 108 72, 96 148, 112 228 S 132 300, 148 296"
                                />
                                <path
                                    class="fff-map-picker__loading-road is-tertiary"
                                    d="M 292 296 C 276 220, 248 156, 268 108 S 312 36, 336 -12"
                                />
                            </svg>

                            <div class="fff-map-picker__loading-pin">
                                <span class="fff-map-picker__loading-ripple"></span>
                                <span class="fff-map-picker__loading-ripple"></span>
                                <span class="fff-map-picker__loading-ripple"></span>
                                <span class="fff-map-picker__loading-pin-icon"></span>
                            </div>

                            <span class="fff-map-picker__loading-shimmer"></span>
                        </div>

                        <p class="fff-map-picker__loading-label">
                            <span class="fff-map-picker__loading-label-pill">
                                <span x-text="labels.loadingMap"></span><span class="fff-map-picker__loading-dots" aria-hidden="true"><span>.</span><span>.</span><span>.</span></span>
                            </span>
                        </p>
                    </div>

                    <div
                        x-ref="mapCanvas"
                        class="fff-map-picker__canvas"
                        x-bind:class="{ 'is-loading': mapLoading }"
                        x-show="! mapError"
                    ></div>

                    <div class="fff-map-picker__error" x-show="mapError" x-cloak>
                        <p x-text="mapError"></p>
                    </div>
                </div>
            </div>

            <div class="fff-map-picker__summary" x-show="summaryLabel" x-cloak>
                <p class="fff-map-picker__summary-label" x-text="summaryLabel"></p>
            </div>
        </div>
    </div>
</x-dynamic-component>

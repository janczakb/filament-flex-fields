@php
    use Filament\Support\Enums\IconSize;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $accessToken = $field->getMapboxToken();
    $placeholder = filled($getPlaceholder())
        ? $getPlaceholder()
        : __('filament-flex-fields::default.address_autocomplete.search_placeholder');
    $hasError = filled($statePath) && $errors->has($statePath);
    $livewireKey = $getLivewireKey();
    $prefixIcon = $field->getPrefixIcon();
    $clearIcon = $field->getClearIcon();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($field->getWrapperClasses())
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'address-autocomplete',
        'livewireKey' => $getLivewireKey(),
    ])
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $field->getFields(), $field->getStoreFormat(), $field->getSize(), $field->getVariant(), filled($accessToken)])), 0, 64) }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('address-autocomplete', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="addressAutocompleteFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            accessToken: @js($accessToken),
            geocodeSearchUrl: @js($field->getGeocodeSearchUrl()),
            geocodeReverseUrl: @js($field->getGeocodeReverseUrl()),
            searchable: @js($field->isSearchable() && ! $isDisabled && ! $isReadOnly),
            countries: @js($field->getCountries()),
            language: @js($field->getLanguage()),
            streetAddressesOnly: @js($field->isStreetAddressesOnly()),
            searchTypes: @js($field->getSearchTypes()),
            readOnly: @js($isDisabled || $isReadOnly),
            minSearchLength: @js($field->getMinSearchLength()),
            searchDebounce: @js($field->getSearchDebounce()),
            labels: {
                search: @js($placeholder),
                missingToken: @js(__('filament-flex-fields::default.address_autocomplete.missing_token')),
                    searchLoading: @js(__('filament-flex-fields::default.address_autocomplete.search_loading')),
                    searchRefreshing: @js(__('filament-flex-fields::default.address_autocomplete.search_refreshing')),
                    searchMinChars: @js(__('filament-flex-fields::default.address_autocomplete.search_min_chars')),
                    searchNoResults: @js(__('filament-flex-fields::default.address_autocomplete.search_no_results')),
                    searchRecent: @js(__('filament-flex-fields::default.address_autocomplete.search_recent')),
                clear: @js(__('filament-flex-fields::default.address_autocomplete.clear')),
                streetAddressRequired: @js(__('filament-flex-fields::default.address_autocomplete.street_address_required')),
                geocodeFailed: @js(__('filament-flex-fields::default.geocoding.failed')),
            },
        })"
        x-init="init()"
        @class([
            'fff-address-autocomplete',
            'fff-flex-text-input',
            'fff-address-autocomplete--'.$getSize(),
            'fff-flex-text-input--'.$getSize(),
            'fff-address-autocomplete--'.$field->getVariant(),
            'fff-flex-text-input--'.$field->getVariant(),
            'has-actions' => ! $isDisabled && ! $isReadOnly,
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div class="fff-address-autocomplete__search-wrap">
            <div
                x-ref="searchShell"
                @class([
                'fff-flex-text-input__shell',
                'is-invalid' => $hasError,
            ])>
                <div class="fff-flex-text-input__row">
                    <div class="fff-flex-text-input__control">
                        <x-filament::input.wrapper
                            :disabled="$isDisabled"
                            :inline-prefix="true"
                            :prefix-icon="$prefixIcon"
                            :valid="! $hasError"
                            :attributes="
                                \Filament\Support\prepare_inherited_attributes(new \Illuminate\View\ComponentAttributeBag())
                                    ->class(['fff-flex-text-input__wrapper'])
                            "
                        >
                            <label class="sr-only" for="{{ $statePath }}__search">{{ $placeholder }}</label>
                            <input
                                id="{{ $statePath }}__search"
                                type="text"
                                role="combobox"
                                x-ref="searchInput"
                                class="fff-flex-text-input__input fi-input fi-input-has-inline-prefix"
                                x-model="searchQuery"
                                x-on:input="onSearchInput()"
                                x-on:focus="onSearchFocus()"
                                x-on:blur="onSearchBlur()"
                                x-on:keydown="onSearchKeydown($event)"
                                x-bind:placeholder="labels.search"
                                x-bind:disabled="readOnly"
                                x-bind:readonly="readOnly"
                                x-bind:aria-expanded="searchOpen"
                                x-bind:aria-activedescendant="highlightedIndex >= 0 ? geocodingOptionId(highlightedIndex) : null"
                                aria-autocomplete="list"
                                aria-controls="{{ $statePath }}__search-listbox"
                                autocomplete="off"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    @if (! $isDisabled && ! $isReadOnly)
                        <div
                            class="fff-flex-text-input__action-group"
                            x-show="selectedLabel"
                            x-cloak
                        >
                            <div class="fff-flex-text-input__action-item fff-flex-text-input__clear">
                                <button
                                    type="button"
                                    class="fff-flex-text-input__action-btn fff-flex-text-input__action-btn--clear"
                                    x-bind:aria-label="labels.clear"
                                    x-bind:title="labels.clear"
                                    x-on:click="clearSelection()"
                                >
                                    {{ \Filament\Support\generate_icon_html($clearIcon, size: IconSize::Small, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'fff-flex-text-input__action-icon'])) }}
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <template x-teleport="body">
                @include('filament-flex-fields::forms.components.partials.geocoding-search-dropdown-panel', [
                    'listboxId' => $statePath.'__search-listbox',
                    'mapContext' => false,
                ])
            </template>
        </div>

        <p
            class="fff-address-autocomplete__token-error"
            x-show="tokenError"
            x-text="tokenError"
            x-cloak
        ></p>

        <p
            class="fff-address-autocomplete__selection-error"
            x-show="selectionError"
            x-text="selectionError"
            x-cloak
        ></p>
        <p
            class="fff-address-autocomplete__selection-error"
            x-show="geocodeError"
            x-text="geocodeError"
            x-cloak
        ></p>
    </div>
</x-dynamic-component>

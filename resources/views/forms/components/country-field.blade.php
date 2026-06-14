@php
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $countries = $getCountriesMetadata();
    $defaultCountry = $getDefaultCountryCode();
    $stateValue = $getState();
    $selectedCode = filled($stateValue) ? strtoupper((string) $stateValue) : null;
    $selectedCountry = $selectedCode
        ? collect($countries)->firstWhere('code', $selectedCode)
        : null;
    $placeholder = filled($getPlaceholder())
        ? $getPlaceholder()
        : __('filament-flex-fields::default.country.placeholder');
    $hasError = filled($statePath) && $errors->has($statePath);
    $livewireKey = $getLivewireKey();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'country-field'])
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize(), $shouldShowCountryCode(), $shouldShowDialCode()])), 0, 64) }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('country-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="countryFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            countries: @js($countries),
            defaultCountry: @js($defaultCountry),
            disabled: @js($isDisabled),
            readOnly: @js($isReadOnly),
            searchable: @js($isSearchable()),
            showCountryCode: @js($shouldShowCountryCode()),
            showDialCode: @js($shouldShowDialCode()),
            searchPlaceholder: @js(__('filament-flex-fields::default.country.search_countries')),
            placeholder: @js($placeholder),
            browserLocaleDefault: @js($shouldUseBrowserLocaleDefault()),
            languageCountryMap: @js(\Bjanczak\FilamentFlexFields\Support\Countries::browserLanguageCountryMap()),
            allowedCountryCodes: @js($field->getResolvedCountryCodes()),
            initialState: @js($selectedCode),
        })"
        x-init="init()"
        x-on:click.outside="if ($refs.countryMenu?.contains($event.target)) { return }; closeMenu()"
        x-on:keydown.escape.window="closeMenu()"
        @class([
            'fff-country-field',
            'fff-flex-text-input',
            'fff-country-field--'.$getSize(),
            'fff-flex-text-input--'.$getSize(),
            'fff-country-field--'.$getVariant(),
            'fff-flex-text-input--'.$getVariant(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div @class([
            'fff-country-field__shell fff-flex-text-input__shell',
            'is-invalid' => $hasError,
        ])>
            <button
                type="button"
                class="fff-country-field__trigger"
                x-ref="countryTrigger"
                x-on:click.stop="toggleMenu()"
                x-bind:aria-expanded="menuOpen ? 'true' : 'false'"
                aria-haspopup="listbox"
                x-bind:aria-label="{{ json_encode($getLabel()) }}"
                @disabled($isDisabled || $isReadOnly)
            >
                @if ($selectedCountry)
                    <span
                        class="fff-country-field__flag-wrap"
                        aria-hidden="true"
                        x-show="! isEmpty"
                    >
                        <img
                            class="fff-country-field__flag is-loaded"
                            src="{{ $selectedCountry['flag_url'] }}"
                            alt="{{ e($selectedCountry['name']) }}"
                            x-bind:src="selectedCountry?.flag_url"
                            x-bind:alt="selectedCountry?.name"
                            decoding="async"
                            x-init="if ($el.complete && $el.naturalWidth > 0) { $el.classList.add('is-loaded') }"
                            x-on:load="$el.classList.add('is-loaded')"
                            x-on:error="$el.classList.remove('is-loaded')"
                        />
                    </span>
                @endif

                <span class="fff-country-field__label">
                    <span
                        @class([
                            'fff-country-field__ssr-label',
                            'is-placeholder' => ! $selectedCountry,
                        ])
                        x-bind:class="{ 'is-replaced': displayReady }"
                    >
                        @if ($selectedCountry)
                            {{ e($selectedCountry['name']) }}
                        @else
                            {{ e($placeholder) }}
                        @endif
                    </span>
                    <span
                        class="fff-country-field__live-label"
                        x-bind:class="{ 'is-ready': displayReady }"
                        x-show="! isEmpty"
                        x-text="selectedCountry?.name"
                    ></span>
                    <span
                        class="fff-country-field__live-placeholder"
                        x-bind:class="{ 'is-ready': displayReady }"
                        x-show="isEmpty"
                        x-text="placeholder"
                    ></span>
                </span>

                @if ($shouldShowCountryCode())
                    <span
                        class="fff-country-field__code"
                        x-show="! isEmpty"
                    >
                        <span
                            class="fff-country-field__ssr-meta"
                            x-bind:class="{ 'is-replaced': displayReady }"
                        >
                            @if ($selectedCountry)
                                {{ e($selectedCountry['code']) }}
                            @endif
                        </span>
                        <span
                            class="fff-country-field__live-meta"
                            x-bind:class="{ 'is-ready': displayReady }"
                            x-text="selectedCountry?.code"
                        ></span>
                    </span>
                @endif

                @if ($shouldShowDialCode())
                    <span
                        class="fff-country-field__dial"
                        x-show="! isEmpty && selectedCountry?.dial_code"
                    >
                        <span
                            class="fff-country-field__ssr-meta"
                            x-bind:class="{ 'is-replaced': displayReady }"
                        >
                            @if ($selectedCountry && filled($selectedCountry['dial_code']))
                                {{ e($selectedCountry['dial_code']) }}
                            @endif
                        </span>
                        <span
                            class="fff-country-field__live-meta"
                            x-bind:class="{ 'is-ready': displayReady }"
                            x-text="selectedCountry?.dial_code"
                        ></span>
                    </span>
                @endif

                <x-filament::icon
                    icon="heroicon-m-chevron-up-down"
                    class="fff-country-field__chevron"
                />
            </button>

            <template x-teleport="body">
                <div
                    @class([
                        'fff-country-field__menu',
                        'fff-country-field__menu--'.$getSize(),
                    ])
                    x-ref="countryMenu"
                    x-show="menuOpen"
                    x-cloak
                    x-transition.opacity.duration.150ms
                    x-bind:class="{ 'is-positioned': menuReady }"
                    x-on:click.stop
                    role="listbox"
                    x-bind:aria-label="{{ json_encode($getLabel()) }}"
                >
                    @if ($isSearchable())
                        <div class="fff-country-field__search-wrap">
                            <input
                                type="search"
                                class="fff-country-field__search"
                                x-model="countrySearch"
                                x-ref="countrySearch"
                                x-bind:placeholder="searchPlaceholder"
                                x-on:keydown.stop
                            />
                        </div>
                    @endif

                    <ul class="fff-country-field__list">
                        <template x-for="country in filteredCountries" :key="country.code">
                            <li>
                                <button
                                    type="button"
                                    class="fff-country-field__option"
                                    x-on:click="selectCountry(country.code)"
                                    x-bind:class="{ 'is-selected': country.code === state }"
                                    role="option"
                                    x-bind:aria-selected="country.code === state ? 'true' : 'false'"
                                >
                                    <span class="fff-country-field__flag-wrap" aria-hidden="true">
                                        <img
                                            class="fff-country-field__flag"
                                            x-bind:src="country.flag_url"
                                            x-bind:alt="country.name"
                                            alt=""
                                            loading="lazy"
                                            decoding="async"
                                            x-init="if ($el.complete) { $el.classList.add('is-loaded') }"
                                            x-on:load="$el.classList.add('is-loaded')"
                                            x-on:error="$el.classList.remove('is-loaded')"
                                        />
                                    </span>
                                    <span class="fff-country-field__option-name" x-text="country.name"></span>
                                    <span
                                        class="fff-country-field__option-code"
                                        x-show="showCountryCode"
                                        x-text="country.code"
                                    ></span>
                                    <span
                                        class="fff-country-field__option-dial"
                                        x-show="showDialCode"
                                        x-text="country.dial_code"
                                    ></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>
    </div>
</x-dynamic-component>

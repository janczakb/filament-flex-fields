@php
    use Bjanczak\FilamentFlexFields\Support\PhoneCountries;
@endphp

@php
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $countries = $getCountriesMetadata();
    $defaultCountry = $getDefaultCountryCode();
    $stateValue = $getState();
    $initialCountryCode = is_array($stateValue) && filled($stateValue['country'] ?? null)
        ? strtoupper((string) $stateValue['country'])
        : $defaultCountry;
    $initialCountry = collect($countries)->firstWhere('code', $initialCountryCode)
        ?? ($countries[0] ?? null);
    $placeholder = filled($getPlaceholder())
        ? $getPlaceholder()
        : __('filament-flex-fields::default.phone.placeholder');
    $hasError = filled($statePath) && $errors->has($statePath);
    $initialNational = is_array($stateValue) ? (string) ($stateValue['national'] ?? '') : '';
    $initialInputValue = PhoneCountries::formatNationalDisplay($initialNational, $initialCountryCode);
    $initialDialPrefix = PhoneCountries::dialCode($initialCountryCode);
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
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'phone-field'])
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize()])), 0, 64) }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('phone-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="phoneFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            countries: @js($countries),
            defaultCountry: @js($defaultCountry),
            initialInputValue: @js($initialInputValue),
            disabled: @js($isDisabled),
            readOnly: @js($isReadOnly),
            showInternationalPrefix: @js($showsInternationalPrefix()),
            searchable: @js($isSearchable()),
            searchPlaceholder: @js(__('filament-flex-fields::default.phone.search_countries')),
            countryLabel: @js(__('filament-flex-fields::default.phone.country')),
        })"
        x-init="init()"
        x-on:click.outside="if ($refs.countryMenu?.contains($event.target)) { return }; closeCountryMenu()"
        x-on:keydown.escape.window="closeCountryMenu()"
        @class([
            'fff-phone-field',
            'fff-flex-text-input',
            'fff-phone-field--'.$getSize(),
            'fff-flex-text-input--'.$getSize(),
            'fff-phone-field--'.$getVariant(),
            'fff-flex-text-input--'.$getVariant(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div @class([
            'fff-phone-field__shell fff-flex-text-input__shell',
            'is-invalid' => $hasError,
        ])>
            <div class="fff-phone-field__row fff-flex-text-input__row">
                <div class="fff-phone-field__country">
                    <button
                        type="button"
                        class="fff-phone-field__country-trigger"
                        x-ref="countryTrigger"
                        x-on:click.stop="toggleCountryMenu()"
                        x-bind:aria-expanded="countryOpen ? 'true' : 'false'"
                        aria-haspopup="listbox"
                        x-bind:aria-label="countryLabel"
                        @disabled($isDisabled || $isReadOnly)
                    >
                        <span class="fff-phone-field__flag-wrap" aria-hidden="true">
                            <img
                                class="fff-phone-field__flag is-loaded"
                                @if ($initialCountry)
                                    src="{{ $initialCountry['flag_url'] }}"
                                    alt="{{ e($initialCountry['name']) }}"
                                @endif
                                x-bind:src="selectedCountry.flag_url"
                                x-bind:alt="selectedCountry.name"
                                decoding="async"
                                x-init="if ($el.complete && $el.naturalWidth > 0) { $el.classList.add('is-loaded') }"
                                x-on:load="$el.classList.add('is-loaded')"
                                x-on:error="$el.classList.remove('is-loaded')"
                            />
                        </span>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            class="fff-phone-field__country-chevron"
                            aria-hidden="true"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M18 9.00005C18 9.00005 13.5811 15 12 15C10.4188 15 6 9 6 9" />
                        </svg>
                    </button>

                    <template x-teleport="body">
                        <div
                            @class([
                                'fff-phone-field__country-menu',
                                'fff-phone-field__country-menu--'.$getSize(),
                            ])
                            x-ref="countryMenu"
                            x-show="countryOpen"
                            x-cloak
                            x-transition.opacity.duration.150ms
                            x-bind:class="{ 'is-positioned': countryMenuReady }"
                            x-on:click.stop
                            role="listbox"
                            x-bind:aria-label="countryLabel"
                        >
                            @if ($isSearchable())
                                <div class="fff-phone-field__country-search-wrap">
                                    <input
                                        type="search"
                                        class="fff-phone-field__country-search"
                                        x-model="countrySearch"
                                        x-ref="countrySearch"
                                        placeholder="{{ __('filament-flex-fields::default.phone.search_countries') }}"
                                        x-on:keydown.stop
                                    />
                                </div>
                            @endif

                            <ul class="fff-phone-field__country-list">
                                <template x-for="country in filteredCountries" :key="country.code">
                                    <li>
                                        <button
                                            type="button"
                                            class="fff-phone-field__country-option"
                                            x-on:click="selectCountry(country.code)"
                                            x-bind:class="{ 'is-selected': country.code === state.country }"
                                            role="option"
                                            x-bind:aria-selected="country.code === state.country ? 'true' : 'false'"
                                        >
                                            <span class="fff-phone-field__flag-wrap" aria-hidden="true">
                                                <img
                                                    class="fff-phone-field__flag"
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
                                            <span class="fff-phone-field__country-name" x-text="country.name"></span>
                                            <span class="fff-phone-field__country-dial" x-text="country.dial_code"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>

                <div class="fff-phone-field__control fff-flex-text-input__control">
                    <div class="fff-phone-field__input-wrap">
                        @if ($showsInternationalPrefix())
                            <span
                                class="fff-phone-field__dial-prefix"
                                x-text="'(' + dialPrefix + ')'"
                            >@if ($initialDialPrefix !== '')({{ e($initialDialPrefix) }})@endif</span>
                        @endif

                        <input
                            type="tel"
                            class="fff-phone-field__input fff-flex-text-input__input fi-input"
                            x-ref="phoneInput"
                            value="{{ e($initialInputValue) }}"
                            x-model="inputValue"
                            x-on:input="onInput($event)"
                            x-on:focus="onPhoneFocus()"
                            placeholder="{{ e($placeholder) }}"
                            autocomplete="tel-national"
                            inputmode="tel"
                            @disabled($isDisabled)
                            @readonly($isReadOnly)
                        />
                    </div>
                </div>

                @if ($hasSuffixIcon())
                    <span class="fff-phone-field__suffix fff-flex-text-input__suffix" aria-hidden="true">
                        {{ \Filament\Support\generate_icon_html($getSuffixIcon()) }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</x-dynamic-component>

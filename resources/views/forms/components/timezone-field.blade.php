@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $timezones = $field->getOptionsForJs();
    $defaultTimezone = $getDefaultTimezoneIdentifier();
    $stateValue = $getState();
    $selectedId = filled($stateValue) ? (string) $stateValue : null;
    $selectedTimezone = $selectedId
        ? collect($timezones)->firstWhere('id', $selectedId)
        : null;
    $placeholder = filled($getPlaceholder())
        ? $getPlaceholder()
        : __('filament-flex-fields::default.timezone.placeholder');
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
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'timezone-field'])
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize(), $shouldShowOffset()])), 0, 64) }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('timezone-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="timezoneFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            timezones: @js($timezones),
            defaultTimezone: @js($defaultTimezone),
            disabled: @js($isDisabled),
            readOnly: @js($isReadOnly),
            searchable: @js($isSearchable()),
            showOffset: @js($shouldShowOffset()),
            searchPlaceholder: @js(__('filament-flex-fields::default.timezone.search_timezones')),
            placeholder: @js($placeholder),
            browserTimezoneDefault: @js($shouldUseBrowserTimezoneDefault()),
            allowedTimezoneIdentifiers: @js($field->getResolvedTimezoneIdentifiers()),
            initialState: @js($selectedId),
            virtualScrollThreshold: @js($field->getVirtualScrollThreshold()),
        })"
        x-init="init()"
        x-on:click.outside="if ($refs.timezoneMenu?.contains($event.target)) { return }; closeMenu()"
        x-on:keydown.escape.window="closeMenu()"
        @class([
            'fff-timezone-field',
            'fff-flex-text-input',
            'fff-timezone-field--'.$getSize(),
            'fff-flex-text-input--'.$getSize(),
            'fff-timezone-field--'.$getVariant(),
            'fff-flex-text-input--'.$getVariant(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div @class([
            'fff-timezone-field__shell fff-flex-text-input__shell',
            'is-invalid' => $hasError,
        ])>
            <button
                type="button"
                class="fff-timezone-field__trigger"
                x-ref="timezoneTrigger"
                x-on:click.stop="toggleMenu()"
                x-bind:aria-expanded="menuOpen ? 'true' : 'false'"
                aria-haspopup="listbox"
                x-bind:aria-label="{{ json_encode($getLabel()) }}"
                @disabled($isDisabled || $isReadOnly)
            >
                <span class="fff-timezone-field__icon-wrap" aria-hidden="true">
                    <x-filament::icon
                        :icon="$getPrefixIcon()"
                        class="fff-timezone-field__icon"
                    />
                </span>

                <span class="fff-timezone-field__label">
                    <span
                        @class([
                            'fff-timezone-field__ssr-label',
                            'is-placeholder' => ! $selectedTimezone,
                        ])
                        x-bind:class="{ 'is-replaced': displayReady }"
                    >
                        @if ($selectedTimezone)
                            {{ e($selectedTimezone['label']) }}
                        @else
                            {{ e($placeholder) }}
                        @endif
                    </span>
                    <span
                        class="fff-timezone-field__live-label"
                        x-bind:class="{ 'is-ready': displayReady }"
                        x-show="! isEmpty"
                        x-text="selectedTimezone?.label"
                    ></span>
                    <span
                        class="fff-timezone-field__live-placeholder"
                        x-bind:class="{ 'is-ready': displayReady }"
                        x-show="isEmpty"
                        x-text="placeholder"
                    ></span>
                </span>

                @if ($shouldShowOffset())
                    <span
                        class="fff-timezone-field__offset"
                        x-show="! isEmpty"
                    >
                        <span
                            class="fff-timezone-field__ssr-meta"
                            x-bind:class="{ 'is-replaced': displayReady }"
                        >
                            @if ($selectedTimezone)
                                {{ e($selectedTimezone['offset']) }}
                            @endif
                        </span>
                        <span
                            class="fff-timezone-field__live-meta"
                            x-bind:class="{ 'is-ready': displayReady }"
                            x-text="selectedTimezone?.offset"
                        ></span>
                    </span>
                @endif

                <x-filament::icon
                    icon="heroicon-m-chevron-up-down"
                    class="fff-timezone-field__chevron"
                />
            </button>

            <template x-teleport="body">
                <div
                    @class([
                        'fff-timezone-field__menu',
                        'fff-timezone-field__menu--'.$getSize(),
                    ])
                    x-ref="timezoneMenu"
                    x-show="menuOpen"
                    x-cloak
                    x-transition.opacity.duration.150ms
                    x-bind:class="{ 'is-positioned': menuReady }"
                    x-on:click.stop
                    role="listbox"
                    x-bind:aria-label="{{ json_encode($getLabel()) }}"
                >
                    @if ($isSearchable())
                        <div class="fff-timezone-field__search-wrap">
                            <input
                                type="search"
                                class="fff-timezone-field__search"
                                x-model="timezoneSearch"
                                x-ref="timezoneSearch"
                                x-bind:placeholder="searchPlaceholder"
                                x-on:keydown.stop
                            />
                        </div>
                    @endif

                    <ul
                        class="fff-timezone-field__list"
                        x-on:scroll.passive="onTimezoneListScroll($event)"
                    >
                        <li
                            x-show="usesVirtualScroll"
                            class="fff-timezone-field__virtual-spacer"
                            :style="`height: ${virtualSpacerTop}px`"
                            aria-hidden="true"
                        ></li>
                        <template x-for="timezone in visibleTimezones" :key="timezone.id">
                            <li>
                                <button
                                    type="button"
                                    class="fff-timezone-field__option"
                                    x-on:click="selectTimezone(timezone.id)"
                                    x-bind:class="{ 'is-selected': timezone.id === state }"
                                    role="option"
                                    x-bind:aria-selected="timezone.id === state ? 'true' : 'false'"
                                >
                                    <span class="fff-timezone-field__option-icon-wrap" aria-hidden="true">
                                        <x-filament::icon
                                            :icon="GravityIcon::Clock"
                                            class="fff-timezone-field__option-icon"
                                        />
                                    </span>
                                    <span class="fff-timezone-field__option-name" x-text="timezone.label"></span>
                                    <span
                                        class="fff-timezone-field__option-offset"
                                        x-show="showOffset"
                                        x-text="timezone.offset"
                                    ></span>
                                </button>
                            </li>
                        </template>
                        <li
                            x-show="usesVirtualScroll"
                            class="fff-timezone-field__virtual-spacer"
                            :style="`height: ${virtualSpacerBottom}px`"
                            aria-hidden="true"
                        ></li>
                    </ul>
                </div>
            </template>
        </div>
    </div>
</x-dynamic-component>

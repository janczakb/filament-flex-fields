@php
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $hasCurrencySelect = $hasCurrencySelect();
    $currencies = $getCurrenciesMetadata();
    $defaultCurrency = $getDefaultCurrencyCode();
    $hasError = filled($statePath) && $errors->has($statePath);
    $livewireKey = $getLivewireKey();
    $initialDisplay = $field->getInitialDisplay();
    $placeholder = $getPlaceholder() ?? __('filament-flex-fields::default.currency.placeholder');
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'currency-field'])
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize(), $getVariant(), $hasCurrencySelect])), 0, 64) }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('currency-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="currencyFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            currencies: @js($currencies),
            defaultCurrency: @js($defaultCurrency),
            hasCurrencySelect: @js($hasCurrencySelect),
            locale: @js($getLocale()),
            minMinor: @js($getMinMinorUnits()),
            maxMinor: @js($getMaxMinorUnits()),
            allowNegative: @js($allowsNegative()),
            animated: @js($isAnimated()),
            commitDecimalsOnBlur: @js($shouldCommitDecimalsOnBlur()),
            searchable: @js($isSearchable()),
            disabled: @js($isDisabled),
            readOnly: @js($isReadOnly),
            placeholder: @js($getPlaceholder() ?? __('filament-flex-fields::default.currency.placeholder')),
            currencyLabel: @js(__('filament-flex-fields::default.currency.currency')),
            searchPlaceholder: @js(__('filament-flex-fields::default.currency.search_currencies')),
        })"
        x-init="init()"
        x-on:click.outside="if ($refs.currencyMenu?.contains($event.target)) { return }; closeCurrencyMenu()"
        x-on:keydown.escape.window="closeCurrencyMenu()"
        @class([
            'fff-currency-field',
            'fff-flex-text-input',
            'fff-currency-field--'.$getSize(),
            'fff-flex-text-input--'.$getSize(),
            'fff-currency-field--'.$getVariant(),
            'fff-flex-text-input--'.$getVariant(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        x-bind:class="{ 'is-focused': isFocused }"
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div @class([
            'fff-currency-field__shell fff-flex-text-input__shell',
            'is-invalid' => $hasError,
        ])>
            <div class="fff-currency-field__row fff-flex-text-input__row">
                @if ($hasCurrencySelect)
                    <div class="fff-currency-field__currency">
                        <button
                            type="button"
                            class="fff-currency-field__currency-trigger"
                            x-ref="currencyTrigger"
                            x-on:click.stop="toggleCurrencyMenu()"
                            x-bind:disabled="isLocked"
                            x-bind:aria-expanded="currencyOpen"
                            aria-haspopup="listbox"
                            x-bind:aria-label="currencyLabel"
                        >
                            <span class="fff-currency-field__currency-code" x-text="activeCurrency?.code">{{ $initialDisplay['currencyCode'] }}</span>
                            <x-filament::icon
                                icon="heroicon-m-chevron-up-down"
                                class="fff-currency-field__currency-chevron"
                            />
                        </button>

                        <template x-teleport="body">
                            <div
                                x-ref="currencyMenu"
                                x-cloak
                                x-show="currencyOpen"
                                x-bind:class="{ 'is-positioned': currencyMenuReady }"
                                x-on:click.stop
                                @class([
                                    'fff-currency-field__currency-menu',
                                    'fff-teleported-menu',
                                    'fff-currency-field__currency-menu--'.$getSize(),
                                    'fff-teleported-menu--'.$getSize(),
                                ])
                                role="listbox"
                            >
                                @if ($isSearchable())
                                    <div class="fff-currency-field__currency-search-wrap fff-teleported-menu__search-wrap">
                                        <input
                                            type="search"
                                            class="fff-currency-field__currency-search fff-teleported-menu__search"
                                            x-ref="currencySearch"
                                            x-model="currencySearch"
                                            x-bind:placeholder="searchPlaceholder"
                                            x-on:keydown.stop
                                        />
                                    </div>
                                @endif

                                <ul class="fff-currency-field__currency-list">
                                    <template x-for="currency in filteredCurrencies" x-bind:key="currency.code">
                                        <li>
                                            <button
                                                type="button"
                                                class="fff-currency-field__currency-option"
                                                x-on:click="selectCurrency(currency.code)"
                                                x-bind:class="{ 'is-selected': activeCurrency?.code === currency.code }"
                                                role="option"
                                                x-bind:aria-selected="activeCurrency?.code === currency.code"
                                            >
                                                <span class="fff-currency-field__currency-option-code" x-text="currency.code"></span>
                                                <span class="fff-currency-field__currency-option-name" x-text="currency.name"></span>
                                                <span class="fff-currency-field__currency-option-symbol" x-text="currency.symbol"></span>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                @endif

                <div
                    class="fff-currency-field__control fff-flex-text-input__control"
                    x-on:click="focusInput()"
                    x-on:mousedown="onHiddenPointerDown($event)"
                >
                    <div
                        @class([
                            'fff-currency-field__display',
                            'is-empty' => $initialDisplay['isEmpty'],
                        ])
                        x-bind:class="{ 'is-empty': isEmpty && ! isFocused }"
                    >
                        <div class="fff-currency-field__digits">
                            <span
                                class="fff-currency-field__ssr-display"
                                x-bind:class="{ 'is-replaced': displayReady }"
                                aria-hidden="true"
                            >
                                @if ($initialDisplay['isEmpty'])
                                    <span class="fff-currency-field__placeholder">{{ e($placeholder) }}</span>
                                @else
                                    @if ($initialDisplay['negative'])
                                        <span class="fff-currency-field__sign">−</span>
                                    @endif

                                    @foreach ($initialDisplay['segments'] as $segment)
                                        <span @class([
                                            'fff-currency-field__separator' => $segment['type'] === 'separator',
                                            'fff-currency-field__digit' => $segment['type'] === 'digit',
                                        ])>
                                            @if ($segment['type'] === 'digit')
                                                <span class="fff-currency-field__digit-inner">{{ e($segment['char']) }}</span>
                                            @else
                                                {{ e($segment['char']) }}
                                            @endif
                                        </span>
                                    @endforeach
                                @endif
                            </span>

                            <span
                                class="fff-currency-field__live-display"
                                x-bind:class="{ 'is-ready': displayReady }"
                            >
                                <template x-if="isEmpty && ! isFocused && ! edit.negative">
                                    <span class="fff-currency-field__placeholder" x-text="placeholder"></span>
                                </template>

                                <template x-if="edit.negative">
                                    <span class="fff-currency-field__sign">−</span>
                                </template>

                                <template x-for="item in displayItems" x-bind:key="item.key">
                                    <span class="fff-currency-field__display-item">
                                        <span
                                            x-show="item.type === 'caret' && isFocused && ! isLocked"
                                            x-cloak
                                            class="fff-currency-field__caret"
                                        ></span>

                                        <span
                                            x-show="item.type !== 'caret'"
                                            x-bind:class="{
                                                'fff-currency-field__separator': item.type === 'separator',
                                                'fff-currency-field__digit': item.type === 'digit',
                                                'is-animating': item.type === 'digit' && shouldAnimateDigit(item),
                                            }"
                                            x-bind:data-fff-cursor-before="item.cursorBefore ?? null"
                                            x-bind:data-fff-cursor-after="item.cursorAfter ?? null"
                                        >
                                            <span
                                                x-bind:class="item.type === 'digit' ? 'fff-currency-field__digit-inner' : ''"
                                                x-text="item.char"
                                            ></span>
                                        </span>
                                    </span>
                                </template>

                                <template x-for="segment in ghostSegments" x-bind:key="segment.key">
                                    <span class="fff-currency-field__digit is-ghost">
                                        <span class="fff-currency-field__digit-inner" x-text="segment.char"></span>
                                    </span>
                                </template>
                            </span>
                        </div>

                        <span class="fff-currency-field__symbol" x-text="activeCurrency?.symbol">{{ e($initialDisplay['symbol']) }}</span>
                    </div>

                    <input
                        type="text"
                        inputmode="decimal"
                        class="fff-currency-field__hidden-input"
                        x-ref="hiddenInput"
                        x-on:mousedown="onHiddenPointerDown($event)"
                        x-on:focus="onFocus()"
                        x-on:blur="onBlur($event)"
                        x-on:keydown="onHiddenKeydown($event)"
                        x-bind:disabled="isLocked"
                        autocomplete="off"
                        tabindex="0"
                        x-bind:aria-label="{{ json_encode($getLabel()) }}"
                    />
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

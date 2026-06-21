@php
    use Filament\Support\Enums\IconSize;

    $fieldWrapperView = $getFieldWrapperView();
    $clearIconHtml = \Filament\Support\generate_icon_html($field->getClearIcon(), size: IconSize::ExtraSmall)?->toHtml() ?? '';
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $livewireKey = $getLivewireKey();
    $initialState = $getState();
    $componentKey = $getKey();
    $availableSets = $field->getAvailableSetsForJs();
    $placeholder = filled($getPlaceholder())
        ? $getPlaceholder()
        : __('filament-flex-fields::default.icon_picker.placeholder');
    $wrapperClasses = $getWrapperClasses();
    $isPrefixInline = $isPrefixInline();
    $isSuffixInline = $isSuffixInline();
    $prefixActions = $getPrefixActions();
    $prefixIcon = $getPrefixIcon();
    $prefixIconColor = $getPrefixIconColor();
    $prefixLabel = $getPrefixLabel();
    $suffixActions = $getSuffixActions();
    $suffixIcon = $getSuffixIcon();
    $suffixIconColor = $getSuffixIconColor();
    $suffixLabel = $getSuffixLabel();
    $initialSelectedHtml = filled($initialState) ? $field->renderIconHtml((string) $initialState) : '';
    $isInitialPlaceholder = blank($initialState);
    $showClearButton = $field->isClearable() && filled($initialState) && ! $isDisabled && ! $isReadOnly;
    $hasInitialSelection = filled($initialState);
    $layout = $field->getSearchResultsLayout();
    $isGridLayout = in_array($layout, ['grid', 'icons'], true);
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    class="fi-fo-select-wrp fff-select-field-wrapper fi-fixed-positioning-context"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'icon-picker-field'])
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'select-field'])
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'teleported-menu'])

    @once
        <link
            rel="modulepreload"
            href="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('icon-picker-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
            as="script"
            crossorigin
        />
    @endonce

    <x-filament::input.wrapper
        :disabled="$isDisabled"
        :inline-prefix="$isPrefixInline"
        :inline-suffix="$isSuffixInline"
        :prefix="$prefixLabel"
        :prefix-actions="$prefixActions"
        :prefix-icon="$prefixIcon"
        :prefix-icon-color="$prefixIconColor"
        :suffix="$suffixLabel"
        :suffix-actions="$suffixActions"
        :suffix-icon="$suffixIcon"
        :suffix-icon-color="$suffixIconColor"
        :valid="! $errors->has($statePath)"
        :x-on:focus-input.stop="'$el.querySelector(\'.fi-select-input-btn\')?.focus()'"
        :attributes="
            \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                ->class([
                    'fi-fo-select',
                    'fi-fo-select-has-inline-prefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                    ...$wrapperClasses,
                ])
        "
    >
        <x-filament-flex-fields::lazy-alpine-mount
            :eager="$hasInitialSelection"
            :mount-immediately="$isDisabled || $isReadOnly || $hasInitialSelection"
        >
        <div
            wire:ignore
            wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$field->getVariant(), $layout, $field->getGridColumns(), $field->getResolvedSetNames()])), 0, 64) }}"
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('icon-picker-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
            x-data="iconPickerFieldFormComponent({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                componentKey: @js($componentKey),
                availableSets: @js($availableSets),
                layout: @js($layout),
                closeOnSelect: @js($field->shouldCloseOnSelect()),
                gridColumns: @js($field->getGridColumns()),
                preload: @js($field->shouldPreload()),
                perPage: @js($field->getPerPage()),
                readOnly: @js($isDisabled || $isReadOnly),
                clearable: @js($field->isClearable()),
                placeholder: @js($placeholder),
                labels: {
                    search: @js(__('filament-flex-fields::default.icon_picker.search')),
                    clear: @js(__('filament-flex-fields::default.icon_picker.clear')),
                    clearSearch: @js(__('filament-flex-fields::default.icon_picker.clear_search')),
                    noResults: @js(__('filament-flex-fields::default.icon_picker.no_results')),
                    loadMore: @js(__('filament-flex-fields::default.icon_picker.load_more')),
                    allSets: @js(__('filament-flex-fields::default.icon_picker.all_sets')),
                },
                initialSelectedHtml: @js($initialSelectedHtml),
                initialSelectedName: @js(filled($initialState) ? (string) $initialState : null),
            })"
            x-init="init()"
            x-on:click.outside="if ($refs.pickerPanel?.contains($event.target)) { return }; closePanel()"
            x-on:keydown.escape.window="closePanel()"
            @class([
                'fi-select-input',
                'fff-icon-picker',
                'fff-icon-picker--layout-'.$layout,
            ])
            x-bind:class="{ 'is-trigger-hydrated': triggerHydrated }"
            role="group"
            aria-label="{{ $getLabel() }}"
        >
            <div
                @class([
                    'fi-select-input-ctn',
                    'fi-select-input-ctn-clearable' => $showClearButton,
                ])
                x-bind:class="{
                    'fi-select-input-ctn-clearable': clearable && state && ! readOnly,
                }"
            >
                <button
                    type="button"
                    class="fi-select-input-btn"
                    x-ref="pickerTrigger"
                    x-on:click.stop="togglePanel()"
                    x-bind:disabled="readOnly"
                    x-bind:aria-expanded="panelOpen"
                    aria-haspopup="dialog"
                >
                    <span class="fi-select-input-value-ctn">
                        <span
                            @class([
                                'fi-select-input-value-label',
                                'fi-select-input-placeholder' => $isInitialPlaceholder,
                            ])
                            x-bind:class="{ 'fi-select-input-placeholder': ! state }"
                        >
                            <span
                                class="fff-icon-picker__preview"
                                data-ssr-visible="{{ $hasInitialSelection ? 'true' : 'false' }}"
                                x-bind:hidden="triggerHydrated && ! state"
                                x-html="selectedHtml"
                            >@if (filled($initialSelectedHtml)){!! $initialSelectedHtml !!}@endif</span>
                            <span
                                class="fff-icon-picker__name"
                                x-text="state || placeholder"
                            >{{ $hasInitialSelection ? e($initialState) : e($placeholder) }}</span>
                        </span>
                    </span>
                </button>

                @if ($field->isClearable())
                    <button
                        type="button"
                        class="fi-select-input-value-remove-btn"
                        x-show="state && ! readOnly"
                        x-cloak
                        x-on:click.stop="clearSelection()"
                        x-bind:aria-label="labels.clear"
                    >
                        {!! $clearIconHtml !!}
                    </button>
                @endif
            </div>

            <template x-teleport="body">
                <div
                    x-ref="pickerPanel"
                    @class([
                        'fff-icon-picker__panel',
                        'fi-dropdown-panel',
                        'fff-select-dropdown-panel',
                        'fff-teleported-menu',
                        'fi-color-primary',
                        'fff-select-dropdown-panel--'.$field->getSize(),
                        'fff-select-dropdown-panel--layout-grid' => $isGridLayout,
                        'fff-select-dropdown-panel--layout-list' => $layout === 'list',
                    ])
                    x-show="panelOpen"
                    x-cloak
                    x-bind:class="{ 'is-positioned': panelReady }"
                    x-on:click.stop
                    role="dialog"
                    x-bind:aria-label="{{ json_encode($getLabel()) }}"
                >
                    @include('filament-flex-fields::forms.components.partials.icon-picker-browser')
                </div>
            </template>
        </div>
        </x-filament-flex-fields::lazy-alpine-mount>
    </x-filament::input.wrapper>
</x-dynamic-component>

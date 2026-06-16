@php
    use Filament\Support\Enums\IconSize;

    $fieldWrapperView = $getFieldWrapperView();
    $clearIconHtml = \Filament\Support\generate_icon_html($field->getClearIcon(), size: IconSize::ExtraSmall)?->toHtml() ?? '';
    $selectedOptionCheckIconHtml = \Filament\Support\generate_icon_html($field->getSelectedOptionCheckIcon(), size: IconSize::Small)?->toHtml() ?? '';
    $extraInputAttributeBag = $getExtraInputAttributeBag();
    $canSelectPlaceholder = $canSelectPlaceholder();
    $isAutofocused = $isAutofocused();
    $isDisabled = $isDisabled();
    $isMultiple = $isMultiple();
    $isReorderable = $isReorderable();
    $isSearchable = $isSearchable();
    $hasDynamicSearchResults = $hasDynamicSearchResults();
    $hasInitialNoOptionsMessage = $hasInitialNoOptionsMessage();
    $hasDynamicOptions = $hasDynamicOptions();
    $hasClientSideOptionList = $hasClientSideOptionList();
    $canOptionLabelsWrap = $canOptionLabelsWrap();
    $isRequired = $isRequired();
    $isConcealed = $isConcealed();
    $usesRichOptionHtml = $usesRichOptionHtml();
    $optionLayout = $getOptionLayout();
    $isHtmlAllowed = $isHtmlAllowed || $usesRichOptionHtml;
    $isNative = (! ($isSearchable || $isMultiple || $isHtmlAllowed) && $isNative());
    $isPrefixInline = $isPrefixInline();
    $isSuffixInline = $isSuffixInline();
    $key = $getKey();
    $id = $getId();
    $prefixActions = $getPrefixActions();
    $prefixIcon = $getPrefixIcon();
    $prefixIconColor = $getPrefixIconColor();
    $prefixLabel = $getPrefixLabel();
    $suffixActions = $getSuffixActions();
    $suffixIcon = $getSuffixIcon();
    $suffixIconColor = $getSuffixIconColor();
    $suffixLabel = $getSuffixLabel();
    $statePath = $getStatePath();
    $state = $getRawState();
    $livewireKey = $getLivewireKey();
    $wrapperClasses = $getWrapperClasses();
    $fieldLabel = $getLabel();
    $showInlineFieldLabel = $hasInlineFieldLabel() && filled($fieldLabel) && ! $isLabelHidden();
    $isInlineSearch = $hasInlineSearch();
    $isGridLayout = $optionLayout === 'grid';
    $useRichListTriggerDisplay = $usesRichOptionHtml && $optionLayout === 'list' && ! $isMultiple;
    $useRichListDropdownLayout = $field->shouldUseRichListDropdownLayout();
    $variant = $getVariant();
    $isItemCardVariant = $variant === 'item-card';
    $itemCardInitialTriggerLabel = $field->getItemCardInitialTriggerLabel();
    $initialTriggerLabel = $field->getInitialTriggerLabel();
    $initialTriggerBadges = $field->getInitialTriggerBadges();
    $isInitialTriggerPlaceholder = blank($state);
    $isUserSelectField = method_exists($field, 'renderUserOption');
    $tagRemoveIconHtml = $isUserSelectField
        ? view('filament-flex-fields::forms.components.partials.tag-pill-remove-icon')->render()
        : $clearIconHtml;
    $shouldPatchUserSelectClient = $isUserSelectField;
    $shouldPatchUserSelectMultiple = $isUserSelectField && $isMultiple;
    $userSelectMinSearchLength = ($isUserSelectField && method_exists($field, 'getMinSearchLength'))
        ? $field->getMinSearchLength()
        : 0;
    $initialOptionsForJs = ($isUserSelectField && method_exists($field, 'getInitialOptionsForJs'))
        ? $field->getInitialOptionsForJs()
        : $getOptionsForJs();
    $skipInitialOptionLabels = $isUserSelectField
        && $isMultiple
        && filled($initialSelectedUserEntriesForJs ?? []);
    $showInitialTriggerSsr = ! $isNative
        && ! $isItemCardVariant
        && (
            $initialTriggerLabel !== null
            || $initialTriggerBadges !== []
            || ($initialMultipleTriggerHtml ?? null) !== null
            || ($isMultiple && filled($getPlaceholder()))
        );
    $selectFieldPatchConfig = [
        'statePath' => $statePath,
        'isInlineSearch' => $isInlineSearch,
        'isGridLayout' => $isGridLayout,
        'useRichListTriggerDisplay' => $useRichListTriggerDisplay,
        'useRichListDropdownLayout' => $useRichListDropdownLayout,
        'dropdownAlign' => $getDropdownAlign(),
        'fieldLabel' => $showInlineFieldLabel ? (string) $fieldLabel : null,
        'clearIconHtml' => $clearIconHtml,
        'selectedOptionCheckIconHtml' => $selectedOptionCheckIconHtml,
        'shouldPatchUserSelectClient' => $shouldPatchUserSelectClient,
        'shouldPatchUserSelectMultiple' => $shouldPatchUserSelectMultiple,
        'isUserSelectField' => $isUserSelectField,
        'initialSelectedUserEntries' => $initialSelectedUserEntriesForJs ?? [],
        'userSelectMinSearchLength' => $userSelectMinSearchLength,
    ];
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    :label-sr-only="$showInlineFieldLabel"
    class="fi-fo-select-wrp fff-select-field-wrapper fi-fixed-positioning-context"
>
    @if (! $isNative)
        @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'select-field'])

        @if ($isUserSelectField)
            @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'user-select'])
        @endif
    @endif
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
        :x-on:focus-input.stop="$isNative ? '$el.querySelector(\'select\')?.focus()' : '$el.querySelector(\'.fi-select-input-btn\')?.focus()'"
        :attributes="
            \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                ->class([
                    'fi-fo-select',
                    'fi-fo-select-has-inline-prefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                    'fi-fo-select-native' => $isNative,
                    ...$wrapperClasses,
                ])
        "
    >
        @if ($isNative)
            @php
                $options = $getOptions();
            @endphp

            <select
                {{
                    $extraInputAttributeBag
                        ->merge([
                            'autofocus' => $isAutofocused,
                            'disabled' => $isDisabled,
                            'id' => $id,
                            'required' => $isRequired && (! $isConcealed),
                            'wire:key' => $hasDynamicOptions ? ($livewireKey . '.' . substr(md5(serialize($options)), 0, 64)) : null,
                            $applyStateBindingModifiers('wire:model') => $statePath,
                        ], escape: false)
                        ->class([
                            'fi-select-input',
                            'fi-select-input-has-inline-prefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                        ])
                }}
            >
                @if ($canSelectPlaceholder)
                    <option value="">
                        @if (! $isDisabled)
                            {{ $getPlaceholder() }}
                        @endif
                    </option>
                @endif

                @foreach ($options as $value => $label)
                    @if (is_array($label))
                        <optgroup label="{{ $value }}">
                            @foreach ($label as $groupedValue => $groupedLabel)
                                <option
                                    @disabled($isOptionDisabled($groupedValue, $groupedLabel))
                                    value="{{ $groupedValue }}"
                                >
                                    {{ is_array($groupedLabel) ? ($groupedLabel['label'] ?? $groupedValue) : $groupedLabel }}
                                </option>
                            @endforeach
                        </optgroup>
                    @else
                        <option
                            @disabled($isOptionDisabled($value, $label))
                            value="{{ $value }}"
                        >
                            {{ is_array($label) ? ($label['label'] ?? $value) : $label }}
                        </option>
                    @endif
                @endforeach
            </select>
        @else
            @once
                <link
                    rel="modulepreload"
                    href="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('select', 'filament/forms') }}"
                    as="script"
                    crossorigin
                />
            @endonce
            @if ($shouldPatchUserSelectClient)
                @include('filament-flex-fields::forms.components.partials.user-select-scripts')
            @endif
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('select-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
                x-data="fffSelectFieldCoordinator({ patchConfig: @js($selectFieldPatchConfig) })"
                x-init="init()"
                class="fff-select-field__shell"
            >
                <div
                    class="fi-hidden"
                    x-data="{
                        isDisabled: @js($isDisabled),
                        init() {
                            const container = $el.nextElementSibling
                            container.dispatchEvent(
                                new CustomEvent('set-select-property', {
                                    detail: { isDisabled: this.isDisabled },
                                }),
                            )
                        },
                    }"
                ></div>
                <div
                    data-fff-select-root
                    x-load
                    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('select', 'filament/forms') }}"
                    x-data="selectFormComponent({
                            canOptionLabelsWrap: @js($canOptionLabelsWrap),
                            canSelectPlaceholder: @js($canSelectPlaceholder),
                            getOptionLabelUsing: @if ($hasClientSideOptionList)
                                null
                            @else
                                async () => {
                                    return await Livewire.fireAction(
                                        $wire.__instance,
                                        'callSchemaComponentMethod',
                                        [@js($key), 'getOptionLabel'],
                                        { async: true },
                                    )
                                }
                            @endif,
                            getOptionLabelsUsing: @if ($hasClientSideOptionList)
                                null
                            @else
                                async () => {
                                    return await Livewire.fireAction(
                                        $wire.__instance,
                                        'callSchemaComponentMethod',
                                        [@js($key), 'getOptionLabelsForJs'],
                                        { async: true },
                                    )
                                }
                            @endif,
                            getOptionsUsing: @if ($hasDynamicOptions)
                                async () => {
                                    return await Livewire.fireAction(
                                        $wire.__instance,
                                        'callSchemaComponentMethod',
                                        [@js($key), 'getOptionsForJs'],
                                        { async: true },
                                    )
                                }
                            @else
                                null
                            @endif,
                            getSearchResultsUsing: @if ($hasDynamicSearchResults)
                                async (search) => {
                                    return await Livewire.fireAction(
                                        $wire.__instance,
                                        'callSchemaComponentMethod',
                                        [@js($key), 'getSearchResultsForJs', { search }],
                                        { async: true },
                                    )
                                }
                            @else
                                null
                            @endif,
                            hasDynamicOptions: @js($hasDynamicOptions),
                            hasDynamicSearchResults: @js($hasDynamicSearchResults),
                            hasInitialNoOptionsMessage: @js($hasInitialNoOptionsMessage),
                            initialOptionLabel: @js((blank($state) || $isMultiple) ? null : $getOptionLabel()),
                            initialOptionLabels: @js($skipInitialOptionLabels ? [] : ((filled($state) && $isMultiple) ? $getOptionLabelsForJs() : [])),
                            initialSelectedUserEntries: @js($initialSelectedUserEntriesForJs ?? []),
                            initialState: @js($state),
                            isAutofocused: @js($isAutofocused),
                            isDisabled: @js($isDisabled),
                            isHtmlAllowed: @js($isHtmlAllowed),
                            isMultiple: @js($isMultiple),
                            isReorderable: @js($isReorderable),
                            isSearchable: @js($isSearchable),
                            livewireId: @js($this->getId()),
                            loadingMessage: @js($getLoadingMessage()),
                            maxItems: @js($getMaxItems()),
                            maxItemsMessage: @js($getMaxItemsMessage()),
                            noOptionsMessage: @js($getNoOptionsMessage()),
                            noSearchResultsMessage: @js($getNoSearchResultsMessage()),
                            options: @js($initialOptionsForJs),
                            optionsLimit: @js($getOptionsLimit()),
                            placeholder: @js($getPlaceholder()),
                            position: @js($getPosition()),
                            searchDebounce: @js($getSearchDebounce()),
                            searchingMessage: @js($getSearchingMessage()),
                            searchPrompt: @js($getSearchPrompt()),
                            searchableOptionFields: @js($getSearchableOptionFields()),
                            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                            statePath: @js($statePath),
                        })"
                wire:ignore
                wire:key="{{ $livewireKey }}.{{
                    substr(md5(serialize([
                        $isDisabled,
                        $isReorderable,
                        $getSize(),
                        $getVariant(),
                        $optionLayout,
                        $usesRichOptionHtml,
                    ])), 0, 64)
                }}"
                x-on:keydown.esc="select.dropdown.isActive && $event.stopPropagation()"
                x-on:set-select-property="$event.detail.isDisabled ? select.disable() : select.enable()"
                {{
                    $attributes
                        ->merge($getExtraAlpineAttributes(), escape: false)
                        ->class(['fi-select-input'])
                }}
            >
                @if ($isItemCardVariant && ! $isNative && $itemCardInitialTriggerLabel !== null)
                    <div class="fff-select-item-card-ssr" aria-hidden="true">
                        <span @class([
                            'fff-select-item-card-ssr__value',
                            'is-placeholder' => blank($state),
                        ])>{{ $itemCardInitialTriggerLabel }}</span>
                        <span class="fff-select-item-card-ssr__chevron" aria-hidden="true"></span>
                    </div>
                @endif

                @if ($showInitialTriggerSsr)
                    <div
                        @class([
                            'fff-select-trigger-ssr',
                            'fi-select-input-ctn' => $field->isClearable() && filled($state) && ! $isMultiple && ! $isDisabled,
                            'fi-select-input-ctn-clearable' => $field->isClearable() && filled($state) && ! $isMultiple && ! $isDisabled,
                            'fff-select-trigger-ssr--multiple' => $isMultiple,
                            'fff-select-trigger-ssr--clearable' => $field->isClearable() && filled($state) && ! $isDisabled,
                            'fff-select-trigger-ssr--inline-field-label' => $showInlineFieldLabel,
                            'fff-select-trigger-ssr--rich-list-trigger' => $useRichListTriggerDisplay,
                            'fff-user-select-trigger-ssr' => $isUserSelectField,
                        ])
                        aria-hidden="true"
                    >
                        <span class="fff-select-trigger-ssr__btn">
                            @if ($showInlineFieldLabel)
                                <span class="fff-select-inline-field-label">{{ $fieldLabel }}</span>
                            @endif

                            <span class="fff-select-trigger-ssr__value-ctn fi-select-input-value-ctn">
                                @if ($isMultiple)
                                    @if (($initialMultipleTriggerHtml ?? null) !== null)
                                        <span class="fi-select-input-value-label">{!! $initialMultipleTriggerHtml !!}</span>
                                    @elseif ($initialTriggerBadges !== [])
                                        <span class="fi-select-input-value-badges-ctn">
                                            @foreach ($initialTriggerBadges as $badge)
                                                <span class="fi-badge fi-size-md">
                                                    <span class="fi-badge-label-ctn">
                                                        <span class="fi-badge-label">{{ $badge['label'] }}</span>
                                                    </span>
                                                    <span class="fi-badge-delete-btn" aria-hidden="true">
                                                        <svg class="fi-icon fi-size-xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" data-slot="icon"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z"></path></svg>
                                                    </span>
                                                </span>
                                            @endforeach
                                        </span>
                                    @else
                                        <span class="fi-select-input-placeholder">{{ $getPlaceholder() }}</span>
                                    @endif
                                @else
                                    <span @class([
                                        'fi-select-input-value-label',
                                        'fi-select-input-placeholder' => $isInitialTriggerPlaceholder,
                                    ])>
                                        @if ($isHtmlAllowed)
                                            {!! $initialTriggerLabel !!}
                                        @else
                                            {{ $initialTriggerLabel }}
                                        @endif
                                    </span>
                                @endif
                            </span>
                        </span>

                        @if ($field->isClearable() && filled($state) && ! $isMultiple && ! $isDisabled)
                            <button
                                type="button"
                                class="fi-select-input-value-remove-btn"
                                aria-hidden="true"
                                tabindex="-1"
                            >
                                {!! $clearIconHtml !!}
                            </button>
                        @endif
                    </div>
                @endif

                <div x-ref="select"></div>
                </div>
            </div>
        @endif
    </x-filament::input.wrapper>

    @if (($userSelectTagsHtml ?? null) !== null)
        {!! $userSelectTagsHtml !!}
    @endif
</x-dynamic-component>

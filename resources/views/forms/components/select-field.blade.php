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
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    :label-sr-only="$showInlineFieldLabel"
    class="fi-fo-select-wrp fff-select-field-wrapper fi-fixed-positioning-context"
>
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
                x-init="
                    const isInlineSearch = @js($isInlineSearch);
                    const isGridLayout = @js($isGridLayout);
                    const useRichListTriggerDisplay = @js($useRichListTriggerDisplay);
                    const useRichListDropdownLayout = @js($useRichListDropdownLayout);
                    const dropdownAlign = @js($getDropdownAlign());
                    const fieldLabel = @js($showInlineFieldLabel ? (string) $fieldLabel : null);

                    const resolveTriggerLabel = (option) => {
                        if (option.triggerLabel !== undefined) {
                            return option.triggerLabel;
                        }

                        return option.label;
                    };

                    const findTriggerLabelInOptions = (value, options) => {
                        const key = String(value);

                        if (! options || ! Array.isArray(options)) {
                            return null;
                        }

                        for (const option of options) {
                            if (option.options && Array.isArray(option.options)) {
                                const found = findTriggerLabelInOptions(value, option.options);

                                if (found !== null) {
                                    return found;
                                }

                                continue;
                            }

                            if (String(option.value) === key) {
                                return resolveTriggerLabel(option);
                            }
                        }

                        return null;
                    };

                    const populateRepositoryWithTriggerLabels = (select, options) => {
                        if (! options || ! Array.isArray(options)) {
                            return;
                        }

                        for (const option of options) {
                            if (option.options && Array.isArray(option.options)) {
                                populateRepositoryWithTriggerLabels(select, option.options);

                                continue;
                            }

                            if (option.value === undefined) {
                                continue;
                            }

                            select.labelRepository[option.value] = resolveTriggerLabel(option);
                        }
                    };

                    const injectInlineFieldLabel = (select) => {
                        if (! fieldLabel || ! select?.selectButton) {
                            return;
                        }

                        const button = select.selectButton;

                        if (button.querySelector('.fff-select-inline-field-label')) {
                            return;
                        }

                        const labelElement = document.createElement('span');
                        labelElement.className = 'fff-select-inline-field-label';
                        labelElement.textContent = fieldLabel;

                        button.insertBefore(labelElement, select.selectedDisplay);
                        button.classList.add('fff-select-input-btn--inline-field-label');
                    };

                    const setupInlineSearch = (select) => {
                        if (! isInlineSearch || ! select?.searchInput || ! select?.searchContainer) {
                            return null;
                        }

                        const { searchContainer, searchInput, selectButton } = select;

                        searchContainer.classList.add('fff-select-inline-search-ctn');
                        selectButton.insertBefore(searchContainer, select.selectedDisplay.nextSibling);

                        return {
                            activate: () => {
                                selectButton.classList.add('fi-select-input-btn--search-active');
                                searchInput.focus();
                            },
                            deactivate: () => {
                                selectButton.classList.remove('fi-select-input-btn--search-active');
                                searchInput.value = '';
                                select.searchQuery = '';
                            },
                        };
                    };

                    const applyGridDropdownWidth = (select) => {
                        if (! isGridLayout || ! select?.dropdown) {
                            return;
                        }

                        const dropdown = select.dropdown;

                        dropdown.classList.add('fi-width-none');
                        dropdown.style.setProperty('width', '22rem', 'important');
                        dropdown.style.setProperty('max-width', 'min(22rem, calc(100vw - 2rem))', 'important');
                        dropdown.style.setProperty('min-width', '22rem', 'important');
                    };

                    const applyRichListDropdownLayout = (select) => {
                        if (! useRichListTriggerDisplay || ! select?.dropdown || ! select?.selectButton) {
                            return;
                        }

                        const dropdown = select.dropdown;
                        const buttonWidth = select.selectButton.offsetWidth;

                        dropdown.style.width = `${buttonWidth}px`;
                        dropdown.style.minWidth = `${buttonWidth}px`;
                        dropdown.style.maxWidth = `min(${buttonWidth}px, calc(100vw - 2rem))`;
                        dropdown.style.overflowX = 'visible';
                    };

                    const applyPlainListDropdownWidth = (select) => {
                        if (isGridLayout || useRichListTriggerDisplay || ! select?.dropdown || ! select?.selectButton) {
                            return;
                        }

                        const dropdown = select.dropdown;
                        const buttonWidth = select.selectButton.offsetWidth;
                        const viewportCap = Math.max(buttonWidth, window.innerWidth - 32);

                        dropdown.style.width = 'auto';
                        dropdown.style.minWidth = `${buttonWidth}px`;
                        dropdown.style.maxWidth = `${viewportCap}px`;

                        const measuredWidth = Math.ceil(dropdown.scrollWidth);
                        const targetWidth = Math.min(Math.max(buttonWidth, measuredWidth), viewportCap);

                        dropdown.style.width = `${targetWidth}px`;
                        dropdown.style.minWidth = `${buttonWidth}px`;
                    };

                    const patchGridResizeListener = (select) => {
                        if (! isGridLayout || select.__fffGridResizePatched) {
                            return;
                        }

                        select.__fffGridResizePatched = true;

                        if (select.resizeListener) {
                            window.removeEventListener('resize', select.resizeListener);
                        }

                        select.resizeListener = () => {
                            applyGridDropdownWidth(select);
                            select.positionDropdown();
                        };

                        window.addEventListener('resize', select.resizeListener);
                    };

                    const readDropdownGapPx = (wrapper) => {
                        if (wrapper?.__fffDropdownGapPx !== undefined) {
                            return wrapper.__fffDropdownGapPx;
                        }

                        const gap = wrapper
                            ? getComputedStyle(wrapper).getPropertyValue('--fff-select-dropdown-gap').trim()
                            : '0.5rem';

                        const probe = document.createElement('div');
                        probe.style.position = 'absolute';
                        probe.style.visibility = 'hidden';
                        probe.style.pointerEvents = 'none';
                        probe.style.height = gap || '0.5rem';
                        probe.style.width = '0';

                        (wrapper ?? document.body).appendChild(probe);

                        const pixels = probe.offsetHeight;

                        probe.remove();

                        if (wrapper) {
                            wrapper.__fffDropdownGapPx = pixels;
                        }

                        return pixels;
                    };

                    const applyQuickPortaledPosition = (select) => {
                        const dropdown = select?.dropdown;
                        const button = select?.selectButton;

                        if (! dropdown || ! button) {
                            return;
                        }

                        const wrapper = resolveSelectWrapper(select);
                        const gap = readDropdownGapPx(wrapper);
                        const buttonRect = button.getBoundingClientRect();
                        const viewportPadding = 5;
                        const viewportWidth = window.innerWidth;
                        const opensAbove = resolveDropdownOpensAbove(
                            select,
                            buttonRect,
                            dropdown.offsetHeight || 0,
                            gap,
                        );

                        dropdown.style.position = 'fixed';
                        dropdown.style.margin = '0';
                        dropdown.style.left = `${Math.max(viewportPadding, Math.min(buttonRect.left, viewportWidth - Math.max(buttonRect.width, dropdown.offsetWidth) - viewportPadding))}px`;
                        dropdown.style.right = 'auto';
                        dropdown.style.minWidth = `${buttonRect.width}px`;
                        dropdown.style.top = opensAbove
                            ? `${buttonRect.top - (dropdown.offsetHeight || 0) - gap}px`
                            : `${buttonRect.bottom + gap}px`;

                        dropdown.classList.toggle('fff-select-dropdown-panel--above', opensAbove);
                        dropdown.classList.toggle('fff-select-dropdown-panel--below', ! opensAbove);
                    };

                    const resolveSelectWrapper = (select) => {
                        return select?.selectButton?.closest('.fff-select-field')
                            ?? select?.dropdown?.closest('.fff-select-field')
                            ?? null;
                    };

                    const syncFocusOutlineOpenState = (select, isOpen) => {
                        const wrapper = resolveSelectWrapper(select);

                        if (! wrapper) {
                            return;
                        }

                        wrapper.classList.toggle('is-dropdown-open', isOpen);
                    };

                    const hideInitialTriggerSsr = (select) => {
                        const ssr = select?.selectButton
                            ?.closest('.fi-select-input')
                            ?.querySelector('.fff-select-trigger-ssr');

                        if (ssr) {
                            ssr.classList.add('is-replaced');
                        }
                    };

                    const syncDropdownPanelState = (select) => {
                        const dropdown = select?.dropdown;
                        const wrapper = resolveSelectWrapper(select);

                        if (! dropdown || ! wrapper) {
                            return;
                        }

                        dropdown.classList.toggle('fff-select-dropdown-panel--layout-grid', isGridLayout);
                        dropdown.classList.toggle('fff-select-dropdown-panel--layout-list', useRichListDropdownLayout);
                        dropdown.classList.toggle(
                            'fff-select-dropdown-panel--layout-plain',
                            ! isGridLayout && ! useRichListDropdownLayout,
                        );
                        dropdown.classList.toggle('fi-width-none', isGridLayout);

                        ['sm', 'md', 'lg'].forEach((size) => {
                            dropdown.classList.toggle(
                                `fff-select-dropdown-panel--${size}`,
                                wrapper.classList.contains(`fff-select-field--${size}`),
                            );
                        });

                        [
                            '--fff-select-focus',
                            '--fff-select-grid-selected-label',
                            '--fff-select-grid-ring-bg',
                            '--fff-select-menu-hover',
                            '--fff-select-menu-selected',
                        ].forEach((name) => {
                            const value = getComputedStyle(wrapper).getPropertyValue(name).trim();

                            if (value !== '') {
                                dropdown.style.setProperty(name, value);
                            }
                        });

                        const isUserSelect = wrapper.classList.contains('fff-user-select');
                        dropdown.classList.toggle('fff-select-dropdown-panel--user-select', isUserSelect);

                        if (isUserSelect) {
                            [
                                '--fff-user-select-avatar-size',
                            ].forEach((name) => {
                                const value = getComputedStyle(wrapper).getPropertyValue(name).trim();

                                if (value !== '') {
                                    dropdown.style.setProperty(name, value);
                                }
                            });
                        }
                    };

                    const focusDropdownSearch = (select) => {
                        if (! select?.isSearchable || ! select?.searchInput || select?.isMultiple || ! select?.isOpen) {
                            return;
                        }

                        requestAnimationFrame(() => {
                            select.searchInput?.focus();
                        });
                    };

                    const cancelDropdownCloseAnimation = (select) => {
                        if (select.__fffDropdownCloseTimeout) {
                            clearTimeout(select.__fffDropdownCloseTimeout);
                            select.__fffDropdownCloseTimeout = null;
                        }

                        if (select.__fffDropdownCloseListener && select.dropdown) {
                            select.dropdown.removeEventListener('transitionend', select.__fffDropdownCloseListener);
                            select.__fffDropdownCloseListener = null;
                        }
                    };

                    const revealDropdownPanel = (select) => {
                        const dropdown = select?.dropdown;

                        if (! dropdown) {
                            return;
                        }

                        cancelDropdownCloseAnimation(select);
                        dropdown.classList.remove('is-closing');
                        dropdown.style.removeProperty('opacity');
                        dropdown.classList.remove('is-open');
                        void dropdown.offsetWidth;

                        requestAnimationFrame(() => {
                            if (select.isOpen) {
                                dropdown.classList.add('is-open');
                            }
                        });
                    };

                    const scheduleDropdownLayout = (select) => {
                        if (! select?.dropdown || ! select.isOpen) {
                            return;
                        }

                        requestAnimationFrame(() => {
                            if (! select.isOpen || ! select.dropdown) {
                                return;
                            }

                            applyDropdownGlassStyles(select);

                            if (isGridLayout) {
                                patchGridResizeListener(select);
                                applyGridDropdownWidth(select);
                            }

                            if (shouldAlignDropdownEnd(select)) {
                                finalizePortaledDropdownLayout(select, { alignEnd: true });

                                return;
                            }

                            if (applyGridDropdownPosition(select)) {
                                return;
                            }

                            if (isPortaledDropdown(select)) {
                                finalizePortaledDropdownLayout(select);

                                return;
                            }

                            select.__fffOriginalPositionDropdown?.call(select);
                        });
                    };

                    const hideDropdownPanel = (select, onHidden) => {
                        const dropdown = select?.dropdown;

                        if (! dropdown) {
                            onHidden?.();

                            return;
                        }

                        cancelDropdownCloseAnimation(select);
                        dropdown.classList.remove('is-open');

                        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                        if (reducedMotion) {
                            dropdown.classList.remove('is-closing');
                            onHidden?.();

                            return;
                        }

                        dropdown.classList.add('is-closing');

                        let finished = false;

                        const complete = () => {
                            if (finished) {
                                return;
                            }

                            finished = true;
                            cancelDropdownCloseAnimation(select);
                            dropdown.classList.remove('is-closing');
                            onHidden?.();
                        };

                        select.__fffDropdownCloseListener = (event) => {
                            if (event.target !== dropdown) {
                                return;
                            }

                            if (event.propertyName === 'opacity' || event.propertyName === 'transform') {
                                complete();
                            }
                        };

                        dropdown.addEventListener('transitionend', select.__fffDropdownCloseListener);
                        select.__fffDropdownCloseTimeout = window.setTimeout(complete, 180);
                    };

                    const patchDocumentClickListener = (select) => {
                        if (! select?.documentClickListener || select.__fffDocumentClickPatched) {
                            return;
                        }

                        select.__fffDocumentClickPatched = true;

                        document.removeEventListener('click', select.documentClickListener);

                        const originalDocumentClickListener = select.documentClickListener;

                        select.documentClickListener = (event) => {
                            if (select.dropdown?.contains(event.target)) {
                                return;
                            }

                            originalDocumentClickListener(event);
                        };

                        document.addEventListener('click', select.documentClickListener);
                    };

                    const isPortaledDropdown = (select) => select?.dropdown?.parentNode === document.body;

                    const resolveDropdownOpensAbove = (select, buttonRect, dropdownHeight, gap) => {
                        const viewportPadding = 5;

                        if (select.position === 'top') {
                            return true;
                        }

                        if (select.position === 'bottom') {
                            return false;
                        }

                        const spaceBelow = window.innerHeight - buttonRect.bottom - viewportPadding;
                        const spaceAbove = buttonRect.top - viewportPadding;
                        const needed = dropdownHeight + gap;

                        if (spaceBelow >= needed) {
                            return false;
                        }

                        if (spaceAbove >= needed) {
                            return true;
                        }

                        return spaceAbove > spaceBelow;
                    };

                    const applyPortaledDropdownPosition = (select, { alignEnd = false } = {}) => {
                        const dropdown = select?.dropdown;
                        const button = select?.selectButton;

                        if (! dropdown || ! button || ! select.isOpen) {
                            return false;
                        }

                        const wrapper = resolveSelectWrapper(select);
                        const gap = readDropdownGapPx(wrapper);
                        const buttonRect = button.getBoundingClientRect();
                        const viewportPadding = 5;
                        const viewportWidth = window.innerWidth;

                        const dropdownWidth = dropdown.offsetWidth;
                        const dropdownHeight = dropdown.offsetHeight;
                        const opensAbove = resolveDropdownOpensAbove(select, buttonRect, dropdownHeight, gap);

                        dropdown.style.position = 'fixed';
                        dropdown.style.margin = '0';

                        if (alignEnd) {
                            dropdown.style.left = 'auto';
                            dropdown.style.right = `${Math.max(viewportPadding, viewportWidth - buttonRect.right)}px`;

                            if (buttonRect.right - dropdownWidth < viewportPadding) {
                                dropdown.style.right = 'auto';
                                dropdown.style.left = `${viewportPadding}px`;
                            }
                        } else {
                            let left = buttonRect.left;

                            left = Math.max(
                                viewportPadding,
                                Math.min(left, viewportWidth - dropdownWidth - viewportPadding),
                            );

                            dropdown.style.left = `${left}px`;
                            dropdown.style.right = 'auto';
                        }

                        dropdown.style.top = opensAbove
                            ? `${buttonRect.top - dropdownHeight - gap}px`
                            : `${buttonRect.bottom + gap}px`;

                        dropdown.classList.toggle('fff-select-dropdown-panel--above', opensAbove);
                        dropdown.classList.toggle('fff-select-dropdown-panel--below', ! opensAbove);

                        return true;
                    };

                    const applyGridDropdownPosition = (select) => {
                        if (! isGridLayout) {
                            return false;
                        }

                        applyGridDropdownWidth(select);

                        return applyPortaledDropdownPosition(select);
                    };

                    const shouldAlignDropdownEnd = (select) => {
                        return dropdownAlign === 'end'
                            && select?.dropdown
                            && select?.selectButton
                            && select.isOpen;
                    };

                    const finalizePortaledDropdownLayout = (select, { alignEnd = false } = {}) => {
                        if (! isPortaledDropdown(select)) {
                            return;
                        }

                        applyRichListDropdownLayout(select);
                        applyPlainListDropdownWidth(select);
                        applyPortaledDropdownPosition(select, { alignEnd });
                    };

                    const portalDropdownToBody = (select) => {
                        const dropdown = select?.dropdown;

                        if (! dropdown || dropdown.parentNode === document.body) {
                            return;
                        }

                        if (! select.__fffDropdownOriginalParent) {
                            select.__fffDropdownOriginalParent = dropdown.parentNode;
                        }

                        document.body.appendChild(dropdown);
                    };

                    const clearPortaledDropdownStyles = (dropdown) => {
                        if (! dropdown) {
                            return;
                        }

                        [
                            'position',
                            'top',
                            'left',
                            'right',
                            'bottom',
                            'margin',
                            'min-width',
                            'max-width',
                            'width',
                        ].forEach((property) => {
                            dropdown.style.removeProperty(property);
                        });
                    };

                    const restoreDropdownParent = (select) => {
                        const dropdown = select?.dropdown;
                        const parent = select?.__fffDropdownOriginalParent;

                        if (! dropdown || ! parent || dropdown.parentNode === parent) {
                            return;
                        }

                        clearPortaledDropdownStyles(dropdown);
                        parent.appendChild(dropdown);
                    };

                    const applyDropdownGlassStyles = (select) => {
                        const dropdown = select?.dropdown;

                        if (! dropdown || dropdown.dataset.fffGlassApplied === 'true') {
                            return;
                        }

                        const wrapper = resolveSelectWrapper(select);
                        const styles = wrapper ? getComputedStyle(wrapper) : null;

                        const readVar = (name, fallback) => {
                            const value = styles?.getPropertyValue(name).trim();

                            return value !== '' ? value : fallback;
                        };

                        dropdown.style.setProperty('background', readVar('--fff-select-menu-bg', 'rgb(255 255 255 / 0.9)'), 'important');
                        dropdown.style.setProperty('backdrop-filter', readVar('--fff-select-menu-blur', 'blur(16px) saturate(180%)'), 'important');
                        dropdown.style.setProperty('-webkit-backdrop-filter', readVar('--fff-select-menu-blur', 'blur(16px) saturate(180%)'), 'important');
                        dropdown.style.setProperty('border', 'none', 'important');
                        dropdown.style.setProperty(
                            'box-shadow',
                            readVar(
                                '--fff-select-menu-shadow',
                                '0 2px 8px 0 #0000000f, 0 -6px 12px 0 #00000008, 0 14px 28px 0 #00000014',
                            ),
                            'important',
                        );
                        dropdown.style.setProperty('border-radius', readVar('--fff-select-menu-radius', '1.5rem'), 'important');
                        dropdown.style.setProperty('z-index', '20', 'important');
                        dropdown.dataset.fffGlassApplied = 'true';
                    };

                    const clearIconHtml = @js($clearIconHtml);
                    const selectedOptionCheckIconHtml = @js($selectedOptionCheckIconHtml);

                    const patchSelectedOptionCheckIcon = (select) => {
                        if (! selectedOptionCheckIconHtml || select.__fffSelectedOptionCheckPatched) {
                            return;
                        }

                        select.__fffSelectedOptionCheckPatched = true;

                        const originalCreateOptionElement = select.createOptionElement.bind(select);

                        select.createOptionElement = function (value, label) {
                            const option = originalCreateOptionElement(value, label);

                            if (! option.classList.contains('fi-selected')) {
                                return option;
                            }

                            const labelSpan = option.querySelector(':scope > span');

                            if (! labelSpan || labelSpan.classList.contains('fff-select-option-selected-row')) {
                                return option;
                            }

                            const labelContent = this.isHtmlAllowed
                                ? labelSpan.innerHTML
                                : labelSpan.textContent;

                            labelSpan.innerHTML = '';
                            labelSpan.classList.add('fff-select-option-selected-row');

                            const labelWrapper = document.createElement('span');
                            labelWrapper.className = 'fff-select-option-selected-row__label';

                            if (this.isHtmlAllowed) {
                                labelWrapper.innerHTML = labelContent;
                            } else {
                                labelWrapper.textContent = labelContent;
                            }

                            const check = document.createElement('span');
                            check.className = 'fff-select-option-selected-check';
                            check.setAttribute('aria-hidden', 'true');
                            check.innerHTML = selectedOptionCheckIconHtml;

                            labelSpan.appendChild(labelWrapper);
                            labelSpan.appendChild(check);

                            return option;
                        };
                    };

                    const patchClearButtonIcon = (select) => {
                        if (! clearIconHtml) {
                            return;
                        }

                        const apply = () => {
                            const isDisabled = resolveSelectWrapper(select)?.classList.contains('fi-disabled') ?? false;

                            select.container?.querySelectorAll('.fi-select-input-value-remove-btn').forEach((button) => {
                                if (isDisabled) {
                                    button.style.display = 'none';

                                    return;
                                }

                                button.style.display = '';
                                button.innerHTML = clearIconHtml;
                            });
                        };

                        const originalUpdateSelectedDisplay = select.updateSelectedDisplay.bind(select);

                        select.updateSelectedDisplay = async function (...args) {
                            const result = await originalUpdateSelectedDisplay(...args);

                            apply();

                            return result;
                        };

                        apply();
                    };

                    let destroyUserSelectMultiple = null;

                    const applySelectFieldPatches = (select) => {
                        if (! select || select.__fffPatchesApplied) {
                            return;
                        }

                        select.__fffPatchesApplied = true;

                        patchClearButtonIcon(select);
                        patchSelectedOptionCheckIcon(select);
                        @if ($shouldPatchUserSelectClient)
                            select.initialSelectedUserEntries = @js($initialSelectedUserEntriesForJs ?? []);
                            select.__fffMinSearchLength = @js($userSelectMinSearchLength);
                            patchUserSelectClient(select);
                        @endif
                        @if ($shouldPatchUserSelectMultiple)
                            destroyUserSelectMultiple = patchUserSelectMultiple(select);
                        @endif
                        patchDocumentClickListener(select);

                        if (select.dropdown) {
                            select.dropdown.classList.add('fff-select-dropdown-panel');
                            syncDropdownPanelState(select);
                            applyDropdownGlassStyles(select);
                        }

                        const originalOpenDropdown = select.openDropdown.bind(select);
                        const originalCloseDropdown = select.closeDropdown.bind(select);
                        const originalPositionDropdown = select.positionDropdown.bind(select);

                        select.__fffOriginalPositionDropdown = originalPositionDropdown;

                        select.positionDropdown = function (...args) {
                            if (this.__fffUseQuickPosition) {
                                if (applyGridDropdownPosition(this)) {
                                    return;
                                }

                                applyQuickPortaledPosition(this);

                                return;
                            }

                            if (shouldAlignDropdownEnd(this)) {
                                if (isGridLayout) {
                                    applyGridDropdownWidth(this);
                                }

                                finalizePortaledDropdownLayout(this, { alignEnd: true });

                                return;
                            }

                            if (applyGridDropdownPosition(this)) {
                                return;
                            }

                            if (isPortaledDropdown(this)) {
                                finalizePortaledDropdownLayout(this);

                                return;
                            }

                            originalPositionDropdown(...args);
                        };

                        select.openDropdown = async function (...args) {
                            cancelDropdownCloseAnimation(this);

                            this.__fffUseQuickPosition = true;

                            const openPromise = originalOpenDropdown(...args);

                            this.__fffUseQuickPosition = false;

                            if (this.dropdown && this.isOpen) {
                                portalDropdownToBody(this);
                                syncDropdownPanelState(this);
                                syncFocusOutlineOpenState(this, true);
                                applyQuickPortaledPosition(this);
                                revealDropdownPanel(this);
                            }

                            openPromise.then(() => {
                                if (! this.dropdown || ! this.isOpen) {
                                    return;
                                }

                                scheduleDropdownLayout(this);
                                focusDropdownSearch(this);
                            });
                        };

                        select.closeDropdown = function (...args) {
                            syncFocusOutlineOpenState(this, false);

                            const selectRef = this;

                            if (! this.dropdown) {
                                return originalCloseDropdown.apply(selectRef, args);
                            }

                            this.isOpen = false;
                            this.selectButton?.setAttribute('aria-expanded', 'false');

                            hideDropdownPanel(this, () => {
                                restoreDropdownParent(selectRef);
                                originalCloseDropdown.apply(selectRef, args);
                            });
                        };

                        if (! useRichListTriggerDisplay) {
                            select.populateLabelRepositoryFromOptions = function (options) {
                                populateRepositoryWithTriggerLabels(this, options);
                            };

                            const originalGetSelectedOptionLabel = select.getSelectedOptionLabel.bind(select);

                            select.getSelectedOptionLabel = function (value) {
                                const triggerLabel = findTriggerLabelInOptions(value, this.options);

                                if (triggerLabel !== null) {
                                    this.labelRepository[value] = triggerLabel;

                                    return triggerLabel;
                                }

                                return originalGetSelectedOptionLabel(value);
                            };

                            const originalGetSelectedOptionLabels = select.getSelectedOptionLabels.bind(select);

                            select.getSelectedOptionLabels = function () {
                                const labels = originalGetSelectedOptionLabels();

                                for (const value of Object.keys(labels)) {
                                    const triggerLabel = findTriggerLabelInOptions(value, this.options);

                                    if (triggerLabel !== null) {
                                        labels[value] = triggerLabel;
                                    }
                                }

                                return labels;
                            };

                            const originalGetLabelForSingleSelection = select.getLabelForSingleSelection.bind(select);

                            select.getLabelForSingleSelection = async function () {
                                const triggerLabel = findTriggerLabelInOptions(this.state, this.options);

                                if (triggerLabel !== null) {
                                    this.labelRepository[this.state] = triggerLabel;

                                    return triggerLabel;
                                }

                                return originalGetLabelForSingleSelection();
                            };

                            const originalUpdateSelectedDisplay = select.updateSelectedDisplay.bind(select);

                            select.updateSelectedDisplay = async function () {
                                populateRepositoryWithTriggerLabels(this, this.options);

                                return originalUpdateSelectedDisplay();
                            };

                            if (! select.__fffTriggerLabelsPrimed) {
                                populateRepositoryWithTriggerLabels(select, select.options);
                            }
                        }

                        injectInlineFieldLabel(select);

                        const inlineSearch = setupInlineSearch(select);

                        if (inlineSearch || isGridLayout) {
                            const openDropdownWithGlass = select.openDropdown.bind(select);

                            select.openDropdown = async function (...args) {
                                await openDropdownWithGlass(...args);
                                applyGridDropdownWidth(select);
                                patchGridResizeListener(select);
                                inlineSearch?.activate();
                            };

                            const closeDropdownWithGlass = select.closeDropdown.bind(select);

                            select.closeDropdown = function (...args) {
                                inlineSearch?.deactivate();

                                return closeDropdownWithGlass(...args);
                            };

                            if (inlineSearch) {
                                select.selectButton.addEventListener('click', () => {
                                    if (select.isOpen) {
                                        inlineSearch.activate();
                                    }
                                });
                            }
                        }

                        if (useRichListTriggerDisplay || select.__fffTriggerLabelsPrimed) {
                            hideInitialTriggerSsr(select);
                        }
                    };

                    const primeTriggerLabelDisplay = async (select) => {
                        if (useRichListTriggerDisplay || ! select || select.__fffTriggerLabelsPrimed) {
                            if (useRichListTriggerDisplay) {
                                hideInitialTriggerSsr(select);
                            }

                            return;
                        }

                        populateRepositoryWithTriggerLabels(select, select.options);
                        await select.updateSelectedDisplay();
                        select.__fffTriggerLabelsPrimed = true;
                        hideInitialTriggerSsr(select);
                    };

                    const waitForTriggerPaint = () => new Promise((resolve) => {
                        requestAnimationFrame(() => requestAnimationFrame(resolve));
                    });

                    const bootSelectFieldPatches = async () => {
                        if (! select) {
                            requestAnimationFrame(bootSelectFieldPatches);

                            return;
                        }

                        applySelectFieldPatches(select);

                        @if ($isUserSelectField)
                            await select.updateSelectedDisplay();
                            await waitForTriggerPaint();
                            (window.__fffHideInitialTriggerSsr ?? hideInitialTriggerSsr)(select);
                        @else
                            await primeTriggerLabelDisplay(select);
                        @endif
                    };

                    $nextTick(bootSelectFieldPatches);

                    return () => {
                        destroyUserSelectMultiple?.();
                    };
                "
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
        @endif
    </x-filament::input.wrapper>

    @if (($userSelectTagsHtml ?? null) !== null)
        {!! $userSelectTagsHtml !!}
    @endif
</x-dynamic-component>

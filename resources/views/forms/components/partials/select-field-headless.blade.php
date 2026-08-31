@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\Enums\IconSize;

    $headlessVerifiedIconHtml = $isUserSelectField
        ? (\Filament\Support\generate_icon_html(GravityIcon::SealCheck, size: IconSize::ExtraSmall)?->toHtml() ?? '')
        : '';
    $headlessUserSelectNoOptionsIconHtml = $isUserSelectField
        ? (\Filament\Support\generate_icon_html(GravityIcon::Persons, size: IconSize::Large)?->toHtml() ?? '')
        : '';
    $headlessUserSelectNoResultsIconHtml = $isUserSelectField
        ? (\Filament\Support\generate_icon_html(GravityIcon::Magnifier, size: IconSize::Large)?->toHtml() ?? '')
        : '';
    $headlessSelectNoOptionsIconHtml = ! $isUserSelectField
        ? (\Filament\Support\generate_icon_html(GravityIcon::LayoutCells, size: IconSize::Large)?->toHtml() ?? '')
        : '';
    $headlessSelectNoResultsIconHtml = ! $isUserSelectField
        ? (\Filament\Support\generate_icon_html(GravityIcon::Magnifier, size: IconSize::Large)?->toHtml() ?? '')
        : '';
    $headlessSearchClearIconHtml = \Filament\Support\generate_icon_html(GravityIcon::CircleXmarkFill, size: IconSize::Small)?->toHtml() ?? '';
    $headlessInitialOptionsForJs = ($isUserSelectField && method_exists($field, 'getInitialOptionsForJs'))
        ? $field->getInitialOptionsForJs()
        : (method_exists($field, 'getHeadlessInitialOptionsForJs')
            ? $field->getHeadlessInitialOptionsForJs()
            : (($hasDynamicOptions && ! $isPreloaded()) ? [] : $getOptionsForJs()));
    $headlessInitialOptionLabel = (blank($state) || $isMultiple) ? null : $field->getInitialTriggerLabel();
    $headlessInitialState = $state;
    $headlessInitialOptionLabels = ($skipInitialOptionLabels ?? false)
        ? []
        : ((filled($state) && $isMultiple) ? $getOptionLabelsForJs() : []);
    $headlessMinSearchLength = ($isUserSelectField && method_exists($field, 'getMinSearchLength'))
        ? $field->getMinSearchLength()
        : 0;
    $headlessComponentKey = $getKey();
    $headlessTriggerDir = $getExtraAttributeBag()->get('dir');
    $headlessInlineSearchSsrValue = ($isInlineSearch && $isSearchable && ! $isMultiple)
        ? ($isInitialTriggerPlaceholder ? '' : strip_tags($initialTriggerLabel))
        : null;
    $shouldDeferHeadlessAlpine = false;
@endphp

@once
    <link
        rel="modulepreload"
        href="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('select-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        as="script"
        crossorigin
    />
@endonce

<div
    wire:ignore
    wire:key="{{ $livewireKey }}.headless.{{ substr(md5(serialize([
        $isDisabled,
        $isMultiple,
        $isSearchable,
        $getSize(),
        $getVariant(),
        $optionLayout,
        $usesRichOptionHtml,
    ])), 0, 64) }}"
    @class([
        'fff-select-field__shell',
        'fff-select-field__shell--headless',
        'fi-select-input',
    ])
>
    <x-filament-flex-fields::lazy-alpine-mount
        :eager="! $shouldDeferHeadlessAlpine"
        :mount-immediately="! $shouldDeferHeadlessAlpine"
        :mount-on-interaction="$shouldDeferHeadlessAlpine"
        :wrap-slot="false"
    >
    @if ($isItemCardVariant && ($itemCardInitialTriggerLabel ?? null) !== null)
        <div
            class="fff-select-item-card-ssr"
            aria-hidden="true"
        >
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
                'fff-select-trigger-ssr--layout-grid' => $isGridLayout,
                'fff-select-trigger-ssr--clearable' => $field->isClearable() && filled($state) && ! $isMultiple && ! $isDisabled,
                'fff-select-trigger-ssr--inline-field-label' => $showInlineFieldLabel,
                'fff-select-trigger-ssr--inline-search' => $isInlineSearch && $isSearchable && ! $isMultiple,
                'fff-select-trigger-ssr--rich-list-trigger' => $useRichListTriggerDisplay,
                'fff-user-select-trigger-ssr' => $isUserSelectField,
            ])
            aria-hidden="true"
        >
            <span class="fff-select-trigger-ssr__btn">
                @if ($showInlineFieldLabel)
                    <span class="fff-select-inline-field-label">{{ $fieldLabel }}</span>
                @endif

                @if ($isInlineSearch && $isSearchable && ! $isMultiple)
                    <span class="fff-select-inline-search-ctn">
                        <input
                            type="text"
                            class="fi-input"
                            readonly
                            tabindex="-1"
                            aria-hidden="true"
                            @if (filled($headlessTriggerDir))
                                dir="{{ $headlessTriggerDir }}"
                            @endif
                            value="{{ $headlessInlineSearchSsrValue }}"
                        />
                    </span>
                @else
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
                                                {!! $chipRemoveIconHtml !!}
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
                @endif
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

    @if ($shouldDeferHeadlessAlpine)
        <template x-if="shouldMount">
            <div>
    @endif

        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('select-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
            x-data="fffHeadlessSelectField({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            initialState: @js($headlessInitialState),
            statePath: @js($statePath),
            componentKey: @js($headlessComponentKey),
            multiple: @js($isMultiple),
            searchable: @js($isSearchable),
            options: @js($headlessInitialOptionsForJs),
            placeholder: @js($getPlaceholder()),
            disabled: @js($isDisabled),
            clearable: @js($field->isClearable()),
            keepSelectedOptionsInDropdown: @js($field->shouldKeepSelectedOptionsInDropdown()),
            isHtmlAllowed: @js($isHtmlAllowed),
            isGridLayout: @js($isGridLayout),
            useRichListDropdownLayout: @js($useRichListDropdownLayout),
            selectedOptionCheckIconHtml: @js($selectedOptionCheckIconHtml),
            hasDynamicSearchResults: @js($hasDynamicSearchResults),
            hasPaginatedSearchResults: @js(method_exists($field, 'hasPaginatedSearchResults') ? $field->hasPaginatedSearchResults() : false),
            hasDynamicOptions: @js($hasDynamicOptions),
            hasClientSideOptionList: @js($hasClientSideOptionList),
            isPreloaded: @js($isPreloaded()),
            hasInitialNoOptionsMessage: @js($hasInitialNoOptionsMessage),
            searchDebounce: @js($getSearchDebounce()),
            minSearchLength: @js($headlessMinSearchLength),
            @php($headlessSelectMessages = $field->getSelectMessagesForJs())
            loadingMessage: @js($headlessSelectMessages['loading']),
            searchingMessage: @js($headlessSelectMessages['searching']),
            loadingMoreMessage: @js($headlessSelectMessages['loadingMore']),
            noOptionsMessage: @js($headlessSelectMessages['noOptions']),
            noSearchResultsMessage: @js($headlessSelectMessages['noSearchResults']),
            searchPrompt: @js($headlessSelectMessages['searchPrompt']),
            initialOptionLabel: @js($headlessInitialOptionLabel),
            initialOptionLabels: @js($headlessInitialOptionLabels),
            initialSelectedUserEntries: @js($initialSelectedUserEntriesForJs ?? []),
            isUserSelectField: @js($isUserSelectField),
            verifiedIconHtml: @js($headlessVerifiedIconHtml),
            tagRemoveIconHtml: @js($tagRemoveIconHtml),
            userSelectNoOptionsIconHtml: @js($headlessUserSelectNoOptionsIconHtml),
            userSelectNoResultsIconHtml: @js($headlessUserSelectNoResultsIconHtml),
            selectNoOptionsIconHtml: @js($headlessSelectNoOptionsIconHtml),
            selectNoResultsIconHtml: @js($headlessSelectNoResultsIconHtml),
            selectEmptyStateHints: @js($field->getSelectEmptyStateHintsForJs()),
            userSelectEmptyStateHints: @js($isUserSelectField ? $field->getUserSelectEmptyStateHintsForJs() : []),
            canOptionLabelsWrap: @js($canOptionLabelsWrap),
            isReorderable: @js($isReorderable),
            @php($smartSuggest = $field->getSmartSuggestConfigForJs())
            smartSuggestEnabled: @js($smartSuggest['enabled']),
            recentOptionValues: @js($smartSuggest['recent']),
            suggestedOptionValues: @js($smartSuggest['suggested']),
            allowCreateOption: @js($smartSuggest['allowCreate']),
            createOptionLabel: @js($smartSuggest['createLabel']),
            entityMentionsEnabled: @js($smartSuggest['entityMentions']),
            mentionTrigger: @js($smartSuggest['mentionTrigger']),
            entityMentionSectionLabel: @js(__('filament-flex-fields::default.select_field.entity_mentions.section')),
            inlineSearch: @js($isInlineSearch),
            optionGroupSeparators: @js(method_exists($field, 'hasOptionGroupSeparators') ? $field->hasOptionGroupSeparators() : true),
            dropdownAlign: @js($field->getDropdownAlign()),
            matchTriggerWidth: @js($variant !== 'item-card'),
        })"
            x-init="init()"
            x-on:keydown.escape.stop="comboboxOpen && comboboxCloseMenu()"
            x-on:click.outside="if ($refs.headlessMenu?.contains($event.target)) { return }; comboboxCloseMenu()"
            @class([
                'fff-select-field__interactive',
            ])
            {{
                $attributes
                    ->merge($getExtraAlpineAttributes(), escape: false)
            }}
        >
        <div
            @class([
                'fi-select-input-ctn',
                'fi-select-input-ctn-clearable' => $field->isClearable() && ! $isMultiple && ! $isDisabled,
                'fi-select-input-ctn-option-labels-not-wrapped' => ! $canOptionLabelsWrap,
            ])
            x-ref="headlessTriggerCtn"
        >
            <button
                type="button"
                class="fi-select-input-btn"
                x-ref="headlessTrigger"
                x-bind:disabled="disabled"
                x-bind:aria-expanded="comboboxOpen ? 'true' : 'false'"
                aria-haspopup="listbox"
                x-bind:class="{ 'fi-select-input-btn--search-active': inlineSearch && searchable && comboboxOpen, 'is-loading': shouldShowHeadlessTriggerLoading() }"
                x-on:click="onHeadlessTriggerClick($event)"
                x-on:keydown.down.prevent="disabled ? null : (comboboxOpen ? comboboxMoveHighlight(1) : comboboxOpenMenu())"
                x-on:keydown.up.prevent="disabled ? null : (comboboxOpen ? comboboxMoveHighlight(-1) : comboboxOpenMenu())"
                x-on:keydown.enter.prevent="disabled ? null : (comboboxOpen ? comboboxSelectHighlighted() : comboboxOpenMenu())"
                x-on:keydown="onHeadlessTriggerMentionKeydown($event)"
            >
                @if ($showInlineFieldLabel)
                    <span class="fff-select-inline-field-label">{{ $fieldLabel }}</span>
                @endif

                <span class="fi-select-input-value-ctn">
                    @if ($isItemCardVariant && ! $isMultiple)
                        <span
                            @class([
                                'fi-select-input-value-label' => filled($state),
                                'fi-select-input-placeholder' => blank($state),
                            ])
                            x-bind:class="{
                                'fi-select-input-value-label': isTriggerLabelSelected(),
                                'fi-select-input-placeholder': ! isTriggerLabelSelected(),
                            }"
                            x-text="triggerLabelHtml()"
                        >{{ $itemCardInitialTriggerLabel }}</span>
                    @else
                    <template x-if="! isUserSelectField && multiple && selectedChips().length > 0">
                        <span
                            class="fi-select-input-value-badges-ctn"
                            x-ref="headlessBadgesCtn"
                            @if ($isReorderable && ! $isDisabled)
                                x-sortable
                                data-sortable-animation-duration="150"
                                x-on:end.stop="reorderSelectedChips($event)"
                                x-on:click.stop
                                x-on:mousedown.stop
                            @endif
                        >
                            <template x-for="(chip, chipIndex) in selectedChips()" x-bind:key="`${chip.value}-${chipIndex}`">
                                <span
                                    @class([
                                        'fi-badge fi-size-md',
                                        'fi-reorderable' => $isReorderable && ! $isDisabled,
                                    ])
                                    x-bind:data-value="chip.value"
                                    x-bind:class="{ 'fff-select-entity-mention-chip': chip.isEntityMention }"
                                    @if ($isReorderable && ! $isDisabled)
                                        x-bind:x-sortable-item="chipIndex"
                                        x-sortable-handle
                                    @endif
                                >
                                    <span class="fi-badge-label-ctn">
                                        <template x-if="isHtmlAllowed">
                                            <span
                                                class="fi-badge-label"
                                                x-bind:class="{ 'fi-wrapped': canOptionLabelsWrap }"
                                                x-html="chip.label"
                                            ></span>
                                        </template>
                                        <template x-if="! isHtmlAllowed">
                                            <span
                                                class="fi-badge-label"
                                                x-bind:class="{ 'fi-wrapped': canOptionLabelsWrap }"
                                                x-text="chip.label"
                                            ></span>
                                        </template>
                                    </span>
                                    <button
                                        type="button"
                                        class="fi-badge-delete-btn"
                                        x-bind:disabled="disabled"
                                        x-on:click.stop="comboboxDeselectValue(chip.value)"
                                        x-bind:aria-label="'Remove ' + chip.label"
                                    >
                                        {!! $chipRemoveIconHtml !!}
                                    </button>
                                </span>
                            </template>
                        </span>
                    </template>

                    <template x-if="! isUserSelectField && (! multiple || selectedChips().length === 0) && ! (inlineSearch && searchable && ! multiple)">
                        <span
                            x-bind:class="{
                                'fi-select-input-value-label': isTriggerLabelSelected(),
                                'fi-select-input-placeholder': ! isTriggerLabelSelected(),
                            }"
                        >
                            <template x-if="isHtmlAllowed && isTriggerLabelSelected()">
                                <span x-html="triggerLabelHtml()"></span>
                            </template>
                            <template x-if="isHtmlAllowed && ! isTriggerLabelSelected()">
                                <span x-text="triggerLabelHtml()"></span>
                            </template>
                            <template x-if="! isHtmlAllowed">
                                <span x-text="triggerLabelHtml()"></span>
                            </template>
                        </span>
                    </template>

                    <template x-if="isUserSelectField && isTriggerLabelSelected() && ! (inlineSearch && searchable && ! multiple)">
                        <span class="fi-select-input-value-label" x-html="triggerLabelHtml()"></span>
                    </template>
                    <template x-if="isUserSelectField && ! isTriggerLabelSelected() && ! (inlineSearch && searchable && ! multiple)">
                        <span class="fi-select-input-placeholder" x-text="triggerLabelHtml()"></span>
                    </template>
                    @endif
                </span>

                @if ($isInlineSearch && $isSearchable)
                    <span class="fff-select-inline-search-ctn" x-on:click.stop x-on:mousedown.stop>
                        <input
                            type="text"
                            role="combobox"
                            autocomplete="off"
                            class="fi-input"
                            @if (filled($headlessTriggerDir))
                                dir="{{ $headlessTriggerDir }}"
                            @endif
                            x-ref="headlessInlineSearchInput"
                            x-bind:value="inlineSearchInputValue()"
                            x-on:input="onInlineSearchInput($event)"
                            x-on:focus="onInlineSearchFocus()"
                            x-on:blur="onInlineSearchBlur()"
                            x-on:keydown.down.prevent="comboboxMoveHighlight(1)"
                            x-on:keydown.up.prevent="comboboxMoveHighlight(-1)"
                            x-on:keydown.enter.prevent="comboboxSelectHighlighted()"
                            x-on:keydown.escape.stop="comboboxCloseMenu()"
                            x-on:keydown="onHeadlessTriggerMentionKeydown($event)"
                            x-bind:readonly="inlineSearchInputReadonly()"
                            x-bind:placeholder="inlineSearchInputPlaceholder()"
                            x-bind:disabled="disabled"
                            x-bind:aria-expanded="comboboxOpen ? 'true' : 'false'"
                        />
                    </span>
                @endif

                @if ($isItemCardVariant && ! $isMultiple)
                    <span class="fff-select-item-card-trigger__chevron" aria-hidden="true"></span>
                @endif

                <span
                    class="fff-select-trigger-loading-indicator"
                    x-show="shouldShowHeadlessTriggerLoading()"
                    x-cloak
                    aria-hidden="true"
                >
                    <x-filament::loading-indicator class="fff-select-trigger-loading-indicator__spinner" />
                </span>
            </button>

            @if ($field->isClearable() && ! $isMultiple && ! $isDisabled)
                <button
                    type="button"
                    class="fi-select-input-value-remove-btn"
                    x-show="isTriggerLabelSelected()"
                    x-on:click.stop="clearSelection()"
                    x-bind:aria-label="'Clear selection'"
                >
                    {!! $clearIconHtml !!}
                </button>
            @endif
        </div>

        <template x-teleport="body">
            <div
                x-ref="headlessMenu"
                x-show="comboboxOpen"
                x-cloak
                x-on:click.stop
                x-on:keydown="onHeadlessMenuKeydown($event)"
                x-bind:class="{ 'is-positioned': menuReady }"
                @class([
                    'fi-dropdown-panel',
                    'fff-select-dropdown-panel',
                    'fff-select-headless-menu',
                    'fff-select-dropdown-panel--below',
                    'fff-teleported-menu',
                    'fff-select-dropdown-panel--dropdown-fixed',
                    'fff-select-dropdown-panel--layout-' . ($isUserSelectField ? 'list' : ($useRichListDropdownLayout ? 'list' : ($isGridLayout ? 'grid' : 'plain'))),
                    'fff-select-dropdown-panel--user-select' => $isUserSelectField,
                    'fi-select-input-ctn-option-labels-not-wrapped' => ! $canOptionLabelsWrap,
                    'fi-width-none' => $isGridLayout && ! $isUserSelectField,
                ])
                role="listbox"
                x-bind:aria-label="{{ json_encode($fieldLabel ?? $statePath) }}"
            >
                @if ($isSearchable && ! $isInlineSearch)
                    <div class="fi-select-input-search-ctn fff-select-input-search-ctn">
                        <input
                            type="search"
                            class="fi-input fi-select-input-search-input"
                            x-model="comboboxQuery"
                            x-ref="headlessSearchInput"
                            x-on:input="comboboxSetQuery($event.target.value)"
                            x-on:keydown.down.prevent="comboboxMoveHighlight(1)"
                            x-on:keydown.up.prevent="comboboxMoveHighlight(-1)"
                            x-on:keydown.enter.prevent="comboboxSelectHighlighted()"
                            x-on:keydown.escape.stop="comboboxCloseMenu()"
                            x-bind:placeholder="@js($getSearchPrompt())"
                        />
                        <button
                            type="button"
                            class="fff-select-search-clear-btn"
                            x-cloak
                            x-show="String(comboboxQuery ?? '').length > 0"
                            x-on:click.stop="comboboxClearSearch()"
                            x-bind:aria-label="@js(__('filament-flex-fields::default.select_field.clear_search'))"
                        >
                            {!! $headlessSearchClearIconHtml !!}
                        </button>
                    </div>
                @endif

                <div
                    @class([
                        'fi-select-input-options-ctn',
                        'fi-dropdown-list' => $isGridLayout,
                    ])
                    x-ref="headlessOptionsList"
                    x-on:scroll.passive="onHeadlessOptionsScroll($event)"
                >
                    <div x-show="shouldShowHeadlessDropdownOptions()" class="fff-select-headless-options-shell">
                        <div
                            class="fff-select-headless-options-root"
                            x-bind:class="{ 'fff-select-headless-options-root--with-separators': optionGroupSeparators }"
                            x-bind:style="comboboxVirtualListStyle()"
                        >
                            <template x-for="row in comboboxFilteredDropdownRows()" :key="row.key">
                                <div
                                    class="fff-select-headless-dropdown-row"
                                    x-bind:data-row-type="row.type"
                                >
                                    <template x-if="row.type === 'section'">
                                        <div class="fi-dropdown-header fff-select-smart-section" x-text="row.label"></div>
                                    </template>

                                    <template x-if="row.type === 'separator'">
                                        <div class="fff-select-option-group-separator" role="separator" aria-hidden="true"></div>
                                    </template>

                                    <template x-if="row.type === 'create'">
                                        <button
                                            type="button"
                                            class="fi-select-input-option fff-select-smart-create"
                                            x-on:click="selectCreateOption(row.value)"
                                            x-text="row.label"
                                        ></button>
                                    </template>

                                    <template x-if="row.type === 'group'">
                                        <div class="fi-select-input-option-group" role="presentation">
                                            <div class="fi-dropdown-header" x-text="row.label"></div>
                                            <div class="fi-dropdown-list" x-show="! row.virtualHeaderOnly">
                                                <template x-for="option in row.options" :key="headlessOptionValue(option)">
                                                    @include('filament-flex-fields::forms.components.partials.select-field-headless-option')
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="row.type === 'option'">
                                        <template x-for="option in [row.option]" :key="headlessOptionValue(option)">
                                            @include('filament-flex-fields::forms.components.partials.select-field-headless-option')
                                        </template>
                                    </template>
                                </div>
                            </template>

                            <div
                                x-ref="headlessLoadMoreSentinel"
                                x-show="shouldShowHeadlessLoadMoreSentinel()"
                                class="fff-select-load-more-sentinel"
                                aria-hidden="true"
                            ></div>

                            <div
                                x-show="shouldShowHeadlessLoadMoreIndicator()"
                                x-cloak
                                class="fff-select-load-more"
                                role="status"
                                aria-live="polite"
                            >
                                <x-filament::loading-indicator class="fff-select-load-more__spinner" />
                                <span class="fff-select-load-more__label" x-text="headlessLoadMoreLabel()"></span>
                            </div>
                        </div>
                    </div>

                    @if ($isUserSelectField)
                        <div
                            x-show="shouldShowHeadlessUserSelectSkeleton()"
                            x-cloak
                            class="fff-user-select__dropdown-skeleton"
                            role="status"
                            aria-live="polite"
                            x-bind:aria-busy="shouldShowHeadlessUserSelectSkeleton() ? 'true' : 'false'"
                            x-bind:aria-label="headlessUserSelectSkeletonAriaLabel()"
                        >
                            <template x-for="(row, index) in headlessUserSelectSkeletonRows()" :key="index">
                                <div
                                    class="fff-user-select__dropdown-skeleton-option"
                                    x-bind:style="'--fff-user-select-skeleton-i: ' + index"
                                >
                                    <span class="fff-user-select__dropdown-skeleton-avatar" aria-hidden="true"></span>
                                    <span class="fff-user-select__dropdown-skeleton-body">
                                        <span class="fff-user-select__dropdown-skeleton-line is-primary"></span>
                                        <span class="fff-user-select__dropdown-skeleton-line is-secondary"></span>
                                    </span>
                                </div>
                            </template>
                        </div>

                        <div
                            x-show="shouldShowHeadlessUserSelectEmptyState()"
                            x-cloak
                            class="fff-user-select__dropdown-empty"
                            role="status"
                        >
                            <span class="fff-user-select__dropdown-empty-icon" aria-hidden="true" x-html="headlessUserSelectEmptyIconHtml()"></span>
                            <span class="fff-user-select__dropdown-empty-title" x-text="headlessUserSelectEmptyTitle()"></span>
                            <span class="fff-user-select__dropdown-empty-hint" x-text="headlessUserSelectEmptyHint()"></span>
                        </div>
                    @else
                        <div
                            x-show="shouldShowHeadlessSelectSkeleton()"
                            x-cloak
                            class="fff-select-dropdown-loading"
                            role="status"
                            aria-live="polite"
                            x-bind:aria-busy="shouldShowHeadlessSelectSkeleton() ? 'true' : 'false'"
                            x-bind:aria-label="headlessSelectSkeletonAriaLabel()"
                        >
                            <x-filament::loading-indicator class="fff-select-dropdown-loading__spinner" />
                            <span class="fff-select-dropdown-loading__label" x-text="headlessSelectSkeletonAriaLabel()"></span>
                        </div>

                        <div
                            x-show="shouldShowHeadlessSelectEmptyState()"
                            x-cloak
                            class="fff-select-dropdown-empty"
                            role="status"
                        >
                            <span class="fff-select-dropdown-empty-icon" aria-hidden="true" x-html="headlessSelectEmptyIconHtml()"></span>
                            <span class="fff-select-dropdown-empty-title" x-text="headlessSelectEmptyTitle()"></span>
                            <span class="fff-select-dropdown-empty-hint" x-text="headlessSelectEmptyHint()"></span>
                        </div>
                    @endif
                </div>
            </div>
        </template>
        </div>

    @if ($shouldDeferHeadlessAlpine)
            </div>
        </template>
    @endif
    </x-filament-flex-fields::lazy-alpine-mount>
</div>

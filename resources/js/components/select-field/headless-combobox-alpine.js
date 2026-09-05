import { createComboboxEngine, DEFAULT_VIRTUALIZE_THRESHOLD, DEFAULT_VIRTUAL_WINDOW_SIZE } from '../../core/combobox-engine.js'
import { normalizeSearchQuery } from '../../core/search-normalize.js'
import { createSearchableSelectMenuMixin, runAfterSheetEnter } from '../../core/searchable-select-menu.js'
import { resolveOverlayMode } from '../../core/overlay-mode.js'
import { updateVerticalScrollFade } from '../../core/vertical-scroll-fade.js'
import { bindOverlayScrollbar, syncOverlayScrollbar } from '../../core/overlay-scrollbar.js'
import {
    buildHeadlessDropdownRows,
    filterHeadlessOptionTree,
    findHeadlessOptionRecord,
    flattenHeadlessDropdownRowOptions,
    flattenHeadlessDropdownRowsForVirtualization,
    flattenHeadlessOptions,
    headlessOptionIsDisabled,
    headlessOptionLabelHtml,
    headlessOptionValue,
    limitHeadlessOptionTree,
    windowHeadlessVirtualRows,
} from './headless-select-options.js'
import {
    cancelAllOptionCheckAnimations,
    runAfterSelectedCheckExit,
    scheduleCheckEnter,
    seedHeadlessKnownSelected,
    setCheckVisibleInstant,
} from './headless-select-selection-ux.js'
import { createHeadlessUserSelectMixin } from './headless-user-select.js'
import {
    scheduleInlineSearchCaretAtEnd,
    resolveInlineSearchInputAfterClose,
    resolveInlineSearchInputPlaceholder,
    resolveInlineSearchInputValue,
    resolveSelectSearchFocusTarget,
    shouldInlineSearchInputBeEditable,
    shouldShowSelectMenuSearch,
    stripHtmlToPlainText,
} from './headless-inline-search.js'
import {
    normalizeInitialSelectedValues,
    resolveHeadlessBoundState,
    shouldIgnoreEmptyHeadlessWireSync,
} from './headless-select-state.js'

const optionalMixinStubs = {
    initLivewireIntegration() {},
    initUserSelectIntegration() {},
    cancelRelationshipSearch() {},
    syncUserSelectTags() {},
    onHeadlessMenuOpenedForLivewire() {},
    observeHeadlessLoadMore() {},
    disconnectHeadlessLoadMoreObserver() {},
    // Used at engine create when hasDynamicSearchResults; real mixin overwrites.
    serverSideFilterFn() {
        return () => true
    },
    comboboxEntityMentionActive() {
        return false
    },
    onHeadlessTriggerMentionKeydown() {},
    userSelectTriggerHtml() {
        return this.placeholder ?? ''
    },
    labelEntry() {
        return null
    },
    optionRecord() {
        return null
    },
    storeLabelEntry() {},
    shouldShowHeadlessTriggerLoading() {
        return false
    },
    shouldShowHeadlessLoadMoreIndicator() {
        return false
    },
    shouldShowHeadlessLoadMoreSentinel() {
        return false
    },
    headlessLoadMoreLabel() {
        return this.loadingMoreMessage || this.loadingMessage || ''
    },
    teardownSelectedOptionLabelRefreshListener() {},
    teardownDynamicOptionsInvalidation() {},
    shouldShowHeadlessUserSelectSkeleton() {
        return false
    },
    shouldShowHeadlessUserSelectEmptyState() {
        return false
    },
    headlessUserSelectSkeletonAriaLabel() {
        return ''
    },
    headlessUserSelectSkeletonRows() {
        return []
    },
    headlessUserSelectEmptyIconHtml() {
        return ''
    },
    headlessUserSelectEmptyTitle() {
        return ''
    },
    headlessUserSelectEmptyHint() {
        return ''
    },
    isUserSelectField: false,
}

function defaultGetOptionLabel(option) {
    return headlessOptionLabelHtml(option, 'dropdown')
}

function defaultGetOptionValue(option) {
    return headlessOptionValue(option)
}

/** Fixed desktop width for optionLayout('grid') theme-style pickers. */
const GRID_DROPDOWN_WIDTH_PX = 400

const selectMenu = createSearchableSelectMenuMixin({
    openKey: 'comboboxOpen',
    readyKey: 'menuReady',
    scrollHandlerKey: 'menuScrollHandler',
    resizeHandlerKey: 'menuResizeHandler',
    scrollParentsKey: 'menuScrollParents',
    triggerRef: 'headlessTrigger',
    menuRef: 'headlessMenu',
    closeMethod: 'comboboxCloseMenu',
    ownerIdPrefix: 'fff-headless-select',
    minMenuWidth: 288,    onMenuClose() {
        this._engine?.close?.()
        this.comboboxHighlightedIndex = -1
        this.inlineSearchFocused = false
        this._engine?.setQuery?.('')
        this.cancelRelationshipSearch?.()
        this._syncFromEngine()

        if (typeof this.usesInlineSearchTriggerInput === 'function' && this.usesInlineSearchTriggerInput()) {
            this.syncInlineSearchInputAfterClose()
        } else {
            this.comboboxQuery = ''
        }
    },
})

function dropdownOptionsScroller(menu) {
    return menu?.querySelector?.('.fi-select-input-options-ctn')
        ?? menu?.querySelector?.('.fi-dropdown-list')
        ?? null
}

function syncDropdownScrollbarInset(menu) {
    if (! menu) {
        return
    }

    const list = dropdownOptionsScroller(menu)

    if (! list) {
        menu.classList.remove('fff-select-dropdown-panel--scrollable')

        return
    }

    menu.classList.toggle(
        'fff-select-dropdown-panel--scrollable',
        list.scrollHeight > list.clientHeight + 1,
    )
    const thumb = menu.querySelector('.fff-select-dropdown-scrollbar__thumb')
    bindOverlayScrollbar(list, thumb?.parentElement, thumb)
    syncOverlayScrollbar(list, thumb)
    updateVerticalScrollFade(list)
}

/**
 * Alpine data factory for the headless combobox engine (SelectField v3).
 */
export default function headlessComboboxAlpine(userConfig = {}) {
    const {
        state = null,
        initialState = null,
        statePath = null,
        multiple = false,
        searchable = true,
        options = [],
        placeholder = '',
        disabled = false,
        clearable = false,
        keepSelectedOptionsInDropdown = false,
        isHtmlAllowed = false,
        isGridLayout = false,
        useRichListDropdownLayout = false,
        selectedOptionCheckIconHtml = '',
        noSearchResultsMessage = '',
        componentKey = null,
        menuDomId = null,
        hasDynamicSearchResults = false,
        hasPaginatedSearchResults = false,
        hasDynamicOptions = false,
        hasClientSideOptionList = true,
        searchDebounce = 1000,
        minSearchLength = 0,
        isPreloaded = false,
        hasInitialNoOptionsMessage = false,
        loadingMessage = '',
        searchingMessage = '',
        loadingMoreMessage = '',
        noOptionsMessage = '',
        noMoreOptionsMessage = '',
        searchPrompt = '',
        optionsLimit = 50,
        searchableOptionFields = ['label'],
        livewireId = null,
        maxItems = null,
        maxItemsMessage = '',
        position = null,
        initialOptionLabel = null,
        initialOptionLabels = [],
        initialSelectedUserEntries = [],
        isUserSelectField = false,
        verifiedIconHtml = '',
        tagRemoveIconHtml = '',
        userSelectNoOptionsIconHtml = '',
        userSelectNoResultsIconHtml = '',
        selectNoOptionsIconHtml = '',
        selectNoResultsIconHtml = '',
        selectEmptyStateHints = {},
        userSelectEmptyStateHints = {},
        canOptionLabelsWrap = true,
        isReorderable = false,
        virtualizeThreshold = DEFAULT_VIRTUALIZE_THRESHOLD,
        virtualWindowSize = DEFAULT_VIRTUAL_WINDOW_SIZE,
        virtualRowHeight = 36,
        smartSuggestEnabled = false,
        recentOptionValues = [],
        suggestedOptionValues = [],
        allowCreateOption = false,
        createOptionLabel = 'Create',
        entityMentionsEnabled = false,
        mentionTrigger = '@',
        entityMentionSectionLabel = 'Mentions',
        inlineSearch = false,
        optionGroupSeparators = true,
        dropdownAlign = 'start',
        matchTriggerWidth = true,
        onChange: userOnChange = null,
        ...engineConfig
    } = userConfig

    const flatOptions = flattenHeadlessOptions(options)

    const needsLivewireMixin = hasDynamicSearchResults
        || hasDynamicOptions
        || ! hasClientSideOptionList
    const needsUserSelectMixin = isUserSelectField
    const needsEntityMentionMixin = entityMentionsEnabled

    const livewireConfig = {
        componentKey,
        hasDynamicSearchResults,
        hasPaginatedSearchResults,
        hasDynamicOptions,
        hasClientSideOptionList,
        searchDebounce,
        minSearchLength,
        isPreloaded,
        hasInitialNoOptionsMessage,
        loadingMessage,
        searchingMessage,
        loadingMoreMessage,
        noOptionsMessage,
        noMoreOptionsMessage,
        noSearchResultsMessage,
        searchPrompt,
        selectEmptyStateHints,
        initialOptionLabel,
        initialOptionLabels,
        multiple,
        livewireId,
        statePath,
        optionsLimit,
    }

    const userSelectConfig = {
        isUserSelectField,
        verifiedIconHtml,
        tagRemoveIconHtml,
        userSelectNoOptionsIconHtml,
        userSelectNoResultsIconHtml,
        userSelectEmptyStateHints,
    }

    const entityMentionConfig = {
        enabledKey: 'entityMentionsEnabled',
        triggerKey: 'mentionTrigger',
        queryKey: 'comboboxQuery',
        sectionLabel: entityMentionSectionLabel,
    }

    // UserSelect mixin is static — dynamic import raced Livewire option paint and
    // threw `renderUserOptionHtml is not a function` (main-thread long tasks / jank).
    const userSelectMixin = needsUserSelectMixin
        ? createHeadlessUserSelectMixin(userSelectConfig)
        : {}

    return {
        ...selectMenu,
        ...optionalMixinStubs,
        ...userSelectMixin,

        state,
        initialState,
        statePath,
        multiple,
        searchable,
        options,
        flatOptions,
        placeholder,
        disabled,
        clearable,
        keepSelectedOptionsInDropdown,
        isHtmlAllowed,
        isGridLayout,
        useRichListDropdownLayout,
        selectedOptionCheckIconHtml,
        noSearchResultsMessage,
        loadingMessage,
        searchingMessage,
        loadingMoreMessage,
        noOptionsMessage,
        noMoreOptionsMessage,
        searchPrompt,
        optionsLimit,
        searchableOptionFields,
        livewireId,
        maxItems,
        maxItemsMessage,
        maxItemsMessageVisible: false,
        position,
        hasDynamicSearchResults,
        hasPaginatedSearchResults,
        hasDynamicOptions,
        hasClientSideOptionList,
        isPreloaded,
        hasInitialNoOptionsMessage,
        searchDebounce,
        minSearchLength,
        initialSelectedUserEntries,
        isUserSelectField,
        selectNoOptionsIconHtml,
        selectNoResultsIconHtml,
        selectEmptyStateHints,
        userSelectEmptyStateHints,
        canOptionLabelsWrap,
        isReorderable,
        initialOptionLabel,
        initialOptionLabels,
        /** Bumped when trigger label sources change so Alpine `x-html` re-evaluates. */
        triggerLabelEpoch: 0,
        _knownLabelEntries: new Map(),

        headlessSelectDropdownState() {
            if (this.isUserSelectField) {
                return null
            }

            if (this.optionsLoading) {
                return 'loading'
            }

            if (this.searchPending) {
                return 'searching'
            }

            if (this.comboboxOpen && this.hasDynamicOptions && ! this.dynamicOptionsLoaded) {
                const pendingCount = typeof this.getEngineOptions === 'function'
                    ? this.getEngineOptions().length
                    : 0

                if (pendingCount === 0) {
                    return 'loading'
                }
            }

            const query = String(this.comboboxQuery ?? '').trim()
            // Count rows actually painted in the list (selected values may be
            // omitted when keepSelectedOptionsInDropdown is false).
            const visibleOptionCount = typeof this.countVisibleDropdownOptions === 'function'
                ? this.countVisibleDropdownOptions()
                : (typeof this.getEngineOptions === 'function' ? this.getEngineOptions().length : 0)

            if (this.hasDynamicSearchResults && query.length < Number(this.minSearchLength ?? 0)) {
                return visibleOptionCount > 0 ? null : 'prompt'
            }

            if (visibleOptionCount > 0) {
                return null
            }

            if (
                this.allowCreateOption
                && query.length > 0
                && this.searchable
                && ! this.hasDynamicSearchResults
            ) {
                return null
            }

            if (this.hasDynamicSearchResults && query.length >= Number(this.minSearchLength ?? 0)) {
                return 'search'
            }

            if (query.length > 0) {
                return 'search'
            }

            if (this.hasExhaustedSelectableOptions()) {
                return 'exhausted'
            }

            return 'options'
        },

        /**
         * Options remaining in the dropdown after selected-item filtering.
         */
        countVisibleDropdownOptions() {
            if (this.smartSuggestEnabled && this._engine && ! this.hasDynamicSearchResults) {
                const flat = typeof this.getEngineOptions === 'function'
                    ? this.getEngineOptions()
                    : []

                if (! this.multiple || this.keepSelectedOptionsInDropdown) {
                    return flat.length
                }

                return flat.filter((option) => ! this.isOptionSelected(headlessOptionValue(option))).length
            }

            const rows = buildHeadlessDropdownRows(this.comboboxFilteredOptionTree(), {
                multiple: this.multiple,
                keepSelectedOptionsInDropdown: this.keepSelectedOptionsInDropdown,
                isOptionSelected: (value) => this.isOptionSelected(value),
                withSeparators: false,
            })

            return flattenHeadlessDropdownRowOptions(rows).length
        },

        /**
         * Multi-select removed every remaining choice from the list
         * (keepSelectedOptionsInDropdown=false) while the source still has options.
         */
        hasExhaustedSelectableOptions() {
            if (! this.multiple || this.keepSelectedOptionsInDropdown) {
                return false
            }

            const sourceCount = flattenHeadlessOptions(this.options ?? []).length

            if (sourceCount === 0) {
                return false
            }

            return this.countVisibleDropdownOptions() === 0
                && String(this.comboboxQuery ?? '').trim() === ''
        },

        shouldShowHeadlessDropdownOptions() {
            if (this.isUserSelectField) {
                return ! this.shouldShowHeadlessUserSelectSkeleton()
                    && ! this.shouldShowHeadlessUserSelectEmptyState()
            }

            return ! this.shouldShowHeadlessSelectEmptyState()
                && ! this.shouldShowHeadlessSelectSkeleton()
        },

        shouldShowHeadlessSelectEmptyState() {
            const state = this.headlessSelectDropdownState()

            return state === 'prompt' || state === 'search' || state === 'options' || state === 'exhausted'
        },

        shouldShowHeadlessSelectSkeleton() {
            if (this.isUserSelectField) {
                return false
            }

            const state = this.headlessSelectDropdownState()

            return state === 'loading' || state === 'searching'
        },

        headlessSelectSkeletonAriaLabel() {
            const state = this.headlessSelectDropdownState()

            if (state === 'searching') {
                return this.searchingMessage
            }

            return this.loadingMessage
        },

        headlessSelectEmptyIconHtml() {
            const state = this.headlessSelectDropdownState()

            if (state === 'search' || state === 'prompt') {
                return this.selectNoResultsIconHtml
            }

            return this.selectNoOptionsIconHtml
        },

        headlessSelectEmptyTitle() {
            const state = this.headlessSelectDropdownState()

            if (state === 'prompt') {
                return this.searchPrompt
            }

            if (state === 'search') {
                return this.noSearchResultsMessage
            }

            if (state === 'exhausted') {
                return this.noMoreOptionsMessage || this.noOptionsMessage
            }

            return this.noOptionsMessage
        },

        headlessSelectEmptyHint() {
            const hints = this.selectEmptyStateHints ?? {}
            const state = this.headlessSelectDropdownState()

            if (state === 'loading' || state === 'searching') {
                return hints.pleaseWait ?? ''
            }

            if (state === 'prompt') {
                return Number(this.minSearchLength ?? 0) > 0
                    ? (hints.minSearchLength ?? '')
                    : (hints.filterList ?? '')
            }

            if (state === 'search') {
                return hints.tryDifferentSearch ?? ''
            }

            if (state === 'exhausted') {
                return hints.allOptionsSelected ?? hints.noOptionsAvailable ?? ''
            }

            return hints.noOptionsAvailable ?? ''
        },

        labelEntry(value) {
            return this._knownLabelEntries.get(String(value)) ?? null
        },

        storeLabelEntry(value, entry) {
            if (! entry) {
                return
            }

            this._knownLabelEntries.set(String(value), entry)
        },

        syncClearablePresentation() {
            if (this.multiple || ! this.clearable) {
                return
            }

            const hasValue = this.isTriggerLabelSelected()
            const wrapper = this.$el?.closest?.('.fff-select-field')
            const ctn = this.$refs.headlessTriggerCtn

            wrapper?.classList.toggle('fff-select-field--clearable-has-value', hasValue)
            ctn?.classList.toggle('fi-select-input-ctn-clearable', hasValue)
        },

        seedInitialTriggerLabels() {
            if (this.initialOptionLabel != null && this.initialOptionLabel !== '' && ! this.multiple) {
                const value = this.comboboxSelectedValues[0]

                if (value != null && value !== '') {
                    this.storeLabelEntry(value, {
                        value,
                        label: String(this.initialOptionLabel),
                        triggerLabel: String(this.initialOptionLabel),
                    })
                }
            }

            if (this.multiple && Array.isArray(this.initialOptionLabels)) {
                for (const entry of this.initialOptionLabels) {
                    if (entry?.value != null) {
                        this.storeLabelEntry(entry.value, entry)
                    }
                }
            }

            if (this.isUserSelectField && Array.isArray(this.initialSelectedUserEntries)) {
                for (const entry of this.initialSelectedUserEntries) {
                    if (entry?.value === undefined || entry?.value === null) {
                        continue
                    }

                    this.storeLabelEntry(entry.value, {
                        value: entry.value,
                        label: entry.user?.name ?? String(entry.value),
                        triggerLabel: entry.user?.name ?? String(entry.value),
                        user: entry.user,
                        fffClientRender: Boolean(entry.user),
                    })

                    if (entry.user && typeof this.storeUserInRepository === 'function') {
                        this.storeUserInRepository(entry.value, entry.user)
                    }
                }
            }
        },

        canShowHydratedTrigger() {
            if (! this._engine) {
                return false
            }

            if (! this.isTriggerLabelSelected()) {
                return true
            }

            // UserSelect paints via client `userSelectTriggerHtml()`. Do not replace SSR
            // until that HTML is a real selection — otherwise Alpine can snapshot the
            // placeholder and never refresh (Assignee FOUC on reload).
            if (this.isUserSelectField) {
                if (! this._optionalMixinsLoaded) {
                    return false
                }

                if (typeof this.userSelectTriggerHtml !== 'function') {
                    return false
                }

                const html = this.userSelectTriggerHtml()

                return html != null
                    && String(html) !== ''
                    && String(html) !== String(this.placeholder ?? '')
            }

            const value = String(this.comboboxSelectedValues[0] ?? '')

            if (this.labelEntry(value)) {
                return true
            }

            if (findHeadlessOptionRecord(this.options, value) || findHeadlessOptionRecord(this.flatOptions, value)) {
                return true
            }

            if (
                this.initialOptionLabel != null
                && this.initialOptionLabel !== ''
                && value === String(resolveHeadlessBoundState(this.state, this.initialState, this.multiple) ?? '')
            ) {
                return true
            }

            return false
        },

        bumpTriggerLabelEpoch() {
            this.triggerLabelEpoch = (Number(this.triggerLabelEpoch) || 0) + 1
        },

        comboboxOpen: false,
        inlineSearchFocused: false,
        comboboxQuery: '',
        comboboxHighlightedIndex: -1,
        comboboxSelectedValues: [],
        menuReady: false,
        displayReady: false,
        menuDomId,
        componentKey,
        knownSelectedChecks: seedHeadlessKnownSelected([]),
        checkExitCancel: null,
        /** @type {Record<string, true>} */
        checkExitKeys: {},
        checkExitTick: 0,
        menuTriggerResizeObserver: null,
        menuOptionsResizeObserver: null,
        virtualScrollTick: 0,
        virtualizeThreshold,
        virtualWindowSize,
        virtualRowHeight,
        smartSuggestEnabled,
        recentOptionValues,
        suggestedOptionValues,
        allowCreateOption,
        createOptionLabel,
        entityMentionsEnabled,
        mentionTrigger,
        entityMentionSectionLabel,
        inlineSearch,
        optionGroupSeparators,
        dropdownAlign,
        matchTriggerWidth,
        virtualRowWindowStart: 0,
        _userHasMutatedSelection: false,

        resolveDropdownAlign() {
            return this.dropdownAlign === 'end' ? 'end' : 'start'
        },

        resolveMatchTriggerWidth() {
            // Grid pickers use a fixed desktop panel — matching the trigger causes a
            // full-width flash before applyGridDropdownWidth re-pins to 400px.
            if (this.isGridLayout) {
                return false
            }

            return this.matchTriggerWidth !== false
        },

        resolveMenuMinWidth() {
            if (this.isGridLayout) {
                return GRID_DROPDOWN_WIDTH_PX
            }

            return 288
        },

        afterOverlayPanelOpened(overlayMode) {
            if (overlayMode === 'sheet' || ! this.isGridLayout) {
                return
            }

            this.applyGridDropdownWidth()
        },

        _engine: null,
        _virtualFlatRows: [],
        _optionalMixinsLoaded: false,

        async loadOptionalMixins() {
            if (this._optionalMixinsLoaded) {
                return
            }

            const jobs = []

            if (needsLivewireMixin) {
                jobs.push(import('./headless-combobox-livewire.js').then(({ createHeadlessComboboxLivewireMixin }) => {
                    Object.assign(this, createHeadlessComboboxLivewireMixin(livewireConfig))
                }))
            }

            if (needsEntityMentionMixin) {
                jobs.push(import('../../core/entity-mention.js').then(({ createEntityMentionMixin }) => {
                    Object.assign(this, createEntityMentionMixin(entityMentionConfig))
                }))
            }

            await Promise.all(jobs)
            this._optionalMixinsLoaded = true

            if (needsLivewireMixin) {
                this.initLivewireIntegration()
            }

            if (needsUserSelectMixin) {
                this.initUserSelectIntegration()
            }
        },

        async init() {
            const pendingSelection = normalizeInitialSelectedValues(
                resolveHeadlessBoundState(this.state, this.initialState, this.multiple),
                this.multiple,
            )

            // Empty non-UserSelect triggers hand off before optional chunks load so
            // multi SSR never swallows clicks (Entity mentions). Pre-selected multi
            // relies on CSS click-through until the engine reveals chips.
            if (! this.isUserSelectField && pendingSelection.length === 0) {
                this.displayReady = true
                this.ensureHeadlessSsrHandoff()
            }

            // Kick off optional chunks without blocking engine create. Dynamic
            // search uses serverSideFilterFn stub until the Livewire mixin assigns
            // (same () => true). Awaiting here serialized Alpine init across the
            // playground and froze the page under x-load.
            const mixinsPromise = this.loadOptionalMixins()

            if (this.isUserSelectField) {
                await mixinsPromise
            } else {
                void mixinsPromise
            }

            const initialSelectedValues = pendingSelection

            this._engine = createComboboxEngine({
                options: flattenHeadlessOptions(this.options),
                multiple: this.multiple,
                searchable: this.searchable,
                getOptionLabel: engineConfig.getOptionLabel ?? defaultGetOptionLabel,
                getOptionValue: engineConfig.getOptionValue ?? defaultGetOptionValue,
                filterFn: this.hasDynamicSearchResults
                    ? (typeof this.serverSideFilterFn === 'function'
                        ? this.serverSideFilterFn()
                        : () => true)
                    : (option, normalizedQuery, getOptionLabel) => {
                        if (normalizedQuery === '') {
                            return true
                        }

                        const fields = Array.isArray(this.searchableOptionFields) && this.searchableOptionFields.length > 0
                            ? this.searchableOptionFields
                            : ['label']

                        if (fields.includes('label') && normalizeSearchQuery(getOptionLabel(option)).includes(normalizedQuery)) {
                            return true
                        }

                        if (fields.includes('description') && normalizeSearchQuery(String(option?.description ?? '')).includes(normalizedQuery)) {
                            return true
                        }

                        if (fields.includes('value') && normalizeSearchQuery(String(headlessOptionValue(option) ?? '')).includes(normalizedQuery)) {
                            return true
                        }

                        return false
                    },
                isOptionDisabled: (option) => this.isHeadlessOptionDisabled(option),
                virtualizeThreshold,
                virtualWindowSize,
                initialSelectedValues,
                recentValues: recentOptionValues,
                suggestedValues: suggestedOptionValues,
                allowCreate: allowCreateOption,
                createOptionLabel: (query) => `${createOptionLabel} "${query}"`,
                onChange: (values) => {
                    this._userHasMutatedSelection = true
                    this.comboboxSelectedValues = values
                    this.syncStateFromEngine(values)
                    this.syncClearablePresentation()
                    this.syncUserSelectTags?.()
                    userOnChange?.(values, this._engine)
                    this.$nextTick(() => {
                        syncDropdownScrollbarInset(this.resolveMenuElement())
                        this.scheduleMenuPositionAfterLayout()
                    })
                },
            })

            this._syncFromEngine()
            this.syncInlineSearchInputAfterClose()
            this.bindSelectMenuLifecycle()

            this.$watch('comboboxOpen', (open) => {
                if (open) {
                    // Options were already synced in comboboxOpenMenu(); avoid a
                    // second flatOptions replace that remounts optionView HTML.
                    this.knownSelectedChecks = seedHeadlessKnownSelected(this.comboboxSelectedValues)
                    this.clearAllOptionCheckExiting()
                    this.checkExitCancel?.()
                    this.checkExitCancel = null
                    this.bindHeadlessMenuPositionObservers()

                    // Defer Livewire fetch + focus until the sheet slide finishes.
                    // Starting getOptionsForJs in the same turn starves rAF and the
                    // enter animation never gets sheet/is-open classes in time.
                    const menu = typeof this.resolveMenuElement === 'function'
                        ? this.resolveMenuElement()
                        : this.$refs?.headlessMenu

                    runAfterSheetEnter(menu, () => {
                        if (! this.comboboxOpen || this.__fffSheetClosing) {
                            return
                        }

                        this.onHeadlessMenuOpenedForLivewire()

                        this.$nextTick(() => {
                            if (! this.comboboxOpen || this.__fffSheetClosing) {
                                return
                            }

                            this.bindDropdownScrollFadeObserver()
                            this.markKnownOptionChecksVisible()

                            if (this.searchable) {
                                this.focusHeadlessSearchInput()
                            }

                            this.observeHeadlessLoadMore?.()
                        })
                    })

                    return
                }

                this.unbindDropdownScrollFadeObserver()
                this.unbindHeadlessMenuPositionObservers()
                this.disconnectHeadlessLoadMoreObserver?.()
                this.freezeOptionChecksForMenuClose()
                this.teardownHeadlessMenuPosition()
            })

            this.$watch('state', (nextState) => {
                if (shouldIgnoreEmptyHeadlessWireSync(
                    nextState,
                    this.initialState,
                    this.multiple,
                    this._userHasMutatedSelection,
                )) {
                    this.syncStateFromEngine(this.comboboxSelectedValues)

                    return
                }

                const nextValues = normalizeInitialSelectedValues(
                    resolveHeadlessBoundState(nextState, this.initialState, this.multiple, {
                        fallbackToInitial: false,
                    }),
                    this.multiple,
                )

                if (this._valuesEqual(nextValues, this.comboboxSelectedValues)) {
                    return
                }

                this._engine?.setSelectedValues?.(nextValues)
                this._syncFromEngine()

                if (! this.comboboxOpen) {
                    this.syncInlineSearchInputAfterClose()
                }
            })

            this.$watch('comboboxQuery', () => {
                this.virtualRowWindowStart = 0
                this._virtualFlatRows = []
                this.virtualScrollTick = (this.virtualScrollTick ?? 0) + 1
                this.applyComboboxQueryToEngine()

                if (! this.comboboxOpen || this.__fffSheetClosing) {
                    return
                }

                this.$nextTick(() => {
                    syncDropdownScrollbarInset(this.resolveMenuElement())
                    this.markKnownOptionChecksVisible()
                    this.scheduleMenuPositionAfterLayout()
                })
            })

            this.seedInitialTriggerLabels()
            this.syncEngineOptions()
            this._syncFromEngine()
            this.syncClearablePresentation()
            this.bumpTriggerLabelEpoch()

            const revealHeadlessTrigger = () => {
                this.bumpTriggerLabelEpoch()
                this.markHeadlessDisplayReady()
            }

            if (this.isUserSelectField && this.multiple) {
                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        requestAnimationFrame(revealHeadlessTrigger)
                    })
                })
            } else if (this.isUserSelectField) {
                // Wait one Alpine flush so `x-if` / `x-html` bind against seeded users
                // before SSR handoff — avoids flashing the placeholder.
                this.$nextTick(() => {
                    this.$nextTick(() => {
                        requestAnimationFrame(revealHeadlessTrigger)
                    })
                })
            } else {
                this.$nextTick(() => {
                    requestAnimationFrame(revealHeadlessTrigger)
                })
            }

            if (this.comboboxOpen) {
                this.scheduleMenuPosition?.()
                this.bindHeadlessMenuPositionObservers()
                this.onHeadlessMenuOpenedForLivewire?.()
                this.$nextTick(() => this.bindDropdownScrollFadeObserver())
            }
        },

        markHeadlessDisplayReady() {
            if (this.displayReady || ! this._engine || ! this.canShowHydratedTrigger()) {
                return
            }

            this.displayReady = true
            this.ensureHeadlessSsrHandoff()
        },

        /**
         * Re-apply SSR → hydrated handoff after Livewire remorphs. Parent
         * dependsOn ->live() can drop `.is-replaced` while Alpine stays alive,
         * leaving the trigger under pointer-events: none for 1–2s.
         */
        ensureHeadlessSsrHandoff() {
            if (! this.displayReady) {
                return
            }

            const shell = this.$el?.closest?.('.fff-select-field__shell--headless')
                ?? this.$el?.closest?.('.fff-select-field__shell')

            if (! shell) {
                return
            }

            shell.dataset.fffSelectAttached = 'true'

            shell.querySelectorAll('.fff-select-trigger-ssr, .fff-select-item-card-ssr').forEach((element) => {
                element.classList.add('is-replaced')
            })
        },

        applyComboboxQueryToEngine() {
            this._engine?.setQuery(this.comboboxQuery)

            if (! this.hasDynamicSearchResults) {
                this._engine?.setOptions(flattenHeadlessOptions(this.options))
            }

            this._syncFromEngine()
        },

        destroy() {
            this.unbindDropdownScrollFadeObserver()
            this.unbindHeadlessMenuPositionObservers()
            this.disconnectHeadlessLoadMoreObserver?.()
            this.teardownSelectedOptionLabelRefreshListener?.()
            this.teardownDynamicOptionsInvalidation?.()
            this._engine?.destroy()
            this._engine = null
        },

        syncStateFromEngine(values) {
            if (this.state === undefined || this.state === null) {
                return
            }

            if (this.multiple) {
                const next = values.slice()

                if (Array.isArray(this.state) && this._valuesEqual(this.state, next)) {
                    return
                }

                this.state = next

                return
            }

            const next = values.length > 0 ? values[0] : null

            if (String(this.state ?? '') === String(next ?? '')) {
                return
            }

            this.state = next
        },

        _valuesEqual(left, right) {
            if (left.length !== right.length) {
                return false
            }

            for (let index = 0; index < left.length; index++) {
                if (String(left[index]) !== String(right[index])) {
                    return false
                }
            }

            return true
        },

        _syncFromEngine() {
            if (! this._engine) {
                return
            }

            const snapshot = this._engine.getSnapshot()

            if (this.hasDynamicSearchResults) {
                this.comboboxQuery = snapshot.query
            }

            this.comboboxHighlightedIndex = snapshot.highlightedIndex

            const nextSelected = Array.from(snapshot.selectedValues)

            // Keep the same array reference when values are unchanged so Alpine
            // does not re-run x-html on the trigger (optionView avatars remount).
            if (! this._valuesEqual(nextSelected, this.comboboxSelectedValues)) {
                this.comboboxSelectedValues = nextSelected
            }
        },

        comboboxFilteredOptions() {
            const flat = this.getEngineOptions()
            const result = this._engine?.filteredOptions() ?? {
                options: flat,
                meta: { startIndex: 0, endIndex: flat.length, total: flat.length },
            }

            return {
                options: result.options,
                meta: { ...result.meta },
            }
        },

        shouldVirtualizeDropdown() {
            if (this.isGridLayout) {
                return false
            }

            return this.countVirtualizableDropdownRows() >= (this.virtualizeThreshold ?? DEFAULT_VIRTUALIZE_THRESHOLD)
        },

        countVirtualizableDropdownRows() {
            if (this.smartSuggestEnabled && this._engine && ! this.hasDynamicSearchResults) {
                return this.getEngineOptions().length
            }

            const rows = buildHeadlessDropdownRows(this.comboboxFilteredOptionTree(), {
                multiple: this.multiple,
                keepSelectedOptionsInDropdown: this.keepSelectedOptionsInDropdown,
                isOptionSelected: (value) => this.isOptionSelected(value),
                withSeparators: this.optionGroupSeparators !== false,
            })

            return flattenHeadlessDropdownRowsForVirtualization(rows).length
        },

        rebuildVirtualFlatRows() {
            const rows = this.buildHeadlessDropdownRowsForDisplay()

            this._virtualFlatRows = flattenHeadlessDropdownRowsForVirtualization(rows)

            return this._virtualFlatRows
        },

        buildHeadlessDropdownRowsForDisplay() {
            if (this.smartSuggestEnabled && this._engine && ! this.shouldVirtualizeDropdown()) {
                return this._engine.smartSections().flatMap((section) => {
                    /** @type {Array<{ type: string, key: string, label?: string, value?: string, option?: unknown }>} */
                    const rows = []

                    if (section.type === 'create') {
                        rows.push({
                            type: 'create',
                            key: `create-${section.value}`,
                            label: section.label,
                            value: section.value,
                        })

                        return rows
                    }

                    if (section.label && section.type !== 'options') {
                        rows.push({
                            type: 'section',
                            key: `section-${section.type}`,
                            label: section.label,
                        })
                    }

                    for (const option of section.options ?? []) {
                        const value = String(headlessOptionValue(option))

                        rows.push({
                            type: 'option',
                            option,
                            // Prefix by section so Alpine x-for keys stay unique when the
                            // same value appears in Recent/Suggested and the main list.
                            key: `${section.type}:${value}`,
                        })
                    }

                    return rows
                })
            }

            return buildHeadlessDropdownRows(this.comboboxFilteredOptionTree(), {
                multiple: this.multiple,
                keepSelectedOptionsInDropdown: this.keepSelectedOptionsInDropdown,
                isOptionSelected: (value) => this.isOptionSelected(value),
                withSeparators: this.optionGroupSeparators !== false,
            })
        },

        comboboxVirtualListStyle() {
            void this.virtualScrollTick

            if (! this.shouldVirtualizeDropdown()) {
                return {}
            }

            const flatRows = this._virtualFlatRows.length > 0
                ? this._virtualFlatRows
                : this.rebuildVirtualFlatRows()
            const { meta } = windowHeadlessVirtualRows(
                flatRows,
                this.virtualRowWindowStart ?? 0,
                this.virtualWindowSize ?? DEFAULT_VIRTUAL_WINDOW_SIZE,
            )

            return {
                paddingTop: `${meta.paddingTop}px`,
                paddingBottom: `${meta.paddingBottom}px`,
            }
        },

        onHeadlessOptionsScroll(event) {
            syncDropdownScrollbarInset(this.resolveMenuElement())

            if (! this.shouldVirtualizeDropdown()) {
                if (this.hasPaginatedSearchResults) {
                    this.observeHeadlessLoadMore?.()
                }

                return
            }

            const flatRows = this._virtualFlatRows.length > 0
                ? this._virtualFlatRows
                : this.rebuildVirtualFlatRows()
            const scrollTop = event?.target?.scrollTop ?? 0
            let consumed = 0
            let start = 0

            for (let index = 0; index < flatRows.length; index += 1) {
                const rowHeight = flatRows[index]?.height ?? this.virtualRowHeight ?? 36

                if (consumed + rowHeight > scrollTop) {
                    start = index
                    break
                }

                consumed += rowHeight
                start = index + 1
            }

            this.virtualRowWindowStart = Math.max(0, start - 2)
            this.virtualScrollTick = (this.virtualScrollTick ?? 0) + 1

            if (this.hasPaginatedSearchResults) {
                this.observeHeadlessLoadMore?.()
            }
        },

        comboboxFilteredOptionTree() {
            const tree = Array.isArray(this.options) ? this.options : []
            const query = String(this.comboboxQuery ?? '').trim()

            let filtered = tree

            if (this.searchable && ! this.hasDynamicSearchResults && query !== '') {
                filtered = filterHeadlessOptionTree(
                    tree,
                    query,
                    (option) => defaultGetOptionLabel(option),
                    this.searchableOptionFields,
                )
            }

            return limitHeadlessOptionTree(filtered, this.optionsLimit)
        },

        comboboxFilteredDropdownRowsWithoutSections() {
            void this.comboboxQuery
            void this.virtualScrollTick
            void this.options

            let rows = this.buildHeadlessDropdownRowsForDisplay()

            if (this.isGridLayout) {
                rows = rows.flatMap((row) => {
                    if (row.type === 'group') {
                        return (row.options ?? []).map((option) => ({
                            type: 'option',
                            option,
                            key: headlessOptionValue(option),
                        }))
                    }

                    if (row.type === 'separator') {
                        return []
                    }

                    return [row]
                })
            }

            // Always flatten nested groups so the Blade loop renders one concrete
            // row type per iteration (no nested option x-for / group wrappers).
            const flatRows = flattenHeadlessDropdownRowsForVirtualization(rows)
            this._virtualFlatRows = flatRows

            if (this.shouldVirtualizeDropdown()) {
                const windowed = windowHeadlessVirtualRows(
                    flatRows,
                    this.virtualRowWindowStart ?? 0,
                    this.virtualWindowSize ?? DEFAULT_VIRTUAL_WINDOW_SIZE,
                )

                return windowed.rows
            }

            return flatRows
        },

        comboboxFilteredDropdownRows() {
            if (this.comboboxEntityMentionActive?.()) {
                const rows = this.comboboxFilteredDropdownRowsWithoutSections()

                return [
                    {
                        type: 'section',
                        key: 'entity-mentions',
                        label: `${this.entityMentionSectionLabel} (${this.mentionTrigger}${this.comboboxEntityMentionState().term})`,
                    },
                    ...rows,
                ]
            }

            return this.comboboxFilteredDropdownRowsWithoutSections()
        },

        getEngineOptions() {
            return flattenHeadlessOptions(this.comboboxFilteredOptionTree())
        },

        syncEngineOptions() {
            const next = flattenHeadlessOptions(this.options)
            const fingerprint = this.fingerprintHeadlessOptions(next)

            // Replacing flatOptions on every open re-runs Alpine x-html for the
            // trigger + rows (optionView avatars remount and flash ~1s).
            if (fingerprint === this._engineOptionsFingerprint) {
                this._engine?.setOptions(next)

                return
            }

            this._engineOptionsFingerprint = fingerprint
            this.flatOptions = next
            this._engine?.setOptions(next)
            this._syncFromEngine()
        },

        fingerprintHeadlessOptions(options) {
            if (! Array.isArray(options) || options.length === 0) {
                return '0'
            }

            let fingerprint = String(options.length)

            for (const option of options) {
                fingerprint += `\0${headlessOptionValue(option)}`
                fingerprint += `\0${option?.triggerLabel ?? ''}`
                fingerprint += `\0${option?.label ?? ''}`
            }

            return fingerprint
        },

        isHeadlessOptionDisabled(option) {
            return headlessOptionIsDisabled(option)
        },

        headlessOptionFlatIndex(value) {
            const normalized = String(value)

            return this.getEngineOptions().findIndex((option) => headlessOptionValue(option) === normalized)
        },

        selectCreateOption(value) {
            if (this.multiple && this.hasReachedMaxItems()) {
                this.showMaxItemsMessage()

                return
            }

            if (! this._engine?.createInlineOption?.(value)) {
                return
            }

            this.hideMaxItemsMessage()
            this._syncFromEngine()
        },

        hasReachedMaxItems() {
            const limit = this.maxItems

            if (limit == null || limit === '' || Number(limit) <= 0) {
                return false
            }

            return this.comboboxSelectedValues.length >= Number(limit)
        },

        showMaxItemsMessage() {
            if (! this.maxItemsMessage) {
                return
            }

            this.maxItemsMessageVisible = true
        },

        hideMaxItemsMessage() {
            this.maxItemsMessageVisible = false
        },

        shouldShowMaxItemsMessage() {
            return Boolean(this.multiple && this.maxItemsMessageVisible && this.maxItemsMessage)
        },

        smartCreateRowLabel() {
            const query = String(this.comboboxQuery ?? '').trim()

            if (query === '') {
                return this.createOptionLabel
            }

            return `${this.createOptionLabel} "${query}"`
        },

        smartCreateRowHtml() {
            const query = String(this.comboboxQuery ?? '').trim()

            if (query === '') {
                return this.escapeHtml(String(this.createOptionLabel ?? ''))
            }

            const label = this.escapeHtml(String(this.createOptionLabel ?? ''))
            const term = this.escapeHtml(query)

            return `${label} <em class="fff-select-smart-create__term">"${term}"</em>`
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
        },

        reorderSelectedChips(event) {
            if (! this.isReorderable || ! this.multiple || this.disabled) {
                return
            }

            if (
                event
                && Number.isInteger(event.oldIndex)
                && Number.isInteger(event.newIndex)
                && event.oldIndex !== event.newIndex
            ) {
                const next = this.comboboxSelectedValues.slice()
                const [moved] = next.splice(event.oldIndex, 1)

                if (moved === undefined) {
                    return
                }

                next.splice(event.newIndex, 0, moved)
                this._engine?.setSelectedValues(next)
                this._syncFromEngine()

                return
            }

            const container = this.$refs.headlessBadgesCtn

            if (! container) {
                return
            }

            const nextValues = Array.from(container.querySelectorAll('[data-value]'))
                .map((element) => element.getAttribute('data-value'))
                .filter((value) => value !== null && value !== '')

            if (nextValues.length === 0) {
                return
            }

            this._engine?.setSelectedValues(nextValues)
            this._syncFromEngine()
        },

        resolveMenuTriggerElement() {
            // Match the visible field track (includes suffix chevron), not the
            // shrink-wrapped multi button/chips — otherwise the teleported menu
            // stays ~content-width (Entity mentions).
            const fieldTrack = this.$el?.closest?.('.fi-input-wrp.fff-select-field')
                ?? this.$el?.closest?.('.fi-input-wrp')
                ?? this.$el?.closest?.('.fff-select-field__shell')

            if (fieldTrack) {
                return fieldTrack
            }

            const button = this.$refs?.headlessTrigger

            if (! button) {
                return this.$refs?.headlessTriggerCtn ?? null
            }

            if (this.multiple) {
                return this.$refs?.headlessTriggerCtn
                    ?? button.closest('.fi-select-input-ctn:not(.fff-select-trigger-ssr)')
                    ?? button
            }

            return button
        },

        hasHeadlessMenuBeenPositioned() {
            return this.resolveMenuElement()?.__fffHasBeenPositioned === true
        },


        isSheetPresentation() {
            // While the exit slide runs, keep sheet chrome even if the viewport
            // already crossed back to desktop.
            if (this.__fffSheetClosing) {
                return true
            }

            if (! this.comboboxOpen) {
                return false
            }

            // Live breakpoint wins over stale open-time mode / leftover sheet classes.
            // Otherwise resize mobile→desktop keeps left:0 via applySheetDropdownWidth().
            return resolveOverlayMode(window) === 'sheet'
        },

        applySheetDropdownWidth() {
            const menu = this.resolveMenuElement()

            if (! menu) {
                return
            }

            menu.classList.remove('fi-width-none')
            menu.style.setProperty('width', '100%', 'important')
            menu.style.setProperty('min-width', '0', 'important')
            menu.style.setProperty('max-width', 'none', 'important')
            menu.style.left = '0'
            menu.style.right = '0'
            menu.style.insetInline = '0'
            menu.style.bottom = '0'
            menu.style.top = 'auto'
        },

        applyHeadlessDropdownWidthLayout() {
            if (this.isGridLayout) {
                this.applyGridDropdownWidth()

                return
            }

            if (this.useRichListDropdownLayout) {
                this.applyRichListDropdownWidth()

                return
            }

            this.applyPlainListDropdownWidth()
        },

        updateMenuPosition({ reveal = false } = {}) {
            const menu = this.resolveMenuElement()
            const trigger = this.resolveMenuTriggerElement()

            if (! menu || ! trigger) {
                return
            }

            // Never reflow width/anchor while the sheet exit animation is playing —
            // panel-width math would shrink the drawer mid-slide.
            if (this.__fffSheetClosing) {
                this.applySheetDropdownWidth()

                return
            }

            // Match patch runtime: finalize width before anchoring, then re-anchor once sized.
            this.applyHeadlessDropdownWidthLayout()
            selectMenu.updateMenuPosition.call(this, { reveal })

            // Overlay/local anchoring match-trigger width overwrites fixed pins — re-apply.
            if (this.isSheetPresentation()) {
                return
            }

            if (this.isGridLayout) {
                this.applyGridDropdownWidth()

                return
            }

            if (this.canOptionLabelsWrap === false) {
                this.applyPlainListDropdownWidth()
            }
        },

        teardownHeadlessMenuPosition() {
            if (! this.hasHeadlessMenuBeenPositioned()) {
                this.menuReady = false
            }

            this.unbindDropdownScrollFadeObserver()
            this.unbindHeadlessMenuPositionObservers()
            this.unbindMenuListeners()

            const menu = this.resolveMenuElement()

            if (! menu) {
                return
            }

            menu.classList.remove('is-open', 'is-closing')

            if (! menu.__fffHasBeenPositioned) {
                menu.classList.remove('is-positioned')
            }
        },

        applyRichListDropdownWidth() {
            if (this.isSheetPresentation()) {
                this.applySheetDropdownWidth()

                return
            }

            if (this.canOptionLabelsWrap === false) {
                this.applyPlainListDropdownWidth()

                return
            }

            const menu = this.resolveMenuElement()
            const trigger = this.resolveMenuTriggerElement()

            if (! menu || ! trigger) {
                return
            }

            const buttonWidth = trigger.offsetWidth

            menu.style.width = `${buttonWidth}px`
            menu.style.minWidth = `${buttonWidth}px`
            menu.style.maxWidth = `min(${buttonWidth}px, calc(100vw - 2rem))`
            menu.style.overflowX = 'visible'
        },

        applyPlainListDropdownWidth() {
            if (this.isSheetPresentation()) {
                this.applySheetDropdownWidth()

                return
            }

            const menu = this.resolveMenuElement()
            const trigger = this.resolveMenuTriggerElement()

            if (! menu || ! trigger) {
                return
            }

            const buttonWidth = trigger.offsetWidth

            // wrapOptionLabels(false): dropdown matches trigger; long labels clamp in CSS.
            if (this.canOptionLabelsWrap === false) {
                const width = `${buttonWidth}px`

                menu.style.setProperty('width', width, 'important')
                menu.style.setProperty('min-width', width, 'important')
                menu.style.setProperty('max-width', width, 'important')
                menu.style.setProperty('overflow-x', 'hidden', 'important')

                return
            }

            const viewportCap = Math.max(buttonWidth, window.innerWidth - 32)
            const contentFloor = this.isUserSelectField ? 320 : buttonWidth

            const minWidth = Math.max(buttonWidth, contentFloor)

            menu.style.minWidth = `${minWidth}px`
            menu.style.maxWidth = `${viewportCap}px`

            if (! this.menuReady) {
                menu.style.width = `${minWidth}px`

                return
            }

            menu.style.width = 'max-content'

            const measuredWidth = Math.ceil(menu.scrollWidth)
            const targetWidth = Math.min(
                Math.max(buttonWidth, measuredWidth, contentFloor),
                viewportCap,
            )

            menu.style.width = `${targetWidth}px`
        },

        applyGridDropdownWidth() {
            if (this.isSheetPresentation()) {
                this.applySheetDropdownWidth()

                return
            }

            const menu = this.resolveMenuElement()

            if (! menu) {
                return
            }

            menu.classList.add('fi-width-none')
            menu.style.setProperty('width', `${GRID_DROPDOWN_WIDTH_PX}px`, 'important')
            menu.style.setProperty('max-width', `min(${GRID_DROPDOWN_WIDTH_PX}px, calc(100vw - 2rem))`, 'important')
            menu.style.setProperty('min-width', `${GRID_DROPDOWN_WIDTH_PX}px`, 'important')
        },

        scheduleMenuPositionAfterLayout() {
            if (! this.comboboxOpen || this.__fffSheetClosing) {
                return
            }

            const menu = this.resolveMenuElement()

            if (menu?.dataset?.fffSheetEntering === 'true') {
                return
            }

            if (this.__fffHeadlessMenuPositionRaf) {
                return
            }

            this.__fffHeadlessMenuPositionRaf = requestAnimationFrame(() => {
                this.__fffHeadlessMenuPositionRaf = requestAnimationFrame(() => {
                    this.__fffHeadlessMenuPositionRaf = 0

                    if (this.comboboxOpen && ! this.__fffSheetClosing) {
                        this.updateMenuPosition({ reveal: false })
                    }
                })
            })
        },

        bindHeadlessMenuPositionObservers() {
            this.unbindHeadlessMenuPositionObservers()

            if (typeof ResizeObserver === 'undefined') {
                return
            }

            const trigger = this.$refs.headlessTrigger
            const anchor = this.resolveMenuTriggerElement()

            if (! trigger && ! anchor) {
                return
            }

            this.menuTriggerResizeObserver = new ResizeObserver(() => {
                this.scheduleMenuPositionAfterLayout()
            })

            if (anchor) {
                this.menuTriggerResizeObserver.observe(anchor)
            }

            if (trigger && trigger !== anchor) {
                this.menuTriggerResizeObserver.observe(trigger)
            }
        },

        unbindHeadlessMenuPositionObservers() {
            if (this.__fffHeadlessMenuPositionRaf) {
                cancelAnimationFrame(this.__fffHeadlessMenuPositionRaf)
                this.__fffHeadlessMenuPositionRaf = 0
            }

            this.menuTriggerResizeObserver?.disconnect()
            this.menuTriggerResizeObserver = null
        },

        syncDropdownOverflowChrome() {
            syncDropdownScrollbarInset(this.resolveMenuElement())
        },

        bindDropdownScrollFadeObserver() {
            this.unbindDropdownScrollFadeObserver()
            syncDropdownScrollbarInset(this.resolveMenuElement())

            const list = this.$refs.headlessOptionsList ?? dropdownOptionsScroller(this.resolveMenuElement())

            if (! list || typeof ResizeObserver === 'undefined') {
                return
            }

            this.menuOptionsResizeObserver = new ResizeObserver(() => {
                syncDropdownScrollbarInset(this.resolveMenuElement())
            })
            this.menuOptionsResizeObserver.observe(list)

            if (list.firstElementChild) {
                this.menuOptionsResizeObserver.observe(list.firstElementChild)
            }
        },

        unbindDropdownScrollFadeObserver() {
            this.menuOptionsResizeObserver?.disconnect()
            this.menuOptionsResizeObserver = null
        },

        comboboxOpenMenu() {
            if (this.disabled) {
                return
            }

            this.ensureInlineSearchInputValueBeforeOpen()

            this.syncEngineOptions()
            this._engine?.open()
            this._syncFromEngine()
            this.comboboxOpen = true

            // Optimistic glass reveal for desktop panels — do not wait for Livewire
            // option fetch / measure. Sheets must NOT get is-open here: playSheetEnter
            // owns the first slide-up (optimistic open paints at translateY(0) and
            // the first height measure then looks like a missing enter animation).
            const menu = typeof this.resolveMenuElement === 'function'
                ? this.resolveMenuElement()
                : this.$refs?.headlessMenu

            if (menu) {
                menu.classList.remove('is-closing')
                menu.hidden = false
                menu.setAttribute('aria-hidden', 'false')

                const sheetMode = resolveOverlayMode(window) === 'sheet'
                    || menu.classList?.contains?.('fff-teleported-menu--sheet')
                    || menu.classList?.contains?.('fff-overlay-sheet')

                if (sheetMode) {
                    // Apply sheet classes synchronously so CSS bottom-sheet rules
                    // win before the async anchor pass — avoids a panel-scale flash
                    // and keeps enter work off the Livewire critical path.
                    menu.classList.add('fff-teleported-menu--sheet')
                    menu.classList.remove(
                        'fff-teleported-menu--panel',
                        'fff-select-dropdown-panel--below',
                        'fff-select-dropdown-panel--above',
                        'fff-teleported-menu--above',
                        'fff-teleported-menu--below',
                    )

                    if (menu.dataset) {
                        menu.dataset.fffOverlayPresentation = 'sheet'
                    }
                } else {
                    menu.classList.add('is-open')
                }
            }

            if (typeof this.scheduleMenuPosition === 'function') {
                this.scheduleMenuPosition()
            }
        },

        ensureInlineSearchInputValueBeforeOpen() {
            if (! this.usesInlineSearchTriggerInput() || ! this.isTriggerLabelSelected()) {
                return
            }

            const label = this.plainOptionLabel(this.comboboxSelectedValues[0])

            if (String(this.comboboxQuery ?? '').trim() === '') {
                this.comboboxQuery = label
                this.comboboxSetQuery(label)

                return
            }

            if (this.comboboxQuery === label) {
                this.comboboxSetQuery(label)
            }
        },

        comboboxCloseMenu({ immediate = false } = {}) {
            if (! this.comboboxOpen) {
                return
            }

            this._engine?.close()
            this.comboboxHighlightedIndex = -1
            this.inlineSearchFocused = false

            if (immediate && typeof this.closeTeleportedMenuImmediate === 'function') {
                this.closeTeleportedMenuImmediate()

                return
            }

            if (typeof this.closeTeleportedMenu === 'function') {
                this.closeTeleportedMenu()

                return
            }

            this.comboboxOpen = false
        },

        syncInlineSearchInputAfterSelection(value) {
            if (! this.usesInlineSearchTriggerInput()) {
                return
            }

            this.comboboxQuery = this.plainOptionLabel(value)
            this._engine?.setQuery('')
        },

        syncInlineSearchInputAfterClose() {
            if (this.usesInlineSearchTriggerInput()) {
                this.comboboxQuery = resolveInlineSearchInputAfterClose(
                    this.isTriggerLabelSelected(),
                    this.isTriggerLabelSelected()
                        ? this.plainOptionLabel(this.comboboxSelectedValues[0])
                        : '',
                )
            } else {
                this.comboboxQuery = ''
            }

            this._engine?.setQuery('')
        },

        focusHeadlessSearchInput() {
            const target = resolveSelectSearchFocusTarget({
                inlineSearch: this.inlineSearch && this.searchable,
                sheetPresentation: this.isSheetPresentation(),
            })
            const input = target === 'inline'
                ? this.$refs.headlessInlineSearchInput
                : this.$refs.headlessSearchInput

            input?.focus({ preventScroll: true })

            // Pin caret only when opening onto an existing value (selected label).
            // Re-forcing on every focus fights mid-word edits and bidi typing.
            if (input && String(input.value ?? '').length > 0) {
                this.$nextTick(() => {
                    scheduleInlineSearchCaretAtEnd(input)
                })
            }
        },

        shouldShowMenuSearch() {
            return shouldShowSelectMenuSearch({
                searchable: this.searchable,
                inlineSearch: this.inlineSearch && ! this.multiple,
                sheetPresentation: this.isSheetPresentation(),
            })
        },

        onHeadlessTriggerClick(event) {
            if (this.disabled) {
                return
            }

            if (this.inlineSearch && this.searchable) {
                if (this.comboboxOpen) {
                    event?.preventDefault?.()
                    this.focusHeadlessSearchInput()

                    return
                }

                this.comboboxOpenMenu()
                this.$nextTick(() => this.focusHeadlessSearchInput())

                return
            }

            this.comboboxToggle()
        },

        usesInlineSearchTriggerInput() {
            return this.inlineSearch && this.searchable && ! this.multiple
        },

        plainOptionLabel(value) {
            if (this.isUserSelectField) {
                const entry = this.labelEntry?.(value) ?? this.optionRecord(value)

                if (entry?.userName) {
                    return String(entry.userName)
                }
            }

            return stripHtmlToPlainText(this.optionLabel(value))
        },

        inlineSearchInputReadonly() {
            if (! this.usesInlineSearchTriggerInput()) {
                return false
            }

            // Drawer search owns keyboard input — keep the trigger non-editable
            // so focus cannot land on the field buried under the sheet.
            if (this.isSheetPresentation()) {
                return true
            }

            return ! shouldInlineSearchInputBeEditable(this.comboboxOpen, this.inlineSearchFocused)
        },

        inlineSearchInputValue() {
            if (! this.usesInlineSearchTriggerInput()) {
                return this.comboboxQuery ?? ''
            }

            return resolveInlineSearchInputValue({
                comboboxQuery: this.comboboxQuery,
            })
        },

        inlineSearchInputPlaceholder() {
            if (! this.usesInlineSearchTriggerInput()) {
                return this.searchPrompt ?? ''
            }

            return resolveInlineSearchInputPlaceholder({
                comboboxQuery: this.comboboxQuery,
                searchPrompt: this.searchPrompt,
            })
        },

        onInlineSearchFocus() {
            if (! this.usesInlineSearchTriggerInput() || this.disabled) {
                return
            }

            this.inlineSearchFocused = true

            if (! this.comboboxOpen) {
                this.comboboxOpenMenu()
            }

            this.$nextTick(() => {
                if (this.isSheetPresentation()) {
                    this.focusHeadlessSearchInput()

                    return
                }

                const input = this.$refs.headlessInlineSearchInput

                if (input && String(input.value ?? '').length > 0) {
                    scheduleInlineSearchCaretAtEnd(input)
                }
            })
        },

        onInlineSearchBlur() {
            if (! this.usesInlineSearchTriggerInput()) {
                return
            }

            window.setTimeout(() => {
                const active = document.activeElement
                const menu = this.resolveMenuElement()
                const trigger = this.$refs.headlessTrigger

                if (menu?.contains(active) || trigger?.contains(active)) {
                    return
                }

                this.inlineSearchFocused = false
            }, 0)
        },

        onInlineSearchClearedIfEmpty(event) {
            if (! this.usesInlineSearchTriggerInput() || this.multiple) {
                return
            }

            const value = event?.target?.value ?? ''

            if (value === '' && this.isTriggerLabelSelected()) {
                this.clearSelection()
            }
        },

        onInlineSearchInput(event) {
            const value = event?.target?.value ?? ''
            const hadSelection = this.isTriggerLabelSelected()

            this.comboboxSetQuery(value)

            if (this.usesInlineSearchTriggerInput() && ! this.multiple && value === '' && hadSelection) {
                this.clearSelection()
            }
        },

        comboboxToggle() {
            if (this.disabled) {
                return
            }

            if (this.comboboxOpen) {
                this.comboboxCloseMenu()

                return
            }

            this.comboboxOpenMenu()
        },

        comboboxSetQuery(value) {
            this.comboboxQuery = value
            this.virtualRowWindowStart = 0
            this._virtualFlatRows = []
            this.virtualScrollTick = (this.virtualScrollTick ?? 0) + 1

            this.applyComboboxQueryToEngine()

            // Alpine $watch('comboboxQuery') also runs these in the browser; keep
            // them here for programmatic callers / unit tests without a watcher.
            if (! this.comboboxOpen || this.__fffSheetClosing) {
                return
            }

            this.$nextTick(() => {
                syncDropdownScrollbarInset(this.resolveMenuElement())
                this.markKnownOptionChecksVisible()
                this.scheduleMenuPositionAfterLayout()
            })
        },

        comboboxClearSearch() {
            this.comboboxSetQuery('')

            this.$refs.headlessSearchInput?.focus?.()
        },

        comboboxMoveHighlight(delta) {
            this._engine?.moveHighlight(delta)
            this.virtualScrollTick = (this.virtualScrollTick ?? 0) + 1
            this._syncFromEngine()

            this.$nextTick(() => {
                const list = this.$refs.headlessOptionsList

                if (! list || ! this.shouldVirtualizeDropdown() || ! this._engine) {
                    return
                }

                const { meta } = this._engine.filteredOptions()
                const rowHeight = this.virtualRowHeight ?? 36
                const highlight = this.comboboxHighlightedIndex

                if (highlight < 0) {
                    return
                }

                const targetTop = highlight * rowHeight
                const targetBottom = targetTop + rowHeight

                if (targetTop < list.scrollTop) {
                    list.scrollTop = targetTop
                } else if (targetBottom > list.scrollTop + list.clientHeight) {
                    list.scrollTop = targetBottom - list.clientHeight
                }
            })
        },

        comboboxSelectHighlighted() {
            const selected = this._engine?.selectHighlighted() ?? false
            const previous = this.comboboxSelectedValues.slice()

            this._syncFromEngine()

            const highlightedKey = this.comboboxSelectedValues.find(
                (value) => ! previous.map(String).includes(String(value)),
            ) ?? this.comboboxSelectedValues.at(-1) ?? null

            if (selected && highlightedKey) {
                this.rememberSelectedOption(highlightedKey)
            }

            if (selected && highlightedKey && this.multiple) {
                this.queueOptionCheckEnter(highlightedKey)
            }

            if (selected && ! this.multiple) {
                this.comboboxCloseMenu({ immediate: true })

                if (highlightedKey) {
                    this.syncInlineSearchInputAfterSelection(highlightedKey)
                }
            }

            return selected
        },

        rememberSelectedOption(value) {
            const record = this.optionRecord(value) ?? this.labelEntry(value)

            if (! record) {
                return
            }

            const entry = {
                ...record,
                entityMention: this.comboboxEntityMentionActive?.() ? true : (record.entityMention ?? false),
            }

            this.storeLabelEntry(value, entry)

            if (record.user) {
                this.storeUserInRepository(value, record.user)
            }
        },

        comboboxSelectValue(value) {
            const key = String(value)

            if (this.multiple && this.hasReachedMaxItems()) {
                this.showMaxItemsMessage()

                return
            }

            this.hideMaxItemsMessage()
            this.rememberSelectedOption(value)
            this._engine?.selectValue(value)
            this._syncFromEngine()

            if (! this.multiple) {
                this.comboboxCloseMenu({ immediate: true })
                this.syncInlineSearchInputAfterSelection(value)

                return
            }

            this.queueOptionCheckEnter(key)
        },

        comboboxDeselectValue(value) {
            const key = String(value)
            const shouldAnimateExit = this.multiple
                && ! this.isGridLayout
                && this.comboboxOpen

            const check = shouldAnimateExit ? this.findOptionCheckElement(key) : null
            const animateExit = Boolean(check && check.getAttribute('data-visible') === 'true')

            // Mark exiting BEFORE syncing selection so Alpine keeps the selected-row
            // chrome (and the check node) while chips update immediately.
            if (animateExit) {
                this.markOptionCheckExiting(key)
            }

            this.knownSelectedChecks.delete(key)
            this._engine?.deselectValue(key)
            this._syncFromEngine()

            if (this.comboboxOpen) {
                this.scheduleMenuPositionAfterLayout()
            }

            if (! animateExit) {
                setCheckVisibleInstant(check, false)

                return
            }

            this.checkExitCancel?.()
            this.checkExitCancel = runAfterSelectedCheckExit(check, () => {
                this.checkExitCancel = null
                this.clearOptionCheckExiting(key)
            })
        },

        finishDeselectValue(key) {
            this.clearOptionCheckExiting(key)
            this.knownSelectedChecks.delete(key)
            this._engine?.deselectValue(key)
            this._syncFromEngine()

            const check = this.findOptionCheckElement(key)
            setCheckVisibleInstant(check, false)

            if (this.comboboxOpen) {
                this.scheduleMenuPositionAfterLayout()
            }
        },

        optionRecord(value) {
            const normalized = String(value)

            const fromOptions = findHeadlessOptionRecord(this.options, normalized)
                ?? findHeadlessOptionRecord(this.flatOptions, normalized)

            if (fromOptions) {
                return fromOptions
            }

            const fromKnown = this._knownLabelEntries?.get(normalized)

            if (fromKnown) {
                return fromKnown
            }

            const fromRepository = this.labelRepository?.[normalized]

            return fromRepository ?? null
        },

        optionLabel(value) {
            if (this.isUserSelectField) {
                const userHtml = this.userOptionLabelHtml(value, 'trigger')

                if (userHtml !== String(value)) {
                    return userHtml
                }
            }

            const stored = this.labelEntry(value)

            if (stored) {
                return headlessOptionLabelHtml(stored, 'trigger')
            }

            const match = this.optionRecord(value)

            return match ? headlessOptionLabelHtml(match, 'trigger') : String(value)
        },

        optionDropdownLabel(option) {
            if (option?.fffClientRender && option?.user) {
                if (typeof this.renderUserOptionHtml === 'function') {
                    return this.renderUserOptionHtml(option.user, 'list')
                }

                return String(option.user?.name ?? option.label ?? option.user?.email ?? '')
            }

            return headlessOptionLabelHtml(option, 'dropdown')
        },

        headlessOptionValue(option) {
            return headlessOptionValue(option)
        },

        selectedChips() {
            return this.comboboxSelectedValues.map((value) => ({
                value,
                label: this.entityMentionChipLabel?.(value) ?? this.optionLabel(value),
                isEntityMention: this.isEntityMentionValue?.(value) ?? false,
            }))
        },

        triggerLabelHtml() {
            // Depend on epoch so Alpine re-runs `x-html` after user/label seeding.
            void this.triggerLabelEpoch

            if (this.isUserSelectField) {
                return this.userSelectTriggerHtml()
            }

            if (this.multiple) {
                return this.placeholder
            }

            if (this.comboboxSelectedValues.length === 0) {
                return this.placeholder
            }

            return this.optionLabel(this.comboboxSelectedValues[0])
        },

        isTriggerLabelSelected() {
            return this.comboboxSelectedValues.length > 0
        },

        isOptionSelected(value) {
            return this.comboboxSelectedValues.includes(String(value))
        },

        isOptionCheckExiting(value) {
            void this.checkExitTick

            return Boolean(this.checkExitKeys[String(value)])
        },

        /**
         * Dropdown may keep the selected-row chrome while the check strokes out.
         * Trigger chips always follow isOptionSelected() only.
         */
        isOptionSelectedInDropdown(value) {
            return this.isOptionSelected(value) || this.isOptionCheckExiting(value)
        },

        markOptionCheckExiting(value) {
            const key = String(value)

            if (this.checkExitKeys[key]) {
                return
            }

            this.checkExitKeys = { ...this.checkExitKeys, [key]: true }
            this.checkExitTick = (this.checkExitTick ?? 0) + 1
        },

        clearOptionCheckExiting(value) {
            const key = String(value)

            if (! this.checkExitKeys[key]) {
                return
            }

            const next = { ...this.checkExitKeys }
            delete next[key]
            this.checkExitKeys = next
            this.checkExitTick = (this.checkExitTick ?? 0) + 1
        },

        clearAllOptionCheckExiting() {
            if (Object.keys(this.checkExitKeys).length === 0) {
                return
            }

            this.checkExitKeys = {}
            this.checkExitTick = (this.checkExitTick ?? 0) + 1
        },

        /**
         * Teleported menus live outside the Alpine root; click.outside must not
         * treat option/search clicks as dismiss.
         */
        isEventInsideHeadlessMenu(event) {
            const target = event?.target

            if (! target || typeof target.closest !== 'function') {
                return false
            }

            const menu = typeof this.resolveMenuElement === 'function'
                ? this.resolveMenuElement()
                : this.$refs?.headlessMenu

            if (menu?.contains?.(target)) {
                return true
            }

            const owner = this.componentKey ?? this.statePath ?? null

            if (owner == null || String(owner) === '') {
                return Boolean(target.closest('.fff-select-headless-menu'))
            }

            const escaped = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
                ? CSS.escape(String(owner))
                : String(owner).replace(/\\/g, '\\\\').replace(/"/g, '\\"')

            return Boolean(target.closest(`.fff-select-headless-menu[data-fff-select-menu-owner="${escaped}"]`))
        },

        toggleOption(value) {
            if (this.disabled) {
                return
            }

            const record = this.optionRecord(value)

            if (record && this.isHeadlessOptionDisabled(record)) {
                return
            }

            if (this.isOptionSelected(value)) {
                if (this.multiple) {
                    this.hideMaxItemsMessage()
                    this.comboboxDeselectValue(value)

                    return
                }

                if (this.clearable) {
                    this.clearSelection()
                }

                // Always close — re-clicking the current value used to leave the
                // glass panel open and cover sibling cascade fields (Region).
                this.comboboxCloseMenu({ immediate: true })

                return
            }

            this.comboboxSelectValue(value)

            // Keep @-mention mode ready for the next pick (multi) or clear the
            // trigger character after a single select so search does not stick.
            if (this.comboboxEntityMentionActive?.()) {
                if (this.multiple) {
                    this.comboboxSetQuery(String(this.mentionTrigger ?? '@'))
                } else {
                    this.comboboxSetQuery('')
                }
            }
        },

        clearSelection() {
            if (this.disabled || ! this.clearable) {
                return
            }

            this._engine?.setSelectedValues([])
            this._syncFromEngine()
            this.syncClearablePresentation()

            if (this.usesInlineSearchTriggerInput() && ! this.comboboxOpen) {
                this.syncInlineSearchInputAfterClose()
            }
        },

        onHeadlessMenuKeydown(event) {
            if (event.key === 'Escape') {
                event.stopPropagation()
                this.comboboxCloseMenu()
            }
        },

        markKnownOptionChecksVisible() {
            if (this.isGridLayout) {
                return
            }

            this.$refs.headlessOptionsList?.querySelectorAll('.fi-select-input-option.fi-selected .fff-select-option-selected-check').forEach((check) => {
                setCheckVisibleInstant(check, true)
            })
        },

        freezeOptionChecksForMenuClose() {
            this.checkExitCancel?.()
            this.checkExitCancel = null
            this.clearAllOptionCheckExiting()

            if (this.isGridLayout) {
                return
            }

            cancelAllOptionCheckAnimations(this.$refs.headlessOptionsList)

            this.$refs.headlessOptionsList?.querySelectorAll('.fi-select-input-option').forEach((option) => {
                const value = String(option.getAttribute('data-value') ?? '')
                const check = option.querySelector('.fff-select-option-selected-check')

                if (! check) {
                    return
                }

                setCheckVisibleInstant(check, this.isOptionSelected(value))
            })
        },

        queueOptionCheckEnter(value) {
            if (! this.comboboxOpen) {
                return
            }

            const key = String(value)

            if (this.isGridLayout || this.knownSelectedChecks.has(key)) {
                const check = this.findOptionCheckElement(key)
                setCheckVisibleInstant(check, true)
                this.knownSelectedChecks.add(key)

                return
            }

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    const check = this.findOptionCheckElement(key)

                    if (! check) {
                        return
                    }

                    scheduleCheckEnter(check)
                    this.knownSelectedChecks.add(key)
                })
            })
        },

        findOptionCheckElement(value) {
            const escaped = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
                ? CSS.escape(value)
                : String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"')

            const button = this.$refs.headlessOptionsList?.querySelector(
                `.fi-select-input-option[data-value="${escaped}"]`,
            )

            return button?.querySelector('.fff-select-option-selected-check') ?? null
        },
    }
}

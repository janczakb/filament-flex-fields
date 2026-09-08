import { createComboboxEngine, DEFAULT_VIRTUALIZE_THRESHOLD, DEFAULT_VIRTUAL_WINDOW_SIZE } from '../../core/combobox-engine.js'
import { normalizeSearchQuery } from '../../core/search-normalize.js'
import { createSearchableSelectMenuMixin, runAfterSheetEnter } from '../../core/searchable-select-menu.js'
import {
    flattenHeadlessOptions,
    headlessOptionLabelHtml,
    headlessOptionValue,
} from './headless-select-options.js'
import { createHeadlessComboboxKeyboardMixin } from './headless-combobox-keyboard.js'
import {
    createHeadlessComboboxScrollVirtMixin,
    syncDropdownScrollbarInset,
} from './headless-combobox-scroll-virt.js'
import { createHeadlessComboboxSsrHandoffMixin } from './headless-combobox-ssr-handoff.js'
import { createHeadlessComboboxEmptyUiMixin } from './headless-combobox-empty-ui.js'
import { createHeadlessComboboxEngineSyncMixin } from './headless-combobox-engine-sync.js'
import { createHeadlessComboboxMenuPositionMixin } from './headless-combobox-menu-position.js'
import { createHeadlessComboboxOpenCloseMixin } from './headless-combobox-open-close.js'
import { createHeadlessComboboxSelectionMixin } from './headless-combobox-selection.js'
import { seedHeadlessKnownSelected } from './headless-select-selection-ux.js'
import { createHeadlessUserSelectMixin } from './headless-user-select.js'
import {
    normalizeInitialSelectedValues,
    resolveHeadlessBoundState,
    shouldHydrateInitialSelectionToWire,
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

    const keyboardMixin = createHeadlessComboboxKeyboardMixin()
    const scrollVirtMixin = createHeadlessComboboxScrollVirtMixin()
    const ssrHandoffMixin = createHeadlessComboboxSsrHandoffMixin()
    const emptyUiMixin = createHeadlessComboboxEmptyUiMixin()
    const engineSyncMixin = createHeadlessComboboxEngineSyncMixin()
    const menuPositionMixin = createHeadlessComboboxMenuPositionMixin({ selectMenu })
    const openCloseMixin = createHeadlessComboboxOpenCloseMixin()
    const selectionMixin = createHeadlessComboboxSelectionMixin()

    return {
        ...selectMenu,
        ...optionalMixinStubs,
        ...userSelectMixin,
        ...keyboardMixin,
        ...scrollVirtMixin,
        ...ssrHandoffMixin,
        ...emptyUiMixin,
        ...engineSyncMixin,
        ...menuPositionMixin,
        ...openCloseMixin,
        ...selectionMixin,

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


        _engine: null,
        _virtualFlatRows: [],
        _virtualPrefixSums: [],
        _virtualScrollTop: 0,
        _virtualViewportHeight: 280,
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
                    if (! this._programmaticSelectionWrite) {
                        this._userHasMutatedSelection = true
                    }

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

            // SSR/default can paint a label while Livewire state is still null.
            // Push the hydrated selection once so required fill validation sees it.
            if (shouldHydrateInitialSelectionToWire(this.state, this.initialState, this.multiple)) {
                this._programmaticSelectionWrite = true

                try {
                    this.syncStateFromEngine(initialSelectedValues)
                } finally {
                    this._programmaticSelectionWrite = false
                }
            }
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
                this._virtualPrefixSums = []
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


        destroy() {
            this.cancelRelationshipSearch?.()
            this.disconnectHeadlessLoadMoreObserver?.()
            this.teardownSelectedOptionLabelRefreshListener?.()
            this.teardownDynamicOptionsInvalidation?.()
            this.unbindVirtualRowMeasurements?.()

            if (typeof this.destroyTeleportedMenuLifecycle === 'function') {
                this.destroyTeleportedMenuLifecycle()
            } else {
                if (this.comboboxOpen && typeof this.closeTeleportedMenuImmediate === 'function') {
                    this.closeTeleportedMenuImmediate()
                }

                this.unbindMenuListeners?.()
                this.teardownHeadlessMenuPosition?.()

                if (typeof this.__fffDropdownUnbind === 'function') {
                    this.__fffDropdownUnbind()
                    this.__fffDropdownUnbind = null
                }
            }

            this.unbindDropdownScrollFadeObserver()
            this.unbindHeadlessMenuPositionObservers()
            this._engine?.destroy()
            this._engine = null
        },
    }
}

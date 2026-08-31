import { createComboboxEngine, DEFAULT_VIRTUALIZE_THRESHOLD, DEFAULT_VIRTUAL_WINDOW_SIZE } from '../../core/combobox-engine.js'
import { createEntityMentionMixin } from '../../core/entity-mention.js'
import { createSearchableSelectMenuMixin } from '../../core/searchable-select-menu.js'
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
    windowHeadlessVirtualRows,
} from './headless-select-options.js'
import {
    cancelAllOptionCheckAnimations,
    runAfterSelectedCheckExit,
    scheduleCheckEnter,
    seedHeadlessKnownSelected,
    setCheckVisibleInstant,
} from './headless-select-selection-ux.js'
import { createHeadlessComboboxLivewireMixin } from './headless-combobox-livewire.js'
import {
    positionInlineSearchCaretAtInlineStart,
    resolveInlineSearchInputAfterClose,
    resolveInlineSearchInputPlaceholder,
    resolveInlineSearchInputValue,
    shouldInlineSearchInputBeEditable,
    stripHtmlToPlainText,
} from './headless-inline-search.js'
import { createHeadlessUserSelectMixin } from './headless-user-select.js'
import {
    normalizeInitialSelectedValues,
    resolveHeadlessBoundState,
} from './headless-select-state.js'

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
    onMenuClose() {
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

function syncDropdownScrollbarInset(menu) {
    if (! menu) {
        return
    }

    const list = menu.querySelector('.fi-select-input-options-ctn')
        ?? menu.querySelector('.fi-dropdown-list')

    if (! list) {
        menu.classList.remove('fff-select-dropdown-panel--scrollable')

        return
    }

    menu.classList.toggle(
        'fff-select-dropdown-panel--scrollable',
        list.scrollHeight > list.clientHeight + 1,
    )
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
        searchPrompt = '',
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

    const livewireMixin = createHeadlessComboboxLivewireMixin({
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
        noSearchResultsMessage,
        searchPrompt,
        selectEmptyStateHints,
        initialOptionLabel,
        initialOptionLabels,
        multiple,
    })

    const userSelectMixin = createHeadlessUserSelectMixin({
        isUserSelectField,
        verifiedIconHtml,
        tagRemoveIconHtml,
        userSelectNoOptionsIconHtml,
        userSelectNoResultsIconHtml,
        userSelectEmptyStateHints,
    })

    const entityMentionMixin = createEntityMentionMixin({
        enabledKey: 'entityMentionsEnabled',
        triggerKey: 'mentionTrigger',
        queryKey: 'comboboxQuery',
        sectionLabel: entityMentionSectionLabel,
    })

    return {
        ...selectMenu,
        ...livewireMixin,
        ...userSelectMixin,
        ...entityMentionMixin,

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
        initialSelectedUserEntries,
        selectNoOptionsIconHtml,
        selectNoResultsIconHtml,
        selectEmptyStateHints,
        userSelectEmptyStateHints,
        canOptionLabelsWrap,
        isReorderable,

        comboboxOpen: false,
        inlineSearchFocused: false,
        comboboxQuery: '',
        comboboxHighlightedIndex: -1,
        comboboxSelectedValues: [],
        menuReady: false,
        displayReady: false,
        knownSelectedChecks: seedHeadlessKnownSelected([]),
        checkExitCancel: null,
        menuTriggerResizeObserver: null,
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

        resolveDropdownAlign() {
            return this.dropdownAlign === 'end' ? 'end' : 'start'
        },

        resolveMatchTriggerWidth() {
            return this.matchTriggerWidth !== false
        },

        _engine: null,
        _virtualFlatRows: [],

        init() {
            const initialSelectedValues = normalizeInitialSelectedValues(
                resolveHeadlessBoundState(this.state, this.initialState, this.multiple),
                this.multiple,
            )

            this._engine = createComboboxEngine({
                options: this.getEngineOptions(),
                multiple: this.multiple,
                searchable: this.searchable,
                getOptionLabel: engineConfig.getOptionLabel ?? defaultGetOptionLabel,
                getOptionValue: engineConfig.getOptionValue ?? defaultGetOptionValue,
                filterFn: this.hasDynamicSearchResults
                    ? this.serverSideFilterFn()
                    : () => true,
                isOptionDisabled: (option) => this.isHeadlessOptionDisabled(option),
                virtualizeThreshold,
                virtualWindowSize,
                initialSelectedValues,
                recentValues: recentOptionValues,
                suggestedValues: suggestedOptionValues,
                allowCreate: allowCreateOption,
                createOptionLabel: (query) => `${createOptionLabel} "${query}"`,
                onChange: (values) => {
                    this.comboboxSelectedValues = values
                    this.syncStateFromEngine(values)
                    this.syncUserSelectTags?.()
                    userOnChange?.(values, this._engine)
                    this.$nextTick(() => {
                        syncDropdownScrollbarInset(this.$refs.headlessMenu)
                        this.scheduleMenuPositionAfterLayout()
                    })
                },
            })

            this._syncFromEngine()
            this.syncInlineSearchInputAfterClose()
            this.bindSelectMenuLifecycle()
            this.initLivewireIntegration()
            this.initUserSelectIntegration()

            this.$watch('comboboxOpen', (open) => {
                if (open) {
                    this.syncEngineOptions()
                    this.knownSelectedChecks = seedHeadlessKnownSelected(this.comboboxSelectedValues)
                    this.checkExitCancel?.()
                    this.checkExitCancel = null
                    this.bindHeadlessMenuPositionObservers()
                    this.onHeadlessMenuOpenedForLivewire()

                    this.$nextTick(() => {
                        syncDropdownScrollbarInset(this.$refs.headlessMenu)
                        this.markKnownOptionChecksVisible()
                        this.scheduleMenuPositionAfterLayout()

                        if (this.searchable) {
                            this.focusHeadlessSearchInput()
                        }

                        this.observeHeadlessLoadMore?.()
                    })

                    return
                }

                this.menuReady = false
                this.unbindHeadlessMenuPositionObservers()
                this.disconnectHeadlessLoadMoreObserver?.()
                this.freezeOptionChecksForMenuClose()
            })

            this.$watch('state', (nextState) => {
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
                if (this.hasDynamicSearchResults) {
                    return
                }

                this._engine?.setQuery('')
                this._engine?.setOptions(this.getEngineOptions())
                this._syncFromEngine()
            })

            if (! this.isUserSelectField || ! this.multiple) {
                this.markHeadlessDisplayReady()
            } else {
                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        if (this.$refs.headlessTrigger) {
                            requestAnimationFrame(() => {
                                this.markHeadlessDisplayReady()
                            })

                            return
                        }

                        this.markHeadlessDisplayReady()
                    })
                })
            }
        },

        markHeadlessDisplayReady() {
            if (this.displayReady) {
                return
            }

            this.displayReady = true

            const shell = this.$el.closest('.fff-select-field__shell--headless')
                ?? this.$el.closest('.fff-select-field__shell')

            if (! shell) {
                return
            }

            shell.dataset.fffSelectAttached = 'true'

            shell.querySelectorAll('.fff-select-trigger-ssr, .fff-select-item-card-ssr').forEach((element) => {
                element.classList.add('is-replaced')
            })
        },

        destroy() {
            this.unbindHeadlessMenuPositionObservers()
            this.disconnectHeadlessLoadMoreObserver?.()
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
            this.comboboxSelectedValues = Array.from(snapshot.selectedValues)
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
                        rows.push({
                            type: 'option',
                            option,
                            key: String(headlessOptionValue(option)),
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
            let tree = Array.isArray(this.options) ? this.options : []

            if (this.searchable && ! this.hasDynamicSearchResults && String(this.comboboxQuery ?? '').trim() !== '') {
                tree = filterHeadlessOptionTree(
                    tree,
                    this.comboboxQuery,
                    (option) => defaultGetOptionLabel(option),
                )
            }

            return tree
        },

        comboboxFilteredDropdownRowsWithoutSections() {
            void this.virtualScrollTick

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

            if (this.shouldVirtualizeDropdown()) {
                const flatRows = flattenHeadlessDropdownRowsForVirtualization(rows)
                this._virtualFlatRows = flatRows

                const windowed = windowHeadlessVirtualRows(
                    flatRows,
                    this.virtualRowWindowStart ?? 0,
                    this.virtualWindowSize ?? DEFAULT_VIRTUAL_WINDOW_SIZE,
                )

                return windowed.rows.map((row) => {
                    if (row.type === 'group-header') {
                        return {
                            type: 'group',
                            label: row.label,
                            options: [],
                            key: row.key,
                            virtualHeaderOnly: true,
                        }
                    }

                    return row
                })
            }

            this._virtualFlatRows = []

            return rows
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
            this.flatOptions = flattenHeadlessOptions(this.options)
            this._engine?.setOptions(this.getEngineOptions())
            this._syncFromEngine()
        },

        isHeadlessOptionDisabled(option) {
            return headlessOptionIsDisabled(option)
        },

        headlessOptionFlatIndex(value) {
            const normalized = String(value)

            return this.getEngineOptions().findIndex((option) => headlessOptionValue(option) === normalized)
        },

        selectCreateOption(value) {
            if (! this._engine?.createInlineOption?.(value)) {
                return
            }

            this._syncFromEngine()
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
            const button = this.$refs.headlessTrigger

            if (! button) {
                return this.$refs.headlessTriggerCtn ?? null
            }

            if (this.multiple) {
                return this.$refs.headlessTriggerCtn
                    ?? button.closest('.fi-select-input-ctn:not(.fff-select-trigger-ssr)')
                    ?? button
            }

            return button
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
            const menu = this.$refs.headlessMenu
            const trigger = this.resolveMenuTriggerElement()

            if (! menu || ! trigger) {
                return
            }

            // Match patch runtime: finalize width before anchoring, then re-anchor once sized.
            this.applyHeadlessDropdownWidthLayout()
            selectMenu.updateMenuPosition.call(this, { reveal })
        },

        teardownHeadlessMenuPosition() {
            this.menuReady = false
            this.unbindHeadlessMenuPositionObservers()
            this.unbindMenuListeners()

            const menu = this.$refs.headlessMenu

            if (! menu) {
                return
            }

            menu.classList.remove('is-positioned', 'is-open', 'is-closing')
        },

        applyRichListDropdownWidth() {
            const menu = this.$refs.headlessMenu
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
            const menu = this.$refs.headlessMenu
            const trigger = this.resolveMenuTriggerElement()

            if (! menu || ! trigger) {
                return
            }

            const buttonWidth = trigger.offsetWidth
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
            const menu = this.$refs.headlessMenu

            if (! menu) {
                return
            }

            menu.classList.add('fi-width-none')
            menu.style.setProperty('width', '22rem', 'important')
            menu.style.setProperty('max-width', 'min(22rem, calc(100vw - 2rem))', 'important')
            menu.style.setProperty('min-width', '22rem', 'important')
        },

        scheduleMenuPositionAfterLayout() {
            if (! this.comboboxOpen) {
                return
            }

            if (this.__fffHeadlessMenuPositionRaf) {
                return
            }

            this.__fffHeadlessMenuPositionRaf = requestAnimationFrame(() => {
                this.__fffHeadlessMenuPositionRaf = requestAnimationFrame(() => {
                    this.__fffHeadlessMenuPositionRaf = 0

                    if (this.comboboxOpen) {
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

        comboboxOpenMenu() {
            if (this.disabled) {
                return
            }

            if (! this.displayReady) {
                this.markHeadlessDisplayReady()
            }

            this.ensureInlineSearchInputValueBeforeOpen()

            this.syncEngineOptions()
            this._engine?.open()
            this._syncFromEngine()
            this.comboboxOpen = true
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

        comboboxCloseMenu() {
            this.teardownHeadlessMenuPosition()
            this._engine?.close()
            this.comboboxHighlightedIndex = -1
            this.inlineSearchFocused = false

            if (! this.comboboxOpen) {
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
            const input = this.inlineSearch
                ? this.$refs.headlessInlineSearchInput
                : this.$refs.headlessSearchInput

            input?.focus({ preventScroll: true })

            if (this.usesInlineSearchTriggerInput()) {
                this.$nextTick(() => {
                    positionInlineSearchCaretAtInlineStart(this.$refs.headlessInlineSearchInput)
                })
            }
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
                positionInlineSearchCaretAtInlineStart(this.$refs.headlessInlineSearchInput)
            })
        },

        onInlineSearchBlur() {
            if (! this.usesInlineSearchTriggerInput()) {
                return
            }

            window.setTimeout(() => {
                const active = document.activeElement
                const menu = this.$refs.headlessMenu
                const trigger = this.$refs.headlessTrigger

                if (menu?.contains(active) || trigger?.contains(active)) {
                    return
                }

                this.inlineSearchFocused = false
            }, 0)
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

            if (this.hasDynamicSearchResults) {
                this._engine?.setQuery(value)
            } else {
                this._engine?.setQuery('')
                this._engine?.setOptions(this.getEngineOptions())
            }

            this._syncFromEngine()
            this.$nextTick(() => {
                syncDropdownScrollbarInset(this.$refs.headlessMenu)
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
                this.comboboxCloseMenu()

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

            this.rememberSelectedOption(value)
            this._engine?.selectValue(value)
            this._syncFromEngine()

            if (! this.multiple) {
                this.comboboxCloseMenu()
                this.syncInlineSearchInputAfterSelection(value)

                return
            }

            this.queueOptionCheckEnter(key)
        },

        comboboxDeselectValue(value) {
            const key = String(value)

            if (this.multiple && ! this.isGridLayout && this.comboboxOpen) {
                const check = this.findOptionCheckElement(key)

                if (check?.getAttribute('data-visible') === 'true') {
                    this.checkExitCancel?.()
                    this.checkExitCancel = runAfterSelectedCheckExit(check, () => {
                        this.checkExitCancel = null
                        this.finishDeselectValue(key)
                    })

                    return
                }
            }

            this.finishDeselectValue(key)
        },

        finishDeselectValue(key) {
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

            return findHeadlessOptionRecord(this.options, normalized)
                ?? findHeadlessOptionRecord(this.flatOptions, normalized)
                ?? this.labelEntry(value)
                ?? null
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
                return this.renderUserOptionHtml(option.user, 'list')
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
                    this.comboboxDeselectValue(value)

                    return
                }

                if (this.clearable) {
                    this.clearSelection()
                }

                return
            }

            this.comboboxSelectValue(value)
        },

        clearSelection() {
            if (this.disabled || ! this.clearable) {
                return
            }

            this._engine?.setSelectedValues([])
            this._syncFromEngine()

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

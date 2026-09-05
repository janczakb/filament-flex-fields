import { trimSearchResultsCache } from '../core/icon-picker-cache.js'
import { createIconPickerSvgLoader } from '../core/icon-picker-svg-loader.js'
import { createIconPickerKeyboardMixin, highlightIconLabel } from '../core/icon-picker-keyboard.js'
import { createIconPickerComboboxBridge } from '../core/icon-picker-combobox-bridge.js'
import { createOverlayMenuKeyboardMixin } from '../core/overlay-menu-keyboard.js'
import { createSearchableSelectMenuMixin, runAfterSheetEnter } from '../core/searchable-select-menu.js'
import { resolveOverlayMode } from '../core/overlay-mode.js'
import { createIconPickerVirtualScrollMixin, mergeAlpineComponentDescriptors } from '../core/icon-picker-virtual-scroll.js'
import { callIconPickerSchemaMethod, canCallIconPickerSchemaMethod, isIconPickerSearchPayload } from '../core/icon-picker-livewire.js'
import { buildSearchResultsCacheKey } from '../core/icon-picker-virtual-window.js'

const teleportedMenu = createSearchableSelectMenuMixin({
    openKey: 'panelOpen',
    readyKey: 'panelReady',
    scrollHandlerKey: 'panelScrollHandler',
    resizeHandlerKey: 'panelResizeHandler',
    triggerRef: 'pickerTrigger',
    menuRef: 'pickerPanel',
    closeMethod: 'closePanel',
    ownerIdPrefix: 'fff-icon-picker',
    matchTriggerWidth: false,
    minMenuWidth: 352,
    onMenuClose() {
        this.svgLoader?.disconnect()
        this.nextPageCache = null
        this.resetIconResultsScroll()
        this.syncDropdownOpenState(false)

        if (this.iconSvgSyncFrame) {
            cancelAnimationFrame(this.iconSvgSyncFrame)
            this.iconSvgSyncFrame = null
        }

        this.cancelVirtualScrollFrame?.()
    },
})

const SKELETON_COUNT = 12

function normalizeIconItem(item) {
    if (typeof item === 'string') {
        return { name: item, label: item }
    }

    return {
        name: item?.name ?? '',
        label: item?.label ?? item?.name ?? '',
    }
}

export default function iconPickerFieldFormComponent({
    state,
    componentKey,
    availableSets,
    layout,
    closeOnSelect,
    gridColumns,
    preload,
    perPage,
    readOnly,
    clearable,
    placeholder,
    labels,
    initialSelectedHtml,
    initialSelectedName,
}) {
    const component = {
        ...teleportedMenu,
        state,
        componentKey,
        availableSets,
        layout,
        closeOnSelect,
        gridColumns,
        preload,
        perPage,
        readOnly,
        clearable: !! clearable,
        placeholder,
        labels,
        panelOpen: false,
        panelReady: false,
        triggerHydrated: false,
        searchQuery: '',
        activeSet: null,
        loadedIconItems: [],
        svgCache: {},
        selectedHtml: initialSelectedHtml ?? '',
        searchPending: false,
        initialLoadPending: false,
        iconResultsLoading: false,
        iconSkeletonVisible: false,
        iconResultsLoadingStartedAt: null,
        page: 1,
        hasMore: false,
        searchRequestToken: 0,
        nextPageCache: null,
        nextPagePrefetchToken: 0,
        searchResultsCache: new Map(),
        skeletonSlots: Array.from({ length: SKELETON_COUNT }, (_, index) => index),
        svgLoader: null,
        loadingMore: false,
        svgCacheVersion: 0,
        iconSvgSyncFrame: null,

        syncIconSkeletonVisibility() {},

        bumpSvgCacheVersion() {
            this.svgCacheVersion = (this.svgCacheVersion ?? 0) + 1
        },

        beginIconResultsLoad() {
            this.iconResultsLoadingStartedAt = Date.now()
            this.iconResultsLoading = true
            this.searchPending = true
            this.initialLoadPending = true
            this.beginIconSkeletonPhase('initial')
        },

        finishIconResultsLoad() {
            this.iconResultsLoading = false
            this.iconResultsLoadingStartedAt = null
            this.searchPending = false
            this.initialLoadPending = false
            this.finishIconSkeletonPhase()
        },

        applySvgPreviewsFromPayload(payload) {
            if (! Array.isArray(payload?.previews)) {
                return false
            }

            let applied = false

            for (const item of payload.previews) {
                if (! item?.name || ! item?.html) {
                    continue
                }

                this.svgCache[item.name] = item.html
                applied = true
            }

            if (applied) {
                this.bumpSvgCacheVersion()
            }

            return applied
        },

        markIconPickerTriggerReady() {
            this.triggerHydrated = true

            const shell = this.$el.closest('.fff-icon-picker-shell')

            shell?.querySelectorAll('.fff-icon-picker-trigger-ssr').forEach((element) => {
                element.classList.add('is-replaced')
            })
        },

        init() {
            Object.assign(this, createIconPickerKeyboardMixin({
                openKey: 'panelOpen',
                itemsKey: 'loadedIconItems',
            }))

            if (this.layout === 'list') {
                Object.assign(this, createOverlayMenuKeyboardMixin({
                    openKey: 'panelOpen',
                    resultsKey: 'loadedIconItems',
                    menuRef: 'iconResults',
                    searchRef: 'iconSearch',
                    selectMethod: 'selectIconFromKeyboard',
                    optionIdPrefix: `${componentKey}-option`,
                    activeIndexKey: 'activeIconIndex',
                    getItemValue: (item) => item?.name ?? item,
                    onEscape: 'closePanel',
                }))
            }

            if (initialSelectedName && initialSelectedHtml) {
                this.svgCache[initialSelectedName] = initialSelectedHtml
            }

            this.svgLoader = createIconPickerSvgLoader({
                getSvgCache: () => this.svgCache,
                patchSvgCache: (updates) => {
                    Object.assign(this.svgCache, updates)
                    this.bumpSvgCacheVersion()
                },
                fetchSvgs: (icons) => this.requestSvgPreviews(icons),
                batchDelayMs: 0,
                batchSize: 64,
            })

            this.bindSelectMenuLifecycle()
            this.bindPanelLifecycle()
            this.initIconPickerKeyboard()

            if (this.layout === 'list') {
                this.initOverlayMenuKeyboard()
            }

            createIconPickerComboboxBridge(this)
            this.bindLoadMoreLifecycle?.()

            this.$watch('state', (value) => {
                void this.syncSelectedPreview(value)
                this.syncClearableClasses()
            })

            this.syncClearableClasses()

            if (this.preload) {
                void this.fetchResultsWhenLivewireReady()
            }

            this.$nextTick(() => {
                this.markIconPickerTriggerReady()
            })
        },

        bindPanelLifecycle() {
            let pendingResultsRefresh = false

            const refreshPanelResults = ({ preserveScroll = false } = {}) => {
                if (! this.panelOpen) {
                    return
                }

                this.measureIconResultsViewport()
                this.syncVirtualGridGeometry?.()
                this.afterVirtualResultsLayout({ preserveScroll })

                if (this.layout === 'list') {
                    this.syncVisibleIconSvgs()
                }
            }

            const deferPanelResultsRefresh = ({ preserveScroll = false } = {}) => {
                const menu = this.$refs.pickerPanel

                runAfterSheetEnter(menu, () => {
                    if (! this.panelOpen) {
                        return
                    }

                    this.$nextTick(() => {
                        refreshPanelResults({ preserveScroll })
                        wrapPanelResizeHandler()
                    })
                })
            }

            const wrapPanelResizeHandler = () => {
                if (this._iconPickerResizeWrapped || typeof this.panelResizeHandler !== 'function') {
                    return
                }

                const originalResizeHandler = this.panelResizeHandler

                this.panelResizeHandler = () => {
                    originalResizeHandler.call(this)
                    this.onVirtualPanelResize?.()
                }

                this._iconPickerResizeWrapped = true
            }

            this.$watch('panelReady', (ready) => {
                if (! ready || ! this.panelOpen) {
                    return
                }

                // Fetch must not wait on grid geometry — geometry can stall on sheet enter.
                if (this.loadedIconItems.length === 0 && ! this.searchPending && ! this.iconResultsLoading) {
                    void this.fetchResultsWhenLivewireReady()
                }

                void this.whenPanelResultsReady?.(() => {
                    if (! this.panelOpen) {
                        return
                    }

                    if (pendingResultsRefresh) {
                        pendingResultsRefresh = false
                        deferPanelResultsRefresh({ preserveScroll: true })
                    }
                })
            })

            this.$watch('panelOpen', (open) => {
                this.syncDropdownOpenState(open)

                if (open) {
                    this.beginIconSkeletonPhase('initial')

                    if (this.loadedIconItems.length > 0) {
                        this.finishIconResultsLoad()

                        if (this.panelReady) {
                            void this.whenPanelResultsReady?.(() => {
                                deferPanelResultsRefresh({ preserveScroll: true })
                            })
                        } else {
                            pendingResultsRefresh = true
                        }
                    } else if (this.panelReady) {
                        void this.fetchResultsWhenLivewireReady()
                    }

                    return
                }

                this._iconPickerResizeWrapped = false
                pendingResultsRefresh = false
                this._finishSkeletonWhenReady = false
                this.clearIconSkeletonTimers?.()
                this.iconLoadingPhase = 'idle'
                this.iconSkeletonFading = false
                this._iconSkeletonShownAt = null
                this.searchPending = false
                this.initialLoadPending = false
                this.iconResultsLoading = false

                if (this.iconSvgSyncFrame) {
                    cancelAnimationFrame(this.iconSvgSyncFrame)
                    this.iconSvgSyncFrame = null
                }

                this.cancelVirtualScrollFrame?.()
                this.disconnectLoadMoreObserver?.()
                this.svgLoader?.disconnect()
                this.nextPageCache = null
                this.clearLoadMoreSkeletonTimers?.()
                this.loadingMore = false
                this.iconScrollNearEnd = false
                this.resetGridGeometryLock?.()
            })
        },

        resolveSelectWrapper() {
            return this.$el?.closest('.fff-select-field') ?? null
        },

        syncDropdownOpenState(isOpen) {
            this.resolveSelectWrapper()?.classList.toggle('is-dropdown-open', isOpen)
        },

        syncClearableClasses() {
            const wrapper = this.resolveSelectWrapper()
            const hasValue = this.clearable && !! this.state && ! this.readOnly

            wrapper?.classList.toggle('fff-select-field--clearable-has-value', hasValue)
        },

        resolveMenuTriggerElement() {
            return this.$refs.pickerTrigger?.closest('.fi-select-input-ctn')
                ?? this.$refs.pickerTrigger
                ?? null
        },

        applyIconPickerPanelWidth() {
            const menu = this.$refs.pickerPanel

            if (! menu) {
                return
            }

            // Live breakpoint only — leftover sheet classes must not block desktop pins
            // after a sheet→panel resize flip.
            if (resolveOverlayMode(window) === 'sheet') {
                return
            }

            if (this.layout === 'grid' || this.layout === 'icons') {
                menu.style.setProperty('width', '22rem', 'important')
                menu.style.setProperty('min-width', '22rem', 'important')
                menu.style.setProperty('max-width', 'min(22rem, calc(100vw - 2rem))', 'important')

                return
            }

            const trigger = this.resolveMenuTriggerElement()

            if (! trigger) {
                return
            }

            const triggerWidth = trigger.offsetWidth
            const targetWidth = Math.max(triggerWidth, 288)

            menu.style.width = `${targetWidth}px`
            menu.style.minWidth = `${targetWidth}px`
            menu.style.maxWidth = `min(${targetWidth}px, calc(100vw - 2rem))`
        },

        updateMenuPosition({ reveal = false, markReady = true } = {}) {
            if (this.__fffSheetClosing) {
                return
            }

            const menu = this.$refs.pickerPanel
            const previousMode = this.__fffOverlayMode ?? null
            const desiredMode = resolveOverlayMode(window)

            // Don't let an in-progress sheet enter skip breakpoint flips on resize.
            if (
                menu?.dataset?.fffSheetEntering === 'true'
                && previousMode
                && previousMode !== desiredMode
            ) {
                delete menu.dataset.fffSheetEntering
            }

            // Mode/chrome first, then desktop width pins (SelectField pattern).
            teleportedMenu.updateMenuPosition.call(this, { reveal, markReady })
            this.applyIconPickerPanelWidth()

            // Belt-and-suspenders: CSS sheet/panel rules key off these classes —
            // force both directions so a lagging class cannot leave the wrong chrome.
            if (desiredMode === 'sheet' && menu) {
                menu.classList.add('fff-teleported-menu--sheet', 'fff-overlay-sheet')
                menu.classList.remove('fff-teleported-menu--panel', 'fff-overlay-panel')
            } else if (desiredMode === 'panel' && menu) {
                menu.classList.remove('fff-teleported-menu--sheet', 'fff-overlay-sheet')
                menu.classList.add('fff-teleported-menu--panel', 'fff-overlay-panel')
            }

            const nextMode = this.__fffOverlayMode ?? desiredMode

            if (previousMode && previousMode !== nextMode) {
                this.resetGridGeometryLock?.()
            }

            // wrapPanelResizeHandler replaces this.panelResizeHandler after bind, so the
            // window listener never sees onVirtualPanelResize — remasure here instead.
            this.$nextTick?.(() => {
                requestAnimationFrame(() => {
                    if (! this.panelOpen) {
                        return
                    }

                    this.onVirtualPanelResize?.()
                })
            })
        },

        selectIconFromKeyboard(icon) {
            if (typeof icon === 'string') {
                this.selectIcon(icon)

                return
            }

            if (icon?.name) {
                this.selectIcon(icon.name)
            }
        },

        togglePanel() {
            if (this.readOnly) {
                return
            }

            if (this.panelOpen) {
                this.closePanel()

                return
            }

            this.panelOpen = true
        },

        closePanel() {
            this.closeTeleportedMenu()
        },

        onSearchInput() {
            this.nextPageCache = null
            this.searchResultsCache.clear()
            void this.fetchResults({ reset: true })
        },

        selectSet(setKey) {
            this.activeSet = setKey
            this.nextPageCache = null
            this.searchResultsCache.clear()
            void this.fetchResults({ reset: true })
        },

        async loadMore() {
            if (! this.hasMore || this.searchPending) {
                this.releaseLoadMoreRequest?.({ immediate: true })

                return
            }

            if (this.nextPageCache?.page === this.page + 1) {
                const payload = this.nextPageCache
                this.nextPageCache = null
                await this.waitMinLoadMoreSkeleton?.()
                this.page = payload.page
                this.applyPagePayload(payload, { reset: false })
                void this.prefetchNextPage()
                this.releaseLoadMoreRequest?.()

                return
            }

            this.page += 1
            await this.fetchResults({ reset: false })
        },

        normalizeIcons(payloadIcons) {
            if (! Array.isArray(payloadIcons)) {
                return []
            }

            return payloadIcons
                .map(normalizeIconItem)
                .filter((item) => item.name !== '')
        },

        readSearchCache(page = this.page) {
            return this.searchResultsCache.get(
                buildSearchResultsCacheKey(this.searchQuery, this.activeSet, page),
            )
        },

        writeSearchCache(payload, page = this.page) {
            this.searchResultsCache.set(
                buildSearchResultsCacheKey(this.searchQuery, this.activeSet, page),
                payload,
            )
            trimSearchResultsCache(this.searchResultsCache)
        },

        async fetchResultsWhenLivewireReady({ reset = true, maxAttempts = 40 } = {}) {
            if (! this.componentKey || ! this.panelOpen) {
                return false
            }

            for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
                if (! this.panelOpen) {
                    return false
                }

                if (canCallIconPickerSchemaMethod(this)) {
                    await this.fetchResults({ reset })

                    return this.loadedIconItems.length > 0
                }

                await new Promise((resolve) => setTimeout(resolve, 16))
            }

            return false
        },

        async fetchResults({ reset = true }) {
            if (! this.componentKey) {
                return
            }

            if (! canCallIconPickerSchemaMethod(this)) {
                return
            }

            if (reset) {
                this.page = 1
                this.releaseLoadMoreRequest?.({ immediate: true })
                this.resetVirtualScrollState()
                this.resetIconResultsScroll()
                this.syncVirtualGridGeometry?.({ force: ! this.gridGeometryLocked })
            }

            const cachedPayload = this.readSearchCache(this.page)

            if (cachedPayload && isIconPickerSearchPayload(cachedPayload)) {
                if (reset) {
                    this.beginIconResultsLoad()
                    this.loadedIconItems = []
                } else {
                    await this.waitMinLoadMoreSkeleton?.()
                }

                this.applyPagePayload(cachedPayload, { reset })

                if (this.hasMore) {
                    void this.prefetchNextPage()
                }

                if (reset) {
                    this.finishIconResultsLoad()
                } else {
                    this.releaseLoadMoreRequest?.()
                }

                return
            }

            if (cachedPayload) {
                this.searchResultsCache.delete(
                    buildSearchResultsCacheKey(this.searchQuery, this.activeSet, this.page),
                )
            }

            if (reset) {
                this.beginIconResultsLoad()
                this.loadedIconItems = []
            }

            const token = ++this.searchRequestToken

            if (! reset) {
                this.searchPending = true
            }

            try {
                const payload = await callIconPickerSchemaMethod(
                    this,
                    'getIconPickerSearchResults',
                    {
                        query: this.searchQuery,
                        set: this.activeSet,
                        page: this.page,
                    },
                )

                if (! isIconPickerSearchPayload(payload)) {
                    throw new Error('Invalid icon picker search payload')
                }

                if (token !== this.searchRequestToken) {
                    return
                }

                this.writeSearchCache(payload, this.page)
                this.applyPagePayload(payload, { reset })

                if (this.hasMore) {
                    void this.prefetchNextPage()
                }
            } catch {
                if (token === this.searchRequestToken && reset) {
                    this.loadedIconItems = []
                    this.hasMore = false
                }
            } finally {
                if (token === this.searchRequestToken) {
                    this.releaseLoadMoreRequest?.()

                    if (reset) {
                        this.finishIconResultsLoad()
                    } else {
                        this.searchPending = false
                    }
                }
            }
        },

        applyPagePayload(payload, { reset }) {
            if (! isIconPickerSearchPayload(payload)) {
                return
            }

            const icons = this.normalizeIcons(payload.icons)
            const scrollElement = this.$refs.iconResults
            const preserveScroll = ! reset && this.panelOpen
            const savedScrollTop = preserveScroll
                ? (scrollElement?.scrollTop ?? this.virtualScrollTop)
                : null
            const hadPreviews = this.applySvgPreviewsFromPayload(payload)

            this.loadedIconItems = reset
                ? icons
                : [...this.loadedIconItems, ...icons]

            this.hasMore = Boolean(payload?.hasMore)

            if (Array.isArray(payload?.sets) && payload.sets.length > 0) {
                this.availableSets = payload.sets
            }

            this.syncIconComboboxOptions?.()
            this.afterVirtualResultsLayout({ preserveScroll })

            if (preserveScroll && savedScrollTop !== null && scrollElement) {
                requestAnimationFrame(() => {
                    scrollElement.scrollTop = savedScrollTop
                    this.virtualScrollTop = savedScrollTop
                    this.syncVisibleIconSvgs()
                })
            } else if (! reset) {
                this.syncVisibleIconSvgs()
            } else if (this.layout === 'list' || ! hadPreviews) {
                this.syncVisibleIconSvgs()
            }
        },

        async prefetchNextPage() {
            if (! this.hasMore || ! this.componentKey || ! canCallIconPickerSchemaMethod(this)) {
                return
            }

            const nextPage = this.page + 1

            if (this.readSearchCache(nextPage)) {
                return
            }

            const token = ++this.nextPagePrefetchToken

            try {
                const payload = await callIconPickerSchemaMethod(
                    this,
                    'getIconPickerSearchResults',
                    {
                        query: this.searchQuery,
                        set: this.activeSet,
                        page: nextPage,
                    },
                )

                if (payload === null) {
                    return
                }

                if (token !== this.nextPagePrefetchToken) {
                    return
                }

                this.writeSearchCache(payload, nextPage)
                this.nextPageCache = {
                    page: nextPage,
                    ...payload,
                }
            } catch {
                this.nextPageCache = null
            }
        },

        async requestSvgPreviews(icons) {
            if (! this.componentKey || ! canCallIconPickerSchemaMethod(this)) {
                return []
            }

            const rendered = await callIconPickerSchemaMethod(
                this,
                'getIconPickerSvgPreviews',
                { icons },
            )

            return Array.isArray(rendered) ? rendered : []
        },

        svgFor(icon) {
            void this.svgCacheVersion

            return this.svgCache[icon] ?? ''
        },

        highlightedLabel(label) {
            return highlightIconLabel(label, this.searchQuery)
        },

        async syncSelectedPreview(icon) {
            if (! icon) {
                this.selectedHtml = ''

                return
            }

            if (this.svgCache[icon]) {
                this.selectedHtml = this.svgCache[icon]

                return
            }

            try {
                const rendered = await this.requestSvgPreviews([icon])

                if (! Array.isArray(rendered)) {
                    return
                }

                for (const item of rendered) {
                    if (! item?.name || ! item?.html) {
                        continue
                    }

                    this.svgCache[item.name] = item.html
                }

                this.bumpSvgCacheVersion()
            } catch {
                return
            }

            this.selectedHtml = this.svgCache[icon] ?? ''
        },

        selectIcon(icon) {
            if (this.readOnly) {
                return
            }

            this.state = icon
            this.selectedHtml = this.svgCache[icon] ?? this.selectedHtml

            if (this.closeOnSelect) {
                this.closePanel()
            }
        },

        clearSelection() {
            if (this.readOnly) {
                return
            }

            this.state = null
            this.selectedHtml = ''
        },
    }

    return mergeAlpineComponentDescriptors(component, createIconPickerVirtualScrollMixin())
}

export { highlightIconLabel }

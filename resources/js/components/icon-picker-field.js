import { trimSearchResultsCache } from '../core/icon-picker-cache.js'
import { createIconPickerSvgLoader } from '../core/icon-picker-svg-loader.js'
import { createIconPickerKeyboardMixin, highlightIconLabel } from '../core/icon-picker-keyboard.js'
import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'
import {
    buildSearchResultsCacheKey,
    ICON_PICKER_VIRTUAL_SCROLL_THRESHOLD,
    resolveScrollTopForIconIndex,
    resolveVirtualWindow,
} from '../core/icon-picker-virtual-window.js'

const teleportedMenu = createSearchableSelectMenuMixin({
    openKey: 'panelOpen',
    readyKey: 'panelReady',
    scrollHandlerKey: 'panelScrollHandler',
    resizeHandlerKey: 'panelResizeHandler',
    triggerRef: 'pickerTrigger',
    menuRef: 'pickerPanel',
    closeMethod: 'closePanel',
    ownerIdPrefix: 'fff-icon-picker',
    onMenuClose() {
        this.svgLoader?.disconnect()
        this.nextPageCache = null
        this.resetIconResultsScroll()
        this.syncDropdownOpenState(false)

        if (this.iconSvgSyncFrame) {
            cancelAnimationFrame(this.iconSvgSyncFrame)
            this.iconSvgSyncFrame = null
        }
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
    return {
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
        searchQuery: '',
        activeSet: null,
        loadedIconItems: [],
        svgCache: {},
        selectedHtml: initialSelectedHtml ?? '',
        searchPending: false,
        initialLoadPending: false,
        page: 1,
        hasMore: false,
        searchRequestToken: 0,
        infiniteScrollObserver: null,
        nextPageCache: null,
        nextPagePrefetchToken: 0,
        searchResultsCache: new Map(),
        skeletonSlots: Array.from({ length: SKELETON_COUNT }, (_, index) => index),
        svgLoader: null,
        loadingMore: false,
        virtualScrollTop: 0,
        virtualViewportHeight: 224,
        measuredStride: null,
        iconSvgSyncFrame: null,

        get usesIconVirtualScroll() {
            return this.loadedIconItems.length > ICON_PICKER_VIRTUAL_SCROLL_THRESHOLD
        },

        get iconVirtualWindow() {
            return resolveVirtualWindow({
                items: this.loadedIconItems,
                scrollTop: this.virtualScrollTop,
                viewportHeight: this.virtualViewportHeight,
                layout: this.layout,
                gridColumns: this.gridColumns,
                measuredStride: this.measuredStride,
            })
        },

        get visibleIconEntries() {
            if (! this.usesIconVirtualScroll) {
                return this.loadedIconItems.map((item, index) => ({ item, index }))
            }

            const window = this.iconVirtualWindow

            return window.slice.map((item, offset) => ({
                item,
                index: window.startIndex + offset,
            }))
        },

        get iconWindowOffsetTop() {
            return this.usesIconVirtualScroll ? this.iconVirtualWindow.offsetTop : 0
        },

        get iconTrackHeight() {
            return this.usesIconVirtualScroll ? this.iconVirtualWindow.trackHeight : 0
        },

        init() {
            Object.assign(this, createIconPickerKeyboardMixin({
                openKey: 'panelOpen',
                itemsKey: 'loadedIconItems',
            }))

            if (initialSelectedName && initialSelectedHtml) {
                this.svgCache[initialSelectedName] = initialSelectedHtml
            }

            this.svgLoader = createIconPickerSvgLoader({
                getSvgCache: () => this.svgCache,
                patchSvgCache: (updates) => {
                    Object.assign(this.svgCache, updates)
                },
                fetchSvgs: (icons) => this.requestSvgPreviews(icons),
            })

            this.bindSelectMenuLifecycle()
            this.bindPanelLifecycle()
            this.initIconPickerKeyboard()
            this.bindInfiniteScroll()

            this.$watch('state', (value) => {
                void this.syncSelectedPreview(value)
                this.syncClearableClasses()
            })

            this.syncClearableClasses()

            if (this.preload) {
                this.initialLoadPending = true
                void this.fetchResults({ reset: true })
            }
        },

        bindPanelLifecycle() {
            let pendingResultsRefresh = false

            const refreshPanelResults = () => {
                if (! this.panelOpen) {
                    return
                }

                this.resetIconResultsScroll()
                this.measureIconResultsViewport()
                this.measureIconStride()
                this.syncVisibleIconSvgs()
                this.observeInfiniteScroll()
            }

            this.$watch('panelReady', (ready) => {
                if (! ready || ! pendingResultsRefresh || ! this.panelOpen) {
                    return
                }

                pendingResultsRefresh = false
                this.$nextTick(refreshPanelResults)
            })

            this.$watch('panelOpen', (open) => {
                this.syncDropdownOpenState(open)

                if (open) {
                    if (this.loadedIconItems.length === 0) {
                        this.initialLoadPending = true
                        void this.fetchResults({ reset: true })
                    } else if (this.panelReady) {
                        this.$nextTick(refreshPanelResults)
                    } else {
                        pendingResultsRefresh = true
                    }

                    return
                }

                pendingResultsRefresh = false

                if (this.iconSvgSyncFrame) {
                    cancelAnimationFrame(this.iconSvgSyncFrame)
                    this.iconSvgSyncFrame = null
                }

                this.infiniteScrollObserver?.disconnect()
                this.infiniteScrollObserver = null
                this.svgLoader?.disconnect()
                this.nextPageCache = null
                this.loadingMore = false
                this.resetIconResultsScroll()
            })
        },

        resetIconResultsScroll() {
            this.virtualScrollTop = 0

            if (this.$refs.iconResults) {
                this.$refs.iconResults.scrollTop = 0
            }
        },

        captureIconResultsScroll() {
            const element = this.$refs.iconResults

            if (! element) {
                return
            }

            this.virtualScrollTop = element.scrollTop
            this.virtualViewportHeight = element.clientHeight || 224
        },

        restoreIconResultsScroll(scrollTop) {
            const element = this.$refs.iconResults

            if (! element) {
                this.virtualScrollTop = scrollTop

                return
            }

            element.scrollTop = scrollTop
            this.virtualScrollTop = scrollTop
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

        bindInfiniteScroll() {
            this.$watch('hasMore', () => {
                this.$nextTick(() => this.observeInfiniteScroll())
            })
        },

        observeInfiniteScroll() {
            this.infiniteScrollObserver?.disconnect()
            this.infiniteScrollObserver = null

            const sentinel = this.$refs.iconScrollSentinel
            const root = this.$refs.iconResults

            if (! sentinel || ! root || ! this.hasMore || this.loadingMore) {
                return
            }

            this.infiniteScrollObserver = new IntersectionObserver((entries) => {
                if (! entries.some((entry) => entry.isIntersecting)) {
                    return
                }

                this.loadMore()
            }, {
                root,
                rootMargin: '120px',
                threshold: 0,
            })

            this.infiniteScrollObserver.observe(sentinel)
        },

        afterResultsLayout({ preserveScroll = false } = {}) {
            if (! this.panelOpen) {
                return
            }

            const scrollTop = preserveScroll
                ? (this.$refs.iconResults?.scrollTop ?? this.virtualScrollTop)
                : null

            this.$nextTick(() => {
                if (! this.panelOpen) {
                    return
                }

                if (preserveScroll && scrollTop !== null) {
                    this.restoreIconResultsScroll(scrollTop)
                } else {
                    this.captureIconResultsScroll()
                }

                this.measureIconResultsViewport()
                this.measureIconStride()
                this.syncVisibleIconSvgs()
                this.observeInfiniteScroll()
            })
        },

        measureIconResultsViewport() {
            const element = this.$refs.iconResults

            if (! element) {
                return
            }

            this.virtualViewportHeight = element.clientHeight || 224
        },

        measureIconStride() {
            if (this.measuredStride) {
                return
            }

            const element = this.$refs.iconResults

            if (! element) {
                return
            }

            // Find the first icon element to measure its height
            const firstIcon = element.querySelector('.fff-icon-picker__preview, .fff-icon-picker__list-entry')

            if (firstIcon && firstIcon.clientHeight > 0) {
                // Determine layout gap, we know gap is 6px based on CSS and constants
                this.measuredStride = firstIcon.clientHeight + 6
            }
        },

        onIconResultsScroll(event) {
            this.virtualScrollTop = event.target.scrollTop
            this.virtualViewportHeight = event.target.clientHeight

            if (this.iconSvgSyncFrame) {
                return
            }

            this.iconSvgSyncFrame = requestAnimationFrame(() => {
                this.iconSvgSyncFrame = null
                this.syncVisibleIconSvgs()
            })
        },

        syncVisibleIconSvgs() {
            const icons = this.visibleIconEntries
                .map((entry) => entry.item?.name)
                .filter(Boolean)

            if (icons.length > 0) {
                this.svgLoader?.queueIcons(icons)
            }
        },

        ensureIconIndexVisible(index) {
            if (! this.usesIconVirtualScroll || index < 0) {
                return
            }

            const targetScrollTop = resolveScrollTopForIconIndex({
                index,
                total: this.loadedIconItems.length,
                layout: this.layout,
                gridColumns: this.gridColumns,
                viewportHeight: this.virtualViewportHeight,
                measuredStride: this.measuredStride,
            })

            const element = this.$refs.iconResults

            if (! element) {
                return
            }

            if (Math.abs(element.scrollTop - targetScrollTop) > 1) {
                element.scrollTop = targetScrollTop
            }

            this.virtualScrollTop = targetScrollTop
            this.syncVisibleIconSvgs()
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

        loadMore() {
            if (! this.hasMore || this.searchPending || this.loadingMore) {
                return
            }

            this.loadingMore = true
            this.infiniteScrollObserver?.disconnect()

            if (this.nextPageCache?.page === this.page + 1) {
                this.applyPagePayload(this.nextPageCache, { reset: false })
                this.nextPageCache = null
                void this.prefetchNextPage()

                return
            }

            this.page += 1
            void this.fetchResults({ reset: false })
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

        async fetchResults({ reset = true }) {
            if (! this.componentKey || ! this.$wire?.callSchemaComponentMethod) {
                return
            }

            if (reset) {
                this.page = 1
                this.loadingMore = false
                this.resetIconResultsScroll()
            }

            const cachedPayload = this.readSearchCache(this.page)

            if (cachedPayload) {
                this.applyPagePayload(cachedPayload, { reset })

                if (this.hasMore) {
                    void this.prefetchNextPage()
                }

                return
            }

            const token = ++this.searchRequestToken
            this.searchPending = true

            if (reset && this.loadedIconItems.length === 0) {
                this.initialLoadPending = true
            }

            try {
                const payload = await this.$wire.callSchemaComponentMethod(
                    this.componentKey,
                    'getIconPickerSearchResults',
                    {
                        query: this.searchQuery,
                        set: this.activeSet,
                        page: this.page,
                    },
                )

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
                    this.searchPending = false
                    this.initialLoadPending = false
                    this.loadingMore = false
                }
            }
        },

        applyPagePayload(payload, { reset }) {
            const icons = this.normalizeIcons(payload?.icons)
            const preserveScroll = ! reset && this.panelOpen

            this.loadedIconItems = reset
                ? icons
                : [...this.loadedIconItems, ...icons]

            this.hasMore = Boolean(payload?.hasMore)
            this.loadingMore = false

            if (Array.isArray(payload?.sets) && payload.sets.length > 0) {
                this.availableSets = payload.sets
            }

            this.afterResultsLayout({ preserveScroll })
        },

        async prefetchNextPage() {
            if (! this.hasMore || ! this.componentKey || ! this.$wire?.callSchemaComponentMethod) {
                return
            }

            const nextPage = this.page + 1

            if (this.readSearchCache(nextPage)) {
                return
            }

            const token = ++this.nextPagePrefetchToken

            try {
                const payload = await this.$wire.callSchemaComponentMethod(
                    this.componentKey,
                    'getIconPickerSearchResults',
                    {
                        query: this.searchQuery,
                        set: this.activeSet,
                        page: nextPage,
                    },
                )

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
            if (! this.componentKey || ! this.$wire?.callSchemaComponentMethod) {
                return []
            }

            return await this.$wire.callSchemaComponentMethod(
                this.componentKey,
                'getIconPickerSvgPreviews',
                { icons },
            )
        },

        svgFor(icon) {
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

                    this.svgCache = {
                        ...this.svgCache,
                        [item.name]: item.html,
                    }
                }
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
}

export { highlightIconLabel }

import {
    ICON_PICKER_LOAD_MORE_SKELETON_ROWS,
    ICON_PICKER_MIN_SKELETON_MS,
    ICON_PICKER_ROW_GAP,
    ICON_PICKER_SKELETON_FADE_MS,
    resolveIconPickerGridGeometry,
    resolveIconPickerMaxScrollTop,
    resolveIconPickerStride,
    resolveLoadMoreSkeletonHeight,
    resolveLoadMoreTailStyle,
    resolveIconTrackStyle,
    resolveVirtualBottomSpacerStyle,
    resolveVirtualTopSpacerStyle,
    resolveScrollTopForIconIndex,
    resolveVirtualWindow,
    shouldPrefetchNextPage,
    shouldUseIconPickerVirtualTrack,
    usesIconPickerVirtualTrack,
    ICON_PICKER_SCROLL_PREFETCH_THRESHOLD_PX,
    ICON_PICKER_SVG_PREFETCH_ROWS,
} from './icon-picker-virtual-window.js'

export function mergeAlpineComponentDescriptors(target, mixin) {
    for (const [key, descriptor] of Object.entries(Object.getOwnPropertyDescriptors(mixin))) {
        Object.defineProperty(target, key, descriptor)
    }

    return target
}

function normalizeVisibleIconItem(item) {
    if (typeof item === 'string') {
        return { name: item, label: item }
    }

    return {
        name: item?.name ?? '',
        label: item?.label ?? item?.name ?? '',
    }
}

export function createIconPickerVirtualScrollMixin() {
    return {
        virtualScrollTop: 0,
        virtualViewportHeight: 224,
        virtualGridCellSize: 0,
        virtualGridStride: null,
        virtualGridGap: ICON_PICKER_ROW_GAP,
        virtualScrollFrame: null,
        gridGeometryLocked: false,
        _gridContainerWidth: 0,
        iconLoadingPhase: 'idle',
        iconSkeletonFading: false,
        loadingMore: false,
        iconScrollNearEnd: false,
        loadMoreObserver: null,
        _iconSkeletonShownAt: null,
        _iconSkeletonFadeTimer: null,
        _iconSkeletonHideTimer: null,
        _finishSkeletonWhenReady: false,
        _loadMoreShownAt: null,
        _loadMoreReleaseTimer: null,

        get usesIconVirtualScroll() {
            return shouldUseIconPickerVirtualTrack({
                layout: this.layout,
                itemCount: this.loadedIconItems.length,
                gridColumns: this.gridColumns,
                viewportHeight: this.virtualViewportHeight,
                measuredStride: this.virtualGridStride,
            })
        },

        resolveLiveResultsWindow() {
            if (! this.usesIconVirtualScroll) {
                const items = this.loadedIconItems

                return {
                    startIndex: 0,
                    endIndex: items.length,
                    startRow: 0,
                    endRow: items.length,
                    mountedRows: items.length,
                    slice: items,
                    paddingTop: 0,
                    paddingBottom: 0,
                    offsetTop: 0,
                    trackHeight: 0,
                    stride: this.resolveIconPickerStride(),
                    columns: 1,
                }
            }

            return resolveVirtualWindow({
                items: this.loadedIconItems,
                scrollTop: this.virtualScrollTop,
                viewportHeight: this.virtualViewportHeight,
                layout: this.layout,
                gridColumns: this.gridColumns,
                measuredStride: this.virtualGridStride,
            })
        },

        get iconVirtualWindow() {
            void this.virtualScrollTop
            void this.loadedIconItems.length
            void this.virtualGridStride
            void this.virtualViewportHeight

            return this.resolveLiveResultsWindow()
        },

        get visibleIconEntries() {
            void this.virtualScrollTop
            void this.loadedIconItems.length

            const window = this.iconVirtualWindow

            return window.slice.map((item, offset) => ({
                item: normalizeVisibleIconItem(item),
                index: window.startIndex + offset,
            }))
        },

        get loadMoreTailHeight() {
            if (! this.loadingMore) {
                return 0
            }

            return resolveLoadMoreSkeletonHeight({
                layout: this.layout,
                measuredStride: this.virtualGridStride,
            })
        },

        get showLoadMoreTailSkeleton() {
            return this.loadingMore
        },

        get showScrollLoadSkeleton() {
            return this.loadingMore
        },

        get iconTrackStyle() {
            void this.virtualScrollTop
            void this.loadedIconItems.length
            void this.loadingMore

            if (! this.usesIconVirtualScroll) {
                return null
            }

            return resolveIconTrackStyle({
                window: this.iconVirtualWindow,
                loadMoreTailHeight: this.loadMoreTailHeight,
            })
        },

        get loadMoreTailStyle() {
            return resolveLoadMoreTailStyle(this.loadMoreTailHeight)
        },

        get virtualBottomSpacerStyle() {
            if (! this.usesIconVirtualScroll) {
                return null
            }

            return resolveVirtualBottomSpacerStyle(this.iconVirtualWindow.paddingBottom)
        },

        get virtualTopSpacerStyle() {
            if (! this.usesIconVirtualScroll) {
                return null
            }

            return resolveVirtualTopSpacerStyle(this.iconVirtualWindow.paddingTop)
        },

        get iconResultsGridStyle() {
            if (this.layout !== 'grid' && this.layout !== 'icons') {
                return null
            }

            const style = {
                '--fff-icon-picker-grid-columns': this.gridColumns,
            }

            if (this.virtualGridCellSize > 0) {
                style['--fff-icon-picker-cell-size'] = `${this.virtualGridCellSize}px`
            }

            if (this.virtualGridGap > 0) {
                style['--fff-icon-picker-row-gap'] = `${this.virtualGridGap}px`
            }

            return style
        },

        get loadMoreSkeletonSlots() {
            if (this.layout === 'list') {
                return [0, 1]
            }

            const columns = Math.max(1, Number(this.gridColumns) || 6)

            return Array.from(
                { length: columns * ICON_PICKER_LOAD_MORE_SKELETON_ROWS },
                (_, index) => index,
            )
        },

        get initialSkeletonSlots() {
            const columns = Math.max(1, Number(this.gridColumns) || 6)

            if (this.layout === 'list') {
                const stride = this.resolveIconPickerStride()
                const rows = Math.max(3, Math.ceil(this.virtualViewportHeight / stride) + 1)

                return Array.from({ length: rows }, (_, index) => index)
            }

            const stride = this.virtualGridStride || this.resolveIconPickerStride()
            const rows = Math.max(2, Math.ceil(this.virtualViewportHeight / stride) + 1)

            return Array.from({ length: columns * rows }, (_, index) => index)
        },

        get showInitialSkeleton() {
            return this.panelOpen
                && (this.iconLoadingPhase === 'initial' || this.iconSkeletonFading)
        },

        get resultsGeometryReady() {
            if (this.layout === 'list') {
                return true
            }

            return this.gridGeometryLocked
                && this.virtualGridCellSize > 0
                && this.virtualGridStride > 0
        },

        get showLoadMoreListHint() {
            return this.loadingMore && this.layout === 'list'
        },

        resolveIconPickerStride() {
            return resolveIconPickerStride(this.layout, this.virtualGridStride)
        },

        clearLoadMoreSkeletonTimers() {
            if (this._loadMoreReleaseTimer) {
                clearTimeout(this._loadMoreReleaseTimer)
                this._loadMoreReleaseTimer = null
            }
        },

        waitMinLoadMoreSkeleton() {
            const elapsed = Date.now() - (this._loadMoreShownAt ?? 0)
            const remaining = Math.max(0, ICON_PICKER_MIN_SKELETON_MS - elapsed)

            if (remaining <= 0) {
                return Promise.resolve()
            }

            return new Promise((resolve) => {
                setTimeout(resolve, remaining)
            })
        },

        clearIconSkeletonTimers() {
            if (this._iconSkeletonFadeTimer) {
                clearTimeout(this._iconSkeletonFadeTimer)
                this._iconSkeletonFadeTimer = null
            }

            if (this._iconSkeletonHideTimer) {
                clearTimeout(this._iconSkeletonHideTimer)
                this._iconSkeletonHideTimer = null
            }
        },

        beginIconSkeletonPhase(phase = 'initial') {
            this.clearIconSkeletonTimers()
            this.iconLoadingPhase = phase
            this.iconSkeletonFading = false
            this._finishSkeletonWhenReady = false
            this._iconSkeletonShownAt = Date.now()

            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.prepareResultsGeometry()
                })
            })
        },

        finishIconSkeletonPhase() {
            if (this.iconLoadingPhase === 'idle') {
                return
            }

            if (! this.panelOpen) {
                this.clearIconSkeletonTimers()
                this.iconLoadingPhase = 'idle'
                this.iconSkeletonFading = false
                this._finishSkeletonWhenReady = false
                this._iconSkeletonShownAt = null

                return
            }

            const elapsed = Date.now() - (this._iconSkeletonShownAt ?? Date.now())
            const remaining = Math.max(0, ICON_PICKER_MIN_SKELETON_MS - elapsed)

            this.clearIconSkeletonTimers()

            this._iconSkeletonFadeTimer = setTimeout(() => {
                this.iconSkeletonFading = true

                this._iconSkeletonHideTimer = setTimeout(() => {
                    this.iconLoadingPhase = 'idle'
                    this.iconSkeletonFading = false
                    this._iconSkeletonHideTimer = null
                }, ICON_PICKER_SKELETON_FADE_MS)
            }, remaining)
        },

        resetVirtualScrollState() {
            this.virtualScrollTop = 0
            this.iconScrollNearEnd = false
            this.loadingMore = false
            this._loadMoreShownAt = null
            this.clearLoadMoreSkeletonTimers()
            this.cancelVirtualScrollFrame()
            this.disconnectLoadMoreObserver()
        },

        resetGridGeometryLock() {
            this.gridGeometryLocked = false
            this._gridContainerWidth = 0
            this.virtualGridStride = null
            this.virtualGridCellSize = 0
        },

        cancelVirtualScrollFrame() {
            if (this.virtualScrollFrame !== null) {
                cancelAnimationFrame(this.virtualScrollFrame)
                this.virtualScrollFrame = null
            }
        },

        syncVirtualGridGeometry({ force = false } = {}) {
            if (this.layout === 'list') {
                this.virtualGridStride = resolveIconPickerStride(this.layout, null)
                this.virtualGridCellSize = 0

                return
            }

            const element = this.$refs.iconResults
            const menu = this.$refs.pickerPanel

            if (! element) {
                return
            }

            // Mid-enter sheet width is unstable — wait for enter to finish.
            if (menu?.dataset?.fffSheetEntering === 'true' && ! force) {
                return
            }

            const containerWidth = element.clientWidth

            if (containerWidth <= 0) {
                return
            }

            const widthDelta = Math.abs(containerWidth - this._gridContainerWidth)
            const sheetMode = menu?.classList?.contains?.('fff-teleported-menu--sheet')
                || menu?.classList?.contains?.('fff-overlay-sheet')
            const lockTolerance = sheetMode ? 8 : 2

            if (
                ! force
                && this.gridGeometryLocked
                && containerWidth > 0
                && widthDelta < lockTolerance
            ) {
                return
            }

            // Once locked in sheet mode, ignore tiny width thrash from scrollbar /
            // fitOverlaySheet — only remasure on real viewport changes.
            if (
                force
                && sheetMode
                && this.gridGeometryLocked
                && widthDelta < lockTolerance
            ) {
                return
            }

            const geometry = resolveIconPickerGridGeometry({
                containerWidth,
                layout: this.layout,
                gridColumns: this.gridColumns,
                gap: this.virtualGridGap,
            })

            this.virtualGridCellSize = geometry.cellSize
            this.virtualGridStride = geometry.stride
            this.virtualGridGap = geometry.gap
            this._gridContainerWidth = containerWidth
            this.gridGeometryLocked = containerWidth > 0
        },

        measureIconResultsViewport() {
            const element = this.$refs.iconResults

            if (! element) {
                return
            }

            this.virtualViewportHeight = element.clientHeight || 224
        },

        captureIconResultsScroll() {
            const element = this.$refs.iconResults

            if (! element) {
                return
            }

            this.virtualScrollTop = element.scrollTop
            this.virtualViewportHeight = element.clientHeight || 224
        },

        clampIconResultsScroll() {
            const element = this.$refs.iconResults

            if (! element || ! this.usesIconVirtualScroll) {
                return
            }

            const window = this.iconVirtualWindow
            const maxScrollTop = resolveIconPickerMaxScrollTop(
                window.trackHeight,
                this.virtualViewportHeight,
                0,
            )

            if (element.scrollTop <= maxScrollTop) {
                return
            }

            element.scrollTop = maxScrollTop
            this.virtualScrollTop = maxScrollTop
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

        resetIconResultsScroll() {
            this.virtualScrollTop = 0
            this.iconScrollNearEnd = false

            if (this.$refs.iconResults) {
                this.$refs.iconResults.scrollTop = 0
            }
        },

        afterVirtualResultsLayout({ preserveScroll = false } = {}) {
            if (! this.panelOpen) {
                return
            }

            const scrollTop = preserveScroll
                ? (this.$refs.iconResults?.scrollTop ?? this.virtualScrollTop)
                : null

            requestAnimationFrame(() => {
                if (! this.panelOpen) {
                    return
                }

                this.measureIconResultsViewport()
                this.syncVirtualGridGeometry()

                if (preserveScroll && scrollTop !== null) {
                    this.restoreIconResultsScroll(scrollTop)
                } else if (! preserveScroll) {
                    this.captureIconResultsScroll()
                }

                this.clampIconResultsScroll()
                this.syncIconScrollNearEnd()
                this.observeLoadMoreSentinel()
                this.queueVisibleIconSvgs({ includePrefetch: true })
            })
        },

        onIconResultsScroll(event) {
            const element = this.$refs.iconResults ?? event.target

            if (! element) {
                return
            }

            this.virtualScrollTop = element.scrollTop
            this.virtualViewportHeight = element.clientHeight || this.virtualViewportHeight
            this.syncIconScrollNearEnd()

            if (this.virtualScrollFrame !== null) {
                return
            }

            this.virtualScrollFrame = requestAnimationFrame(() => {
                this.virtualScrollFrame = null
                this.queueVisibleIconSvgs({ includePrefetch: true })
                this.maybeRequestLoadMoreFromScroll()
            })
        },

        syncIconScrollNearEnd() {
            if (! this.hasMore) {
                this.iconScrollNearEnd = false

                return
            }

            const window = this.iconVirtualWindow

            this.iconScrollNearEnd = shouldPrefetchNextPage({
                scrollTop: this.virtualScrollTop,
                trackHeight: window.trackHeight,
                viewportHeight: this.virtualViewportHeight,
                tailHeight: this.loadMoreTailHeight,
                thresholdPx: ICON_PICKER_SCROLL_PREFETCH_THRESHOLD_PX,
            })
        },

        maybeRequestLoadMoreFromScroll() {
            if (! this.hasMore || this.loadingMore || this.searchPending) {
                return
            }

            const window = this.iconVirtualWindow

            if (! shouldPrefetchNextPage({
                scrollTop: this.virtualScrollTop,
                trackHeight: window.trackHeight,
                viewportHeight: this.virtualViewportHeight,
                tailHeight: this.loadMoreTailHeight,
                thresholdPx: ICON_PICKER_SCROLL_PREFETCH_THRESHOLD_PX,
            })) {
                return
            }

            this.requestLoadMorePage()
        },

        disconnectLoadMoreObserver() {
            this.loadMoreObserver?.disconnect()
            this.loadMoreObserver = null
        },

        observeLoadMoreSentinel() {
            this.disconnectLoadMoreObserver()

            const sentinel = this.$refs.iconLoadMoreSentinel
            const root = this.$refs.iconResults

            if (! sentinel || ! root || ! this.hasMore || this.loadingMore || this.searchPending) {
                return
            }

            this.loadMoreObserver = new IntersectionObserver((entries) => {
                if (! entries.some((entry) => entry.isIntersecting)) {
                    return
                }

                this.requestLoadMorePage()
            }, {
                root,
                rootMargin: '320px 0px',
                threshold: 0,
            })

            this.loadMoreObserver.observe(sentinel)
        },

        requestLoadMorePage() {
            if (! this.hasMore || this.loadingMore || this.searchPending) {
                return
            }

            this._loadMoreShownAt = Date.now()
            this.syncIconScrollNearEnd()
            this.loadingMore = true
            this.disconnectLoadMoreObserver()
            this.loadMore?.()
        },

        releaseLoadMoreRequest({ immediate = false } = {}) {
            this.clearLoadMoreSkeletonTimers()

            if (immediate) {
                this.loadingMore = false
                this.syncIconScrollNearEnd()

                this.$nextTick(() => {
                    this.observeLoadMoreSentinel()
                })

                return
            }

            const elapsed = Date.now() - (this._loadMoreShownAt ?? 0)
            const remaining = Math.max(0, ICON_PICKER_MIN_SKELETON_MS - elapsed)

            this._loadMoreReleaseTimer = setTimeout(() => {
                this._loadMoreReleaseTimer = null
                this.loadingMore = false
                this.syncIconScrollNearEnd()

                this.$nextTick(() => {
                    this.observeLoadMoreSentinel()
                })
            }, remaining)
        },

        queueVisibleIconSvgs({ includePrefetch = false } = {}) {
            const visibleIcons = this.visibleIconEntries
                .map((entry) => entry.item?.name)
                .filter(Boolean)

            if (visibleIcons.length > 0) {
                this.svgLoader?.queueIcons(visibleIcons, { flush: true })
            }

            if (! includePrefetch || ! this.usesIconVirtualScroll) {
                return
            }

            const window = this.iconVirtualWindow
            const columns = window.columns ?? Math.max(1, Number(this.gridColumns) || 6)
            const stride = window.stride || this.resolveIconPickerStride()
            const viewportRows = Math.max(4, Math.ceil(this.virtualViewportHeight / stride))
            const prefetchRows = Math.max(ICON_PICKER_SVG_PREFETCH_ROWS, viewportRows + 4)
            const prefetchIcons = []
            const start = Math.max(0, window.startIndex - (columns * 2))
            const end = Math.min(this.loadedIconItems.length, window.endIndex + (columns * prefetchRows))

            for (let index = start; index < end; index += 1) {
                const name = this.loadedIconItems[index]?.name

                if (name && ! visibleIcons.includes(name)) {
                    prefetchIcons.push(name)
                }
            }

            if (prefetchIcons.length > 0) {
                this.svgLoader?.queueIcons([...new Set(prefetchIcons)])
            }
        },

        syncVisibleIconSvgs() {
            this.queueVisibleIconSvgs({ includePrefetch: true })
        },

        ensureIconIndexVisible(index) {
            if (index < 0) {
                return
            }

            if (! this.usesIconVirtualScroll) {
                const option = document.getElementById(`${this.componentKey}-option-${index}`)

                option?.scrollIntoView({ block: 'nearest', inline: 'nearest' })

                return
            }

            const targetScrollTop = resolveScrollTopForIconIndex({
                index,
                total: this.loadedIconItems.length,
                layout: this.layout,
                gridColumns: this.gridColumns,
                viewportHeight: this.virtualViewportHeight,
                measuredStride: this.virtualGridStride,
            })

            const element = this.$refs.iconResults

            if (! element) {
                return
            }

            if (Math.abs(element.scrollTop - targetScrollTop) > 1) {
                element.scrollTop = targetScrollTop
            }

            this.virtualScrollTop = targetScrollTop
            this.queueVisibleIconSvgs({ includePrefetch: true })
        },

        onVirtualPanelResize() {
            const menu = this.$refs.pickerPanel

            if (menu?.dataset?.fffSheetEntering === 'true') {
                return
            }

            // Height fits must not rewrite cell stride mid-scroll. Remasure width only
            // when it actually changed; always refresh viewport height for virtual window.
            this.syncVirtualGridGeometry({ force: true })
            this.measureIconResultsViewport()
            this.clampIconResultsScroll()
            this.observeLoadMoreSentinel()
        },

        prepareResultsGeometry() {
            const menu = this.$refs.pickerPanel

            if (menu?.dataset?.fffSheetEntering === 'true') {
                return
            }

            this.measureIconResultsViewport()
            this.syncVirtualGridGeometry({ force: ! this.gridGeometryLocked })
        },

        ensureResultsGeometryReady(maxAttempts = 24) {
            return new Promise((resolve) => {
                const attempt = (pass = 0) => {
                    if (! this.panelOpen) {
                        resolve(false)

                        return
                    }

                    const menu = this.$refs.pickerPanel

                    if (menu?.dataset?.fffSheetEntering === 'true') {
                        if (pass >= maxAttempts) {
                            resolve(this.resultsGeometryReady || this.layout === 'list')

                            return
                        }

                        requestAnimationFrame(() => attempt(pass + 1))

                        return
                    }

                    this.measureIconResultsViewport()
                    this.syncVirtualGridGeometry({ force: pass === 0 && ! this.gridGeometryLocked })

                    if (this.resultsGeometryReady || this.layout === 'list') {
                        resolve(true)

                        return
                    }

                    if (pass >= maxAttempts) {
                        this.syncVirtualGridGeometry({ force: true })
                        resolve(this.resultsGeometryReady || this.layout === 'list')

                        return
                    }

                    requestAnimationFrame(() => attempt(pass + 1))
                }

                attempt()
            })
        },

        whenPanelResultsReady(callback) {
            return this.ensureResultsGeometryReady().then((ready) => {
                if (! this.panelOpen) {
                    return
                }

                if (! ready && this.layout !== 'list') {
                    this.syncVirtualGridGeometry({ force: true })
                }

                return callback?.()
            })
        },

        bindLoadMoreLifecycle() {
            this.$watch('hasMore', () => {
                this.$nextTick(() => this.observeLoadMoreSentinel())
            })

            this.$watch('loadedIconItems.length', () => {
                this.$nextTick(() => this.observeLoadMoreSentinel())
            })
        },
    }
}

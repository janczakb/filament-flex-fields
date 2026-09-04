import {
    allTodosComplete,
    applyTodoUndoPatch,
    childProgressLabel,
    cloneTodoItems,
    countDoneChildren,
    createAudioPool,
    diffTodoDoneIds,
    filterItems,
    indexTodoDoneById,
    mergeTextLineRects,
    prefersReducedMotion,
    sortCompletedLast,
    scrollTopToRevealIndex,
    toggleTodoTree,
    buildVirtualOffsets,
    virtualWindow,
} from './todo-list-field/helpers.js'
import { animateCheckOff, animateCheckOn } from './todo-list-field/motion.js'
import { runTodoListCelebration, registerTodoListCelebration } from './todo-list-field/celebrations.js'
import { bindOverlayScrollbar, syncOverlayScrollbar } from '../core/overlay-scrollbar.js'

export { registerTodoListCelebration }

function uuid() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID()
    }

    return `todo-${Date.now()}-${Math.random().toString(16).slice(2)}`
}

export default function todoListFieldFormComponent(config = {}) {
    const audio = createAudioPool()
    const pageSize = config.pageSize ?? null
    const infiniteScroll = Boolean(config.infiniteScroll ?? pageSize)

    return {
        state: Array.isArray(config.state) ? config.state : cloneTodoItems(config.items),
        disabled: Boolean(config.disabled),
        sounds: config.sounds !== false,
        checkSound: config.checkSound ?? null,
        accentSound: config.accentSound ?? null,
        createSound: config.createSound ?? null,
        celebration: config.celebration ?? 'fireworks',
        celebrationDurationMs: config.celebrationDurationMs ?? 5500,
        celebrationSound: config.celebrationSound ?? null,
        celebrationStartSound: config.celebrationStartSound ?? null,
        celebrationAudio: config.celebrationAudio && typeof config.celebrationAudio === 'object'
            ? config.celebrationAudio
            : {},
        celebrationFullscreen: Boolean(config.celebrationFullscreen),
        strikethroughStyle: config.strikethroughStyle ?? 'hand',
        doneSettleMs: config.doneSettleMs ?? 500,
        allowCreate: Boolean(config.allowCreate),
        createWithDescription: Boolean(config.createWithDescription),
        allowDelete: Boolean(config.allowDelete),
        deletableMode: config.deletableMode ?? 'created',
        allowEdit: Boolean(config.allowEdit),
        editableMode: config.editableMode ?? 'all',
        editWithDescription: Boolean(config.editWithDescription),
        reorderable: Boolean(config.reorderable),
        reorderAnimationDuration: Number(config.reorderAnimationDuration ?? 200),
        undoCompletionNotifications: config.undoCompletionNotifications !== false,
        undoEvent: config.undoEvent ?? `fff-todo-list-undo-${uuid()}`,
        editSyncedEvent: config.editSyncedEvent ?? `fff-todo-list-edited-${uuid()}`,
        componentKey: config.componentKey ?? null,
        createUsingEnabled: Boolean(config.createUsingEnabled),
        editUsingEnabled: Boolean(config.editUsingEnabled),
        deleteUsingEnabled: Boolean(config.deleteUsingEnabled),
        reorderUsingEnabled: Boolean(config.reorderUsingEnabled),
        afterToggledEnabled: Boolean(config.afterToggledEnabled),
        labels: config.labels ?? {},
        createLabel: config.createLabel ?? 'Create option',
        createPlaceholder: config.createPlaceholder ?? 'New task…',
        createDescriptionPlaceholder: config.createDescriptionPlaceholder ?? 'Description (optional)…',
        searchable: Boolean(config.searchable),
        searchPrompt: config.searchPrompt ?? 'Search…',
        virtualizing: Boolean(config.virtualizing),
        pageSize,
        infiniteScroll,
        remoteLoader: config.remoteLoader ?? null,
        persistCompletedOrder: Boolean(config.persistCompletedOrder),
        maxItems: config.maxItems ?? null,

        search: '',
        visibleCount: pageSize || Number.POSITIVE_INFINITY,
        remotePage: 1,
        hasMore: Boolean(pageSize) || Boolean(config.hasMore),
        loadingMore: false,
        creating: false,
        createBusy: false,
        createDraft: '',
        createDescriptionDraft: '',
        _ignoreCreateRowClick: false,
        _createBlurTimer: 0,
        _enterExitTimers: {},
        settleTimers: {},
        settledIds: {},
        // Flat id→done map — nested x-for + Livewire entangle often keeps stale child.done in row scopes.
        doneIds: {},
        enteringIds: {},
        exitingIds: {},
        celebrationHandle: null,
        scrollTop: 0,
        viewportHeight: 320,
        // Measured group heights for variable-row virtualization (id → px).
        rowHeights: {},
        reducedMotion: prefersReducedMotion(),
        _observer: null,
        _undoCleanup: null,
        _editSyncedCleanup: null,
        _measureRaf: 0,
        _strikeSyncRaf: 0,
        _strikeSyncRaf2: 0,
        _onWindowResize: null,
        lastUndoSnapshot: null,

        init() {
            if (! Array.isArray(this.state) || this.state.length === 0) {
                this.state = cloneTodoItems(config.items ?? [])
            }

            if (this.pageSize) {
                this.visibleCount = this.pageSize
                this.hasMore = this.items().length > this.visibleCount || Boolean(this.remoteLoader)
            }

            this.rebuildDoneIds()

            for (const item of this.items()) {
                if (item.done) {
                    this.settledIds = { ...this.settledIds, [String(item.id)]: true }
                }

                for (const child of item.children ?? []) {
                    if (child.done) {
                        this.settledIds = { ...this.settledIds, [String(child.id)]: true }
                    }
                }
            }

            this.bindUndoListener()
            this.bindEditSyncedListener()

            this.$nextTick(() => {
                this.measureViewport()
                this.bindOverlayScrollbar()
                this.bindInfiniteObserver()
                this.bindStrikeResizeObserver()
                this.bindVirtualRowObserver()
                this.syncDoneStyles()
                this.syncStrikeWidths()
                this.scheduleMeasureRowHeights()

                const revealLive = () => {
                    this.syncDoneStyles()
                    this.syncStrikeWidths()
                    this.scheduleMeasureRowHeights()
                    this.syncOverlayScrollbar()
                    this.$el.classList.add('is-hydrated')

                    requestAnimationFrame(() => {
                        this.syncStrikeWidths()
                        this.scheduleMeasureRowHeights()
                        this.syncOverlayScrollbar()
                        requestAnimationFrame(() => {
                            this.$el.classList.add('is-motion-ready')
                        })
                    })
                }

                // Keep SSR painted until fonts + layout are measurable — a short
                // race (e.g. 400ms) swaps before webfonts settle and children jump.
                const fontsReady = typeof document !== 'undefined' && document.fonts?.ready
                    ? document.fonts.ready.catch(() => {})
                    : Promise.resolve()

                Promise.race([
                    fontsReady,
                    new Promise((resolve) => setTimeout(resolve, 1500)),
                ]).then(() => {
                    this.$nextTick(() => {
                        requestAnimationFrame(() => revealLive())
                    })
                })

                // Late font settle after hydrate — remeasure without remount.
                fontsReady.then(() => {
                    if (! this.$el?.classList?.contains('is-hydrated')) {
                        return
                    }

                    this.syncStrikeWidths()
                    this.scheduleMeasureRowHeights()
                    this.syncOverlayScrollbar()
                })
            })

            this.$watch('search', () => {
                if (this.pageSize && ! this.remoteLoader) {
                    this.visibleCount = this.pageSize
                    this.hasMore = this.filteredAll().length > this.visibleCount
                }

                this.$nextTick(() => {
                    this.scheduleSyncStrikeWidths()
                    this.syncOverlayScrollbar()
                })
            })

            this.$watch('state', () => {
                this.rebuildDoneIds()
                this.pruneRowHeights()
                this.$nextTick(() => {
                    this.scheduleSyncStrikeWidths()
                    this.syncOverlayScrollbar()
                })
            })
        },

        clearMotionTimers() {
            clearTimeout(this._createBlurTimer)
            this._createBlurTimer = 0

            for (const timer of Object.values(this.settleTimers ?? {})) {
                clearTimeout(timer)
            }

            for (const timer of Object.values(this._enterExitTimers ?? {})) {
                clearTimeout(timer)
            }

            this.settleTimers = {}
            this._enterExitTimers = {}
        },

        rebuildDoneIds(list = this.items()) {
            const next = {}

            for (const [id, done] of indexTodoDoneById(list)) {
                if (done) {
                    next[id] = true
                }
            }

            this.doneIds = next
        },

        isTodoDone(id) {
            return Boolean(this.doneIds[String(id)])
        },

        isSettledId(id) {
            return Boolean(this.settledIds[String(id)])
        },

        virtualSourceList() {
            let list = this.filteredAll()

            if (this.infiniteScroll && this.pageSize && ! this.remoteLoader) {
                list = list.slice(0, this.visibleCount)
            }

            return list
        },

        estimatedRowHeight(item) {
            const id = String(item?.id ?? '')
            const cached = this.rowHeights[id]

            if (cached) {
                return cached
            }

            const children = item?.children ?? []
            const avg = this.measuredAverageRowHeight()

            if (avg > 0) {
                if (children.length > 0) {
                    return Math.round(avg + children.length * avg * 0.7)
                }

                return avg
            }

            return this.bootstrapRowHeight(item)
        },

        measuredAverageRowHeight() {
            const values = Object.values(this.rowHeights ?? {})

            if (values.length === 0) {
                return 0
            }

            return Math.round(values.reduce((sum, value) => sum + value, 0) / values.length)
        },

        /**
         * Pre-measure fallback from content only — never applied as CSS height on rows.
         * Once any row is measured, estimatedRowHeight prefers the live average.
         */
        bootstrapRowHeight(item) {
            const label = String(item?.label ?? '')
            const description = String(item?.description ?? '')
            const children = item?.children ?? []
            const labelLines = Math.max(1, Math.min(8, Math.ceil(label.length / 42) || 1))
            const descLines = description
                ? Math.max(1, Math.min(10, Math.ceil(description.length / 56) || 1))
                : 0
            // Approximate natural box: vertical padding + title lines + description + children.
            const padding = 20
            const title = labelLines * 20
            const desc = descLines > 0 ? 6 + descLines * 16 : 0
            const stack = children.length * (padding + 20)

            return Math.max(36, padding + title + desc + stack)
        },

        pruneRowHeights() {
            const alive = new Set(this.items().map((item) => String(item.id)))
            const next = {}

            for (const [id, height] of Object.entries(this.rowHeights ?? {})) {
                if (alive.has(id)) {
                    next[id] = height
                }
            }

            this.rowHeights = next
        },

        scheduleMeasureRowHeights() {
            if (! this.virtualizing || typeof requestAnimationFrame !== 'function') {
                return
            }

            cancelAnimationFrame(this._measureRaf)
            this._measureRaf = requestAnimationFrame(() => {
                this.$nextTick?.(() => this.measureVisibleRowHeights())
            })
        },

        measureVisibleRowHeights() {
            if (! this.virtualizing) {
                return
            }

            const viewport = this.$refs?.viewport

            if (! viewport) {
                return
            }

            const list = this.virtualSourceList()
            const getHeight = (item) => this.estimatedRowHeight(item)
            const offsetsBefore = buildVirtualOffsets(list, getHeight)
            const scrollBefore = viewport.scrollTop
            const groups = viewport.querySelectorAll('.fff-todo-list-field__group[data-todo-group-id]')
            const next = { ...this.rowHeights }
            let changed = false
            let deltaAbove = 0

            for (const group of groups) {
                const id = group.getAttribute('data-todo-group-id')

                if (! id) {
                    continue
                }

                const height = Math.ceil(group.getBoundingClientRect().height)

                if (height < 1 || next[id] === height) {
                    continue
                }

                const index = list.findIndex((row) => String(row.id) === id)
                const previous = next[id] ?? (index >= 0 ? getHeight(list[index]) : height)

                if (index >= 0 && (offsetsBefore[index] ?? 0) < scrollBefore) {
                    deltaAbove += height - previous
                }

                next[id] = height
                changed = true
            }

            if (! changed) {
                return
            }

            this.rowHeights = next

            if (deltaAbove !== 0) {
                const nextScroll = Math.max(0, scrollBefore + deltaAbove)
                viewport.scrollTop = nextScroll
                this.scrollTop = nextScroll
            }

            this.syncOverlayScrollbar()
        },

        bindVirtualRowObserver() {
            this._virtualRowObserver?.disconnect?.()

            if (! this.virtualizing || typeof ResizeObserver === 'undefined') {
                return
            }

            const list = this.$refs?.viewport?.querySelector?.('.fff-todo-list-field__list')
            const target = list || this.$refs?.viewport

            if (! target) {
                return
            }

            this._virtualRowObserver = new ResizeObserver(() => {
                this.scheduleMeasureRowHeights()
            })
            this._virtualRowObserver.observe(target)
        },

        destroy() {
            cancelAnimationFrame(this._measureRaf)
            cancelAnimationFrame(this._strikeSyncRaf)
            cancelAnimationFrame(this._strikeSyncRaf2)
            this._measureRaf = 0
            this._strikeSyncRaf = 0
            this._strikeSyncRaf2 = 0

            if (typeof window !== 'undefined' && this._onWindowResize) {
                window.removeEventListener('resize', this._onWindowResize)
                this._onWindowResize = null
            }

            this._observer?.disconnect?.()
            this._strikeResizeObserver?.disconnect?.()
            this._viewportResizeObserver?.disconnect?.()
            this._virtualRowObserver?.disconnect?.()
            this._undoCleanup?.()
            this._editSyncedCleanup?.()
            this.clearMotionTimers()
            this.stopCelebration()
        },

        bindUndoListener() {
            this._undoCleanup?.()

            if (! this.undoCompletionNotifications || ! this.undoEvent) {
                return
            }

            const handler = (...args) => {
                const payload = args[0]
                const data = payload?.snapshot !== undefined || payload?.changedIds !== undefined
                    ? payload
                    : (payload?.[0] ?? payload)

                const snapshot = data?.snapshot ?? data
                const changedIds = data?.changedIds ?? null

                if (Array.isArray(snapshot)) {
                    this.restoreUndoSnapshot(snapshot, changedIds)
                }
            }

            if (typeof window.Livewire?.on === 'function') {
                const stop = window.Livewire.on(this.undoEvent, handler)
                this._undoCleanup = typeof stop === 'function' ? stop : () => {}

                return
            }

            const domHandler = (event) => handler(event.detail)
            window.addEventListener(this.undoEvent, domHandler)
            this._undoCleanup = () => window.removeEventListener(this.undoEvent, domHandler)
        },

        bindEditSyncedListener() {
            this._editSyncedCleanup?.()

            if (! this.editSyncedEvent) {
                return
            }

            const handler = (...args) => {
                const payload = args[0]
                const data = payload?.item !== undefined
                    ? payload
                    : (payload?.[0] ?? payload)
                const item = data?.item ?? data

                if (item && typeof item === 'object' && item.id != null) {
                    this.applyServerEditedItem(item)
                }
            }

            if (typeof window.Livewire?.on === 'function') {
                const stop = window.Livewire.on(this.editSyncedEvent, handler)
                this._editSyncedCleanup = typeof stop === 'function' ? stop : () => {}

                return
            }

            const domHandler = (event) => handler(event.detail)
            window.addEventListener(this.editSyncedEvent, domHandler)
            this._editSyncedCleanup = () => window.removeEventListener(this.editSyncedEvent, domHandler)
        },

        applyServerEditedItem(item) {
            const needle = String(item.id)

            this.state = this.items().map((row) => {
                if (String(row.id) === needle) {
                    return {
                        ...row,
                        ...item,
                        id: row.id,
                        children: Array.isArray(item.children) ? item.children : (row.children ?? []),
                    }
                }

                return {
                    ...row,
                    children: (row.children ?? []).map((child) => {
                        if (String(child.id) !== needle) {
                            return child
                        }

                        return {
                            ...child,
                            ...item,
                            id: child.id,
                        }
                    }),
                }
            })

            this.$nextTick(() => {
                this.scheduleSyncStrikeWidths()
                this.syncOverlayScrollbar()
            })
        },

        restoreUndoSnapshot(snapshot, changedIds = null) {
            const before = cloneTodoItems(this.items())
            const next = applyTodoUndoPatch(before, snapshot, changedIds)
            const idSet = Array.isArray(changedIds) && changedIds.length > 0
                ? new Set(changedIds.map((id) => String(id)))
                : null

            this.state = next
            this.lastUndoSnapshot = null
            this.rebuildDoneIds(next)
            this.settledIds = {}

            for (const item of this.items()) {
                if (item.done) {
                    this.settledIds[String(item.id)] = true
                }

                for (const child of item.children ?? []) {
                    if (child.done) {
                        this.settledIds[String(child.id)] = true
                    }
                }
            }

            // Same afterToggled / DB hook as checkbox toggle.
            const toggledIds = idSet
                ? [...idSet]
                : diffTodoDoneIds(before, next)

            if (toggledIds.length > 0) {
                this.notifyToggled(toggledIds, next)
            }

            this.$nextTick(() => {
                this.syncDoneStyles()
                this.syncStrikeWidths()

                if (! idSet) {
                    return
                }

                for (const id of idSet) {
                    const row = this.rowEl(id)

                    if (! row) {
                        continue
                    }

                    const wasDone = (() => {
                        for (const item of before) {
                            if (String(item.id) === id) {
                                return Boolean(item.done)
                            }

                            for (const child of item.children ?? []) {
                                if (String(child.id) === id) {
                                    return Boolean(child.done)
                                }
                            }
                        }

                        return false
                    })()

                    const isDoneNow = (() => {
                        for (const item of next) {
                            if (String(item.id) === id) {
                                return Boolean(item.done)
                            }

                            for (const child of item.children ?? []) {
                                if (String(child.id) === id) {
                                    return Boolean(child.done)
                                }
                            }
                        }

                        return false
                    })()

                    if (wasDone && ! isDoneNow) {
                        animateCheckOff(row, { reducedMotion: this.reducedMotion })
                    }
                }
            })
        },

        notifyCompletion(count, snapshot, changedIds = []) {
            if (! this.undoCompletionNotifications || count < 1) {
                return
            }

            if (typeof window.FilamentNotification !== 'function' || typeof window.FilamentNotificationAction !== 'function') {
                return
            }

            const frozenSnapshot = cloneTodoItems(snapshot)
            const frozenChangedIds = [...changedIds]

            this.lastUndoSnapshot = frozenSnapshot

            const title = count === 1
                ? (this.labels.taskCompleted || '1 task completed')
                : String(this.labels.tasksCompleted || ':count tasks completed').replace(':count', String(count))

            new window.FilamentNotification()
                .title(title)
                .duration(8000)
                .actions([
                    new window.FilamentNotificationAction('undo')
                        .label(this.labels.undo || 'Undo')
                        .color('danger')
                        .dispatch(this.undoEvent, {
                            snapshot: frozenSnapshot,
                            changedIds: frozenChangedIds,
                        })
                        .close(),
                ])
                .send()
        },

        async callSchemaMethod(method, params = {}) {
            if (! this.componentKey || ! this.$wire?.callSchemaComponentMethod) {
                return null
            }

            return await this.$wire.callSchemaComponentMethod(this.componentKey, method, params)
        },

        collectItemsByIds(list, ids) {
            const idSet = new Set((ids ?? []).map((id) => String(id)))
            const found = []

            for (const item of list ?? []) {
                if (idSet.has(String(item.id))) {
                    found.push(item)
                }

                for (const child of item.children ?? []) {
                    if (idSet.has(String(child.id))) {
                        found.push(child)
                    }
                }
            }

            return found
        },

        notifyToggled(changedIds, items) {
            if (! this.afterToggledEnabled || ! changedIds?.length) {
                return
            }

            void this.callSchemaMethod('todoItemsToggled', {
                changed: this.collectItemsByIds(items, changedIds),
                items,
            }).catch(() => {})
        },

        hasChildren(item) {
            return Array.isArray(item?.children) && item.children.length > 0
        },

        hasMeta(item) {
            return this.hasChildren(item) || Boolean(item?.date)
        },

        childProgressLabel(item) {
            return childProgressLabel(item)
        },

        countDoneChildren(item) {
            return countDoneChildren(item)
        },

        findItemById(id) {
            const needle = String(id)

            for (const item of this.items()) {
                if (String(item.id) === needle) {
                    return item
                }

                for (const child of item.children ?? []) {
                    if (String(child.id) === needle) {
                        return child
                    }
                }
            }

            return null
        },

        bindStrikeResizeObserver() {
            this._strikeResizeObserver?.disconnect?.()

            if (typeof window !== 'undefined' && this._onWindowResize) {
                window.removeEventListener('resize', this._onWindowResize)
                this._onWindowResize = null
            }

            if (typeof ResizeObserver === 'undefined' || ! this.$el) {
                return
            }

            this._strikeResizeObserver = new ResizeObserver(() => {
                // Wait for text wrap to settle — syncing in the same frame keeps stale px widths.
                this.scheduleSyncStrikeWidths()
            })
            this._strikeResizeObserver.observe(this.$el)

            const viewport = this.$refs?.viewport

            if (viewport && viewport !== this.$el) {
                this._strikeResizeObserver.observe(viewport)
            }

            if (typeof window !== 'undefined') {
                this._onWindowResize = () => this.scheduleSyncStrikeWidths()
                window.addEventListener('resize', this._onWindowResize, { passive: true })
            }
        },

        scheduleSyncStrikeWidths() {
            if (typeof requestAnimationFrame !== 'function') {
                this.syncStrikeWidths()

                return
            }

            cancelAnimationFrame(this._strikeSyncRaf)
            cancelAnimationFrame(this._strikeSyncRaf2)
            this._strikeSyncRaf = requestAnimationFrame(() => {
                this._strikeSyncRaf2 = requestAnimationFrame(() => {
                    this.syncStrikeWidths()
                    this.scheduleMeasureRowHeights()
                })
            })
        },

        syncStrikeWidths() {
            const root = this.$refs?.viewport || this.$el

            if (! root) {
                return
            }

            root.querySelectorAll('.fff-todo-list-field__title-wrap').forEach((wrap) => {
                const label = wrap.querySelector('.fff-todo-list-field__label')
                const host = wrap.querySelector('.fff-todo-list-field__strikes')
                const template = host?.querySelector('.fff-todo-list-field__strike')

                if (! label || ! host || ! template) {
                    return
                }

                const wrapRect = wrap.getBoundingClientRect()
                let lines = []

                try {
                    const range = document.createRange()
                    range.selectNodeContents(label)
                    lines = mergeTextLineRects(range.getClientRects())
                    range.detach?.()
                } catch {
                    lines = []
                }

                if (lines.length === 0) {
                    const fallback = label.getBoundingClientRect()

                    if (fallback.width >= 0.5 && fallback.height >= 0.5) {
                        lines = [{
                            top: fallback.top,
                            left: fallback.left,
                            width: fallback.width,
                            height: fallback.height,
                        }]
                    }
                }

                const needed = Math.max(lines.length, 1)
                let strikes = Array.from(host.querySelectorAll('.fff-todo-list-field__strike'))

                while (strikes.length < needed) {
                    const clone = template.cloneNode(true)
                    clone.removeAttribute('style')
                    clone.hidden = false
                    host.appendChild(clone)
                    strikes = Array.from(host.querySelectorAll('.fff-todo-list-field__strike'))
                }

                while (strikes.length > needed) {
                    strikes.pop()?.remove()
                }

                strikes = Array.from(host.querySelectorAll('.fff-todo-list-field__strike'))

                if (lines.length === 0) {
                    const strike = strikes[0]

                    if (strike) {
                        strike.style.top = '50%'
                        strike.style.left = '0'
                        strike.style.removeProperty('width')
                        strike.style.transform = 'translateY(-50%)'
                        strike.hidden = true
                    }

                    return
                }

                lines.forEach((line, index) => {
                    const strike = strikes[index]

                    if (! strike) {
                        return
                    }

                    const top = line.top - wrapRect.top + (line.height / 2)

                    strike.hidden = false
                    strike.style.top = `${top}px`
                    strike.style.transform = 'translateY(-50%)'

                    if (lines.length === 1) {
                        // Prefer CSS calc(100% + 4px) so hydrate matches SSR / final length.
                        strike.style.left = '0'
                        strike.style.removeProperty('width')

                        return
                    }

                    // Multi-line: each SVG must match that line's text width only —
                    // never stretch to the title-wrap box (shorter wrap lines would overshoot).
                    const left = Math.max(0, line.left - wrapRect.left)
                    const width = Math.max(Math.ceil(line.width) + 4, 8)

                    strike.style.left = `${left}px`
                    strike.style.width = `${width}px`
                })
            })
        },

        syncDoneStyles({ snapStrike = true } = {}) {
            for (const item of this.items()) {
                const syncRow = (id, done) => {
                    const row = this.rowEl(id)

                    if (! row) {
                        return
                    }

                    const input = row.querySelector('input.fff-todo-list-field__checkbox-input')

                    if (input) {
                        input.checked = Boolean(done)
                    }

                    // Toggle path only syncs the native input — strike/tick motion owns the CSS vars.
                    if (! snapStrike) {
                        return
                    }

                    if (done) {
                        row.style.setProperty('--text-line-scale', '1')
                        row.style.setProperty('--text-x', '0px')

                        return
                    }

                    row.style.setProperty('--text-line-scale', '0')
                    row.style.setProperty('--text-x', '0px')
                    row.style.removeProperty('--checkbox-lines-offset')
                }

                syncRow(item.id, item.done)

                for (const child of item.children ?? []) {
                    syncRow(child.id, child.done)
                }
            }

            this.syncStrikeWidths()
        },

        measureViewport() {
            const el = this.$refs?.viewport

            if (! el) {
                return
            }

            this.viewportHeight = el.clientHeight || 320
            this.syncOverlayScrollbar()
        },

        bindOverlayScrollbar() {
            const scroller = this.$refs?.viewport
            const track = this.$refs?.scrollbar
            const thumb = this.$refs?.scrollbarThumb

            if (! scroller || ! track || ! thumb) {
                return
            }

            bindOverlayScrollbar(scroller, track, thumb)
            this.syncOverlayScrollbar()

            this._viewportResizeObserver?.disconnect?.()

            if (typeof ResizeObserver === 'undefined') {
                return
            }

            this._viewportResizeObserver = new ResizeObserver(() => {
                this.measureViewport()
                this.scheduleSyncStrikeWidths()
            })
            this._viewportResizeObserver.observe(scroller)
        },

        syncOverlayScrollbar() {
            const scroller = this.$refs?.viewport
            const thumb = this.$refs?.scrollbarThumb

            if (! scroller || ! thumb) {
                return
            }

            syncOverlayScrollbar(scroller, thumb)
        },

        bindInfiniteObserver() {
            this._observer?.disconnect?.()

            if (! this.infiniteScroll || typeof IntersectionObserver === 'undefined') {
                return
            }

            this._observer = new IntersectionObserver((entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    this.loadMore()
                }
            }, { root: this.$refs.viewport, rootMargin: '120px' })

            this.$watch('hasMore', () => {
                this.$nextTick(() => this.observeSentinel())
            })

            this.$nextTick(() => this.observeSentinel())
        },

        observeSentinel() {
            const sentinel = this.$refs?.sentinel

            if (! sentinel || ! this._observer) {
                return
            }

            this._observer.disconnect()
            this._observer.observe(sentinel)
        },

        items() {
            return Array.isArray(this.state) ? this.state : []
        },

        filteredAll() {
            const ordered = this.reorderable
                ? this.items()
                : sortCompletedLast(this.items(), this.persistCompletedOrder)

            return filterItems(ordered, this.search)
        },

        renderedItems() {
            const list = this.virtualSourceList()

            if (this.virtualizing) {
                const windowed = virtualWindow(
                    list,
                    this.scrollTop,
                    this.viewportHeight,
                    (item) => this.estimatedRowHeight(item),
                )

                return {
                    items: windowed.items,
                    offsetY: windowed.offsetY,
                    totalHeight: windowed.totalHeight,
                    total: list.length,
                }
            }

            return {
                items: list,
                offsetY: 0,
                totalHeight: null,
                total: list.length,
            }
        },

        listStyle() {
            const rendered = this.renderedItems()

            if (! rendered.totalHeight) {
                return null
            }

            let windowHeight = 0

            for (const item of rendered.items) {
                windowHeight += this.estimatedRowHeight(item)
            }

            return {
                paddingTop: `${rendered.offsetY}px`,
                paddingBottom: `${Math.max(0, rendered.totalHeight - rendered.offsetY - windowHeight)}px`,
            }
        },

        onScroll(event) {
            this.scrollTop = event.target.scrollTop
            this.viewportHeight = event.target.clientHeight
            this.syncOverlayScrollbar()
            this.scheduleMeasureRowHeights()

            if (! this.infiniteScroll) {
                return
            }

            const el = event.target
            const nearBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 80

            if (nearBottom) {
                this.loadMore()
            }
        },

        async loadMore() {
            if (! this.infiniteScroll || this.loadingMore || ! this.hasMore) {
                return
            }

            if (this.remoteLoader) {
                this.loadingMore = true

                try {
                    const nextPage = this.remotePage + 1
                    let payload

                    if (typeof this.remoteLoader === 'string' && this.$wire?.[this.remoteLoader]) {
                        payload = await this.$wire[this.remoteLoader](nextPage, this.search)
                    } else if (typeof window[this.remoteLoader] === 'function') {
                        payload = await window[this.remoteLoader](nextPage, this.search)
                    }

                    const rows = Array.isArray(payload) ? payload : (payload?.items ?? [])
                    const more = Array.isArray(payload) ? rows.length >= (this.pageSize || rows.length) : Boolean(payload?.hasMore)

                    if (rows.length) {
                        this.state = [...this.items(), ...rows]
                        this.remotePage = nextPage
                    }

                    this.hasMore = more && rows.length > 0
                } finally {
                    this.loadingMore = false
                    this.$nextTick(() => this.observeSentinel())
                }

                return
            }

            if (! this.pageSize) {
                this.hasMore = false

                return
            }

            this.loadingMore = true
            this.visibleCount += this.pageSize
            this.hasMore = this.filteredAll().length > this.visibleCount
            this.loadingMore = false
            this.$nextTick(() => this.observeSentinel())
        },

        isDone(item) {
            return Boolean(item?.done)
        },

        isSettled(item) {
            return Boolean(item?.done) && Boolean(this.settledIds[item.id])
        },

        play(url, options = {}) {
            if (! this.sounds || ! url) {
                return null
            }

            return audio.play(url, options)
        },

        allCompletableDone(list = this.items()) {
            return allTodosComplete(list)
        },

        stopCelebration() {
            this.celebrationHandle?.stop?.()
            this.celebrationHandle = null
            this.resetCelebrationCanvas()
        },

        resetCelebrationCanvas() {
            const canvas = this.$refs?.celebration

            if (! canvas) {
                return
            }

            canvas.classList.remove(
                'fff-todo-list-field__celebration--fullscreen',
                'is-anchored',
            )
            canvas.style.inset = ''
            canvas.style.top = ''
            canvas.style.left = ''
            canvas.style.right = ''
            canvas.style.bottom = ''
            canvas.style.width = ''
            canvas.style.height = ''
            canvas.style.borderRadius = ''
        },

        layoutCelebrationCanvas({ fullscreen = false, anchorId = null } = {}) {
            const canvas = this.$refs?.celebration

            if (! canvas) {
                return
            }

            this.resetCelebrationCanvas()

            if (fullscreen) {
                return
            }

            if (! anchorId) {
                return
            }

            const row = this.rowEl(anchorId)
            const shell = canvas.parentElement

            if (! row || ! shell) {
                return
            }

            const rowRect = row.getBoundingClientRect()
            const shellRect = shell.getBoundingClientRect()

            // Strict row bounds — never spill outside the option chrome.
            canvas.classList.add('is-anchored')
            canvas.style.inset = 'auto'
            canvas.style.top = `${Math.max(0, rowRect.top - shellRect.top)}px`
            canvas.style.left = `${Math.max(0, rowRect.left - shellRect.left)}px`
            canvas.style.width = `${Math.max(1, rowRect.width)}px`
            canvas.style.height = `${Math.max(1, rowRect.height)}px`
        },

        triggerCelebration(key, options = {}) {
            if (! key || this.reducedMotion) {
                return
            }

            const canvas = this.$refs?.celebration

            if (! canvas) {
                return
            }

            this.stopCelebration()

            const fullscreen = Boolean(options.fullscreen ?? this.celebrationFullscreen)
            const anchorId = fullscreen ? null : (options.anchorId ?? null)

            this.layoutCelebrationCanvas({ fullscreen, anchorId })

            const pack = this.celebrationAudio?.[key] && typeof this.celebrationAudio[key] === 'object'
                ? this.celebrationAudio[key]
                : null

            this.celebrationHandle = runTodoListCelebration(key, {
                canvas,
                durationMs: this.celebrationDurationMs,
                playSound: (url, playOptions) => this.play(url, playOptions),
                startSound: pack
                    ? (pack.start ?? null)
                    : (key === 'fireworks' ? this.celebrationStartSound : null),
                burstSound: pack
                    ? (pack.burst ?? null)
                    : (key === 'fireworks' ? this.celebrationSound : null),
                reducedMotion: this.reducedMotion,
                fullscreen,
                onStop: () => this.resetCelebrationCanvas(),
            })
        },

        rowEl(id) {
            const root = this.$root || this.$el

            return root?.querySelector?.(`.fff-todo-list-field__row[data-todo-id="${CSS.escape(String(id))}"]`)
        },

        toggle(id, event) {
            event?.preventDefault?.()

            if (this.disabled) {
                return
            }

            const before = cloneTodoItems(this.items())
            const { items: next, completedCount, changedIds } = toggleTodoTree(before, id)

            if (changedIds.length === 0) {
                return
            }

            const markingDone = completedCount > 0
            this.state = cloneTodoItems(next)
            // Flat map updates immediately — nested x-for scopes often keep stale child.done.
            this.rebuildDoneIds(next)

            for (const changedId of changedIds) {
                const key = String(changedId)
                clearTimeout(this.settleTimers[key])
                const nextTimers = { ...this.settleTimers }
                delete nextTimers[key]
                this.settleTimers = nextTimers

                const nextSettled = { ...this.settledIds }
                delete nextSettled[key]
                this.settledIds = nextSettled
            }

            // Keep existing row DOM (stable x-for keys) so check / strike motion can run.
            this.$nextTick(() => {
                this.syncStrikeWidths()
                // Sync inputs only — do not snap strike CSS vars (that would kill animation).
                this.syncDoneStyles({ snapStrike: false })
                if (markingDone) {
                    this.play(this.checkSound)
                    this.notifyCompletion(completedCount, before, changedIds)
                    this.notifyToggled(changedIds, next)

                    for (const changedId of changedIds) {
                        const key = String(changedId)

                        animateCheckOn(this.rowEl(key), {
                            reducedMotion: this.reducedMotion,
                            onStrikeComplete: () => {
                                this.syncStrikeWidths()

                                if (this.reducedMotion || this.doneSettleMs <= 0) {
                                    this.settledIds = { ...this.settledIds, [key]: true }

                                    return
                                }

                                this.settleTimers = {
                                    ...this.settleTimers,
                                    [key]: setTimeout(() => {
                                        this.settledIds = { ...this.settledIds, [key]: true }
                                        const timers = { ...this.settleTimers }
                                        delete timers[key]
                                        this.settleTimers = timers
                                    }, this.doneSettleMs),
                                }
                            },
                        })
                    }

                    const toggled = this.findItemById(id)
                    const celebrationKey = toggled?.celebration === false
                        ? null
                        : (toggled?.celebration || null)

                    if (celebrationKey) {
                        this.triggerCelebration(celebrationKey, {
                            fullscreen: Boolean(toggled?.celebrationFullscreen),
                            anchorId: String(id),
                        })
                    } else if (this.celebration && this.allCompletableDone(next)) {
                        this.triggerCelebration(this.celebration)
                    }

                    return
                }

                this.stopCelebration()
                this.notifyToggled(changedIds, next)

                for (const changedId of changedIds) {
                    animateCheckOff(this.rowEl(String(changedId)), {
                        reducedMotion: this.reducedMotion,
                    })
                }
            })
        },

        canAddMore() {
            if (! this.allowCreate || this.disabled) {
                return false
            }

            if (this.maxItems === null || this.maxItems === undefined) {
                return true
            }

            return this.items().length < this.maxItems
        },

        startCreate() {
            if (! this.canAddMore() || this.createBusy) {
                return
            }

            clearTimeout(this._createBlurTimer)
            this._createBlurTimer = 0
            this.creating = true
            this.createDraft = ''
            this.createDescriptionDraft = ''
            this.$nextTick(() => this.$refs?.createInput?.focus())
        },

        cancelCreate() {
            clearTimeout(this._createBlurTimer)
            this._createBlurTimer = 0
            this.createBusy = false
            this.creating = false
            this.createDraft = ''
            this.createDescriptionDraft = ''
        },

        onCreateDockFocusOut(event) {
            const next = event?.relatedTarget

            if (next && event?.currentTarget?.contains?.(next)) {
                return
            }

            // relatedTarget is often null on input→input — defer and inspect activeElement.
            clearTimeout(this._createBlurTimer)
            this._createBlurTimer = window.setTimeout(() => {
                this._createBlurTimer = 0

                if (! this.creating || this.createBusy) {
                    return
                }

                const active = typeof document !== 'undefined' ? document.activeElement : null
                const dock = this.$refs?.createDock

                if (active && dock?.contains?.(active)) {
                    return
                }

                if (this.createDraft.trim()) {
                    void this.confirmCreate()
                } else {
                    this.cancelCreate()
                }

                this._ignoreCreateRowClick = true
                setTimeout(() => {
                    this._ignoreCreateRowClick = false
                }, 0)
            }, 30)
        },

        onCreateLabelEnter() {
            if (this.createBusy) {
                return
            }

            if (this.createWithDescription) {
                clearTimeout(this._createBlurTimer)
                this._createBlurTimer = 0
                this.$nextTick(() => this.$refs?.createDescriptionInput?.focus())

                return
            }

            void this.confirmCreate()
        },

        onCreateRowClick(event) {
            if (this._ignoreCreateRowClick) {
                this._ignoreCreateRowClick = false

                return
            }

            if (this.creating || this.disabled) {
                return
            }

            if (event?.target?.closest?.('.fff-todo-list-field__create-input, .fff-todo-list-field__create-description')) {
                return
            }

            this.startCreate()
        },

        onCreateIconMouseDown(event) {
            if (this.creating) {
                // Keep focus long enough for click to cancel (avoids blur→reopen race).
                event?.preventDefault?.()
            }
        },

        onCreateIconClick(event) {
            event?.stopPropagation?.()

            if (this.disabled || ! this.canAddMore()) {
                if (this.creating) {
                    this.cancelCreate()
                }

                return
            }

            if (this.creating) {
                this.cancelCreate()

                return
            }

            this.startCreate()
        },

        async confirmCreate() {
            if (this.createBusy) {
                return
            }

            const label = String(this.createDraft ?? '').trim()
            const description = this.createWithDescription
                ? (String(this.createDescriptionDraft ?? '').trim() || null)
                : null

            if (! label || ! this.canAddMore()) {
                this.cancelCreate()

                return
            }

            clearTimeout(this._createBlurTimer)
            this._createBlurTimer = 0
            this.createBusy = true

            let item = {
                id: uuid(),
                label,
                description,
                done: false,
                disabled: false,
                created: true,
                sound: null,
                celebration: null,
                deletable: this.allowDelete && this.deletableMode !== 'none',
                children: [],
                date: null,
            }

            try {
                if (this.createUsingEnabled) {
                    const result = await this.callSchemaMethod('createTodoItem', { item })

                    if (! result || typeof result !== 'object' || ! result.id || ! result.label) {
                        this.cancelCreate()

                        return
                    }

                    item = {
                        ...item,
                        ...result,
                        created: true,
                        description: result.description !== undefined ? result.description : item.description,
                        children: Array.isArray(result.children) ? result.children : [],
                        date: result.date ?? null,
                    }
                }

                const id = String(item.id)
                this.enteringIds = { ...this.enteringIds, [id]: true }
                this.state = [...this.items(), item]
                this.creating = false
                this.createDraft = ''
                this.createDescriptionDraft = ''
                this.play(this.createSound || this.accentSound)
                this.scrollToTodo(id)

                clearTimeout(this._enterExitTimers[id])
                this._enterExitTimers = {
                    ...this._enterExitTimers,
                    [id]: setTimeout(() => {
                        const nextEntering = { ...this.enteringIds }
                        delete nextEntering[id]
                        this.enteringIds = nextEntering
                        const timers = { ...this._enterExitTimers }
                        delete timers[id]
                        this._enterExitTimers = timers
                    }, this.reducedMotion ? 0 : 380),
                }
            } catch {
                this.cancelCreate()
            } finally {
                this.createBusy = false
            }
        },

        scrollToTodo(id) {
            const viewport = this.$refs?.viewport

            if (! viewport || id == null) {
                return
            }

            // Infinite (local): expand the loaded window so the new row exists in the list.
            if (this.infiniteScroll && this.pageSize && ! this.remoteLoader) {
                const total = this.filteredAll().length
                this.visibleCount = Math.max(this.visibleCount, total)
                this.hasMore = total > this.visibleCount
            }

            const pinToEnd = () => {
                const list = this.filteredAll()
                const index = list.findIndex((row) => String(row.id) === String(id))
                const viewH = viewport.clientHeight || this.viewportHeight
                const getHeight = (item) => this.estimatedRowHeight(item)

                if (this.virtualizing && index >= 0) {
                    const target = scrollTopToRevealIndex(index, getHeight, viewH, list)
                    this.scrollTop = target
                    viewport.scrollTop = target
                    this.scheduleMeasureRowHeights()
                } else {
                    viewport.scrollTop = viewport.scrollHeight
                }

                const row = this.rowEl(id)

                if (row && typeof row.scrollIntoView === 'function') {
                    row.scrollIntoView({
                        block: 'end',
                        inline: 'nearest',
                        behavior: 'instant',
                    })
                } else if (! this.virtualizing) {
                    viewport.scrollTop = viewport.scrollHeight
                }

                this.scrollTop = viewport.scrollTop
                this.viewportHeight = viewH
                this.syncOverlayScrollbar()
            }

            this.$nextTick(() => {
                pinToEnd()
                requestAnimationFrame(() => {
                    pinToEnd()
                    requestAnimationFrame(() => pinToEnd())
                })
            })
        },

        canDeleteItem(item) {
            if (! this.allowDelete || this.disabled || item?.disabled) {
                return false
            }

            if (item?.deletable === true) {
                return true
            }

            if (this.deletableMode === 'all') {
                return true
            }

            if (this.deletableMode === 'created') {
                return Boolean(item?.created)
            }

            return false
        },

        canEditItem(item) {
            if (! this.allowEdit || this.disabled || item?.disabled) {
                return false
            }

            if (item?.editable === true) {
                return true
            }

            if (item?.editable === false) {
                return false
            }

            if (this.editableMode === 'all') {
                return true
            }

            if (this.editableMode === 'created') {
                return Boolean(item?.created)
            }

            return false
        },

        actionSlotCount() {
            return (this.allowEdit ? 1 : 0) + (this.allowDelete ? 1 : 0)
        },

        actionGutterStyle() {
            const slots = this.actionSlotCount()

            if (slots < 1) {
                return null
            }

            const slot = 1.75
            const gap = 0.125
            const width = (slots * slot) + (Math.max(0, slots - 1) * gap)

            return {
                '--fff-todo-list-actions-width': `${width}rem`,
            }
        },

        editItem(id, event) {
            event?.stopPropagation?.()
            event?.preventDefault?.()

            if (this.disabled || id == null) {
                return
            }

            const item = this.findItemById(id)

            if (! item || ! this.canEditItem(item)) {
                return
            }

            if (! this.componentKey || typeof this.$wire?.mountAction !== 'function') {
                return
            }

            this.$wire.mountAction(
                'editTodoItem',
                { id: String(id), item },
                { schemaComponent: this.componentKey },
            )
        },

        canReorderItem(item) {
            return this.reorderable && ! this.disabled && ! item?.disabled && ! String(this.search || '').trim()
        },
        async reorderItems(event) {
            if (! this.reorderable || this.disabled || String(this.search || '').trim()) {
                return
            }

            const oldIndex = event?.oldDraggableIndex ?? event?.oldIndex
            const newIndex = event?.newDraggableIndex ?? event?.newIndex

            if (oldIndex === undefined || newIndex === undefined || oldIndex === newIndex) {
                return
            }

            const previous = [...this.items()]
            const list = [...previous]
            const [moved] = list.splice(oldIndex, 1)

            if (! moved || moved.disabled) {
                return
            }

            list.splice(newIndex, 0, moved)
            this.state = list

            if (this.reorderUsingEnabled) {
                try {
                    const ok = await this.callSchemaMethod('reorderTodoItems', { items: list })

                    if (ok === false) {
                        this.state = previous
                        this.$nextTick(() => this.syncStrikeWidths())

                        return
                    }
                } catch {
                    this.state = previous
                    this.$nextTick(() => this.syncStrikeWidths())

                    return
                }
            }

            this.$nextTick(() => this.syncStrikeWidths())
        },

        async removeItem(id, event) {
            event?.stopPropagation?.()
            event?.preventDefault?.()

            if (this.disabled) {
                return
            }

            const needle = String(id)
            const item = this.findItemById(id)

            if (! item || ! this.canDeleteItem(item)) {
                return
            }

            // Prefer the parent row from state so `children` is present for cascade delete.
            const cascadeItem = this.items().find((row) => String(row.id) === needle) ?? item
            const childIds = (cascadeItem.children ?? []).map((child) => String(child.id))
            const exitingKeys = [needle, ...childIds]

            if (this.deleteUsingEnabled) {
                try {
                    const ok = await this.callSchemaMethod('deleteTodoItem', {
                        id: needle,
                        item: cascadeItem,
                    })

                    if (ok === false) {
                        return
                    }
                } catch {
                    return
                }
            }

            const finish = () => {
                this.state = this.items().map((row) => {
                    if (String(row.id) === needle) {
                        return null
                    }

                    return {
                        ...row,
                        children: (row.children ?? []).filter((child) => String(child.id) !== needle),
                    }
                }).filter(Boolean)

                const nextExiting = { ...this.exitingIds }
                const timers = { ...this._enterExitTimers }

                for (const key of exitingKeys) {
                    delete nextExiting[key]
                    clearTimeout(timers[key])
                    delete timers[key]
                }

                this.exitingIds = nextExiting
                this._enterExitTimers = timers
                this.play(this.accentSound)
            }

            if (this.reducedMotion) {
                finish()

                return
            }

            const nextExiting = { ...this.exitingIds }

            for (const key of exitingKeys) {
                nextExiting[key] = true
                clearTimeout(this._enterExitTimers[key])
            }

            this.exitingIds = nextExiting
            this._enterExitTimers = {
                ...this._enterExitTimers,
                [needle]: setTimeout(finish, 320),
            }
        },

        itemClasses(item) {
            const id = String(item.id)

            return {
                'is-done': this.isTodoDone(id),
                'is-settled': this.isTodoDone(id) && this.isSettledId(id),
                'is-disabled': this.disabled || item.disabled,
                'is-reorderable': this.canReorderItem(item),
                'is-entering': Boolean(this.enteringIds[id]),
                'is-exiting': Boolean(this.exitingIds[id]),
            }
        },
    }
}

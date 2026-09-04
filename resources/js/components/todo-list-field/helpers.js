export function createAudioPool() {
    const cache = new Map()

    const fadeVolume = (node, durationMs = 350) => {
        if (! node) {
            return
        }

        const from = Number(node.volume) || 0
        const startedAt = performance.now()
        const duration = Math.max(40, durationMs)

        const step = (now) => {
            const t = Math.min(1, (now - startedAt) / duration)
            node.volume = Math.max(0, from * (1 - t))

            if (t < 1) {
                requestAnimationFrame(step)

                return
            }

            try {
                node.pause()
                node.currentTime = 0
            } catch {
                // Ignore.
            }
        }

        requestAnimationFrame(step)
    }

    return {
        /**
         * @param {string|null|undefined} url
         * @param {{ fadeOutAfterMs?: number, fadeMs?: number }} [options]
         * @returns {{ stop: Function, fadeOut: Function }|null}
         */
        play(url, options = {}) {
            if (! url || typeof Audio === 'undefined') {
                return null
            }

            try {
                let base = cache.get(url)

                if (! base) {
                    base = new Audio(url)
                    base.preload = 'auto'
                    cache.set(url, base)
                }

                const node = base.cloneNode(true)
                node.volume = 0.85
                void node.play().catch(() => {})

                let fadeTimer = 0

                if (options.fadeOutAfterMs != null) {
                    fadeTimer = window.setTimeout(() => {
                        fadeVolume(node, options.fadeMs ?? 400)
                        fadeTimer = 0
                    }, Math.max(0, options.fadeOutAfterMs))
                }

                return {
                    stop() {
                        if (fadeTimer) {
                            clearTimeout(fadeTimer)
                            fadeTimer = 0
                        }

                        try {
                            node.pause()
                            node.currentTime = 0
                        } catch {
                            // Ignore.
                        }
                    },
                    fadeOut(ms = 400) {
                        if (fadeTimer) {
                            clearTimeout(fadeTimer)
                            fadeTimer = 0
                        }

                        fadeVolume(node, ms)
                    },
                }
            } catch {
                return null
            }
        },
    }
}

export function prefersReducedMotion() {
    return typeof window !== 'undefined'
        && window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches
}

export function filterItems(items, query) {
    const needle = String(query ?? '').trim().toLowerCase()

    if (! needle) {
        return items
    }

    return items.filter((item) => {
        const label = String(item.label ?? '').toLowerCase()
        const description = String(item.description ?? '').toLowerCase()

        if (label.includes(needle) || description.includes(needle)) {
            return true
        }

        return (item.children ?? []).some((child) => {
            const childLabel = String(child.label ?? '').toLowerCase()
            const childDescription = String(child.description ?? '').toLowerCase()

            return childLabel.includes(needle) || childDescription.includes(needle)
        })
    })
}

export function sortCompletedLast(items, enabled) {
    if (! enabled) {
        return items
    }

    return [...items].sort((a, b) => Number(a.done) - Number(b.done))
}

export function paginateItems(items, page, pageSize) {
    if (! pageSize) {
        return { items, page: 1, pages: 1, total: items.length }
    }

    const pages = Math.max(1, Math.ceil(items.length / pageSize))
    const safePage = Math.min(Math.max(1, page), pages)
    const start = (safePage - 1) * pageSize

    return {
        items: items.slice(start, start + pageSize),
        page: safePage,
        pages,
        total: items.length,
    }
}

export function virtualWindow(items, scrollTop, viewportHeight, itemHeight = 52, overscan = 4) {
    const list = items ?? []
    const getHeight = typeof itemHeight === 'function'
        ? itemHeight
        : () => Math.max(1, Number(itemHeight) || 52)

    if (list.length === 0) {
        return {
            start: 0,
            end: 0,
            offsetY: 0,
            totalHeight: 0,
            items: [],
            offsets: [0],
        }
    }

    const offsets = buildVirtualOffsets(list, getHeight)
    const totalHeight = offsets[list.length]
    const top = Math.max(0, Number(scrollTop) || 0)
    const view = Math.max(1, Number(viewportHeight) || 1)
    const pad = Math.max(0, Number(overscan) || 0)

    let start = findVirtualIndexAtOffset(offsets, top)
    start = Math.max(0, start - pad)

    let end = findVirtualIndexAtOffset(offsets, top + view) + 1
    end = Math.min(list.length, Math.max(start + 1, end + pad))

    return {
        start,
        end,
        offsetY: offsets[start],
        totalHeight,
        items: list.slice(start, end),
        offsets,
    }
}

/**
 * Prefix sums for variable-height virtual lists.
 * offsets[i] = pixel offset of item i; offsets[n] = total height.
 *
 * @param {Array<Record<string, mixed>>} items
 * @param {(item: Record<string, mixed>, index: number) => number} getHeight
 * @returns {number[]}
 */
export function buildVirtualOffsets(items, getHeight) {
    const list = items ?? []
    const offsets = new Array(list.length + 1)
    offsets[0] = 0

    for (let i = 0; i < list.length; i++) {
        offsets[i + 1] = offsets[i] + Math.max(1, Number(getHeight(list[i], i)) || 1)
    }

    return offsets
}

/**
 * Largest index i where offsets[i] <= offset (clamped to last item).
 *
 * @param {number[]} offsets
 * @param {number} offset
 */
export function findVirtualIndexAtOffset(offsets, offset) {
    if (! offsets || offsets.length < 2) {
        return 0
    }

    const maxIndex = offsets.length - 2
    const target = Math.max(0, Number(offset) || 0)
    let lo = 0
    let hi = maxIndex

    while (lo < hi) {
        const mid = (lo + hi + 1) >> 1

        if (offsets[mid] <= target) {
            lo = mid
        } else {
            hi = mid - 1
        }
    }

    return lo
}

/**
 * Scroll offset so item at `index` sits fully inside the viewport (prefer bottom alignment).
 *
 * Fixed: (index, itemHeight, viewportHeight, totalCount)
 * Variable: (index, getHeight, viewportHeight, items[])
 */
export function scrollTopToRevealIndex(index, itemHeightOrGetHeight, viewportHeight, totalCountOrItems) {
    const view = Math.max(1, Number(viewportHeight) || 1)
    const i = Math.max(0, Number(index) || 0)

    if (typeof itemHeightOrGetHeight === 'function' && Array.isArray(totalCountOrItems)) {
        const items = totalCountOrItems
        const offsets = buildVirtualOffsets(items, itemHeightOrGetHeight)
        const safeIndex = Math.min(i, Math.max(0, items.length - 1))
        const itemBottom = offsets[safeIndex + 1] ?? 0
        const maxScroll = Math.max(0, (offsets[items.length] ?? 0) - view)

        return Math.min(maxScroll, Math.max(0, itemBottom - view))
    }

    const height = Math.max(1, Number(itemHeightOrGetHeight) || 1)
    const total = Math.max(0, Number(totalCountOrItems) || 0)
    const maxScroll = Math.max(0, total * height - view)
    const itemBottom = (i + 1) * height

    return Math.min(maxScroll, Math.max(0, itemBottom - view))
}

export function handStrikePath(width = 100) {
    const s = width / 100

    return `M${0 * s} ${4.2 * s} C${6 * s} ${2.2 * s} ${12 * s} ${6.0 * s} ${18 * s} ${3.0 * s} C${24 * s} ${6.1 * s} ${30 * s} ${2.4 * s} ${36 * s} ${4.6 * s} C${42 * s} ${6.3 * s} ${48 * s} ${2.7 * s} ${54 * s} ${4.4 * s} C${60 * s} ${5.9 * s} ${66 * s} ${2.8 * s} ${72 * s} ${4.5 * s} C${78 * s} ${5.8 * s} ${86 * s} ${3.0 * s} ${93 * s} ${4.2 * s} L${100 * s} ${4.05 * s}`
}

export function handScribblePaths(width = 100) {
    return [handStrikePath(width)]
}

/**
 * Merge Range/getClientRects fragments that sit on the same visual line.
 *
 * @param {ArrayLike<{ top: number, left: number, right: number, bottom: number, width: number, height: number }>} rects
 * @returns {Array<{ top: number, left: number, width: number, height: number }>}
 */
export function mergeTextLineRects(rects, tolerance = 2) {
    const lines = []

    for (const rect of Array.from(rects ?? [])) {
        if (! rect || rect.width < 0.5 || rect.height < 0.5) {
            continue
        }

        const last = lines[lines.length - 1]

        if (last && Math.abs(last.top - rect.top) <= tolerance) {
            const right = Math.max(last.left + last.width, rect.right)
            last.left = Math.min(last.left, rect.left)
            last.width = right - last.left
            last.height = Math.max(last.height, rect.height)
            continue
        }

        lines.push({
            top: rect.top,
            left: rect.left,
            width: rect.width,
            height: rect.height,
        })
    }

    return lines
}

export function cloneTodoItems(items) {
    return (items ?? []).map((item) => ({
        ...item,
        children: Array.isArray(item.children)
            ? item.children.map((child) => ({ ...child }))
            : [],
    }))
}

export function countDoneChildren(item) {
    const children = item?.children ?? []

    return children.filter((child) => child.done).length
}

export function childProgressLabel(item) {
    const total = (item?.children ?? []).length

    if (total === 0) {
        return ''
    }

    return `${countDoneChildren(item)}/${total}`
}

/**
 * Apply check/uncheck to an item id (parent or child) with parent↔children sync.
 * Returns { items, completedCount, changedIds }.
 */
export function toggleTodoTree(items, id, forceDone = null) {
    const next = cloneTodoItems(items)
    const needle = String(id)
    let target = null
    let parent = null

    for (const item of next) {
        if (String(item.id) === needle) {
            target = item
            break
        }

        for (const child of item.children ?? []) {
            if (String(child.id) === needle) {
                target = child
                parent = item
                break
            }
        }

        if (target) {
            break
        }
    }

    if (! target || target.disabled) {
        return { items: next, completedCount: 0, changedIds: [] }
    }

    const wasDone = Boolean(target.done)
    const willDone = forceDone === null ? ! wasDone : Boolean(forceDone)
    const changedIds = []

    if (wasDone === willDone) {
        return { items: next, completedCount: 0, changedIds }
    }

    if (! parent) {
        // Parent toggle → cascade to children
        target.done = willDone
        changedIds.push(String(target.id))

        for (const child of target.children ?? []) {
            if (child.disabled) {
                continue
            }

            if (Boolean(child.done) !== willDone) {
                child.done = willDone
                changedIds.push(String(child.id))
            }
        }
    } else {
        target.done = willDone
        changedIds.push(String(target.id))

        const kids = parent.children ?? []
        const actionable = kids.filter((child) => ! child.disabled)
        const allDone = actionable.length > 0 && actionable.every((child) => child.done)

        if (! parent.disabled && Boolean(parent.done) !== allDone) {
            parent.done = allDone
            changedIds.push(String(parent.id))
        }
    }

    // Count newly completed among changedIds when marking done
    let newlyCompleted = 0

    if (willDone) {
        newlyCompleted = changedIds.length
    }

    return { items: next, completedCount: newlyCompleted, changedIds }
}

export function countCompletableTodos(items) {
    let total = 0

    for (const item of items ?? []) {
        if (! item.disabled) {
            total += 1
        }

        for (const child of item.children ?? []) {
            if (! child.disabled) {
                total += 1
            }
        }
    }

    return total
}

export function allTodosComplete(items) {
    for (const item of items ?? []) {
        if (! item.disabled && ! item.done) {
            return false
        }

        for (const child of item.children ?? []) {
            if (! child.disabled && ! child.done) {
                return false
            }
        }
    }

    return (items ?? []).length > 0
}

/**
 * Map of todo id → done from a tree snapshot.
 *
 * @param {Array<Record<string, mixed>>} items
 * @returns {Map<string, boolean>}
 */
export function indexTodoDoneById(items) {
    const map = new Map()

    for (const item of items ?? []) {
        map.set(String(item.id), Boolean(item.done))

        for (const child of item.children ?? []) {
            map.set(String(child.id), Boolean(child.done))
        }
    }

    return map
}

/**
 * Ids whose `done` flag differs between two item trees (parents + children).
 *
 * @param {Array<Record<string, mixed>>} before
 * @param {Array<Record<string, mixed>>} after
 * @returns {string[]}
 */
export function diffTodoDoneIds(before, after) {
    const beforeDone = indexTodoDoneById(before)
    const afterDone = indexTodoDoneById(after)
    const ids = new Set([...beforeDone.keys(), ...afterDone.keys()])
    const changed = []

    for (const id of ids) {
        if (Boolean(beforeDone.get(id)) !== Boolean(afterDone.get(id))) {
            changed.push(id)
        }
    }

    return changed
}

/**
 * Restore only the todos listed in changedIds from snapshot into currentItems.
 * Later edits outside that set are preserved.
 *
 * @param {Array<Record<string, mixed>>} currentItems
 * @param {Array<Record<string, mixed>>} snapshot
 * @param {Array<string|number>|null|undefined} changedIds
 * @returns {Array<Record<string, mixed>>}
 */
export function applyTodoUndoPatch(currentItems, snapshot, changedIds) {
    if (! Array.isArray(changedIds) || changedIds.length === 0) {
        return cloneTodoItems(snapshot)
    }

    const doneById = indexTodoDoneById(snapshot)
    const idSet = new Set(changedIds.map((id) => String(id)))
    const next = cloneTodoItems(currentItems)

    for (const item of next) {
        const itemId = String(item.id)

        if (idSet.has(itemId) && doneById.has(itemId)) {
            item.done = doneById.get(itemId)
        }

        for (const child of item.children ?? []) {
            const childId = String(child.id)

            if (idSet.has(childId) && doneById.has(childId)) {
                child.done = doneById.get(childId)
            }
        }
    }

    return next
}

import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    allTodosComplete,
    applyTodoUndoPatch,
    buildVirtualOffsets,
    childProgressLabel,
    diffTodoDoneIds,
    filterItems,
    findVirtualIndexAtOffset,
    handStrikePath,
    mergeTextLineRects,
    paginateItems,
    sortCompletedLast,
    scrollTopToRevealIndex,
    toggleTodoTree,
    virtualWindow,
} from '../../resources/js/components/todo-list-field/helpers.js'
import {
    getTodoListCelebration,
    listTodoListCelebrations,
    registerTodoListCelebration,
    runTodoListCelebration,
} from '../../resources/js/components/todo-list-field/celebrations.js'

describe('todo list helpers', () => {
    it('filters by label and description', () => {
        const items = [
            { id: '1', label: 'Water', description: 'Drink' },
            { id: '2', label: 'Walk', description: 'Park' },
        ]

        assert.equal(filterItems(items, 'wat').length, 1)
        assert.equal(filterItems(items, 'park').length, 1)
        assert.equal(filterItems(items, '').length, 2)
    })

    it('paginates and virtualizes', () => {
        const items = Array.from({ length: 25 }, (_, i) => ({ id: String(i), label: `T${i}` }))
        const page = paginateItems(items, 2, 10)

        assert.equal(page.page, 2)
        assert.equal(page.pages, 3)
        assert.equal(page.items.length, 10)
        assert.equal(page.items[0].id, '10')

        const windowed = virtualWindow(items, 200, 160, 50)
        assert.ok(windowed.items.length < items.length)
        assert.ok(windowed.totalHeight === 25 * 50)
    })

    it('virtualizes variable row heights', () => {
        const items = Array.from({ length: 20 }, (_, i) => ({ id: String(i), label: `T${i}` }))
        const getHeight = (item) => (Number(item.id) % 2 === 0 ? 40 : 80)
        const offsets = buildVirtualOffsets(items, getHeight)

        assert.equal(offsets[0], 0)
        assert.equal(offsets[20], 10 * 40 + 10 * 80)
        assert.equal(findVirtualIndexAtOffset(offsets, 0), 0)
        assert.equal(findVirtualIndexAtOffset(offsets, 40), 1)
        assert.equal(findVirtualIndexAtOffset(offsets, 119), 1)
        assert.equal(findVirtualIndexAtOffset(offsets, 120), 2)

        const windowed = virtualWindow(items, 200, 160, getHeight)
        assert.ok(windowed.items.length < items.length)
        assert.equal(windowed.totalHeight, 1200)
        assert.equal(windowed.offsetY, offsets[windowed.start])

        const reveal = scrollTopToRevealIndex(19, getHeight, 160, items)
        assert.ok(reveal > 0)
        assert.ok(reveal <= windowed.totalHeight - 160)
    })

    it('scrolls virtual lists to reveal a created index', () => {
        // 20 items × 50px in a 160px viewport → last item needs scroll near bottom
        assert.equal(scrollTopToRevealIndex(19, 50, 160, 20), 20 * 50 - 160)
        assert.equal(scrollTopToRevealIndex(0, 50, 160, 20), 0)
        assert.equal(scrollTopToRevealIndex(2, 50, 160, 20), 0)
    })

    it('toggles by string/number id interchangeably', () => {
        const items = [
            {
                id: 1,
                label: 'Parent',
                done: false,
                children: [
                    { id: 2, label: 'Child', done: false },
                ],
            },
        ]

        const parentOn = toggleTodoTree(items, '1')
        assert.equal(parentOn.items[0].done, true)
        assert.equal(parentOn.items[0].children[0].done, true)
        assert.deepEqual(parentOn.changedIds.sort(), ['1', '2'])

        const childOff = toggleTodoTree(parentOn.items, 2)
        assert.equal(childOff.items[0].children[0].done, false)
        assert.equal(childOff.items[0].done, false)
    })

    it('sorts completed last when enabled', () => {
        const items = [
            { id: '1', done: true },
            { id: '2', done: false },
            { id: '3', done: true },
        ]

        assert.deepEqual(sortCompletedLast(items, true).map((i) => i.id), ['2', '1', '3'])
        assert.deepEqual(sortCompletedLast(items, false).map((i) => i.id), ['1', '2', '3'])
    })

    it('builds a hand strike path', () => {
        assert.match(handStrikePath(100), /^M0 4\.2/)
    })

    it('merges text rect fragments on the same line', () => {
        const lines = mergeTextLineRects([
            { top: 10, left: 0, right: 40, bottom: 24, width: 40, height: 14 },
            { top: 10.5, left: 42, right: 90, bottom: 24, width: 48, height: 14 },
            { top: 28, left: 0, right: 70, bottom: 42, width: 70, height: 14 },
        ])

        assert.equal(lines.length, 2)
        assert.equal(lines[0].left, 0)
        assert.equal(lines[0].width, 90)
        assert.equal(lines[1].width, 70)
        // Shorter wrap line must stay shorter than the full first line (strike sizing depends on this).
        assert.ok(lines[1].width < lines[0].width)
    })

    it('syncs parent and children in a sub-stack', () => {
        const items = [
            {
                id: 'pack',
                label: 'Pack',
                done: false,
                children: [
                    { id: 'a', label: 'A', done: false },
                    { id: 'b', label: 'B', done: false },
                    { id: 'c', label: 'C', done: true },
                ],
            },
        ]

        assert.equal(childProgressLabel(items[0]), '1/3')

        const parentOn = toggleTodoTree(items, 'pack')
        assert.equal(parentOn.items[0].done, true)
        assert.equal(parentOn.items[0].children.every((child) => child.done), true)
        assert.equal(parentOn.completedCount, 3)
        assert.deepEqual(parentOn.changedIds.sort(), ['a', 'b', 'pack'].sort())

        const childOff = toggleTodoTree(parentOn.items, 'a')
        assert.equal(childOff.items[0].children[0].done, false)
        assert.equal(childOff.items[0].done, false)

        const almost = toggleTodoTree(items, 'a')
        const last = toggleTodoTree(almost.items, 'b')
        assert.equal(last.items[0].done, true)
        assert.ok(last.changedIds.includes('pack'))
        assert.equal(allTodosComplete(last.items), true)
    })

    it('skips locked children when cascading', () => {
        const items = [
            {
                id: 'pack',
                label: 'Pack',
                done: false,
                children: [
                    { id: 'a', label: 'A', done: false },
                    { id: 'lock', label: 'Lock', done: false, disabled: true },
                ],
            },
        ]

        const next = toggleTodoTree(items, 'pack')
        assert.equal(next.items[0].done, true)
        assert.equal(next.items[0].children[0].done, true)
        assert.equal(next.items[0].children[1].done, false)
    })

    it('filters by child labels', () => {
        const items = [
            {
                id: 'pack',
                label: 'Pack',
                children: [{ id: 'pass', label: 'Passport', description: null }],
            },
        ]

        assert.equal(filterItems(items, 'pass').length, 1)
    })

    it('undo patch restores only changed ids', () => {
        const snapshot = [
            {
                id: 'pack',
                done: false,
                children: [
                    { id: 'a', done: false },
                    { id: 'b', done: false },
                ],
            },
            { id: 'solo', done: false, children: [] },
        ]

        const current = [
            {
                id: 'pack',
                done: true,
                children: [
                    { id: 'a', done: true },
                    { id: 'b', done: true },
                ],
            },
            { id: 'solo', done: true, children: [] },
            { id: 'new', done: false, children: [], created: true },
        ]

        const patched = applyTodoUndoPatch(current, snapshot, ['pack', 'a', 'b'])

        assert.equal(patched[0].done, false)
        assert.equal(patched[0].children[0].done, false)
        assert.equal(patched[0].children[1].done, false)
        assert.equal(patched[1].done, true)
        assert.equal(patched[2].id, 'new')
    })

    it('diffTodoDoneIds lists only toggled rows', () => {
        const before = [
            {
                id: 'pack',
                done: true,
                children: [
                    { id: 'a', done: true },
                    { id: 'b', done: true },
                ],
            },
            { id: 'solo', done: true, children: [] },
        ]
        const after = [
            {
                id: 'pack',
                done: false,
                children: [
                    { id: 'a', done: false },
                    { id: 'b', done: true },
                ],
            },
            { id: 'solo', done: true, children: [] },
        ]

        assert.deepEqual(diffTodoDoneIds(before, after).sort(), ['a', 'pack'])
    })
})

describe('todo list celebrations', () => {
    it('registers five built-ins', () => {
        const keys = listTodoListCelebrations()

        for (const key of ['fireworks', 'confetti', 'sparkles', 'streamers', 'bloom']) {
            assert.ok(keys.includes(key), `missing ${key}`)
            assert.ok(getTodoListCelebration(key))
        }

        assert.equal(getTodoListCelebration('fireworks').durationMs, 2500)
        assert.equal(getTodoListCelebration('confetti').durationMs, 1900)
        assert.equal(getTodoListCelebration('sparkles').durationMs, 2200)
        assert.equal(getTodoListCelebration('streamers').durationMs, 2200)
    })

    it('scales fireworks duration for in-box vs fullscreen', () => {
        const canvas = {
            clientWidth: 100,
            clientHeight: 48,
            width: 100,
            height: 48,
            style: {},
            classList: { add() {}, remove() {} },
            getContext() {
                return {
                    setTransform() {},
                    clearRect() {},
                    fillRect() {},
                    beginPath() {},
                    arc() {},
                    fill() {},
                    save() {},
                    restore() {},
                    ellipse() {},
                    stroke() {},
                    moveTo() {},
                    lineTo() {},
                }
            },
        }

        let inBoxMs = null
        let fullMs = null
        const def = getTodoListCelebration('fireworks')
        const prevStart = def.start

        def.start = (api) => {
            if (api.fullscreen) {
                fullMs = api.durationMs
            } else {
                inBoxMs = api.durationMs
            }

            return { stop() {} }
        }

        runTodoListCelebration('fireworks', {
            canvas,
            durationMs: 5500,
            playSound: () => null,
            reducedMotion: true,
            fullscreen: false,
        })

        runTodoListCelebration('fireworks', {
            canvas,
            durationMs: 5500,
            playSound: () => null,
            reducedMotion: true,
            fullscreen: true,
        })

        def.start = prevStart

        assert.equal(inBoxMs, 2500)
        assert.equal(fullMs, 4500)
    })

    it('prefers built-in duration over field celebrationDurationMs', () => {
        const canvas = {
            clientWidth: 100,
            clientHeight: 80,
            width: 100,
            height: 80,
            style: {},
            classList: { add() {}, remove() {} },
            getContext() {
                return {
                    setTransform() {},
                    clearRect() {},
                    fillRect() {},
                    beginPath() {},
                    arc() {},
                    fill() {},
                    save() {},
                    restore() {},
                    ellipse() {},
                    stroke() {},
                    moveTo() {},
                    lineTo() {},
                }
            },
        }

        let captured = null
        registerTodoListCelebration('sparkles-duration-probe', {
            durationMs: 2200,
            start(api) {
                captured = api.durationMs

                return { stop() {} }
            },
        })

        runTodoListCelebration('sparkles-duration-probe', {
            canvas,
            durationMs: 5500,
            playSound: () => null,
            reducedMotion: true,
        })

        assert.equal(captured, 2200)
    })

    it('returns a stoppable handle for celebrations', () => {
        registerTodoListCelebration('test-burst', {
            durationMs: 40,
            start() {
                return { stop() {} }
            },
        })

        const canvas = {
            clientWidth: 100,
            clientHeight: 80,
            width: 100,
            height: 80,
            getContext() {
                return {
                    setTransform() {},
                    clearRect() {},
                    fillRect() {},
                    beginPath() {},
                    arc() {},
                    fill() {},
                    save() {},
                    restore() {},
                    ellipse() {},
                }
            },
        }

        const handle = runTodoListCelebration('confetti', {
            canvas,
            durationMs: 30,
            playSound: () => {},
            reducedMotion: true,
        })

        assert.ok(handle)
        assert.equal(typeof handle.stop, 'function')
        handle.stop()
        assert.ok(getTodoListCelebration('test-burst'))
    })
})

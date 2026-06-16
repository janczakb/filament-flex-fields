import assert from 'node:assert/strict'
import { describe, it, beforeEach, afterEach } from 'node:test'

import fffSelectFieldCoordinator, {
    FFF_SELECT_ATTACH_MAX_ATTEMPTS,
    FFF_SELECT_INNER_INSTANCE_KEY,
    bootSelectFieldPatches,
    createSelectFieldAttachFailureMessage,
    markSelectFieldShellAttached,
} from '../../resources/js/components/select-field.js'

function createMockShell({ innerRoot = null, alpineData = null } = {}) {
    const events = []

    const shell = {
        dataset: {},
        dispatchEvent(event) {
            events.push(event)

            return true
        },
        querySelector(selector) {
            if (selector === '[data-fff-select-root]') {
                return innerRoot
            }

            return null
        },
    }

    return { shell, events }
}

function createCoordinatorTestSelect() {
    const classList = {
        add() {},
        remove() {},
        contains: () => false,
        toggle() {},
    }

    return {
        dropdown: {
            classList,
            isActive: false,
            closest: () => null,
            style: {},
        },
        selectButton: {
            classList,
            querySelector: () => null,
            insertBefore() {},
            closest: () => null,
        },
        labelRepository: {},
        options: [],
        openDropdown() {},
        closeDropdown() {},
        positionDropdown() {},
    }
}

function createInnerAlpineData(selectInstance = null) {
    return {
        [FFF_SELECT_INNER_INSTANCE_KEY]: selectInstance,
        $nextTick: (callback) => {
            callback()
        },
    }
}

function createInnerRoot(alpineData) {
    return {
        _x_dataStack: alpineData ? [alpineData] : [],
    }
}

function bindCoordinator(coordinator, shell) {
    coordinator.$el = shell
    coordinator.$nextTick = (callback) => {
        callback()
    }

    return coordinator
}

describe('fffSelectFieldCoordinator', () => {
    /** @type {Array<{ restore: () => void }>} */
    let restorers = []

    beforeEach(() => {
        restorers = []

        if (typeof globalThis.window === 'undefined') {
            globalThis.window = globalThis
            restorers.push({
                restore: () => {
                    delete globalThis.window
                },
            })
        }

        globalThis.__fffSelectFieldPatchApplicator = () => () => {}
        restorers.push({
            restore: () => {
                delete globalThis.__fffSelectFieldPatchApplicator
            },
        })
    })

    afterEach(() => {
        for (const { restore } of restorers.reverse()) {
            restore()
        }
    })

    it('exposes attach lifecycle without window globals', () => {
        const coordinator = fffSelectFieldCoordinator({
            patchConfig: { isGridLayout: false },
        })

        assert.equal(typeof coordinator.init, 'function')
        assert.equal(typeof coordinator.attachToInnerSelect, 'function')
        assert.equal(typeof coordinator.destroy, 'function')
        assert.equal(typeof coordinator.reportAttachFailure, 'function')
        assert.equal(typeof bootSelectFieldPatches, 'function')
        assert.equal(typeof globalThis.bootSelectFieldPatches, 'undefined')
    })

    it('creates actionable attach failure messages with statePath', () => {
        const message = createSelectFieldAttachFailureMessage({
            statePath: 'data.status',
        }, 120)

        assert.match(message, /data\.status/)
        assert.match(message, /120 attempts/)
    })

    it('marks shell dataset when attached', () => {
        const shell = { dataset: {} }

        markSelectFieldShellAttached(shell, true)
        assert.equal(shell.dataset.fffSelectAttached, 'true')

        markSelectFieldShellAttached(shell, false)
        assert.equal(shell.dataset.fffSelectAttached, 'false')
    })

    it('attaches patches when inner select is immediately available', async () => {
        const selectInstance = createCoordinatorTestSelect()
        const alpineData = createInnerAlpineData(selectInstance)
        const innerRoot = createInnerRoot(alpineData)
        const { shell, events } = createMockShell({ innerRoot })
        const coordinator = bindCoordinator(
            fffSelectFieldCoordinator({ patchConfig: { statePath: 'status' } }),
            shell,
        )

        coordinator.attachToInnerSelect()
        await new Promise((resolve) => setTimeout(resolve, 0))

        assert.equal(coordinator.attached, true)
        assert.equal(shell.dataset.fffSelectAttached, 'true')
        assert.equal(typeof coordinator.detachPatches, 'function')
        assert.equal(events.some((event) => event.type === 'fff-select-coordinator-attached'), true)
    })

    it('waits for delayed inner select initialization', async () => {
        const innerRoot = createInnerRoot(null)
        const { shell } = createMockShell({ innerRoot })
        const coordinator = bindCoordinator(
            fffSelectFieldCoordinator({ patchConfig: { statePath: 'plan' } }),
            shell,
        )

        let frame = 0

        globalThis.requestAnimationFrame = (callback) => {
            frame++

            if (frame === 2) {
                innerRoot._x_dataStack = [createInnerAlpineData(createCoordinatorTestSelect())]
            }

            callback()

            return frame
        }

        restorers.push({
            restore: () => {
                delete globalThis.requestAnimationFrame
            },
        })

        coordinator.attachToInnerSelect()
        await new Promise((resolve) => setTimeout(resolve, 0))

        assert.equal(coordinator.attached, true)
        assert.equal(coordinator.attachAttempts, 2)
    })

    it('reports attach failure after max attempts when select never appears', () => {
        const innerRoot = createInnerRoot({})
        const { shell, events } = createMockShell({ innerRoot })
        const coordinator = bindCoordinator(
            fffSelectFieldCoordinator({ patchConfig: { statePath: 'missing' } }),
            shell,
        )

        coordinator.maxAttachAttempts = 3

        const errors = []

        globalThis.requestAnimationFrame = (callback) => {
            callback()

            return 1
        }

        globalThis.console.error = (message) => {
            errors.push(message)
        }

        restorers.push({
            restore: () => {
                delete globalThis.requestAnimationFrame
                delete globalThis.console.error
            },
        })

        coordinator.attachToInnerSelect()

        assert.equal(coordinator.attached, false)
        assert.equal(coordinator.attachFailureReported, true)
        assert.equal(errors.length, 1)
        assert.match(String(errors[0]), /missing/)
        assert.equal(events.some((event) => event.type === 'fff-select-coordinator-attach-failed'), true)
    })

    it('reports attach failure when inner root is missing', () => {
        const { shell, events } = createMockShell({ innerRoot: null })
        const coordinator = bindCoordinator(
            fffSelectFieldCoordinator({ patchConfig: { statePath: 'orphan' } }),
            shell,
        )

        coordinator.maxAttachAttempts = 2

        const errors = []

        globalThis.requestAnimationFrame = (callback) => {
            callback()

            return 1
        }

        globalThis.console.error = (message) => {
            errors.push(message)
        }

        restorers.push({
            restore: () => {
                delete globalThis.requestAnimationFrame
                delete globalThis.console.error
            },
        })

        coordinator.attachToInnerSelect()

        assert.equal(errors.length, 1)
        assert.equal(events.some((event) => event.type === 'fff-select-coordinator-attach-failed'), true)
    })

    it('uses Alpine.$data when available', async () => {
        const selectInstance = createCoordinatorTestSelect()
        const innerRoot = createInnerRoot(null)
        const { shell } = createMockShell({ innerRoot })

        globalThis.Alpine = {
            $data(root) {
                assert.equal(root, innerRoot)

                return createInnerAlpineData(selectInstance)
            },
        }

        restorers.push({
            restore: () => {
                delete globalThis.Alpine
            },
        })

        const coordinator = bindCoordinator(
            fffSelectFieldCoordinator({ patchConfig: { statePath: 'alpine' } }),
            shell,
        )

        coordinator.attachToInnerSelect()
        await new Promise((resolve) => setTimeout(resolve, 0))

        assert.equal(coordinator.attached, true)
    })

    it('exports stable attach attempt budget for regression tests', () => {
        assert.equal(FFF_SELECT_ATTACH_MAX_ATTEMPTS, 120)
    })
})

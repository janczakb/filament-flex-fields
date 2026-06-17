const DEMO_DELAY_MS = 1800
const STORAGE_KEY = 'fff-skeleton-demo'

function parseJsonAttribute(element, attribute) {
    const raw = element?.getAttribute?.(attribute)

    if (! raw) {
        return []
    }

    try {
        const parsed = JSON.parse(raw)

        return Array.isArray(parsed) ? parsed : []
    } catch {
        return []
    }
}

function batchHasAssetUrls(batch) {
    return parseJsonAttribute(batch, 'data-fff-stylesheets').length > 0
        || parseJsonAttribute(batch, 'data-fff-chunks').length > 0
}

function readEnabledFromStorage(win) {
    try {
        return win.localStorage?.getItem(STORAGE_KEY) === '1'
    } catch {
        return false
    }
}

function writeEnabledToStorage(win, enabled) {
    try {
        if (enabled) {
            win.localStorage?.setItem(STORAGE_KEY, '1')
        } else {
            win.localStorage?.removeItem(STORAGE_KEY)
        }
    } catch {
        // Ignore storage errors in restricted contexts.
    }
}

export function installPlaygroundSkeletonDemo(injector, { window: win = globalThis.window } = {}) {
    if (! injector?.registerInjectorHooks) {
        throw new Error('Playground skeleton demo requires a flex-field asset injector instance.')
    }

    let enabled = readEnabledFromStorage(win)

    injector.registerInjectorHooks({
        shouldBatchTriggerPending(batch, defaultCheck) {
            if (! enabled) {
                return defaultCheck(batch)
            }

            return batchHasAssetUrls(batch)
        },
        markPendingStarted(target) {
            if (! enabled || ! target?.dataset) {
                return
            }

            target.dataset.fffPendingStartedAt = String(Date.now())
        },
        async getPendingReleaseDelayMs(target, { force, awaitInflightAssetLoads }) {
            if (force || ! enabled || ! target?.dataset) {
                return 0
            }

            await awaitInflightAssetLoads()

            const startedAt = Number(target.dataset.fffPendingStartedAt ?? Date.now())

            return Math.max(0, DEMO_DELAY_MS - (Date.now() - startedAt))
        },
        shouldSkipBackgroundPreload() {
            return enabled
        },
    })

    const api = {
        enable() {
            enabled = true
            writeEnabledToStorage(win, true)
            injector.purgeLazyAssets()
        },
        disable() {
            enabled = false
            writeEnabledToStorage(win, false)
        },
        isEnabled() {
            return enabled
        },
        purge: injector.purgeLazyAssets,
    }

    win.FffSkeletonDemo = api

    return api
}

export function install(injector) {
    return installPlaygroundSkeletonDemo(injector)
}

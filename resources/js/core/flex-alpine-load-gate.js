/**
 * Single-flight dynamic import + idempotent Alpine.data registration (FFART P0).
 * Delegates bundle readiness to the asset injector when available (I31).
 */

const inflightImports = new Map()
/** @type {Map<string, () => unknown>} */
const alpineDataFactories = new Map()
/** @type {Set<string>} */
const registeredAlpineNames = new Set()

function normalizeUrl(url, baseUri = typeof document !== 'undefined' ? document.baseURI : 'http://localhost/') {
    if (!url) {
        return ''
    }

    try {
        return new URL(url, baseUri).href
    } catch {
        return String(url)
    }
}

function getInjector() {
    return globalThis.FffAssetInjector
        ?? globalThis.window?.FffAssetInjector
        ?? null
}

async function waitForInjector(timeoutMs = 5000, intervalMs = 16) {
    const deadline = Date.now() + timeoutMs

    while (Date.now() < deadline) {
        const injector = getInjector()

        if (injector?.awaitBundleReady) {
            return injector
        }

        await new Promise((resolve) => {
            setTimeout(resolve, intervalMs)
        })
    }

    return null
}

function waitForLinkLoad(link) {
    return new Promise((resolve, reject) => {
        if (!link) {
            resolve()
            return
        }

        if (link.rel === 'stylesheet' && link.sheet) {
            resolve()
            return
        }

        const onLoad = () => {
            cleanup()
            resolve()
        }

        const onError = () => {
            cleanup()
            reject(new Error(`Failed to load asset: ${link.href}`))
        }

        const cleanup = () => {
            link.removeEventListener('load', onLoad)
            link.removeEventListener('error', onError)
        }

        link.addEventListener('load', onLoad)
        link.addEventListener('error', onError)
    })
}

function createAlpineLoadGate() {
    return {
        normalizeUrl,

        importModule(url) {
            const normalized = normalizeUrl(url)

            if (!normalized) {
                return Promise.reject(new Error('Flex Alpine load gate requires a URL.'))
            }

            if (inflightImports.has(normalized)) {
                return inflightImports.get(normalized)
            }

            const promise = import(/* @vite-ignore */ normalized).finally(() => {
                inflightImports.delete(normalized)
            })

            inflightImports.set(normalized, promise)

            return promise
        },

        async awaitBundleReady(componentId) {
            let injector = getInjector()

            if (!injector?.awaitBundleReady) {
                injector = await waitForInjector()
            }

            if (injector?.awaitBundleReady) {
                await injector.awaitBundleReady(componentId)

                return
            }

            if (typeof document === 'undefined') {
                return
            }

            const selectors = [
                `link[data-fff-stylesheet="${componentId}"]`,
                `link[data-fff-managed-asset][data-fff-component="${componentId}"]`,
                `link[rel="stylesheet"][href*="flex-fields-${componentId}"]`,
                `link[rel="modulepreload"][href*="${componentId}"]`,
            ]

            const links = selectors.flatMap((selector) => [...document.querySelectorAll(selector)])

            await Promise.allSettled(links.map((link) => waitForLinkLoad(link)))
        },

        registerAlpineData(name, factory) {
            if (!name || typeof factory !== 'function') {
                return
            }

            alpineDataFactories.set(name, factory)

            if (typeof Alpine === 'undefined') {
                if (typeof document !== 'undefined') {
                    document.addEventListener('alpine:init', () => {
                        this.registerAlpineData(name, factory)
                    }, { once: true })
                }

                return
            }

            if (registeredAlpineNames.has(name)) {
                return
            }

            Alpine.data(name, (...args) => factory(...args))
            registeredAlpineNames.add(name)
        },

        getInflightCount() {
            return inflightImports.size
        },
    }
}

let sharedGate = null

export function getAlpineLoadGate() {
    if (!sharedGate) {
        sharedGate = createAlpineLoadGate()
    }

    return sharedGate
}

export function resetAlpineLoadGateForTests() {
    inflightImports.clear()
    alpineDataFactories.clear()
    registeredAlpineNames.clear()
    sharedGate = null
}

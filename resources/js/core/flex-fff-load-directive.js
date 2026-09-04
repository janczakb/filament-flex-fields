/**
 * FFART x-fff-load — CRG-aware Alpine lazy loader (Faza 8).
 * Mirrors Filament x-load: defer Alpine init until the entry module is imported.
 */

import { getAlpineLoadGate } from './flex-alpine-load-gate.js'

function isFffLoadDirectiveRegistered() {
    return globalThis.__fffFffLoadDirectiveRegistered === true
}

function markFffLoadDirectiveRegistered() {
    globalThis.__fffFffLoadDirectiveRegistered = true
}

export function resetFffLoadDirectiveForTests() {
    delete globalThis.__fffFffLoadDirectiveRegistered
}

export function resolveFffLoadComponentId(el) {
    if (el?.dataset?.fffAssetConsumer) {
        return el.dataset.fffAssetConsumer
    }

    const fromAncestor = el?.closest?.('[data-fff-asset-consumer]')?.dataset?.fffAssetConsumer

    if (fromAncestor) {
        return fromAncestor
    }

    const fieldSelectors = [
        '.fi-fo-select-wrp',
        '.fff-select-field-wrapper',
        '.fi-fo-field-wrp',
        '[data-fff-lazy-alpine-mount]',
    ]

    for (const selector of fieldSelectors) {
        const fieldScope = el?.closest?.(selector)

        if (!fieldScope) {
            continue
        }

        const fromFieldBatch = fieldScope.querySelector?.('[data-fff-asset-consumer]')?.dataset?.fffAssetConsumer

        if (fromFieldBatch) {
            return fromFieldBatch
        }

        const fromParentBatch = fieldScope.parentElement?.querySelector?.('[data-fff-asset-consumer]')?.dataset?.fffAssetConsumer

        if (fromParentBatch) {
            return fromParentBatch
        }
    }

    const entryUrl = el?.getAttribute?.('x-fff-load-src')
        ?? el?.getAttribute?.('x-load-src')
        ?? ''
    const match = String(entryUrl).match(/\/components\/(?:flex-fields-)?([^./?]+)\.js/i)

    return match?.[1] ?? null
}

function resolveComponentId(el) {
    return resolveFffLoadComponentId(el)
}

function resolveEntryUrl(el) {
    return el.getAttribute('x-fff-load-src')
        ?? el.getAttribute('x-load-src')
        ?? null
}

function ignoreAttributeName(Alpine) {
    return typeof Alpine?.prefixed === 'function'
        ? Alpine.prefixed('ignore')
        : 'x-ignore'
}

function deferAlpineInit(el, Alpine) {
    if (!Alpine?.skipDuringClone || el._x_async === 'loaded') {
        return
    }

    const ignoreAttr = ignoreAttributeName(Alpine)

    Alpine.skipDuringClone(() => {
        el._x_async = 'init'
        el._x_ignore = true
        el.setAttribute(ignoreAttr, '')
    })
}

function shouldInitTree(el, ignoreAttr) {
    return el.isConnected && !el.closest?.(`[${ignoreAttr}]`)
}

async function activateAlpineInit(el, Alpine, { cancelled = () => false } = {}) {
    if (!Alpine?.skipDuringClone) {
        return
    }

    const ignoreAttr = ignoreAttributeName(Alpine)

    await Alpine.skipDuringClone(async () => {
        if (cancelled() || el._x_async === 'loaded') {
            return
        }

        if (el._x_async !== 'init' && el._x_async !== 'await') {
            deferAlpineInit(el, Alpine)
        }

        if (el._x_async !== 'init' || cancelled()) {
            return
        }

        el._x_async = 'await'

        const entryUrl = resolveEntryUrl(el)
        const componentId = resolveComponentId(el)

        if (!entryUrl) {
            return
        }

        const gate = getAlpineLoadGate()

        if (componentId) {
            await gate.awaitBundleReady(componentId)
        }

        if (cancelled()) {
            return
        }

        await gate.importModule(entryUrl)

        if (cancelled() || !el.isConnected) {
            return
        }

        el._x_async = 'loaded'

        Alpine.destroyTree(el)
        el._x_ignore = false
        el.removeAttribute(ignoreAttr)

        if (shouldInitTree(el, ignoreAttr)) {
            Alpine.initTree(el)
        }
    })()
}

function recoverLateFffLoadElements(Alpine) {
    if (typeof document === 'undefined') {
        return
    }

    document.querySelectorAll('[x-fff-load]').forEach((el) => {
        if (el._x_async === 'loaded' || el._x_async === 'await') {
            return
        }

        if (el._x_dataStack?.length) {
            Alpine.destroyTree(el)
        }

        deferAlpineInit(el, Alpine)
        void activateAlpineInit(el, Alpine)
    })
}

export function registerFffLoadDirective(Alpine) {
    if (!Alpine?.directive || isFffLoadDirectiveRegistered()) {
        return
    }

    markFffLoadDirectiveRegistered()

    const inlineHandler = (el) => {
        deferAlpineInit(el, Alpine)
    }

    const asyncHandler = (el, _payload, { cleanup }) => {
        let cancelled = false

        cleanup(() => {
            cancelled = true
        })

        return activateAlpineInit(el, Alpine, {
            cancelled: () => cancelled,
        }).catch(() => {
            if (!cancelled) {
                el.dispatchEvent(new CustomEvent('fff-load-error', { bubbles: true }))
            }
        })
    }

    asyncHandler.inline = inlineHandler

    Alpine.directive('fff-load', asyncHandler).before('ignore')
}

export function ensureFffLoadDirectiveRegistered(Alpine = globalThis.Alpine) {
    if (!Alpine) {
        return false
    }

    registerFffLoadDirective(Alpine)
    recoverLateFffLoadElements(Alpine)

    return true
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        ensureFffLoadDirectiveRegistered(globalThis.Alpine)
    })

    ensureFffLoadDirectiveRegistered(globalThis.Alpine)
}

import assert from 'node:assert/strict'
import { after, before, describe, it } from 'node:test'

import { createOverlayRuntime } from '../../resources/js/core/overlay-runtime.js'

function createStyleStub() {
    return {
        overflow: 'visible',
        overflowX: 'visible',
        overflowY: 'visible',
        removeProperty() {},
        setProperty(property, value) {
            this[property] = value
        },
    }
}

function createElement(tagName = 'div') {
    const style = createStyleStub()
    const classList = new Set()
    const attributes = new Map()
    const dataset = {}

    const element = {
        tagName: tagName.toUpperCase(),
        parentElement: null,
        style,
        hidden: true,
        offsetWidth: 1,
        scrollHeight: 120,
        clientHeight: 120,
        classList: {
            add: (...tokens) => {
                for (const token of tokens) {
                    classList.add(token)
                }
            },
            remove: (...tokens) => {
                for (const token of tokens) {
                    classList.delete(token)
                }
            },
            toggle: (token, force) => {
                if (force === true) {
                    classList.add(token)

                    return
                }

                if (force === false) {
                    classList.delete(token)

                    return
                }

                if (classList.has(token)) {
                    classList.delete(token)
                } else {
                    classList.add(token)
                }
            },
            contains: (token) => classList.has(token),
        },
        dataset,
        contains(node) {
            return node === this
        },
        querySelector() {
            return null
        },
        querySelectorAll() {
            return []
        },
        getBoundingClientRect() {
            return {
                top: 100,
                bottom: 140,
                left: 24,
                width: 240,
                height: this.scrollHeight || 40,
            }
        },
        setAttribute(name, value) {
            attributes.set(name, value)
        },
        getAttribute(name) {
            return attributes.get(name) ?? null
        },
        removeAttribute(name) {
            attributes.delete(name)
        },
    }

    return element
}

function createTestContext() {
    const classList = {
        contains: () => false,
        add() {},
        remove() {},
        toggle() {},
    }

    const document = {
        documentElement: { classList },
        body: { classList },
        querySelector: () => null,
    }

    const window = {
        innerWidth: 1280,
        innerHeight: 800,
        matchMedia: () => ({ matches: false }),
        getComputedStyle: () => createStyleStub(),
        addEventListener() {},
        removeEventListener() {},
        CustomEvent: class CustomEvent {
            constructor(type, init = {}) {
                this.type = type
                this.detail = init.detail ?? null
            }
        },
        dispatchEvent() {},
        requestAnimationFrame(callback) {
            callback()

            return 1
        },
        cancelAnimationFrame() {},
    }

    return { document, window }
}

describe('overlay-runtime', () => {
    let previousDocument
    let previousWindow
    let previousMatchMedia

    before(() => {
        previousDocument = globalThis.document
        previousWindow = globalThis.window
        previousMatchMedia = globalThis.matchMedia
    })

    after(() => {
        globalThis.document = previousDocument
        globalThis.window = previousWindow
        globalThis.matchMedia = previousMatchMedia
    })

    it('open exclusive closes other overlays', () => {
        const context = createTestContext()
        globalThis.document = context.document
        globalThis.window = context.window
        globalThis.matchMedia = context.window.matchMedia

        const runtime = createOverlayRuntime(context)

        const firstAnchor = createElement()
        const firstPanel = createElement()
        const secondAnchor = createElement()
        const secondPanel = createElement()

        runtime.open({
            id: 'country',
            panel: firstPanel,
            anchor: firstAnchor,
            mode: 'panel',
            exclusive: true,
        })

        runtime.open({
            id: 'phone',
            panel: secondPanel,
            anchor: secondAnchor,
            mode: 'panel',
            exclusive: true,
        })

        assert.equal(runtime.isOpen('country'), false)
        assert.equal(runtime.isOpen('phone'), true)
        assert.deepEqual(runtime.getOpenIds(), ['phone'])

        runtime.destroy()
    })

    it('claimExclusive keeps only one exclusive lock open', () => {
        const context = createTestContext()
        const observability = []

        context.window.dispatchEvent = (event) => {
            observability.push(event)
        }

        globalThis.document = context.document
        globalThis.window = context.window
        globalThis.matchMedia = context.window.matchMedia

        const runtime = createOverlayRuntime(context)
        const displaced = []
        const events = []

        runtime.onTelemetry((event) => {
            events.push(event.type + ':' + event.id)
        })

        runtime.claimExclusive('fff-country-field-menu', {
            onDisplace: () => {
                displaced.push('country')
            },
        })

        runtime.claimExclusive('fff-phone-field-menu', {
            onDisplace: () => {
                displaced.push('phone')
            },
        })

        assert.equal(runtime.isOpen('fff-country-field-menu'), false)
        assert.equal(runtime.isOpen('fff-phone-field-menu'), true)
        assert.deepEqual(runtime.getOpenIds(), ['fff-phone-field-menu'])
        assert.deepEqual(displaced, ['country'])
        assert.deepEqual(events, [
            'open:fff-country-field-menu',
            'close:fff-country-field-menu',
            'open:fff-phone-field-menu',
        ])

        const openEvents = observability.filter((event) => event.type === 'fff:observability')

        assert.equal(openEvents.length, 2)
        assert.deepEqual(openEvents[0].detail, {
            event: 'overlay.open',
            id: 'fff-country-field-menu',
            openLatencyMs: null,
        })
        assert.deepEqual(openEvents[1].detail, {
            event: 'overlay.open',
            id: 'fff-phone-field-menu',
            openLatencyMs: null,
        })

        runtime.releaseExclusive('fff-phone-field-menu')

        assert.equal(runtime.isOpen('fff-phone-field-menu'), false)
        assert.equal(events.at(-1), 'close:fff-phone-field-menu')

        runtime.destroy()
    })

    it('managed exclusive open displaces lock-only claims', () => {
        const context = createTestContext()
        globalThis.document = context.document
        globalThis.window = context.window
        globalThis.matchMedia = context.window.matchMedia

        const runtime = createOverlayRuntime(context)
        const displaced = []

        runtime.claimExclusive('searchable-menu', {
            onDisplace: () => {
                displaced.push('searchable-menu')
            },
        })

        const anchor = createElement()
        const panel = createElement()

        runtime.open({
            id: 'future-picker',
            panel,
            anchor,
            mode: 'panel',
            exclusive: true,
        })

        assert.equal(runtime.isOpen('searchable-menu'), false)
        assert.equal(runtime.isOpen('future-picker'), true)
        assert.deepEqual(displaced, ['searchable-menu'])

        runtime.destroy()
    })

    it('managed open with onDisplace closes the previous host when displaced', () => {
        const context = createTestContext()
        globalThis.document = context.document
        globalThis.window = context.window
        globalThis.matchMedia = context.window.matchMedia

        const runtime = createOverlayRuntime(context)
        const displaced = []

        const firstAnchor = createElement()
        const firstPanel = createElement()
        const secondAnchor = createElement()
        const secondPanel = createElement()

        runtime.open({
            id: 'country',
            panel: firstPanel,
            anchor: firstAnchor,
            exclusive: true,
            manageVisibility: false,
            onDisplace: () => {
                displaced.push('country')
            },
        })

        runtime.open({
            id: 'phone',
            panel: secondPanel,
            anchor: secondAnchor,
            exclusive: true,
            manageVisibility: false,
        })

        assert.equal(runtime.isOpen('country'), false)
        assert.equal(runtime.isOpen('phone'), true)
        assert.deepEqual(displaced, ['country'])

        runtime.destroy()
    })

    it('emits telemetry on open', () => {
        const context = createTestContext()
        globalThis.document = context.document
        globalThis.window = context.window
        globalThis.matchMedia = context.window.matchMedia

        const runtime = createOverlayRuntime(context)
        const events = []

        runtime.onTelemetry((event) => {
            events.push(event)
        })

        const anchor = createElement()
        const panel = createElement()

        runtime.open({
            id: 'select-menu',
            panel,
            anchor,
            mode: 'panel',
            exclusive: true,
        })

        assert.deepEqual(events, [{ type: 'open', id: 'select-menu' }])
        assert.equal(panel.hidden, false)
        assert.equal(panel.getAttribute('aria-hidden'), 'false')
        assert.equal(panel.classList.contains('is-open'), true)

        runtime.close('select-menu')

        assert.equal(events.length, 2)
        assert.equal(events[1].type, 'close')
        assert.equal(events[1].id, 'select-menu')
        assert.equal(typeof events[1].durationMs, 'number')

        runtime.destroy()
    })

    it('opens sheet mode and marks panel dataset', () => {
        const context = createTestContext()
        globalThis.document = context.document
        globalThis.window = context.window
        globalThis.matchMedia = context.window.matchMedia

        const runtime = createOverlayRuntime(context)
        const anchor = createElement()
        const panel = createElement()

        runtime.open({
            id: 'mobile-menu',
            panel,
            anchor,
            mode: 'sheet',
            exclusive: true,
        })

        assert.equal(panel.dataset.fffOverlaySheet, 'true')
        assert.equal(['peek', 'content'].includes(panel.dataset.fffOverlaySnap), true)
        assert.equal(panel.classList.contains('fff-overlay-sheet'), true)
        assert.equal(panel.style.width, '100%')
        assert.equal(panel.style.left, '0')
        assert.equal(panel.style.right, '0')

        runtime.destroy()
    })

    it('setMode flips an open sheet to a positioned desktop panel', () => {
        const context = createTestContext()
        globalThis.document = context.document
        globalThis.window = context.window
        globalThis.matchMedia = context.window.matchMedia

        const runtime = createOverlayRuntime(context)
        const anchor = createElement()
        const panel = createElement()

        runtime.open({
            id: 'resize-menu',
            panel,
            anchor,
            mode: 'sheet',
            exclusive: true,
            manageVisibility: false,
        })

        assert.equal(panel.style.left, '0')
        assert.equal(panel.classList.contains('fff-overlay-sheet'), true)

        runtime.setMode('resize-menu', 'panel')

        assert.equal(panel.classList.contains('fff-overlay-sheet'), false)
        assert.equal(panel.classList.contains('fff-overlay-panel'), true)
        assert.equal(panel.dataset.fffOverlayMode, 'panel')
        assert.notEqual(panel.style.left, '0')
        assert.match(String(panel.style.left), /px$/)

        runtime.destroy()
    })
})

import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import {
    bindOverlaySheetDismiss,
    fitOverlaySheetToContent,
    overlaySheetCanExpand,
    OVERLAY_SHEET_MIN_HEIGHT_PX,
    resolveOverlaySheetExpandedHeight,
    resolveOverlaySheetMinHeight,
    resolveOverlaySheetPeekHeight,
    resolveOverlaySheetSnap,
} from '../../resources/js/core/overlay-sheet-dismiss.js'

describe('overlay-sheet-dismiss', () => {
    it('resolves peek and expanded heights from the viewport', () => {
        const win = { innerHeight: 800 }

        assert.equal(resolveOverlaySheetPeekHeight(win), 400)
        assert.equal(resolveOverlaySheetExpandedHeight(win), 640)
        assert.equal(resolveOverlaySheetMinHeight(win), OVERLAY_SHEET_MIN_HEIGHT_PX)
    })

    it('caps peek height at 32rem', () => {
        const win = { innerHeight: 2000 }

        assert.equal(resolveOverlaySheetPeekHeight(win), 512)
    })

    it('floors fitted height so empty states are not clipped', () => {
        const classList = new Set()
        const style = {
            setProperty(property, value) {
                this[property] = value
            },
        }
        const panel = {
            style,
            dataset: {},
            scrollHeight: 96,
            classList: {
                add: (token) => classList.add(token),
                remove: (token) => classList.delete(token),
            },
            getBoundingClientRect: () => ({ height: 96 }),
            querySelectorAll: () => [],
            querySelector: () => null,
        }

        const result = fitOverlaySheetToContent(panel, { innerHeight: 800 })

        assert.equal(result.fitted, OVERLAY_SHEET_MIN_HEIGHT_PX)
        assert.equal(style.height, `${OVERLAY_SHEET_MIN_HEIGHT_PX}px`)
        assert.equal(style['min-height'], `${OVERLAY_SHEET_MIN_HEIGHT_PX}px`)
    })

    it('fits sheet height to measured content under the peek cap', () => {
        const classList = new Set()
        const style = {
            setProperty(property, value) {
                this[property] = value
            },
        }
        const panel = {
            style,
            dataset: {},
            scrollHeight: 320,
            classList: {
                add: (token) => classList.add(token),
                remove: (token) => classList.delete(token),
            },
            getBoundingClientRect: () => ({ height: 320 }),
            querySelectorAll: () => [],
            querySelector: () => null,
        }

        const result = fitOverlaySheetToContent(panel, { innerHeight: 800 })

        assert.equal(result.fitted, 320)
        assert.equal(result.canExpand, false)
        assert.equal(style.height, '320px')
        assert.equal(style.width, '100%')
        assert.equal(panel.dataset.fffOverlaySnap, 'content')
        assert.equal(classList.has('fff-overlay-sheet--measuring'), false)
    })

    it('uses list scrollHeight when flex layout collapses options during measure', () => {
        const classList = new Set()
        const style = {
            setProperty(property, value) {
                this[property] = value
            },
        }
        const list = {
            scrollHeight: 520,
            getBoundingClientRect: () => ({ height: 0 }),
            offsetHeight: 0,
        }
        const search = {
            getBoundingClientRect: () => ({ height: 44 }),
            offsetHeight: 44,
        }
        const handle = {
            getBoundingClientRect: () => ({ height: 24 }),
            offsetHeight: 24,
        }
        const panel = {
            style,
            dataset: {},
            scrollHeight: 70,
            classList: {
                add: (token) => classList.add(token),
                remove: (token) => classList.delete(token),
            },
            getBoundingClientRect: () => ({ height: 70 }),
            querySelectorAll: () => [],
            querySelector(selector) {
                if (String(selector).includes('handle')) {
                    return handle
                }

                if (String(selector).includes('search')) {
                    return search
                }

                if (String(selector).includes('options') || String(selector).includes('scroll')) {
                    return list
                }

                return null
            },
        }

        const result = fitOverlaySheetToContent(panel, { innerHeight: 800 })

        assert.equal(result.fitted, 400)
        assert.equal(result.canExpand, true)
        assert.equal(panel.dataset.fffOverlaySnap, 'peek')
    })

    it('detects expandable content from overflow children', () => {
        const child = {
            scrollHeight: 900,
            clientHeight: 300,
        }
        const panel = {
            scrollHeight: 400,
            querySelectorAll: () => [child],
        }

        assert.equal(overlaySheetCanExpand(panel, 400), true)
        assert.equal(overlaySheetCanExpand({
            scrollHeight: 400,
            querySelectorAll: () => [{ scrollHeight: 100, clientHeight: 100 }],
        }, 400), false)
    })

    it('dismisses when dragged down far enough from peek', () => {
        assert.equal(resolveOverlaySheetSnap({
            deltaY: 180,
            velocityY: 0,
            startHeight: 400,
            peekHeight: 400,
            expandedHeight: 640,
            canExpand: true,
            wasExpanded: false,
        }), 'dismiss')
    })

    it('expands when dragged up with overflow content', () => {
        assert.equal(resolveOverlaySheetSnap({
            deltaY: -160,
            velocityY: -0.2,
            startHeight: 400,
            peekHeight: 400,
            expandedHeight: 640,
            canExpand: true,
            wasExpanded: false,
        }), 'expanded')
    })

    it('collapses to peek when dragged down from expanded', () => {
        assert.equal(resolveOverlaySheetSnap({
            deltaY: 200,
            velocityY: 0.1,
            startHeight: 640,
            peekHeight: 400,
            expandedHeight: 640,
            canExpand: true,
            wasExpanded: true,
        }), 'peek')
    })

    it('dismisses on a fast flick down from peek', () => {
        assert.equal(resolveOverlaySheetSnap({
            deltaY: 40,
            velocityY: 0.8,
            startHeight: 400,
            peekHeight: 400,
            expandedHeight: 640,
            canExpand: false,
            wasExpanded: false,
        }), 'dismiss')
    })

    it('drag-dismiss continues from the pull offset with transform !important', () => {
        globalThis.Element = class Element {}

        const classList = new Set(['is-open', 'fff-overlay-sheet'])
        const styleProps = {}
        const listeners = {}
        const panel = {
            style: {
                setProperty(name, value, priority) {
                    styleProps[name] = { value, priority }
                    this[name] = value
                },
                removeProperty(name) {
                    delete styleProps[name]
                    this[name] = ''
                },
                transition: '',
                transform: '',
                height: '400px',
            },
            dataset: {
                fffSheetFittedHeight: '400',
                fffOverlaySnap: 'content',
            },
            classList: {
                add: (token) => classList.add(token),
                remove: (token) => classList.delete(token),
                contains: (token) => classList.has(token),
            },
            getBoundingClientRect: () => ({ height: 400 }),
            querySelectorAll: () => [],
            addEventListener(type, handler) {
                if (! listeners[type]) {
                    listeners[type] = []
                }

                listeners[type].push(handler)
            },
            removeEventListener(type, handler) {
                listeners[type] = (listeners[type] || []).filter((entry) => entry !== handler)
            },
            setPointerCapture() {},
            releasePointerCapture() {},
            offsetWidth: 1,
        }

        let dismissed = 0
        const win = {
            innerHeight: 800,
            setTimeout() {
                return 1
            },
        }

        const cleanup = bindOverlaySheetDismiss({
            panel,
            onDismiss: () => {
                dismissed += 1
            },
            window: win,
        })

        const handle = new Element()
        handle.closest = (sel) => (String(sel).includes('handle') ? handle : null)

        const fire = (type, event) => {
            for (const handler of listeners[type] || []) {
                handler(event)
            }
        }

        fire('pointerdown', {
            pointerType: 'touch',
            pointerId: 1,
            clientY: 100,
            timeStamp: 0,
            target: handle,
            button: 0,
        })
        fire('pointermove', {
            pointerId: 1,
            clientY: 220,
            timeStamp: 16,
        })

        assert.equal(styleProps.transform?.value, 'translate3d(0, 120px, 0)')
        assert.equal(styleProps.transform?.priority, 'important')
        assert.equal(styleProps.height?.value, '400px')

        fire('pointerup', {
            pointerId: 1,
            clientY: 280,
            timeStamp: 32,
        })

        assert.equal(classList.has('is-dismissing'), true)
        assert.equal(classList.has('is-open'), false)
        assert.match(styleProps.transform?.value ?? '', /translate3d\(0, \d+px, 0\)/)
        assert.equal(dismissed, 0)

        fire('transitionend', {
            target: panel,
            propertyName: 'transform',
        })

        assert.equal(dismissed, 1)
        assert.equal(classList.has('is-dismissing'), false)

        cleanup()
    })
})

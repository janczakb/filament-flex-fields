import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import {
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
})

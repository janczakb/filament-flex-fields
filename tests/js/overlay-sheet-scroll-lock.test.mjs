import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import {
    lockOverlaySheetScroll,
    unlockOverlaySheetScroll,
    flushOverlaySheetToScreenBottom,
    purgeOverlayBackdrops,
    resolveOverlayKeyboardInset,
    syncOverlaySheetToVisualViewport,
} from '../../resources/js/core/overlay-sheet-scroll-lock.js'
import { createOverlayBackdrop, removeOverlayBackdrop } from '../../resources/js/core/overlay-backdrop.js'

function createDoc() {
    const classList = new Set()
    const htmlStyle = new Map()
    const bodyStyle = new Map()
    const bodyAttrs = new Map()
    const htmlAttrs = new Map()

    return {
        documentElement: {
            attributes: htmlAttrs,
            classList: {
                add: (name) => classList.add(name),
                remove: (name) => classList.delete(name),
                contains: (name) => classList.has(name),
            },
            style: {
                overflow: '',
                setProperty(name, value) {
                    htmlStyle.set(name, value)
                    if (name === 'overflow') {
                        this.overflow = value
                    }
                },
                removeProperty(name) {
                    htmlStyle.delete(name)
                    if (name === 'overflow') {
                        this.overflow = ''
                    }
                },
            },
            getAttribute(name) {
                return htmlAttrs.get(name) ?? null
            },
            setAttribute(name, value) {
                htmlAttrs.set(name, String(value))
            },
            removeAttribute(name) {
                htmlAttrs.delete(name)
            },
            hasAttribute(name) {
                return htmlAttrs.has(name)
            },
        },
        body: {
            style: {
                overflow: '',
                setProperty(name, value) {
                    bodyStyle.set(name, value)
                    if (name === 'overflow') {
                        this.overflow = value
                    }
                },
                removeProperty(name) {
                    bodyStyle.delete(name)
                    if (name === 'overflow') {
                        this.overflow = ''
                    }
                },
            },
            getAttribute(name) {
                return bodyAttrs.get(name) ?? null
            },
            setAttribute(name, value) {
                bodyAttrs.set(name, String(value))
            },
            removeAttribute(name) {
                bodyAttrs.delete(name)
            },
            hasAttribute(name) {
                return bodyAttrs.has(name)
            },
        },
        activeElement: null,
        querySelectorAll() {
            return []
        },
        querySelector() {
            return null
        },
        addEventListener() {},
        removeEventListener() {},
        __bodyStyle: bodyStyle,
        __htmlStyle: htmlStyle,
    }
}

describe('overlay-sheet-scroll-lock', () => {
    it('blocks body scroll with overflow:hidden and restores it on unlock', () => {
        const doc = createDoc()
        const win = { scrollY: 40, scrollTo() {}, addEventListener() {}, removeEventListener() {} }

        lockOverlaySheetScroll(doc, win)
        assert.equal(doc.documentElement.classList.contains('fff-overlay-sheet-open'), true)
        assert.equal(doc.__htmlStyle.get('overflow'), 'hidden')
        assert.equal(doc.__bodyStyle.get('overflow'), 'hidden')
        assert.equal(doc.body.style.position, undefined)

        unlockOverlaySheetScroll(doc, win)
        assert.equal(doc.documentElement.classList.contains('fff-overlay-sheet-open'), false)
        assert.equal(doc.__htmlStyle.get('overflow'), undefined)
        assert.equal(doc.__bodyStyle.get('overflow'), undefined)
    })

    it('flushes sheets to bottom:0', () => {
        const styles = new Map()
        const panel = {
            style: {
                setProperty(name, value) {
                    styles.set(name, value)
                },
            },
        }

        assert.equal(flushOverlaySheetToScreenBottom(panel, { innerHeight: 800 }), 0)
        assert.equal(styles.get('bottom'), '0')
    })

    it('docks sheet above the keyboard using VisualViewport inset', () => {
        const styles = new Map()
        const panel = {
            dataset: { fffSheetFittedHeight: '400', fffOverlaySheetMinHeight: '280' },
            style: {
                setProperty(name, value) {
                    styles.set(name, value)
                },
            },
        }
        const win = {
            innerHeight: 800,
            visualViewport: { height: 420, offsetTop: 80 },
        }

        assert.equal(resolveOverlayKeyboardInset(win), 300)
        assert.equal(syncOverlaySheetToVisualViewport(panel, win), 300)
        assert.equal(styles.get('bottom'), '300px')
        assert.equal(styles.get('height'), '400px')
        assert.equal(styles.get('max-height'), '400px')
    })

    it('caps sheet height to the visible viewport when keyboard is open', () => {
        const styles = new Map()
        const panel = {
            dataset: { fffSheetFittedHeight: '500', fffOverlaySheetMinHeight: '280' },
            style: {
                setProperty(name, value) {
                    styles.set(name, value)
                },
            },
        }
        const win = {
            innerHeight: 800,
            visualViewport: { height: 320, offsetTop: 100 },
        }

        syncOverlaySheetToVisualViewport(panel, win)
        assert.equal(styles.get('bottom'), '380px')
        assert.equal(styles.get('height'), '316px')
    })
})

describe('overlay-backdrop', () => {
    it('removes backdrops immediately without waiting for transitionend', () => {
        const removed = []
        const backdrop = {
            classList: {
                remove() {},
            },
            style: {},
            remove() {
                removed.push(true)
            },
        }

        const doc = {
            querySelectorAll() {
                return [backdrop]
            },
        }

        removeOverlayBackdrop(doc, 'x')
        assert.equal(removed.length, 1)
        assert.equal(backdrop.style.display, 'none')
    })

    it('purgeOverlayBackdrops removes every leftover dimmer', () => {
        const removed = []
        const doc = {
            querySelectorAll() {
                return [{
                    remove() {
                        removed.push(1)
                    },
                }]
            },
        }

        purgeOverlayBackdrops(doc)
        assert.equal(removed.length, 1)
    })

    it('createOverlayBackdrop appends a dimmer node', () => {
        const appended = []
        const doc = {
            querySelector() {
                return null
            },
            createElement() {
                return {
                    className: '',
                    dataset: {},
                    style: {
                        setProperty() {},
                        zIndex: '',
                    },
                    classList: { add() {} },
                    setAttribute() {},
                    addEventListener() {},
                }
            },
            body: {
                appendChild(node) {
                    appended.push(node)
                },
            },
        }

        globalThis.requestAnimationFrame = (cb) => cb()
        createOverlayBackdrop(doc, 'menu-1')
        assert.equal(appended.length, 1)
    })
})

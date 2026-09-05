import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import {
    createOverlayBackdrop,
    OVERLAY_SHEET_BACKDROP_Z_INDEX,
    OVERLAY_SHEET_PANEL_Z_INDEX,
    removeOverlayBackdrop,
} from '../../resources/js/core/overlay-backdrop.js'

function makeDocument() {
    /** @type {any[]} */
    const bodyChildren = []

    const syncSiblings = () => {
        for (let index = 0; index < bodyChildren.length; index += 1) {
            const node = bodyChildren[index]
            node.previousSibling = index > 0 ? bodyChildren[index - 1] : null
            node.nextSibling = index < bodyChildren.length - 1 ? bodyChildren[index + 1] : null
            node.parentNode = body
        }
    }

    const body = {
        children: bodyChildren,
        appendChild(node) {
            const existing = bodyChildren.indexOf(node)
            if (existing !== -1) {
                bodyChildren.splice(existing, 1)
            }
            bodyChildren.push(node)
            syncSiblings()

            return node
        },
        insertBefore(node, before) {
            const existing = bodyChildren.indexOf(node)
            if (existing !== -1) {
                bodyChildren.splice(existing, 1)
            }
            const index = bodyChildren.indexOf(before)
            if (index === -1) {
                bodyChildren.push(node)
            } else {
                bodyChildren.splice(index, 0, node)
            }
            syncSiblings()

            return node
        },
    }

    /** @type {Map<string, any>} */
    const byAttr = new Map()

    return {
        body,
        querySelector(selector) {
            const match = String(selector).match(/data-fff-overlay-backdrop="([^"]+)"/)
            if (! match) {
                return null
            }

            return byAttr.get(match[1]) ?? null
        },
        createElement(tag) {
            const classList = new Set()
            const node = {
                tagName: String(tag).toUpperCase(),
                className: '',
                dataset: {},
                parentNode: null,
                previousSibling: null,
                nextSibling: null,
                style: {
                    zIndex: '',
                    setProperty(name, value) {
                        if (name === 'z-index') {
                            this.zIndex = value
                        }
                    },
                },
                classList: {
                    add: (token) => classList.add(token),
                    remove: (token) => classList.delete(token),
                    contains: (token) => classList.has(token),
                },
                setAttribute() {},
                addEventListener() {},
                remove() {
                    const index = bodyChildren.indexOf(node)
                    if (index !== -1) {
                        bodyChildren.splice(index, 1)
                    }
                    byAttr.delete(node.dataset.fffOverlayBackdrop)
                    node.parentNode = null
                    node.previousSibling = null
                    node.nextSibling = null
                    syncSiblings()
                },
            }

            Object.defineProperty(node.dataset, 'fffOverlayBackdrop', {
                configurable: true,
                enumerable: true,
                get() {
                    return this._id
                },
                set(value) {
                    this._id = value
                    byAttr.set(value, node)
                },
            })

            return node
        },
        _bodyChildren: bodyChildren,
    }
}

describe('overlay-backdrop', () => {
    it('inserts the dimmer before the sheet and uses sheet z-index constants', () => {
        const document = makeDocument()
        const panel = document.createElement('div')
        document.body.appendChild(panel)

        globalThis.requestAnimationFrame = (cb) => cb()

        const backdrop = createOverlayBackdrop(document, 'menu-1', {
            zIndex: OVERLAY_SHEET_BACKDROP_Z_INDEX,
            beforeElement: panel,
        })

        assert.equal(document._bodyChildren.indexOf(backdrop), 0)
        assert.equal(document._bodyChildren.indexOf(panel), 1)
        assert.ok(document._bodyChildren.indexOf(backdrop) < document._bodyChildren.indexOf(panel))
        assert.equal(String(backdrop.style.zIndex), String(OVERLAY_SHEET_BACKDROP_Z_INDEX))
        assert.ok(OVERLAY_SHEET_PANEL_Z_INDEX > OVERLAY_SHEET_BACKDROP_Z_INDEX)
    })

    it('moves an existing backdrop behind the panel when re-synced', () => {
        const document = makeDocument()
        const panel = document.createElement('div')
        globalThis.requestAnimationFrame = (cb) => cb()

        const first = createOverlayBackdrop(document, 'menu-2', {
            zIndex: OVERLAY_SHEET_BACKDROP_Z_INDEX,
        })
        document.body.appendChild(panel)

        // Simulate a bad order: panel then backdrop
        document._bodyChildren.length = 0
        panel.parentNode = null
        first.parentNode = null
        document.body.appendChild(panel)
        document.body.appendChild(first)

        assert.ok(document._bodyChildren.indexOf(panel) < document._bodyChildren.indexOf(first))

        createOverlayBackdrop(document, 'menu-2', {
            zIndex: OVERLAY_SHEET_BACKDROP_Z_INDEX,
            beforeElement: panel,
        })

        assert.ok(document._bodyChildren.indexOf(first) < document._bodyChildren.indexOf(panel))
    })

    it('removes the backdrop after leave', () => {
        const document = makeDocument()
        globalThis.requestAnimationFrame = (cb) => cb()
        globalThis.window = { setTimeout: (cb) => { cb(); return 1 } }

        createOverlayBackdrop(document, 'menu-3')
        assert.ok(document.querySelector('[data-fff-overlay-backdrop="menu-3"]'))

        removeOverlayBackdrop(document, 'menu-3')
        assert.equal(document.querySelector('[data-fff-overlay-backdrop="menu-3"]'), null)
    })
})

import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import {
    lockOverlaySheetScroll,
    unlockOverlaySheetScroll,
} from '../../resources/js/core/overlay-sheet-scroll-lock.js'

function createDoc() {
    const classList = new Set()

    return {
        documentElement: {
            attributes: new Map(),
            classList: {
                add: (name) => classList.add(name),
                remove: (name) => classList.delete(name),
                contains: (name) => classList.has(name),
            },
            getAttribute(name) {
                return this.attributes.get(name) ?? null
            },
            setAttribute(name, value) {
                this.attributes.set(name, String(value))
            },
            removeAttribute(name) {
                this.attributes.delete(name)
            },
        },
    }
}

describe('overlay-sheet-scroll-lock', () => {
    it('locks and unlocks the document with ref-counting', () => {
        const doc = createDoc()

        lockOverlaySheetScroll(doc)
        assert.equal(doc.documentElement.classList.contains('fff-overlay-sheet-open'), true)
        assert.equal(doc.documentElement.getAttribute('data-fff-overlay-sheet-locks'), '1')

        lockOverlaySheetScroll(doc)
        assert.equal(doc.documentElement.getAttribute('data-fff-overlay-sheet-locks'), '2')

        unlockOverlaySheetScroll(doc)
        assert.equal(doc.documentElement.classList.contains('fff-overlay-sheet-open'), true)
        assert.equal(doc.documentElement.getAttribute('data-fff-overlay-sheet-locks'), '1')

        unlockOverlaySheetScroll(doc)
        assert.equal(doc.documentElement.classList.contains('fff-overlay-sheet-open'), false)
        assert.equal(doc.documentElement.getAttribute('data-fff-overlay-sheet-locks'), null)
    })
})

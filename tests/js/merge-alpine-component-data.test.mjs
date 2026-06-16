import { describe, it } from 'node:test'
import assert from 'node:assert/strict'
import { mergeAlpineComponentData } from '../../resources/js/support/merge-alpine-component-data.js'

describe('mergeAlpineComponentData', () => {
    it('preserves getters from the base object', () => {
        let count = 0
        const data = mergeAlpineComponentData({
            get label() {
                return `count-${count}`
            },
        })

        assert.equal(data.label, 'count-0')
        count = 2
        assert.equal(data.label, 'count-2')
        assert.equal(typeof Object.getOwnPropertyDescriptor(data, 'label')?.get, 'function')
    })

    it('preserves getters against the merged component instance', () => {
        const mixin = {
            get total() {
                return (this.items ?? []).length
            },
        }

        const data = mergeAlpineComponentData({ items: [1, 2, 3] }, mixin)

        assert.equal(data.total, 3)
        assert.equal(typeof Object.getOwnPropertyDescriptor(data, 'total')?.get, 'function')
    })

    it('does not throw when mixin getters read component state', () => {
        const mixin = {
            get usesVirtualScroll() {
                return (this.timezones ?? []).length > 50
            },
        }

        const data = mergeAlpineComponentData({ timezones: [] }, mixin)

        assert.equal(data.usesVirtualScroll, false)
    })
})

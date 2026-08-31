import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    buildHeadlessDropdownRows,
    flattenHeadlessDropdownRowsForVirtualization,
    windowHeadlessVirtualRows,
} from '../../resources/js/components/select-field/headless-select-options.js'

describe('headless-select-options', () => {
    it('inserts separators between option groups when enabled', () => {
        const rows = buildHeadlessDropdownRows([
            { label: 'North America', options: [{ value: 'usa', label: 'United States' }] },
            { label: 'Europe', options: [{ value: 'fr', label: 'France' }] },
        ], {
            withSeparators: true,
        })

        assert.deepEqual(
            rows.map((row) => row.type),
            ['group', 'separator', 'group'],
        )
    })

    it('skips separators when disabled', () => {
        const rows = buildHeadlessDropdownRows([
            { label: 'A', options: [{ value: 'a', label: 'A' }] },
            { label: 'B', options: [{ value: 'b', label: 'B' }] },
        ], {
            withSeparators: false,
        })

        assert.deepEqual(rows.map((row) => row.type), ['group', 'group'])
    })

    it('windows flattened rows for grouped virtualization', () => {
        const rows = buildHeadlessDropdownRows([
            { label: 'Group', options: [
                { value: 'one', label: 'One' },
                { value: 'two', label: 'Two' },
                { value: 'three', label: 'Three' },
            ] },
        ])

        const flat = flattenHeadlessDropdownRowsForVirtualization(rows)
        const windowed = windowHeadlessVirtualRows(flat, 1, 2)

        assert.equal(windowed.meta.total, 4)
        assert.equal(windowed.rows.length, 2)
        assert.equal(windowed.rows[0].type, 'option')
        assert.ok(windowed.meta.paddingTop > 0)
    })
})

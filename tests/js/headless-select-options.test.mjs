import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    buildHeadlessDropdownRows,
    filterHeadlessOptionTree,
    flattenHeadlessDropdownRowsForVirtualization,
    limitHeadlessOptionTree,
} from '../../resources/js/components/select-field/headless-select-options.js'
import {
    buildVirtualRowPrefixSums,
    findVirtualRowIndexAtScrollTop,
    windowHeadlessVirtualRows,
} from '../fixtures/js/select-field/headless-virtual-prefix-sums.js'

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

    it('filters nested group options and drops empty continents', () => {
        const filtered = filterHeadlessOptionTree([
            {
                label: 'North America',
                options: [
                    { value: 'usa', label: 'United States' },
                    { value: 'canada', label: 'Canada' },
                ],
            },
            {
                label: 'Europe',
                options: [
                    { value: 'fr', label: 'France' },
                    { value: 'de', label: 'Germany' },
                ],
            },
        ], 'fr')

        assert.equal(filtered.length, 1)
        assert.equal(filtered[0].label, 'Europe')
        assert.deepEqual(filtered[0].options.map((option) => option.value), ['fr'])
    })

    it('keeps a whole group when the section label matches', () => {
        const filtered = filterHeadlessOptionTree([
            {
                label: 'Europe',
                options: [
                    { value: 'fr', label: 'France' },
                    { value: 'de', label: 'Germany' },
                ],
            },
            {
                label: 'Asia',
                options: [
                    { value: 'jp', label: 'Japan' },
                ],
            },
        ], 'Europe')

        assert.equal(filtered.length, 1)
        assert.equal(filtered[0].options.length, 2)
    })

    it('limits leaf options while preserving groups', () => {
        const limited = limitHeadlessOptionTree([
            {
                label: 'Group',
                options: [
                    { value: 'a', label: 'A' },
                    { value: 'b', label: 'B' },
                    { value: 'c', label: 'C' },
                ],
            },
            { value: 'd', label: 'D' },
        ], 2)

        assert.equal(limited.length, 1)
        assert.deepEqual(limited[0].options.map((option) => option.value), ['a', 'b'])
    })

    it('searches option values when searchableOptionFields includes value', () => {
        const filtered = filterHeadlessOptionTree([
            { value: 'draft', label: 'Working copy' },
            { value: 'published', label: 'Live' },
        ], 'draft', undefined, ['value'])

        assert.equal(filtered.length, 1)
        assert.equal(filtered[0].value, 'draft')
    })

    it('binary-searches virtual row index from prefix sums of mixed heights', () => {
        const flat = [
            { type: 'group-header', height: 24 },
            { type: 'option', height: 36 },
            { type: 'option', height: 36 },
            { type: 'separator', height: 17 },
            { type: 'option', height: 36 },
        ]
        const prefixSums = buildVirtualRowPrefixSums(flat)

        assert.deepEqual(prefixSums, [0, 24, 60, 96, 113, 149])
        assert.equal(findVirtualRowIndexAtScrollTop(prefixSums, 0), 0)
        assert.equal(findVirtualRowIndexAtScrollTop(prefixSums, 24), 1)
        assert.equal(findVirtualRowIndexAtScrollTop(prefixSums, 59), 1)
        assert.equal(findVirtualRowIndexAtScrollTop(prefixSums, 60), 2)
        assert.equal(findVirtualRowIndexAtScrollTop(prefixSums, 112), 3)
        assert.equal(findVirtualRowIndexAtScrollTop(prefixSums, 113), 4)
        assert.equal(findVirtualRowIndexAtScrollTop(prefixSums, 1000), 4)
    })
})

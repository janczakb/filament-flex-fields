import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import tagsFieldFormComponent from '../../resources/js/components/tags-field.js'

describe('tags-field searchRequestId stale ignore', () => {
    it('ignores stale Livewire suggestion responses after a newer requestId', async () => {
        let resolveFirst
        let resolveSecond

        const first = new Promise((resolve) => {
            resolveFirst = resolve
        })
        const second = new Promise((resolve) => {
            resolveSecond = resolve
        })

        let call = 0

        const component = tagsFieldFormComponent({
            state: [],
            splitKeys: ['Tab'],
            maxTags: null,
            suggestions: [],
            suggestionsOnly: false,
            duplicateInsensitive: false,
            tagPrefix: '',
            tagSuffix: '',
            disabled: false,
            searchSuggestions: true,
            minSearchLength: 1,
            componentKey: 'tags',
        })

        component.$wire = {
            callSchemaComponentMethod: async () => {
                call += 1

                if (call === 1) {
                    return first
                }

                return second
            },
        }

        const firstFetch = component.fetchSuggestionSearch('la')
        const secondFetch = component.fetchSuggestionSearch('lar')

        resolveSecond(['laravel'])
        await secondFetch

        assert.deepEqual(component.searchResults, ['laravel'])
        assert.equal(component.searchPending, false)

        resolveFirst(['legacy'])
        await firstFetch

        assert.deepEqual(component.searchResults, ['laravel'])
    })
})

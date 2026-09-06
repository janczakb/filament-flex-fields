/**
 * Per playground SelectField variant: × clear must commit empty wire state,
 * refill must work, conflicts must not resurrect stale values.
 */
import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    commitHeadlessSelectionToWire,
    isHeadlessWireStateEmpty,
    wireStateFromEngineValues,
} from '../../resources/js/components/select-field/headless-select-state.js'

/** Mirrors SelectPlaygroundVariantRegistry keys + filled samples. */
const VARIANTS = Object.freeze([
    { key: 'select__basic', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__searchable', multiple: false, filled: 'tailwind', alternate: 'laravel', clearable: true },
    { key: 'select__multiple', multiple: true, filled: ['action', 'adventure', 'drama', 'comedy'], alternate: ['horror'], clearable: true },
    { key: 'select__multiple_checklist', multiple: true, filled: ['california', 'texas'], alternate: ['delaware'], clearable: true },
    { key: 'select__email_recipients', multiple: true, filled: ['jane', 'john'], alternate: ['fred'], clearable: true },
    { key: 'select__custom_value_user', multiple: false, filled: 'fred', alternate: 'jane', clearable: true },
    { key: 'select__grouped', multiple: false, filled: 'usa', alternate: 'uk', clearable: true },
    { key: 'select__disabled_animals', multiple: false, filled: 'dog', alternate: 'bird', clearable: true },
    { key: 'select__dynamic_options', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__truncate_labels', multiple: false, filled: 'enterprise_agreement', alternate: 'short', clearable: true },
    { key: 'select__reorderable', multiple: true, filled: ['tailwind', 'laravel', 'livewire', 'alpine'], alternate: ['alpine', 'tailwind'], clearable: true },
    { key: 'select__rich', multiple: false, filled: 'pro', alternate: 'team', clearable: true },
    { key: 'select__grid', multiple: false, filled: 'sky', alternate: 'mint', clearable: true },
    { key: 'select__required', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__sm', multiple: false, filled: 'draft', alternate: 'published', clearable: true },
    { key: 'select__md', multiple: false, filled: 'reviewing', alternate: 'draft', clearable: true },
    { key: 'select__lg', multiple: false, filled: 'published', alternate: 'reviewing', clearable: true },
    { key: 'select__multiple_sm', multiple: true, filled: ['action', 'comedy'], alternate: ['horror'], clearable: true },
    { key: 'select__bordered', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__flat', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__soft', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__faded', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__underlined', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__secondary', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__item_card', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__inline_search', multiple: false, filled: 'tailwind', alternate: 'laravel', clearable: true },
    { key: 'select__entity_mentions', multiple: true, filled: ['jane', 'john'], alternate: ['fred'], clearable: true },
    { key: 'select__inline_field_label', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__clearable', multiple: false, filled: 'published', alternate: 'draft', clearable: true },
    { key: 'select__not_clearable', multiple: false, filled: 'published', alternate: 'draft', clearable: false },
    { key: 'select__domain_affix', multiple: false, filled: 'acme', alternate: 'flex', clearable: true },
    { key: 'select__prefix_icon', multiple: false, filled: 'tailwind', alternate: 'alpine', clearable: true },
    { key: 'select__chip_primary', multiple: true, filled: ['action', 'comedy'], alternate: ['drama'], clearable: true },
    { key: 'select__chip_success', multiple: true, filled: ['adventure', 'drama'], alternate: ['horror'], clearable: true },
    { key: 'select__chip_danger', multiple: true, filled: ['horror', 'thriller'], alternate: ['action'], clearable: true },
    { key: 'select__create_single', multiple: false, filled: 'laravel', alternate: 'svelte', clearable: true },
    { key: 'select__create_multiple', multiple: true, filled: ['laravel'], alternate: ['laravel', 'svelte'], clearable: true },
    { key: 'select__create_with_sections', multiple: false, filled: 'tailwind', alternate: 'filament', clearable: true },
    { key: 'select__scale_10k', multiple: false, filled: 'opt_0', alternate: 'opt_99', clearable: true },
    { key: 'select__cascade_country', multiple: false, filled: 'us', alternate: 'pl', clearable: true },
    { key: 'select__cascade_region', multiple: false, filled: 'ca', alternate: 'tx', clearable: true },
    { key: 'select__rtl', multiple: false, filled: 'riyadh', alternate: 'jeddah', clearable: true },
    { key: 'select__rtl_inline', multiple: false, filled: 'laravel', alternate: 'tailwind', clearable: true },
    { key: 'select__rtl_hebrew_inline', multiple: false, filled: 'tel_aviv', alternate: 'haifa', clearable: true },
    { key: 'select__rtl_dropdown_clearable', multiple: false, filled: 'jeddah', alternate: 'riyadh', clearable: true },
])

function engineValuesFromState(state, multiple) {
    if (multiple) {
        return Array.isArray(state) ? state.map(String) : []
    }

    if (state == null || state === '') {
        return []
    }

    return [String(state)]
}

describe('playground variant × clear empty wire commit', () => {
    for (const variant of VARIANTS) {
        for (let cycle = 0; cycle < 25; cycle++) {
            it(`${variant.key} clear cycle #${cycle}: filled → × empty → refill`, () => {
                let state = null

                // first pick
                let commit = commitHeadlessSelectionToWire(
                    state,
                    engineValuesFromState(variant.filled, variant.multiple),
                    variant.multiple,
                )
                assert.equal(commit.skip, false)
                state = commit.nextState
                assert.equal(isHeadlessWireStateEmpty(state, variant.multiple), false)

                // × clear
                commit = commitHeadlessSelectionToWire(
                    state,
                    [],
                    variant.multiple,
                )
                assert.equal(commit.skip, false)
                state = commit.nextState
                assert.equal(isHeadlessWireStateEmpty(state, variant.multiple), true)
                assert.deepEqual(state, wireStateFromEngineValues([], variant.multiple))

                // refill alternate
                commit = commitHeadlessSelectionToWire(
                    state,
                    engineValuesFromState(variant.alternate, variant.multiple),
                    variant.multiple,
                )
                assert.equal(commit.skip, false)
                state = commit.nextState
                assert.equal(isHeadlessWireStateEmpty(state, variant.multiple), false)

                // clear again — empty must stick (no ghost revive)
                commit = commitHeadlessSelectionToWire(state, [], variant.multiple)
                state = commit.nextState
                assert.equal(isHeadlessWireStateEmpty(state, variant.multiple), true)
            })
        }

        for (let hammer = 0; hammer < 15; hammer++) {
            it(`${variant.key} conflict hammer #${hammer}`, () => {
                let state = null
                const steps = [
                    variant.filled,
                    variant.alternate,
                    null,
                    variant.filled,
                    null,
                    variant.alternate,
                    null,
                    variant.filled,
                ]

                for (const step of steps) {
                    const values = step == null
                        ? []
                        : engineValuesFromState(step, variant.multiple)
                    const commit = commitHeadlessSelectionToWire(state, values, variant.multiple)

                    if (! commit.skip) {
                        state = commit.nextState
                    }
                }

                assert.equal(isHeadlessWireStateEmpty(state, variant.multiple), false)
                assert.deepEqual(
                    state,
                    wireStateFromEngineValues(engineValuesFromState(variant.filled, variant.multiple), variant.multiple),
                )
            })
        }
    }
})

describe('playground not_clearable still allows programmatic empty wire', () => {
    it('select__not_clearable can still commit [] → null on wire', () => {
        const commit = commitHeadlessSelectionToWire('published', [], false)
        assert.equal(commit.skip, false)
        assert.equal(commit.nextState, null)
    })
})

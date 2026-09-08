import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import { createHeadlessComboboxEmptyUiMixin } from '../../resources/js/components/select-field/headless-combobox-empty-ui.js'
import { createHeadlessUserSelectMixin } from '../../resources/js/components/select-field/headless-user-select.js'

function host(overrides = {}) {
    return {
        hasDynamicSearchResults: true,
        minSearchLength: 2,
        optionsLoading: false,
        searchPending: false,
        comboboxQuery: '',
        comboboxFilteredOptions: () => ({ meta: { total: 0 } }),
        ...createHeadlessUserSelectMixin({ isUserSelectField: true }),
        ...overrides,
    }
}

describe('UserSelect dropdown empty / skeleton states', () => {
    it('shows search prompt on open while under min length even if optionsLoading', () => {
        const state = host({ optionsLoading: true })

        assert.equal(state.headlessUserSelectDropdownState(), 'prompt')
        assert.equal(state.shouldShowHeadlessUserSelectSkeleton(), false)
        assert.equal(state.shouldShowHeadlessUserSelectEmptyState(), true)
    })

    it('shows search prompt on open while under min length even if searchPending', () => {
        const state = host({ searchPending: true })

        assert.equal(state.headlessUserSelectDropdownState(), 'prompt')
        assert.equal(state.shouldShowHeadlessUserSelectSkeleton(), false)
    })

    it('shows skeleton only after the query meets min length and a search is in flight', () => {
        const state = host({
            comboboxQuery: 'ja',
            searchPending: true,
        })

        assert.equal(state.headlessUserSelectDropdownState(), 'searching')
        assert.equal(state.shouldShowHeadlessUserSelectSkeleton(), true)
        assert.equal(state.shouldShowHeadlessUserSelectEmptyState(), false)
    })

    it('keeps painted options visible under min length when results already exist', () => {
        const state = host({
            optionsLoading: true,
            comboboxFilteredOptions: () => ({ meta: { total: 3 } }),
        })

        assert.equal(state.headlessUserSelectDropdownState(), null)
        assert.equal(state.shouldShowHeadlessUserSelectSkeleton(), false)
    })
})

describe('SelectField dropdown empty / skeleton states (min search)', () => {
    it('prefers prompt over loading skeleton while under min length', () => {
        const state = {
            ...createHeadlessComboboxEmptyUiMixin(),
            isUserSelectField: false,
            hasDynamicSearchResults: true,
            minSearchLength: 2,
            optionsLoading: true,
            searchPending: false,
            comboboxOpen: true,
            hasDynamicOptions: false,
            dynamicOptionsLoaded: false,
            comboboxQuery: '',
            allowCreateOption: false,
            searchable: true,
            countVisibleDropdownOptions: () => 0,
            getEngineOptions: () => [],
            hasExhaustedSelectableOptions: () => false,
        }

        assert.equal(state.headlessSelectDropdownState(), 'prompt')
        assert.equal(state.shouldShowHeadlessSelectSkeleton(), false)
    })
})

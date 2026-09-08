import {
    buildHeadlessDropdownRows,
    flattenHeadlessDropdownRowOptions,
    flattenHeadlessOptions,
    headlessOptionValue,
} from './headless-select-options.js'

export function createHeadlessComboboxEmptyUiMixin() {
    return {
        headlessSelectDropdownState() {
            if (this.isUserSelectField) {
                return null
            }

            const query = String(this.comboboxQuery ?? '').trim()
            const minLen = Number(this.minSearchLength ?? 0)
            // keepSelectedOptionsInDropdown(false) omits selected rows from the painted list.
            const visibleOptionCount = typeof this.countVisibleDropdownOptions === 'function'
                ? this.countVisibleDropdownOptions()
                : (typeof this.getEngineOptions === 'function' ? this.getEngineOptions().length : 0)

            // Prefer search prompt over skeleton while under min length (async search fields).
            if (this.hasDynamicSearchResults && minLen > 0 && query.length < minLen) {
                return visibleOptionCount > 0 ? null : 'prompt'
            }

            if (this.optionsLoading) {
                return 'loading'
            }

            if (this.searchPending) {
                return 'searching'
            }

            if (this.comboboxOpen && this.hasDynamicOptions && ! this.dynamicOptionsLoaded) {
                const pendingCount = typeof this.getEngineOptions === 'function'
                    ? this.getEngineOptions().length
                    : 0

                if (pendingCount === 0) {
                    return 'loading'
                }
            }

            if (visibleOptionCount > 0) {
                return null
            }

            if (
                this.allowCreateOption
                && query.length > 0
                && this.searchable
                && ! this.hasDynamicSearchResults
            ) {
                return null
            }

            if (this.hasDynamicSearchResults && query.length >= minLen) {
                return 'search'
            }

            if (query.length > 0) {
                return 'search'
            }

            if (this.hasExhaustedSelectableOptions()) {
                return 'exhausted'
            }

            return 'options'
        },

        /**
         * Options remaining in the dropdown after selected-item filtering.
         */
        countVisibleDropdownOptions() {
            if (this.smartSuggestEnabled && this._engine && ! this.hasDynamicSearchResults) {
                const flat = typeof this.getEngineOptions === 'function'
                    ? this.getEngineOptions()
                    : []

                if (! this.multiple || this.keepSelectedOptionsInDropdown) {
                    return flat.length
                }

                return flat.filter((option) => ! this.isOptionSelected(headlessOptionValue(option))).length
            }

            const rows = buildHeadlessDropdownRows(this.comboboxFilteredOptionTree(), {
                multiple: this.multiple,
                keepSelectedOptionsInDropdown: this.keepSelectedOptionsInDropdown,
                isOptionSelected: (value) => this.isOptionSelected(value),
                withSeparators: false,
            })

            return flattenHeadlessDropdownRowOptions(rows).length
        },

        /**
         * Multi-select removed every remaining choice from the list
         * (keepSelectedOptionsInDropdown=false) while the source still has options.
         */
        hasExhaustedSelectableOptions() {
            if (! this.multiple || this.keepSelectedOptionsInDropdown) {
                return false
            }

            const sourceCount = flattenHeadlessOptions(this.options ?? []).length

            if (sourceCount === 0) {
                return false
            }

            return this.countVisibleDropdownOptions() === 0
                && String(this.comboboxQuery ?? '').trim() === ''
        },

        shouldShowHeadlessDropdownOptions() {
            if (this.isUserSelectField) {
                return ! this.shouldShowHeadlessUserSelectSkeleton()
                    && ! this.shouldShowHeadlessUserSelectEmptyState()
            }

            return ! this.shouldShowHeadlessSelectEmptyState()
                && ! this.shouldShowHeadlessSelectSkeleton()
        },

        shouldShowHeadlessSelectEmptyState() {
            const state = this.headlessSelectDropdownState()

            return state === 'prompt' || state === 'search' || state === 'options' || state === 'exhausted'
        },

        shouldShowHeadlessSelectSkeleton() {
            if (this.isUserSelectField) {
                return false
            }

            const state = this.headlessSelectDropdownState()

            return state === 'loading' || state === 'searching'
        },

        headlessSelectSkeletonAriaLabel() {
            const state = this.headlessSelectDropdownState()

            if (state === 'searching') {
                return this.searchingMessage
            }

            return this.loadingMessage
        },

        headlessSelectEmptyIconHtml() {
            const state = this.headlessSelectDropdownState()

            if (state === 'search' || state === 'prompt') {
                return this.selectNoResultsIconHtml
            }

            return this.selectNoOptionsIconHtml
        },

        headlessSelectEmptyTitle() {
            const state = this.headlessSelectDropdownState()

            if (state === 'prompt') {
                return this.searchPrompt
            }

            if (state === 'search') {
                return this.noSearchResultsMessage
            }

            if (state === 'exhausted') {
                return this.noMoreOptionsMessage || this.noOptionsMessage
            }

            return this.noOptionsMessage
        },

        headlessSelectEmptyHint() {
            const hints = this.selectEmptyStateHints ?? {}
            const state = this.headlessSelectDropdownState()

            if (state === 'loading' || state === 'searching') {
                return hints.pleaseWait ?? ''
            }

            if (state === 'prompt') {
                return Number(this.minSearchLength ?? 0) > 0
                    ? (hints.minSearchLength ?? '')
                    : (hints.filterList ?? '')
            }

            if (state === 'search') {
                return hints.tryDifferentSearch ?? ''
            }

            if (state === 'exhausted') {
                return hints.allOptionsSelected ?? hints.noOptionsAvailable ?? ''
            }

            return hints.noOptionsAvailable ?? ''
        }
    }
}

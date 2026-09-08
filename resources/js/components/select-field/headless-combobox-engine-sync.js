import {
    filterHeadlessOptionTree,
    flattenHeadlessOptions,
    headlessOptionIsDisabled,
    headlessOptionLabelHtml,
    headlessOptionValue,
    limitHeadlessOptionTree,
} from './headless-select-options.js'
import {
    canWriteHeadlessWireState,
    commitHeadlessSelectionToWire,
} from './headless-select-state.js'
import { DEFAULT_VIRTUALIZE_THRESHOLD } from '../../core/combobox-engine.js'

function defaultGetOptionLabel(option) {
    return headlessOptionLabelHtml(option, 'dropdown')
}

/**
 * Filament `optionsLimit` is a DOM render budget. Once the list is large enough
 * to virtualize, TanStack owns that budget — truncating here would freeze scroll
 * at ~50 rows (default limit) and never reach the virt threshold.
 *
 * @param {Array<unknown>} filtered
 * @param {{ isGridLayout?: boolean, virtualizeThreshold?: number }} ctx
 */
export function shouldBypassOptionsLimitForVirtualization(filtered, ctx = {}) {
    if (ctx.isGridLayout) {
        return false
    }

    const threshold = ctx.virtualizeThreshold ?? DEFAULT_VIRTUALIZE_THRESHOLD
    const count = flattenHeadlessOptions(filtered).length

    return count >= threshold
}

export function createHeadlessComboboxEngineSyncMixin() {
    return {
        applyComboboxQueryToEngine() {
            this._engine?.setQuery(this.comboboxQuery)

            if (! this.hasDynamicSearchResults) {
                this._engine?.setOptions(flattenHeadlessOptions(this.options))
            }

            this._syncFromEngine()
        },

        syncStateFromEngine(values) {
            const commit = commitHeadlessSelectionToWire(this.state, values, this.multiple)

            if (commit.skip || ! canWriteHeadlessWireState(this.state)) {
                return
            }

            this.state = commit.nextState
        },

        _valuesEqual(left, right) {
            if (left.length !== right.length) {
                return false
            }

            for (let index = 0; index < left.length; index++) {
                if (String(left[index]) !== String(right[index])) {
                    return false
                }
            }

            return true
        },

        _syncFromEngine() {
            if (! this._engine) {
                return
            }

            const snapshot = this._engine.getSnapshot()

            if (this.hasDynamicSearchResults) {
                this.comboboxQuery = snapshot.query
            }

            this.comboboxHighlightedIndex = snapshot.highlightedIndex

            const nextSelected = Array.from(snapshot.selectedValues)

            // Keep the same array reference when values are unchanged so Alpine
            // does not re-run x-html on the trigger (optionView avatars remount).
            if (! this._valuesEqual(nextSelected, this.comboboxSelectedValues)) {
                this.comboboxSelectedValues = nextSelected
            }
        },

        comboboxFilteredOptions() {
            const flat = this.getEngineOptions()
            const result = this._engine?.filteredOptions() ?? {
                options: flat,
                meta: { startIndex: 0, endIndex: flat.length, total: flat.length },
            }

            return {
                options: result.options,
                meta: { ...result.meta },
            }
        },

        comboboxFilteredOptionTree() {
            const tree = Array.isArray(this.options) ? this.options : []
            const query = String(this.comboboxQuery ?? '').trim()

            let filtered = tree

            if (this.searchable && ! this.hasDynamicSearchResults && query !== '') {
                filtered = filterHeadlessOptionTree(
                    tree,
                    query,
                    (option) => defaultGetOptionLabel(option),
                    this.searchableOptionFields,
                )
            }

            if (shouldBypassOptionsLimitForVirtualization(filtered, {
                isGridLayout: this.isGridLayout,
                virtualizeThreshold: this.virtualizeThreshold,
            })) {
                return filtered
            }

            return limitHeadlessOptionTree(filtered, this.optionsLimit)
        },

        getEngineOptions() {
            return flattenHeadlessOptions(this.comboboxFilteredOptionTree())
        },

        syncEngineOptions() {
            const next = flattenHeadlessOptions(this.options)
            const fingerprint = this.fingerprintHeadlessOptions(next)

            // Replacing flatOptions on every open re-runs Alpine x-html for the
            // trigger + rows (optionView avatars remount and flash ~1s).
            if (fingerprint === this._engineOptionsFingerprint) {
                this._engine?.setOptions(next)

                return
            }

            this._engineOptionsFingerprint = fingerprint
            this.flatOptions = next
            this._engine?.setOptions(next)
            this._syncFromEngine()
        },

        fingerprintHeadlessOptions(options) {
            if (! Array.isArray(options) || options.length === 0) {
                return '0'
            }

            let fingerprint = String(options.length)

            for (const option of options) {
                fingerprint += `\0${headlessOptionValue(option)}`
                fingerprint += `\0${option?.triggerLabel ?? ''}`
                fingerprint += `\0${option?.label ?? ''}`
            }

            return fingerprint
        },

        isHeadlessOptionDisabled(option) {
            return headlessOptionIsDisabled(option)
        },

        headlessOptionFlatIndex(value) {
            const normalized = String(value)

            return this.getEngineOptions().findIndex((option) => headlessOptionValue(option) === normalized)
        }
    }
}

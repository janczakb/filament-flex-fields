import { emitObservabilityEvent } from '../../core/observability.js'
import { createRelationshipSearchAdapter } from '../../core/combobox-engine.js'
import { flattenHeadlessOptions } from './headless-select-options.js'

/**
 * Livewire-backed search, dynamic options, and label resolution for headless SelectField.
 * Mirrors Filament Select's getSearchResultsForJs / getOptionsForJs / getOptionLabel(s) contract.
 */
export function createHeadlessComboboxLivewireMixin({
    componentKey = null,
    hasDynamicSearchResults = false,
    hasPaginatedSearchResults = false,
    hasDynamicOptions = false,
    hasClientSideOptionList = true,
    searchDebounce = 1000,
    minSearchLength = 0,
    isPreloaded = false,
    hasInitialNoOptionsMessage = false,
    loadingMessage = '',
    searchingMessage = '',
    loadingMoreMessage = '',
    noOptionsMessage = '',
    noSearchResultsMessage = '',
    searchPrompt = '',
    selectEmptyStateHints = {},
    initialOptionLabel = null,
    initialOptionLabels = [],
    multiple = false,
} = {}) {
    return {
        componentKey,
        hasDynamicSearchResults,
        hasPaginatedSearchResults,
        hasDynamicOptions,
        hasClientSideOptionList,
        searchDebounce,
        minSearchLength,
        isPreloaded,
        hasInitialNoOptionsMessage,
        loadingMessage,
        searchingMessage,
        loadingMoreMessage,
        noOptionsMessage,
        noSearchResultsMessage,
        searchPrompt,
        selectEmptyStateHints,

        searchPending: false,
        optionsLoading: false,
        loadingMore: false,
        dynamicOptionsLoaded: false,
        searchResultsCursor: null,
        searchResultsHasMore: false,
        _activeSearchQuery: '',
        labelRepository: {},
        _searchDebounceTimer: null,
        _searchRequestId: 0,
        _loadMoreRequestId: 0,
        _relationshipAdapter: null,
        _loadMoreObserver: null,

        initLivewireIntegration() {
            this.seedLabelRepository()

            if (this.hasDynamicSearchResults) {
                this._relationshipAdapter = createRelationshipSearchAdapter({
                    debounceMs: this.searchDebounce,
                    minSearchLength: this.minSearchLength,
                    fetchResults: async (search, signal) => {
                        const results = await this.callSchemaMethod('getSearchResultsForJs', { search })

                        if (signal?.aborted) {
                            throw new DOMException('Aborted', 'AbortError')
                        }

                        return Array.isArray(results) ? results : []
                    },
                })
            }

            if (this.hasDynamicOptions && this.isPreloaded) {
                this.fetchDynamicOptions()
            }

            if (! this.hasClientSideOptionList && this.hasMissingSelectedLabels()) {
                this.resolveSelectedLabelsFromServer()
            }

            this.$watch('comboboxQuery', (query) => {
                if (this.hasDynamicSearchResults) {
                    this.scheduleAsyncSearch(query)
                }
            })
        },

        seedLabelRepository() {
            if (initialOptionLabel != null && initialOptionLabel !== '') {
                const value = this.comboboxSelectedValues[0]

                if (value !== undefined) {
                    this.storeLabelEntry(value, {
                        value,
                        label: String(initialOptionLabel),
                        triggerLabel: String(initialOptionLabel),
                    })
                }
            }

            if (Array.isArray(initialOptionLabels) && initialOptionLabels.length > 0) {
                for (const entry of initialOptionLabels) {
                    if (entry?.value === undefined) {
                        continue
                    }

                    this.storeLabelEntry(entry.value, entry)
                }
            }

            if (Array.isArray(this.initialSelectedUserEntries)) {
                for (const entry of this.initialSelectedUserEntries) {
                    if (entry?.value === undefined) {
                        continue
                    }

                    this.storeLabelEntry(entry.value, {
                        value: entry.value,
                        label: entry.user?.name ?? String(entry.value),
                        triggerLabel: entry.user?.name ?? String(entry.value),
                        user: entry.user,
                        fffClientRender: Boolean(entry.user),
                    })

                    if (entry.user && typeof this.storeUserInRepository === 'function') {
                        this.storeUserInRepository(entry.value, entry.user)
                    }
                }
            }

            this.populateLabelRepositoryFromOptions(this.options)
        },

        populateLabelRepositoryFromOptions(options) {
            const walk = (items) => {
                if (! Array.isArray(items)) {
                    return
                }

                for (const option of items) {
                    if (option?.options && Array.isArray(option.options)) {
                        walk(option.options)

                        continue
                    }

                    if (option?.value === undefined) {
                        continue
                    }

                    this.storeLabelEntry(option.value, option)

                    if (option.user && typeof this.storeUserInRepository === 'function') {
                        this.storeUserInRepository(option.value, option.user)
                    }
                }
            }

            walk(options)
        },

        storeLabelEntry(value, entry) {
            if (value === undefined || value === null || ! entry) {
                return
            }

            const key = String(value)

            this.labelRepository[key] = {
                ...entry,
                value: entry.value ?? value,
            }
        },

        labelEntry(value) {
            const key = String(value)

            return this.labelRepository[key]
                ?? this.optionRecord(value)
                ?? null
        },

        hasMissingSelectedLabels() {
            if (this.comboboxSelectedValues.length === 0) {
                return false
            }

            return this.comboboxSelectedValues.some((value) => {
                const entry = this.labelEntry(value)

                return entry == null || entry.label == null || entry.label === ''
            })
        },

        resolveLivewire() {
            if (this.$wire?.callSchemaComponentMethod) {
                return this.$wire
            }

            const host = this.$el?.closest('[wire\\:id], [wire-id]')

            if (host && typeof Livewire !== 'undefined' && typeof Livewire.find === 'function') {
                const wireId = host.getAttribute('wire:id') ?? host.getAttribute('wire-id')
                const component = wireId ? Livewire.find(wireId) : null

                if (component?.callSchemaComponentMethod) {
                    return component
                }
            }

            return this.$wire ?? null
        },

        async callSchemaMethod(method, params = {}) {
            if (! this.componentKey) {
                return null
            }

            const wire = this.resolveLivewire()

            if (wire?.callSchemaComponentMethod) {
                return await wire.callSchemaComponentMethod(this.componentKey, method, params)
            }

            if (typeof Livewire !== 'undefined' && Livewire.fireAction && wire?.__instance) {
                return await Livewire.fireAction(
                    wire.__instance,
                    'callSchemaComponentMethod',
                    [this.componentKey, method, params],
                    { async: true },
                )
            }

            return null
        },

        async fetchDynamicOptions() {
            if (! this.hasDynamicOptions || this.optionsLoading) {
                return false
            }

            this.optionsLoading = true

            try {
                const results = await this.callSchemaMethod('getOptionsForJs')

                if (results === null) {
                    return false
                }

                this.applyRemoteOptions(Array.isArray(results) ? results : [])
                this.dynamicOptionsLoaded = true

                return true
            } catch {
                this.applyRemoteOptions([])

                return false
            } finally {
                this.optionsLoading = false
            }
        },

        scheduleAsyncSearch(query) {
            clearTimeout(this._searchDebounceTimer)

            const rawQuery = String(query ?? '').trim()
            const trimmed = typeof this.comboboxEntityMentionSearchTerm === 'function'
                ? this.comboboxEntityMentionSearchTerm()
                : rawQuery

            if (trimmed.length < this.minSearchLength) {
                this.searchPending = false
                this.loadingMore = false
                this.searchResultsCursor = null
                this.searchResultsHasMore = false
                this._activeSearchQuery = ''
                this._relationshipAdapter?.cancel?.()
                this.applyRemoteOptions([])

                return
            }

            const debounceMs = this._relationshipAdapter?.debounceMs ?? this.searchDebounce

            this._searchDebounceTimer = setTimeout(() => {
                this.fetchSearchResults(trimmed, rawQuery, { append: false })
            }, debounceMs)
        },

        async fetchSearchResults(search, observabilityQuery = search, { append = false } = {}) {
            if (! this.hasDynamicSearchResults) {
                return
            }

            const requestId = append ? ++this._loadMoreRequestId : ++this._searchRequestId

            if (append) {
                if (! this.hasPaginatedSearchResults || ! this.searchResultsHasMore || this.loadingMore) {
                    return
                }

                this.loadingMore = true
            } else {
                this.searchPending = true
                this.searchResultsCursor = null
                this.searchResultsHasMore = false
                this._activeSearchQuery = String(search ?? '')
            }

            emitObservabilityEvent('select.search', {
                field: this.componentKey,
                query: String(observabilityQuery ?? search ?? ''),
                source: append ? 'livewire-load-more' : 'livewire',
                mention: typeof this.comboboxEntityMentionActive === 'function'
                    ? this.comboboxEntityMentionActive()
                    : false,
            })

            try {
                let normalized = []

                if (this.hasPaginatedSearchResults) {
                    const page = await this.callSchemaMethod('getSearchResultsPageForJs', {
                        search,
                        cursor: append ? this.searchResultsCursor : null,
                    })

                    if (requestId !== (append ? this._loadMoreRequestId : this._searchRequestId)) {
                        return
                    }

                    const payload = page && typeof page === 'object' ? page : { items: [], cursor: null, hasMore: false }
                    normalized = Array.isArray(payload.items) ? payload.items : []
                    this.searchResultsCursor = payload.cursor ?? null
                    this.searchResultsHasMore = Boolean(payload.hasMore)
                } else {
                    const results = this._relationshipAdapter
                        ? await this._relationshipAdapter.search(search, {
                            onWarning: (warning) => {
                                emitObservabilityEvent('select.search.warning', {
                                    field: this.componentKey,
                                    ...warning,
                                })
                            },
                        })
                        : await this.callSchemaMethod('getSearchResultsForJs', { search })

                    if (requestId !== this._searchRequestId) {
                        return
                    }

                    normalized = Array.isArray(results) ? results : []
                }

                this.populateLabelRepositoryFromOptions(normalized)

                if (append) {
                    this.appendRemoteOptions(normalized)
                } else {
                    this.applyRemoteOptions(normalized)
                }
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return
                }

                if (requestId === (append ? this._loadMoreRequestId : this._searchRequestId) && ! append) {
                    this.applyRemoteOptions([])
                    this.searchResultsCursor = null
                    this.searchResultsHasMore = false
                }
            } finally {
                if (append) {
                    if (requestId === this._loadMoreRequestId) {
                        this.loadingMore = false
                        this.$nextTick?.(() => this.observeHeadlessLoadMore?.())
                    }
                } else if (requestId === this._searchRequestId) {
                    this.searchPending = false
                    this.$nextTick?.(() => this.observeHeadlessLoadMore?.())
                }
            }
        },

        async loadMoreSearchResults() {
            const query = this._activeSearchQuery || String(this.comboboxQuery ?? '').trim()

            if (query.length < this.minSearchLength) {
                return
            }

            await this.fetchSearchResults(query, query, { append: true })
        },

        appendRemoteOptions(nextOptions) {
            const existing = Array.isArray(this.options) ? this.options.slice() : []
            const incoming = Array.isArray(nextOptions) ? nextOptions : []
            const seen = new Set(existing.map((option) => String(option?.value ?? option?.id ?? '')))

            for (const option of incoming) {
                const key = String(option?.value ?? option?.id ?? '')

                if (key === '' || seen.has(key)) {
                    continue
                }

                seen.add(key)
                existing.push(option)
            }

            this.applyRemoteOptions(existing)
        },

        resetPaginatedSearchState() {
            this.searchResultsCursor = null
            this.searchResultsHasMore = false
            this.loadingMore = false
            this._activeSearchQuery = ''
        },

        disconnectHeadlessLoadMoreObserver() {
            this._loadMoreObserver?.disconnect?.()
            this._loadMoreObserver = null
        },

        observeHeadlessLoadMore() {
            this.disconnectHeadlessLoadMoreObserver()

            if (! this.hasPaginatedSearchResults || ! this.searchResultsHasMore || this.loadingMore || this.searchPending) {
                return
            }

            const sentinel = this.$refs?.headlessLoadMoreSentinel
            const root = this.$refs?.headlessOptionsList

            if (! sentinel || ! root || typeof IntersectionObserver === 'undefined') {
                return
            }

            this._loadMoreObserver = new IntersectionObserver((entries) => {
                if (! entries.some((entry) => entry.isIntersecting)) {
                    return
                }

                this.loadMoreSearchResults()
            }, {
                root,
                rootMargin: '120px',
                threshold: 0,
            })

            this._loadMoreObserver.observe(sentinel)
        },

        shouldShowHeadlessLoadMoreIndicator() {
            return this.hasPaginatedSearchResults
                && this.loadingMore
                && this.shouldShowHeadlessDropdownOptions()
        },

        shouldShowHeadlessLoadMoreSentinel() {
            return this.hasPaginatedSearchResults
                && this.searchResultsHasMore
                && ! this.loadingMore
                && ! this.searchPending
                && this.shouldShowHeadlessDropdownOptions()
        },

        shouldShowHeadlessTriggerLoading() {
            if (this.isUserSelectField) {
                return false
            }

            return this.optionsLoading || this.searchPending
        },

        headlessLoadMoreLabel() {
            return this.loadingMoreMessage || this.loadingMessage
        },

        cancelRelationshipSearch() {
            this._relationshipAdapter?.cancel?.()
            clearTimeout(this._searchDebounceTimer)
            this.resetPaginatedSearchState()
            this.disconnectHeadlessLoadMoreObserver()
        },

        async resolveSelectedLabelsFromServer() {
            if (! this.componentKey || this.comboboxSelectedValues.length === 0) {
                return
            }

            try {
                if (multiple) {
                    const results = await this.callSchemaMethod('getOptionLabelsForJs')

                    if (Array.isArray(results)) {
                        this.populateLabelRepositoryFromOptions(results)
                    }

                    return
                }

                const label = await this.callSchemaMethod('getOptionLabel')

                if (label != null && label !== '') {
                    const value = this.comboboxSelectedValues[0]

                    this.storeLabelEntry(value, {
                        value,
                        label: String(label),
                        triggerLabel: String(label),
                    })
                }
            } catch {
                // Labels remain as raw values until the next successful fetch.
            }
        },

        applyRemoteOptions(nextOptions) {
            this.options = nextOptions
            this.flatOptions = flattenHeadlessOptions(nextOptions)
            this._engine?.setOptions(this.getEngineOptions())
            this._syncFromEngine()

            this.$nextTick(() => {
                this.markKnownOptionChecksVisible?.()
                this.scheduleMenuPositionAfterLayout?.()
            })
        },

        async onHeadlessMenuOpenedForLivewire() {
            if (this.hasDynamicOptions && ! this.dynamicOptionsLoaded) {
                const loaded = await this.fetchDynamicOptions()

                if (! loaded) {
                    this.dynamicOptionsLoaded = false
                }
            }

            if (
                this.hasDynamicSearchResults
                && this.isPreloaded
                && this.comboboxQuery.trim() === ''
                && this.flatOptions.length === 0
            ) {
                await this.fetchDynamicOptions()
            }

            if (
                this.hasPaginatedSearchResults
                && this.comboboxQuery.trim().length >= this.minSearchLength
                && this.flatOptions.length === 0
                && ! this.searchPending
            ) {
                await this.fetchSearchResults(this.comboboxQuery.trim(), this.comboboxQuery.trim(), { append: false })
            }

            if (this.hasMissingSelectedLabels()) {
                await this.resolveSelectedLabelsFromServer()
            }
        },

        headlessDropdownMessage() {
            if (this.isUserSelectField || this.shouldShowHeadlessSelectEmptyState()) {
                return ''
            }

            if (this.optionsLoading) {
                return this.loadingMessage
            }

            if (this.searchPending) {
                return this.searchingMessage
            }

            const query = String(this.comboboxQuery ?? '').trim()
            const total = this.comboboxFilteredOptions().meta.total

            if (this.hasDynamicSearchResults && query.length < this.minSearchLength) {
                return this.searchPrompt
            }

            if (total > 0) {
                return ''
            }

            if (this.hasDynamicSearchResults && query.length >= this.minSearchLength) {
                return this.noSearchResultsMessage
            }

            if (this.hasInitialNoOptionsMessage || this.hasDynamicOptions) {
                return this.noOptionsMessage
            }

            return this.noSearchResultsMessage
        },

        headlessSelectDropdownState() {
            if (this.isUserSelectField) {
                return null
            }

            if (this.optionsLoading) {
                return 'loading'
            }

            if (this.searchPending) {
                return 'searching'
            }

            if (this.comboboxOpen && this.hasDynamicOptions && ! this.dynamicOptionsLoaded) {
                return 'loading'
            }

            const query = String(this.comboboxQuery ?? '').trim()
            const visibleOptionCount = this.getEngineOptions().length

            if (this.hasDynamicSearchResults && query.length < this.minSearchLength) {
                return visibleOptionCount > 0 ? null : 'prompt'
            }

            if (visibleOptionCount > 0) {
                return null
            }

            if (this.hasDynamicSearchResults && query.length >= this.minSearchLength) {
                return 'search'
            }

            if (query.length > 0) {
                return 'search'
            }

            return 'options'
        },

        shouldShowHeadlessDropdownOptions() {
            if (this.isUserSelectField) {
                return ! this.shouldShowHeadlessUserSelectSkeleton()
                    && ! this.shouldShowHeadlessUserSelectEmptyState()
            }

            return ! this.shouldShowHeadlessSelectEmptyState()
        },

        shouldShowHeadlessSelectEmptyState() {
            const state = this.headlessSelectDropdownState()

            return state === 'prompt' || state === 'search' || state === 'options'
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

        headlessSelectSkeletonRows() {
            return [0, 1, 2]
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

            return hints.noOptionsAvailable ?? ''
        },

        shouldShowHeadlessDropdownMessage() {
            return this.headlessDropdownMessage() !== ''
        },

        serverSideFilterFn() {
            return () => true
        },
    }
}

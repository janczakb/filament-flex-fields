import { emitObservabilityEvent } from '../../core/observability.js'
import { createRelationshipSearchAdapter } from '../../core/combobox-engine.js'
import { findHeadlessOptionRecord, flattenHeadlessOptions, headlessOptionValue } from './headless-select-options.js'

/**
 * Livewire commit `succeed` often runs after the awaited schema call resolves.
 * A 0ms guard races that callback and re-enters fetchDynamicOptions forever
 * (Region dependsOn: Loading spinner resets many times per second).
 */
export const DYNAMIC_OPTIONS_FETCH_GUARD_MS = 150

/** Collapse Livewire remorph storms into a single soft refresh. */
export const DYNAMIC_OPTIONS_INVALIDATION_DEBOUNCE_MS = 75

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
    livewireId = null,
    statePath = null,
    optionsLimit = 50,
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
        livewireId,
        optionsLimit,

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
        _refreshOptionLabelListener: null,

        initLivewireIntegration() {
            this.seedLabelRepository()
            this.bindSelectedOptionLabelRefreshListener()
            this.bindDynamicOptionsInvalidation()

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

        bindDynamicOptionsInvalidation() {
            if (! this.hasDynamicOptions || this._dynamicOptionsInvalidationBound) {
                return
            }

            if (typeof Livewire === 'undefined' || typeof Livewire.hook !== 'function') {
                return
            }

            this._dynamicOptionsInvalidationBound = true

            // Parent ->live() / dependsOn updates remorph the Livewire component while
            // this shell stays wire:ignore. Invalidate the cached list — but never on
            // commits caused by our own getOptionsForJs fetch (that loops forever and
            // leaves the dropdown stuck flickering Loading…).
            this._dynamicOptionsCommitHook = Livewire.hook('commit', ({ component, succeed }) => {
                if (! this.hasDynamicOptions) {
                    return
                }

                if (livewireId && component?.id && component.id !== livewireId) {
                    return
                }

                succeed(() => {
                    // Always re-assert SSR handoff — parent live() remorphs can
                    // strip `.is-replaced` and freeze the trigger under CSS.
                    this.ensureHeadlessSsrHandoff?.()

                    if (this.shouldIgnoreDynamicOptionsCommitInvalidation()) {
                        return
                    }

                    this.invalidateDynamicOptionsCache({
                        // Keep painted options so the next open is soft (no hard
                        // Loading freeze while getOptionsForJs waits on Livewire).
                        clearStaleOptions: false,
                    })

                    if (this.comboboxOpen) {
                        // Parent ->live() changed while open — hard refresh, no stale list.
                        this.scheduleDynamicOptionsRefresh({ soft: false })
                    }
                    // Closed: do NOT prefetch. A background getOptionsForJs rides the
                    // same Livewire pipeline as Country ->live() and freezes sibling
                    // triggers under wire:loading / main-thread morph for seconds.
                })
            })
        },

        /**
         * @deprecated No-op. Background option prefetch blocked Region clicks via
         * Livewire loading; options load when the menu opens instead.
         */
        scheduleIdleDynamicOptionsPrefetch() {
            if (this._dynamicOptionsIdlePrefetchTimer) {
                clearTimeout(this._dynamicOptionsIdlePrefetchTimer)
                this._dynamicOptionsIdlePrefetchTimer = null
            }
        },

        /**
         * Mark the client option cache stale. When the menu is closed, drop the
         * painted list so a later open cannot flash the previous parent’s options
         * (Country USA → Poland) and never kick off a Livewire fetch in the
         * background — that blocks the trigger for seconds via wire:loading.
         */
        invalidateDynamicOptionsCache({ clearStaleOptions = false } = {}) {
            this.dynamicOptionsLoaded = false

            if (
                clearStaleOptions
                && Array.isArray(this.flatOptions)
                && this.flatOptions.length > 0
            ) {
                this.applyRemoteOptions([])
            }
        },

        shouldIgnoreDynamicOptionsCommitInvalidation() {
            if (
                this._suppressDynamicOptionsInvalidation
                || this.optionsLoading
                || this._dynamicOptionsFetchInFlight
            ) {
                return true
            }

            if ((this._skipDynamicOptionsInvalidations ?? 0) > 0) {
                this._skipDynamicOptionsInvalidations -= 1

                return true
            }

            return false
        },

        scheduleDynamicOptionsRefresh({ soft = true } = {}) {
            if (! this.hasDynamicOptions) {
                return
            }

            if (this._dynamicOptionsRefreshTimer) {
                clearTimeout(this._dynamicOptionsRefreshTimer)
            }

            this._dynamicOptionsRefreshTimer = setTimeout(() => {
                this._dynamicOptionsRefreshTimer = null

                if (
                    ! this.comboboxOpen
                    || this.optionsLoading
                    || this._dynamicOptionsFetchInFlight
                    || this._suppressDynamicOptionsInvalidation
                ) {
                    return
                }

                if (this.dynamicOptionsLoaded) {
                    return
                }

                this.fetchDynamicOptions({ soft })
            }, DYNAMIC_OPTIONS_INVALIDATION_DEBOUNCE_MS)
        },

        teardownDynamicOptionsInvalidation() {
            if (typeof this._dynamicOptionsCommitHook === 'function') {
                this._dynamicOptionsCommitHook()
                this._dynamicOptionsCommitHook = null
            }

            if (this._dynamicOptionsRefreshTimer) {
                clearTimeout(this._dynamicOptionsRefreshTimer)
                this._dynamicOptionsRefreshTimer = null
            }

            if (this._dynamicOptionsIdlePrefetchTimer) {
                clearTimeout(this._dynamicOptionsIdlePrefetchTimer)
                this._dynamicOptionsIdlePrefetchTimer = null
            }

            if (this._dynamicOptionsFetchGuardTimer) {
                clearTimeout(this._dynamicOptionsFetchGuardTimer)
                this._dynamicOptionsFetchGuardTimer = null
            }

            this._dynamicOptionsInvalidationBound = false
            this._suppressDynamicOptionsInvalidation = false
            this._skipDynamicOptionsInvalidations = 0
        },

        beginDynamicOptionsFetchGuard() {
            this._suppressDynamicOptionsInvalidation = true

            if (this._dynamicOptionsFetchGuardTimer) {
                clearTimeout(this._dynamicOptionsFetchGuardTimer)
                this._dynamicOptionsFetchGuardTimer = null
            }
        },

        endDynamicOptionsFetchGuard() {
            if (this._dynamicOptionsFetchGuardTimer) {
                clearTimeout(this._dynamicOptionsFetchGuardTimer)
            }

            // Skip the next commit succeed(s) from our own getOptionsForJs round-trip
            // even if they arrive after the timed suppress window.
            this._skipDynamicOptionsInvalidations = Math.max(
                this._skipDynamicOptionsInvalidations ?? 0,
                2,
            )

            this._dynamicOptionsFetchGuardTimer = setTimeout(() => {
                this._dynamicOptionsFetchGuardTimer = null
                this._suppressDynamicOptionsInvalidation = false
            }, DYNAMIC_OPTIONS_FETCH_GUARD_MS)
        },

        bindSelectedOptionLabelRefreshListener() {
            if (this._refreshOptionLabelListener || ! livewireId || ! statePath) {
                return
            }

            this._refreshOptionLabelListener = async (event) => {
                if (event?.detail?.livewireId !== livewireId || event?.detail?.statePath !== statePath) {
                    return
                }

                this.ensureHeadlessSsrHandoff?.()

                // Never start another Livewire round-trip while the menu is closed.
                // Parent Country ->live() already owns the request; concurrent
                // getOptionLabel / getOptionsForJs calls freeze Region clicks.
                if (! this.comboboxOpen) {
                    if (this.hasDynamicOptions) {
                        this.invalidateDynamicOptionsCache({ clearStaleOptions: false })
                    }

                    this._pendingSelectedLabelRefresh = true

                    return
                }

                await this.resolveSelectedLabelsFromServer()

                if (! this.hasDynamicOptions) {
                    return
                }

                this.invalidateDynamicOptionsCache({ clearStaleOptions: false })
                await this.fetchDynamicOptions({ soft: true })
            }

            window.addEventListener(
                'filament-forms::select.refreshSelectedOptionLabel',
                this._refreshOptionLabelListener,
            )
        },

        teardownSelectedOptionLabelRefreshListener() {
            if (! this._refreshOptionLabelListener) {
                return
            }

            window.removeEventListener(
                'filament-forms::select.refreshSelectedOptionLabel',
                this._refreshOptionLabelListener,
            )
            this._refreshOptionLabelListener = null
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

            if (this.labelRepository[key]) {
                return this.labelRepository[key]
            }

            const fromKnown = this._knownLabelEntries?.get(key)

            if (fromKnown) {
                return fromKnown
            }

            return findHeadlessOptionRecord(this.options, key)
                ?? findHeadlessOptionRecord(this.flatOptions, key)
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

        async fetchDynamicOptions({ soft = false } = {}) {
            if (! this.hasDynamicOptions) {
                return false
            }

            if (this.optionsLoading || this._dynamicOptionsFetchInFlight) {
                this._fetchDynamicOptionsQueued = true

                // Once anything requests a hard refresh, keep hard for the follow-up.
                if (! soft) {
                    this._fetchDynamicOptionsQueuedSoft = false
                } else if (this._fetchDynamicOptionsQueuedSoft === undefined) {
                    this._fetchDynamicOptionsQueuedSoft = true
                }

                return false
            }

            // Soft refresh keeps existing options painted so commit storms cannot
            // flicker Loading ↔ value. Hard loading only when the list is empty.
            const hasPaintedOptions = Array.isArray(this.flatOptions) && this.flatOptions.length > 0
            const useSoftLoading = soft || hasPaintedOptions

            this._dynamicOptionsFetchInFlight = true
            this.optionsLoading = ! useSoftLoading
            this.beginDynamicOptionsFetchGuard()

            try {
                const results = await this.callSchemaMethod('getOptionsForJs')

                if (results === null) {
                    this.applyRemoteOptions([])

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
                this._dynamicOptionsFetchInFlight = false
                this.endDynamicOptionsFetchGuard()

                if (this._fetchDynamicOptionsQueued) {
                    const queuedSoft = this._fetchDynamicOptionsQueuedSoft !== false
                    this._fetchDynamicOptionsQueued = false
                    this._fetchDynamicOptionsQueuedSoft = undefined
                    await this.fetchDynamicOptions({ soft: queuedSoft })
                }
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
                        this.$nextTick?.(() => {
                            this.observeHeadlessLoadMore?.()
                            this.syncDropdownOverflowChrome?.()
                        })
                    }
                } else if (requestId === this._searchRequestId) {
                    this.searchPending = false
                    this.$nextTick?.(() => {
                        this.observeHeadlessLoadMore?.()
                        this.syncDropdownOverflowChrome?.()
                    })
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
            const normalized = Array.isArray(nextOptions) ? nextOptions : []

            this.options = normalized
            this.flatOptions = flattenHeadlessOptions(normalized)
            this._engineOptionsFingerprint = this.fingerprintHeadlessOptions?.(this.flatOptions) ?? null
            this._virtualFlatRows = []
            this.virtualScrollTick = (this.virtualScrollTick ?? 0) + 1
            this._engine?.setOptions(this.flatOptions)

            if (this.hasDynamicOptions && ! this.hasDynamicSearchResults) {
                const allowed = new Set(this.flatOptions.map((option) => String(headlessOptionValue(option))))
                const nextSelected = this.comboboxSelectedValues.filter((value) => allowed.has(String(value)))

                if (nextSelected.length !== this.comboboxSelectedValues.length) {
                    this._engine?.setSelectedValues(nextSelected)
                }
            }

            this._syncFromEngine()

            this.$nextTick(() => {
                this.markKnownOptionChecksVisible?.()
                this.scheduleMenuPositionAfterLayout?.()
                this.syncDropdownOverflowChrome?.()
            })
        },

        async onHeadlessMenuOpenedForLivewire() {
            this.ensureHeadlessSsrHandoff?.()

            // Always re-fetch dynamic / dependsOn option closures — parent live()
            // updates must not leave a stale empty list behind wire:ignore.
            if (this.hasDynamicOptions) {
                this.dynamicOptionsLoaded = false
                const soft = Array.isArray(this.flatOptions) && this.flatOptions.length > 0
                const loaded = await this.fetchDynamicOptions({ soft })

                if (! loaded) {
                    this.dynamicOptionsLoaded = false
                }
            }

            if (
                this.hasDynamicSearchResults
                && this.isPreloaded
                && this.comboboxQuery.trim() === ''
                && this.flatOptions.length === 0
                && ! this.hasDynamicOptions
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

            if (this._pendingSelectedLabelRefresh || this.hasMissingSelectedLabels()) {
                this._pendingSelectedLabelRefresh = false
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

        shouldShowHeadlessDropdownMessage() {
            return this.headlessDropdownMessage() !== ''
        },

        serverSideFilterFn() {
            return () => true
        },
    }
}

import { emitObservabilityEvent } from '../../core/observability.js'

/**
 * Livewire-backed async search, pagination, and load-more for headless SelectField.
 * Spread into createHeadlessComboboxLivewireMixin — methods use Alpine `this`.
 */
export function createHeadlessComboboxLivewireSearchMixin() {
    return {
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
    }
}

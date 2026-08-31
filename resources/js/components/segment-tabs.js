import { createSegmentOverflowMixin } from './segment-scroll-shadow.js'

export default function segmentTabsSchemaComponent({
    activeTabIndex = 0,
    activeTabKey = null,
    isTabPersisted = false,
    isTabPersistedInQueryString = false,
    isTabPersistedFlag = false,
    livewireId,
    optionKeys = [],
    schemaKey,
    separators = false,
    tabPersistKey = null,
    tabQueryStringKey = null,
    overflowShell = true,
}) {
    const initialTab = activeTabKey ?? optionKeys[activeTabIndex] ?? optionKeys[0] ?? null

    return {
        ...createSegmentOverflowMixin(),
        tab:
            isTabPersisted && typeof Alpine !== 'undefined' && typeof Alpine.$persist === 'function'
                ? Alpine.$persist(initialTab).as(tabPersistKey)
                : initialTab,
        optionKeys,
        separators,
        overflowShell,
        indicatorStyle: '',
        indicatorAnimated: false,
        indicatorHydrated: false,
        resizeObserver: null,
        boundResetHandler: null,
        unsubscribeLivewireHook: null,

        normalize(value) {
            return value === null || value === undefined ? null : String(value)
        },

        isSelected(value) {
            return this.normalize(this.tab) === this.normalize(value)
        },

        select(value) {
            this.tab = value
            this.$nextTick(() => this.updateIndicator({ scrollIntoView: true, scrollSmooth: true }))
        },

        selectedIndex() {
            const current = this.normalize(this.tab)

            return this.optionKeys.findIndex((key) => this.normalize(key) === current)
        },

        showSeparator(separatorIndex) {
            if (! this.separators) {
                return false
            }

            const selectedIndex = this.selectedIndex()

            if (selectedIndex === -1) {
                return true
            }

            return separatorIndex !== selectedIndex - 1 && separatorIndex !== selectedIndex
        },

        separatorClass(separatorIndex) {
            return this.showSeparator(separatorIndex) ? '' : 'is-hidden'
        },

        updateIndicator(options = {}) {
            const track = this.$refs.track

            if (! track) {
                return
            }

            const selected = track.querySelector('[data-segment-selected=true]')

            if (! selected) {
                this.indicatorStyle = 'opacity: 0;'
                this.indicatorHydrated = false

                return
            }

            this.indicatorStyle =
                'width: ' + selected.offsetWidth + 'px;' +
                'height: ' + selected.offsetHeight + 'px;' +
                'transform: translate3d(' + selected.offsetLeft + 'px, ' + selected.offsetTop + 'px, 0);' +
                'opacity: 1;'
            this.indicatorHydrated = true

            if (this.overflowShell && options.scrollIntoView) {
                this.scrollSelectedSegmentTabIntoView(selected, options.scrollSmooth ?? false)
            }
        },

        enableIndicatorAnimation() {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.indicatorAnimated = true
                })
            })
        },

        updateQueryString() {
            if (! isTabPersistedInQueryString || ! tabQueryStringKey) {
                return
            }

            const url = new URL(window.location.href)
            url.searchParams.set(tabQueryStringKey, this.tab)
            history.replaceState(null, document.title, url.toString())
        },

        init() {
            if (isTabPersistedInQueryString && tabQueryStringKey) {
                const queryString = new URLSearchParams(window.location.search)
                const queryTab = queryString.get(tabQueryStringKey)

                if (queryTab && this.optionKeys.includes(queryTab)) {
                    this.tab = queryTab
                }
            }

            if (! this.tab || ! this.optionKeys.includes(this.tab)) {
                this.tab = this.optionKeys[activeTabIndex] ?? this.optionKeys[0] ?? null
            }

            this.$watch('tab', () => {
                this.updateQueryString()
                this.$nextTick(() => this.updateIndicator({ scrollIntoView: true, scrollSmooth: true }))
            })

            this.$nextTick(() => {
                this.positionInitialOverflowScroll()
                this.updateIndicator()
                this.bindSegmentOverflowScrollShadow()
                this.enableIndicatorAnimation()
            })

            if (typeof ResizeObserver !== 'undefined' && this.$refs.track) {
                this.resizeObserver = new ResizeObserver(() => {
                    this.updateIndicator()
                    this.updateSegmentScrollShadow()
                })
                this.resizeObserver.observe(this.$refs.track)
            }

            this.unsubscribeLivewireHook = Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(() => {
                    this.$nextTick(() => {
                        if (message.component.id !== livewireId) {
                            return
                        }

                        if (! this.optionKeys.includes(this.tab)) {
                            this.tab = this.optionKeys[activeTabIndex] ?? this.tab
                        }

                        this.updateIndicator()
                    })
                })
            })

            this.boundResetHandler = (event) => {
                if (
                    event.detail.livewireId !== livewireId ||
                    event.detail.schemaKey !== schemaKey ||
                    isTabPersistedFlag ||
                    isTabPersistedInQueryString
                ) {
                    return
                }

                this.$nextTick(() => {
                    this.tab = this.optionKeys[activeTabIndex] ?? this.tab
                    this.updateIndicator()
                })
            }

            window.addEventListener('reset-schema-component-state', this.boundResetHandler)
        },

        destroy() {
            this.unbindSegmentOverflowScrollShadow()
            this.unsubscribeLivewireHook?.()
            this.resizeObserver?.disconnect()

            if (this.boundResetHandler) {
                window.removeEventListener('reset-schema-component-state', this.boundResetHandler)
            }
        },
    }
}

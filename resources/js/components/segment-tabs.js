export default function segmentTabsSchemaComponent({
    activeTab,
    activeTabKey,
    isTabPersisted,
    isTabPersistedInQueryString,
    livewireId,
    schemaKey,
    tab,
    tabQueryStringKey,
}) {
    return {
        boundResetHandler: null,
        indicatorAnimated: false,
        indicatorStyle: '',
        resizeObserver: null,
        tab,
        unsubscribeLivewireHook: null,

        init() {
            const tabs = this.getTabs()
            const queryString = new URLSearchParams(window.location.search)

            if (
                isTabPersistedInQueryString &&
                queryString.has(tabQueryStringKey) &&
                tabs.includes(queryString.get(tabQueryStringKey))
            ) {
                this.tab = queryString.get(tabQueryStringKey)
            }

            if (! this.tab || ! tabs.includes(this.tab)) {
                if (activeTabKey && tabs.includes(activeTabKey)) {
                    this.tab = activeTabKey
                } else {
                    this.tab = tabs[activeTab - 1]
                }
            }

            this.$watch('tab', () => {
                this.updateQueryString()
                this.$nextTick(() => this.updateIndicator())
            })

            this.$nextTick(() => {
                this.updateIndicator()
                this.enableIndicatorAnimation()
            })

            if (typeof ResizeObserver !== 'undefined' && this.$refs.track) {
                this.resizeObserver = new ResizeObserver(() => this.updateIndicator())
                this.resizeObserver.observe(this.$refs.track)
            }

            this.unsubscribeLivewireHook = Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(() => {
                    this.$nextTick(() => {
                        if (message.component.id !== livewireId) {
                            return
                        }

                        const tabs = this.getTabs()

                        if (! tabs.includes(this.tab)) {
                            this.tab = tabs[activeTab - 1] ?? this.tab
                        }

                        this.updateIndicator()
                    })
                })
            })

            this.boundResetHandler = (event) => {
                if (
                    event.detail.livewireId !== livewireId ||
                    event.detail.schemaKey !== schemaKey ||
                    isTabPersisted ||
                    isTabPersistedInQueryString
                ) {
                    return
                }

                this.$nextTick(() => {
                    this.tab = this.getTabs()[activeTab - 1] ?? this.tab
                    this.updateIndicator()
                })
            }

            window.addEventListener('reset-schema-component-state', this.boundResetHandler)
        },

        destroy() {
            this.unsubscribeLivewireHook?.()
            this.resizeObserver?.disconnect()

            if (this.boundResetHandler) {
                window.removeEventListener('reset-schema-component-state', this.boundResetHandler)
            }
        },

        getTabs() {
            return this.$refs.tabsData
                ? JSON.parse(this.$refs.tabsData.value)
                : []
        },

        isSelected(value) {
            return String(this.tab) === String(value)
        },

        select(value) {
            this.tab = value
        },

        selectedIndex() {
            return this.getTabs().findIndex((key) => String(key) === String(this.tab))
        },

        showSeparator(separatorIndex, separators) {
            if (! separators) {
                return false
            }

            const selectedIndex = this.selectedIndex()

            if (selectedIndex === -1) {
                return true
            }

            return separatorIndex !== selectedIndex - 1 && separatorIndex !== selectedIndex
        },

        separatorClass(separatorIndex, separators) {
            return this.showSeparator(separatorIndex, separators) ? '' : 'is-hidden'
        },

        updateIndicator() {
            const track = this.$refs.track

            if (! track) {
                return
            }

            const selected = track.querySelector('[data-segment-selected=true]')

            if (! selected) {
                this.indicatorStyle = 'opacity: 0;'

                return
            }

            this.indicatorStyle =
                'width: ' + selected.offsetWidth + 'px;' +
                'height: ' + selected.offsetHeight + 'px;' +
                'transform: translate3d(' + selected.offsetLeft + 'px, ' + selected.offsetTop + 'px, 0);' +
                'opacity: 1;'
        },

        enableIndicatorAnimation() {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.indicatorAnimated = true
                })
            })
        },

        updateQueryString() {
            if (! isTabPersistedInQueryString) {
                return
            }

            const url = new URL(window.location.href)
            url.searchParams.set(tabQueryStringKey, this.tab)

            history.replaceState(null, document.title, url.toString())
        },
    }
}

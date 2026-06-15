/**
 * Windowed list rendering for long teleported dropdown menus.
 */
export const FFF_VIRTUAL_LIST_THRESHOLD = 50
export const FFF_VIRTUAL_LIST_ROW_HEIGHT = 40
export const FFF_VIRTUAL_LIST_OVERSCAN = 5

function resolveListItems(component, itemsKey) {
    const value = component[itemsKey]

    if (typeof value === 'function') {
        return value.call(component)
    }

    return value ?? []
}

export function createVirtualizedListMixin({
    itemsKey = 'filteredItems',
    scrollRef = 'virtualListScroll',
    itemHeight = FFF_VIRTUAL_LIST_ROW_HEIGHT,
    buffer = FFF_VIRTUAL_LIST_OVERSCAN,
    threshold = FFF_VIRTUAL_LIST_THRESHOLD,
} = {}) {
    return {
        virtualListScrollTop: 0,
        virtualListViewportHeight: 280,
        virtualListItemHeight: itemHeight,
        virtualListThreshold: threshold,
        virtualListScrollFrame: null,

        resolveVirtualListItems() {
            return resolveListItems(this, itemsKey)
        },

        usesVirtualList() {
            return this.resolveVirtualListItems().length > this.virtualListThreshold
        },

        countryListEntries() {
            const items = this.resolveVirtualListItems()

            if (! this.usesVirtualList()) {
                return items.map((item, index) => ({ item, index }))
            }

            return this.virtualVisibleItems()
        },

        virtualListTotalHeight() {
            return this.resolveVirtualListItems().length * itemHeight
        },

        virtualVisibleItems() {
            const items = this.resolveVirtualListItems()

            if (items.length === 0) {
                return []
            }

            const startIndex = Math.max(0, Math.floor(this.virtualListScrollTop / itemHeight) - buffer)
            const visibleCount = Math.ceil(this.virtualListViewportHeight / itemHeight) + (buffer * 2)
            const endIndex = Math.min(items.length, startIndex + visibleCount)

            return items.slice(startIndex, endIndex).map((item, offset) => ({
                item,
                index: startIndex + offset,
            }))
        },

        virtualSpacerTop() {
            if (! this.usesVirtualList()) {
                return 0
            }

            const items = this.resolveVirtualListItems()

            if (items.length === 0) {
                return 0
            }

            const startIndex = Math.max(0, Math.floor(this.virtualListScrollTop / itemHeight) - buffer)

            return startIndex * itemHeight
        },

        virtualSpacerBottom() {
            if (! this.usesVirtualList()) {
                return 0
            }

            const items = this.resolveVirtualListItems()

            if (items.length === 0) {
                return 0
            }

            const startIndex = Math.max(0, Math.floor(this.virtualListScrollTop / itemHeight) - buffer)
            const visibleCount = Math.ceil(this.virtualListViewportHeight / itemHeight) + (buffer * 2)
            const endIndex = Math.min(items.length, startIndex + visibleCount)

            return Math.max(0, (items.length - endIndex) * itemHeight)
        },

        onVirtualListScroll(event) {
            if (this.virtualListScrollFrame) {
                return
            }

            const scrollTop = event.target.scrollTop

            this.virtualListScrollFrame = requestAnimationFrame(() => {
                this.virtualListScrollTop = scrollTop
                this.virtualListScrollFrame = null
            })
        },

        measureVirtualListViewport() {
            const element = this.$refs[scrollRef]

            if (! element) {
                return
            }

            this.virtualListViewportHeight = element.clientHeight || 280
        },

        resetVirtualListScroll() {
            this.virtualListScrollTop = 0

            const element = this.$refs[scrollRef]

            if (element) {
                element.scrollTop = 0
            }
        },
    }
}

/**
 * Shared combobox-engine virtual window helpers for teleported overlay pickers.
 */
export function createOverlayVirtualListMixin({
    engineKey = '_overlayEngine',
    scrollTickKey = 'virtualScrollTick',
    rowHeight = 36,
    thresholdKey = 'overlayVirtualizeThreshold',
} = {}) {
    return {
        overlayVirtualizeThreshold: 100,
        [scrollTickKey]: 0,

        shouldOverlayVirtualize() {
            const engine = this[engineKey]

            if (! engine) {
                return false
            }

            const threshold = this[thresholdKey] ?? 100

            return engine.filteredOptions().meta.total >= threshold
        },

        onOverlayEngineScroll(event) {
            const engine = this[engineKey]

            if (! engine || ! this.shouldOverlayVirtualize?.()) {
                return
            }

            const scrollTop = event?.target?.scrollTop ?? 0
            const start = Math.floor(scrollTop / rowHeight)

            engine.setVirtualWindowStart(start)
            this[scrollTickKey] = (this[scrollTickKey] ?? 0) + 1
        },

        overlayEngineVirtualMeta() {
            void this[scrollTickKey]

            const engine = this[engineKey]

            if (! engine) {
                return { options: [], meta: { startIndex: 0, endIndex: 0, total: 0 } }
            }

            return engine.filteredOptions()
        },

        overlayVirtualTrackStyle(total, rowHeightPx = rowHeight) {
            return {
                minHeight: `${Math.max(total, 0) * rowHeightPx}px`,
            }
        },
    }
}

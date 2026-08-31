/**
 * Horizontal scroll-shadow state for segment overflow shells (fade masks + chevrons).
 */

export {
    positionSegmentOverflowScrollElement,
    scrollSegmentItemIntoView,
    updateHorizontalSegmentScrollShadow,
} from '../core/segment-overflow-position.js'

import {
    positionSegmentOverflowScrollElement,
    scrollSegmentItemIntoView,
    updateHorizontalSegmentScrollShadow,
} from '../core/segment-overflow-position.js'

export function scrollSegmentHorizontally(element, direction, ratio = 0.8) {
    if (! element) {
        return
    }

    const size = element.clientWidth
    const scrollSize = element.scrollWidth
    const maxScroll = Math.max(0, scrollSize - size)
    const isRTL = getComputedStyle(element).direction === 'rtl'
    const delta = direction * size * ratio * (isRTL ? -1 : 1)
    const current = element.scrollLeft
    const next = Math.min(isRTL ? 0 : maxScroll, Math.max(isRTL ? -maxScroll : 0, current + delta))

    if (next === current) {
        return
    }

    element.scrollTo({
        left: next,
        behavior: 'smooth',
    })
}

export function createSegmentOverflowMixin() {
    return {
        segmentCanScrollBefore: false,
        segmentCanScrollAfter: false,
        segmentOverflowInitialScrollDone: false,
        segmentOverflowInteractive: false,
        segmentScrollShadowRaf: null,
        segmentScrollShadowObserver: null,
        segmentScrollShadowHandler: null,

        getSegmentScrollElement() {
            return this.$refs.scrollShadow ?? null
        },

        updateSegmentScrollShadow(immediate = false) {
            const element = this.getSegmentScrollElement()

            if (! element) {
                this.segmentCanScrollBefore = false
                this.segmentCanScrollAfter = false

                return
            }

            const apply = () => {
                const { canScrollBefore, canScrollAfter } = updateHorizontalSegmentScrollShadow(element)

                this.segmentCanScrollBefore = canScrollBefore
                this.segmentCanScrollAfter = canScrollAfter
                this.segmentOverflowInteractive = true
            }

            if (immediate) {
                if (this.segmentScrollShadowRaf !== null) {
                    cancelAnimationFrame(this.segmentScrollShadowRaf)
                    this.segmentScrollShadowRaf = null
                }

                apply()

                return
            }

            if (this.segmentScrollShadowRaf !== null) {
                cancelAnimationFrame(this.segmentScrollShadowRaf)
            }

            this.segmentScrollShadowRaf = requestAnimationFrame(() => {
                this.segmentScrollShadowRaf = null
                apply()
            })
        },

        scrollSegmentOverflowBy(direction) {
            scrollSegmentHorizontally(this.getSegmentScrollElement(), direction)
        },

        bindSegmentOverflowScrollShadow() {
            const element = this.getSegmentScrollElement()

            if (! element) {
                return
            }

            this.segmentScrollShadowHandler = () => this.updateSegmentScrollShadow()
            element.addEventListener('scroll', this.segmentScrollShadowHandler, { passive: true })

            if (typeof ResizeObserver !== 'undefined') {
                this.segmentScrollShadowObserver = new ResizeObserver(() => this.updateSegmentScrollShadow())
                this.segmentScrollShadowObserver.observe(element)
            }

            this.updateSegmentScrollShadow(true)
        },

        unbindSegmentOverflowScrollShadow() {
            const element = this.getSegmentScrollElement()

            if (element && this.segmentScrollShadowHandler) {
                element.removeEventListener('scroll', this.segmentScrollShadowHandler)
            }

            this.segmentScrollShadowObserver?.disconnect()
            this.segmentScrollShadowObserver = null
            this.segmentScrollShadowHandler = null

            if (this.segmentScrollShadowRaf !== null) {
                cancelAnimationFrame(this.segmentScrollShadowRaf)
                this.segmentScrollShadowRaf = null
            }
        },

        scrollSelectedSegmentTabIntoView(selected, smooth = false) {
            if (! this.segmentOverflowInitialScrollDone) {
                return
            }

            scrollSegmentItemIntoView(
                this.getSegmentScrollElement(),
                selected,
                smooth,
            )
        },

        positionInitialOverflowScroll() {
            if (! this.overflowShell || this.segmentOverflowInitialScrollDone) {
                return
            }

            const scrollElement = this.getSegmentScrollElement()

            if (scrollElement?.dataset.ssrScrollPositioned === 'true') {
                this.segmentOverflowInitialScrollDone = true
                this.updateSegmentScrollShadow(true)

                return
            }

            const track = this.$refs.track

            if (! scrollElement || ! track) {
                this.$nextTick(() => this.positionInitialOverflowScroll())

                return
            }

            positionSegmentOverflowScrollElement(scrollElement)
            this.segmentOverflowInitialScrollDone = true
            this.updateSegmentScrollShadow(true)
        },
    }
}

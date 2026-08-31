/**
 * Blocking IIFE: positions segment overflow scroll before Alpine hydrates.
 * Bundled to resources/dist/core/segment-overflow-ssr.js (no ESM/defer).
 */

import {
    positionSegmentOverflowScrollElement,
    SEGMENT_OVERFLOW_SELECTOR,
} from './segment-overflow-position.js'

const scrollElement = document.currentScript?.parentElement

if (scrollElement?.matches?.(SEGMENT_OVERFLOW_SELECTOR)) {
    positionSegmentOverflowScrollElement(scrollElement)
}

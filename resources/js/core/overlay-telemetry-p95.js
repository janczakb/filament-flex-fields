/** @type {number[]} */
const openLatencySamples = []

const MAX_SAMPLES = 256

/**
 * @param {number} latencyMs
 */
export function recordOverlayOpenLatency(latencyMs) {
    if (! Number.isFinite(latencyMs) || latencyMs < 0) {
        return
    }

    openLatencySamples.push(latencyMs)

    if (openLatencySamples.length > MAX_SAMPLES) {
        openLatencySamples.shift()
    }
}

/**
 * @returns {number | null}
 */
export function overlayOpenLatencyP95() {
    if (openLatencySamples.length === 0) {
        return null
    }

    const sorted = [...openLatencySamples].sort((a, b) => a - b)
    const index = Math.min(sorted.length - 1, Math.ceil(sorted.length * 0.95) - 1)

    return sorted[Math.max(0, index)]
}

/**
 * @returns {{ count: number, p95: number | null, last: number | null }}
 */
export function overlayOpenLatencySummary() {
    return {
        count: openLatencySamples.length,
        p95: overlayOpenLatencyP95(),
        last: openLatencySamples.at(-1) ?? null,
    }
}

export function resetOverlayOpenLatencySamples() {
    openLatencySamples.length = 0
}

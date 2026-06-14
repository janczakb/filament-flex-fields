export function cssVarPx(element, varName, fallback = 0) {
    if (! element) {
        return fallback
    }

    const raw = getComputedStyle(element).getPropertyValue(varName).trim()

    if (! raw) {
        return fallback
    }

    if (raw.endsWith('px')) {
        const value = parseFloat(raw)

        return Number.isFinite(value) ? value : fallback
    }

    if (raw.endsWith('rem')) {
        const rootSize = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16
        const value = parseFloat(raw) * rootSize

        return Number.isFinite(value) ? value : fallback
    }

    const value = parseFloat(raw)

    return Number.isFinite(value) ? value : fallback
}

export function resolveBarCount(width, { idealBarWidthPx, gapPx, minBars = 8, maxBars = 128 }) {
    if (width <= 0) {
        return minBars
    }

    const count = Math.floor((width + gapPx) / (idealBarWidthPx + gapPx))

    return Math.max(minBars, Math.min(maxBars, count))
}

export function resamplePeaks(source, targetCount, { minPeak = 0 } = {}) {
    if (targetCount <= 0) {
        return []
    }

    if (! source?.length) {
        return Array.from({ length: targetCount }, () => minPeak)
    }

    if (targetCount === source.length) {
        return [...source]
    }

    const sourceCount = source.length
    const result = []

    for (let index = 0; index < targetCount; index++) {
        const start = Math.floor((index * sourceCount) / targetCount)
        const end = Math.max(start + 1, Math.floor(((index + 1) * sourceCount) / targetCount))
        let peak = minPeak

        for (let sample = start; sample < end; sample++) {
            peak = Math.max(peak, source[sample])
        }

        result.push(peak)
    }

    return result
}

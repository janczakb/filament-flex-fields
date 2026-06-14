export const AUDIO_WAVEFORM_SAMPLE_COUNT = 64

const waveformCache = new Map()

export function hashFingerprint(value) {
    let hash = 2166136261 >>> 0

    for (let index = 0; index < value.length; index++) {
        hash ^= value.charCodeAt(index)
        hash = Math.imul(hash, 16777619) >>> 0
    }

    return hash >>> 0
}

function makeRandom(seed) {
    let state = seed >>> 0

    return () => {
        state = (Math.imul(state, 1664525) + 1013904223) >>> 0

        return state / 4294967296
    }
}

export function smoothPeaks(peaks, passes = 2) {
    let result = [...peaks]

    for (let pass = 0; pass < passes; pass++) {
        const next = []

        for (let index = 0; index < result.length; index++) {
            const previous = result[index - 1] ?? result[index]
            const current = result[index]
            const following = result[index + 1] ?? result[index]

            next.push((previous + current + following) / 3)
        }

        result = next
    }

    return result
}

export function normalizePeaks(peaks, minPeak = 8, maxPeak = 100) {
    if (! peaks.length) {
        return []
    }

    const maximum = Math.max(...peaks)

    if (maximum <= 0) {
        return peaks.map(() => minPeak)
    }

    const range = Math.max(1, maxPeak - minPeak)

    return peaks.map((peak) => Math.round(minPeak + ((peak / maximum) * range)))
}

export function placeholderWaveform(sampleCount = AUDIO_WAVEFORM_SAMPLE_COUNT) {
    return Array.from({ length: sampleCount }, () => 12)
}

export function generateWaveformFromFingerprint(fingerprint, sampleCount = AUDIO_WAVEFORM_SAMPLE_COUNT) {
    if (! fingerprint) {
        return placeholderWaveform(sampleCount)
    }

    const seed = hashFingerprint(fingerprint)
    const random = makeRandom(seed)

    const f1 = 2 + (seed % 7)
    const f2 = 5 + ((seed >> 8) % 11)
    const f3 = 12 + ((seed >> 16) % 15)
    const phase1 = random() * Math.PI * 2
    const phase2 = random() * Math.PI * 2

    const peaks = []

    for (let index = 0; index < sampleCount; index++) {
        const t = index / sampleCount
        const envelope = 0.55 + (0.45 * Math.sin(t * Math.PI))
        const harmonicOne = Math.abs(Math.sin(t * Math.PI * f1 + phase1))
        const harmonicTwo = Math.abs(Math.sin(t * Math.PI * f2 + phase2)) * 0.7
        const harmonicThree = Math.abs(Math.sin(t * Math.PI * f3)) * 0.4
        const noise = 0.15 * random()

        peaks.push(envelope * (harmonicOne + harmonicTwo + harmonicThree + noise))
    }

    return normalizePeaks(smoothPeaks(peaks, 2))
}

export function extractPeaksFromBuffer(audioBuffer, sampleCount = AUDIO_WAVEFORM_SAMPLE_COUNT) {
    const channel = audioBuffer.getChannelData(0)
    const blockSize = Math.max(1, Math.floor(channel.length / sampleCount))
    const peaks = []

    for (let index = 0; index < sampleCount; index++) {
        const start = index * blockSize
        const end = Math.min(start + blockSize, channel.length)
        let sumSquares = 0
        let count = 0

        for (let sample = start; sample < end; sample++) {
            const value = channel[sample] ?? 0

            sumSquares += value * value
            count++
        }

        peaks.push(count > 0 ? Math.sqrt(sumSquares / count) : 0)
    }

    return normalizePeaks(peaks)
}

export function getCachedWaveform(src) {
    return waveformCache.get(src) ?? null
}

export function cacheWaveform(src, peaks) {
    if (! src || ! peaks?.length) {
        return
    }

    waveformCache.set(src, peaks)
}

export async function extractWaveformFromUrl(src, sampleCount = AUDIO_WAVEFORM_SAMPLE_COUNT) {
    if (! src) {
        return null
    }

    const cached = getCachedWaveform(src)

    if (cached) {
        return cached
    }

    try {
        const response = await fetch(src)

        if (! response.ok) {
            return null
        }

        const arrayBuffer = await response.arrayBuffer()
        const AudioContextClass = window.AudioContext || window.webkitAudioContext

        if (! AudioContextClass) {
            return null
        }

        const context = new AudioContextClass()
        const audioBuffer = await context.decodeAudioData(arrayBuffer.slice(0))

        await context.close()

        const peaks = extractPeaksFromBuffer(audioBuffer, sampleCount)

        cacheWaveform(src, peaks)

        return peaks
    } catch {
        return null
    }
}

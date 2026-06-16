export const BARCODE_FORMATS = {
    qr: 'qr',
    ean_13: 'ean_13',
    ean_8: 'ean_8',
    upc_a: 'upc_a',
    upc_e: 'upc_e',
    code_128: 'code_128',
    code_39: 'code_39',
    itf: 'itf',
    pdf417: 'pdf417',
    data_matrix: 'data_matrix',
}

let cachedBeepAudio = null
let sharedBeepAudioContext = null

function resolveAudioContextClass() {
    if (typeof window === 'undefined') {
        return null
    }

    return window.AudioContext || window.webkitAudioContext || null
}

export function usesTransientScanBeep() {
    if (typeof navigator === 'undefined') {
        return false
    }

    const userAgent = navigator.userAgent ?? ''

    return /Android|iPhone|iPad|iPod/i.test(userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
}

function ensureBeepAudioElement(beepUrl) {
    if (! beepUrl || usesTransientScanBeep()) {
        return null
    }

    if (! cachedBeepAudio || cachedBeepAudio.src !== beepUrl) {
        cachedBeepAudio = new Audio(beepUrl)
        cachedBeepAudio.preload = 'auto'
    }

    return cachedBeepAudio
}

async function unlockHtmlAudioElement(audio) {
    if (! audio) {
        return
    }

    const wasMuted = audio.muted
    audio.muted = true

    try {
        await audio.play()
        audio.pause()
        audio.currentTime = 0
    } finally {
        audio.muted = wasMuted
    }
}

async function ensureSharedBeepAudioContext() {
    const AudioContextClass = resolveAudioContextClass()

    if (! AudioContextClass) {
        return null
    }

    if (! sharedBeepAudioContext) {
        sharedBeepAudioContext = new AudioContextClass()
    }

    if (sharedBeepAudioContext.state === 'suspended') {
        await sharedBeepAudioContext.resume()
    }

    return sharedBeepAudioContext
}

export async function prepareScanBeepAudio(beepUrl = null) {
    if (typeof window === 'undefined') {
        return
    }

    await ensureSharedBeepAudioContext()

    if (usesTransientScanBeep()) {
        return
    }

    const audio = ensureBeepAudioElement(beepUrl)

    if (! audio) {
        return
    }

    try {
        await unlockHtmlAudioElement(audio)
    } catch {
        // Ignore — playScanBeep will retry or use the synthesized fallback.
    }
}

async function playTransientScanBeep(context) {
    const startedAt = context.currentTime
    const gain = context.createGain()

    gain.connect(context.destination)
    gain.gain.setValueAtTime(0.0001, startedAt)
    gain.gain.exponentialRampToValueAtTime(0.07, startedAt + 0.012)
    gain.gain.exponentialRampToValueAtTime(0.0001, startedAt + 0.16)

    const tone = context.createOscillator()
    tone.type = 'sine'
    tone.frequency.setValueAtTime(880, startedAt)
    tone.frequency.setValueAtTime(988, startedAt + 0.05)
    tone.connect(gain)
    tone.start(startedAt)
    tone.stop(startedAt + 0.17)
}

async function playFallbackBeep() {
    const context = await ensureSharedBeepAudioContext()

    if (! context) {
        return
    }

    await playTransientScanBeep(context)
}

export async function playScanBeep(beepUrl = null) {
    if (typeof window === 'undefined') {
        return
    }

    await ensureSharedBeepAudioContext()

    if (usesTransientScanBeep()) {
        await playFallbackBeep()

        return
    }

    const audio = ensureBeepAudioElement(beepUrl)

    if (audio) {
        try {
            audio.muted = false
            audio.currentTime = 0
            await audio.play()

            return
        } catch {
            // Fall through to synthesized beep when autoplay blocks or file missing.
        }
    }

    await playFallbackBeep()
}

export function matchesBarcodeFormat(value, format) {
    const normalized = String(value ?? '').trim()

    if (normalized === '') {
        return false
    }

    switch (format) {
    case BARCODE_FORMATS.qr:
        return normalized.length >= 1
    case BARCODE_FORMATS.ean_13:
        return /^\d{13}$/.test(normalized)
    case BARCODE_FORMATS.ean_8:
        return /^\d{8}$/.test(normalized)
    case BARCODE_FORMATS.upc_a:
        return /^\d{12}$/.test(normalized)
    case BARCODE_FORMATS.upc_e:
        return /^\d{6,8}$/.test(normalized)
    case BARCODE_FORMATS.code_128:
        return /^[\x20-\x7E]+$/.test(normalized)
    case BARCODE_FORMATS.code_39:
        return /^[0-9A-Z\-. $/+%]+$/.test(normalized)
    case BARCODE_FORMATS.itf:
        return /^\d+$/.test(normalized) && normalized.length >= 4 && normalized.length % 2 === 0
    case BARCODE_FORMATS.pdf417:
        return normalized.length >= 4
    case BARCODE_FORMATS.data_matrix:
        return normalized.length >= 1
    default:
        return false
    }
}

export function detectBarcodeFormat(value) {
    const normalized = String(value ?? '').trim()

    if (normalized === '') {
        return null
    }

    const ordered = [
        BARCODE_FORMATS.ean_13,
        BARCODE_FORMATS.ean_8,
        BARCODE_FORMATS.upc_a,
        BARCODE_FORMATS.upc_e,
        BARCODE_FORMATS.itf,
        BARCODE_FORMATS.code_39,
        BARCODE_FORMATS.code_128,
        BARCODE_FORMATS.pdf417,
        BARCODE_FORMATS.data_matrix,
        BARCODE_FORMATS.qr,
    ]

    return ordered.find((format) => matchesBarcodeFormat(normalized, format)) ?? null
}

export function supportsBarcodeChecksum(format) {
    return [
        BARCODE_FORMATS.ean_13,
        BARCODE_FORMATS.ean_8,
        BARCODE_FORMATS.upc_a,
        BARCODE_FORMATS.upc_e,
    ].includes(format)
}

export function validateModulo10Checksum(digits, expectedLength) {
    if (digits.length !== expectedLength || ! /^\d+$/.test(digits)) {
        return false
    }

    let sum = 0

    for (let index = 0; index < expectedLength - 1; index += 1) {
        const weight = (expectedLength - 1 - index) % 2 === 0 ? 3 : 1
        sum += Number(digits[index]) * weight
    }

    const checkDigit = (10 - (sum % 10)) % 10

    return checkDigit === Number(digits[expectedLength - 1])
}

export function isBarcodeChecksumValid(value, format) {
    const digits = String(value ?? '').replace(/\D/g, '')

    switch (format) {
    case BARCODE_FORMATS.ean_13:
        return validateModulo10Checksum(digits, 13)
    case BARCODE_FORMATS.ean_8:
        return validateModulo10Checksum(digits, 8)
    case BARCODE_FORMATS.upc_a:
        return validateModulo10Checksum(digits, 12)
    case BARCODE_FORMATS.upc_e:
        return digits.length >= 6 && validateModulo10Checksum(digits.padStart(8, '0'), 8)
    default:
        return true
    }
}

export function validateBarcodeValue(value, {
    supportedFormats = Object.values(BARCODE_FORMATS),
    validateChecksum = false,
    labels = {},
} = {}) {
    const normalized = String(value ?? '').trim()

    if (normalized === '') {
        return null
    }

    const formats = supportedFormats.length > 0 ? supportedFormats : Object.values(BARCODE_FORMATS)
    const matched = formats.find((format) => matchesBarcodeFormat(normalized, format))

    if (! matched) {
        return labels.unrecognized ?? 'Unrecognized barcode format.'
    }

    if (validateChecksum && supportsBarcodeChecksum(matched) && ! isBarcodeChecksumValid(normalized, matched)) {
        return labels.checksum ?? 'Invalid barcode checksum.'
    }

    return null
}

export function prefersReducedMotion() {
    return typeof window !== 'undefined'
        && window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches === true
}

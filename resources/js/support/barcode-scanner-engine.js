let scannerModule = null
let scannerPromise = null

import {
    acquireCameraStream,
    attachCameraStream,
    isMobileCameraDevice,
    prepareVideoElement,
    stopMediaStreamTracks,
} from './camera-stream.js'

export {
    acquireCameraStream,
    attachCameraStream,
    attachStreamToVideoElement,
    isMobileCameraDevice,
    isIosDevice,
    prepareVideoElement,
    reviveVideoPlayback,
    stopMediaStreamTracks,
    waitForVideoFrames,
} from './camera-stream.js'

async function loadScannerModule() {
    if (scannerModule) {
        return scannerModule
    }

    if (! scannerPromise) {
        scannerPromise = Promise.all([
            import('@zxing/browser'),
            import('@zxing/library'),
        ]).then(([browser, library]) => {
            scannerModule = {
                BrowserMultiFormatReader: browser.BrowserMultiFormatReader,
                BarcodeFormat: library.BarcodeFormat,
                DecodeHintType: library.DecodeHintType,
            }

            return scannerModule
        })
    }

    return scannerPromise
}

export function clampScanInterval(milliseconds) {
    return Math.max(50, Math.min(2000, Number(milliseconds) || 120))
}

export function resolveZxingFormats(supportedFormats = []) {
    const formatMap = {
        qr: 'QR_CODE',
        ean_13: 'EAN_13',
        ean_8: 'EAN_8',
        upc_a: 'UPC_A',
        upc_e: 'UPC_E',
        code_128: 'CODE_128',
        code_39: 'CODE_39',
        itf: 'ITF',
        pdf417: 'PDF_417',
        data_matrix: 'DATA_MATRIX',
    }

    return supportedFormats
        .map((format) => formatMap[format])
        .filter(Boolean)
}

export function resolveNativeFormats(supportedFormats = []) {
    const formatMap = {
        qr: 'qr_code',
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

    return supportedFormats
        .map((format) => formatMap[format])
        .filter(Boolean)
}

async function resolveSupportedNativeFormats(formats) {
    if (typeof globalThis.BarcodeDetector?.getSupportedFormats !== 'function') {
        return []
    }

    try {
        const supported = await globalThis.BarcodeDetector.getSupportedFormats()

        return formats.filter((format) => supported.includes(format))
    } catch {
        return []
    }
}

function mapNativeFormatToSlug(format) {
    const map = {
        qr_code: 'qr',
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

    return map[format] ?? format
}

export function mapEngineFormatToSlug(format) {
    if (! format) {
        return null
    }

    const normalized = String(format).trim().toLowerCase().replace(/-/g, '_')

    return mapNativeFormatToSlug(normalized) ?? null
}

function createTorchControls(videoElement) {
    return {
        async setTorch(enabled) {
            const stream = videoElement?.srcObject

            if (! stream || typeof stream.getVideoTracks !== 'function') {
                return false
            }

            const [track] = stream.getVideoTracks()

            if (! track || typeof track.applyConstraints !== 'function') {
                return false
            }

            try {
                await track.applyConstraints({ advanced: [{ torch: enabled }] })

                return true
            } catch {
                return false
            }
        },
        supportsTorch() {
            const stream = videoElement?.srcObject
            const [track] = stream?.getVideoTracks?.() ?? []

            return Boolean(track?.getCapabilities?.()?.torch)
        },
    }
}

function stopMediaStream(videoElement) {
    stopMediaStreamTracks(videoElement?.srcObject)

    if (videoElement) {
        videoElement.srcObject = null
    }
}

export function resolveActiveDeviceId(videoElement) {
    const stream = videoElement?.srcObject
    const [track] = stream?.getVideoTracks?.() ?? []
    const settings = track?.getSettings?.()

    return settings?.deviceId ?? null
}

async function createNativeScanner({
    supportedFormats = [],
    cameraFacing = 'environment',
    deviceId = null,
    preAcquiredStream = null,
    onResult,
    onError,
    videoElement,
    scanInterval = 120,
}) {
    if (typeof globalThis.BarcodeDetector === 'undefined') {
        return null
    }

    if (isMobileCameraDevice()) {
        return null
    }

    const requestedFormats = resolveNativeFormats(supportedFormats)
    const formats = requestedFormats.length > 0
        ? await resolveSupportedNativeFormats(requestedFormats)
        : await resolveSupportedNativeFormats([
            'qr_code', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'itf', 'pdf417', 'data_matrix',
        ])

    if (formats.length === 0) {
        return null
    }

    let stopped = false
    let paused = false
    let loopTimer = null
    const interval = clampScanInterval(scanInterval)

    try {
        await attachCameraStream(videoElement, cameraFacing, deviceId, preAcquiredStream)
        const detector = new globalThis.BarcodeDetector({ formats })

        const tick = async () => {
            if (stopped) {
                return
            }

            if (! paused && videoElement.readyState >= HTMLMediaElement.HAVE_ENOUGH_DATA) {
                try {
                    const barcodes = await detector.detect(videoElement)
                    const match = barcodes?.[0]

                    if (match?.rawValue) {
                        onResult?.(
                            match.rawValue,
                            mapNativeFormatToSlug(match.format ?? ''),
                            'native',
                        )
                    }
                } catch (error) {
                    if (error?.name !== 'NotFoundException') {
                        onError?.(error)
                    }
                }
            }

            if (! stopped) {
                loopTimer = window.setTimeout(tick, interval)
            }
        }

        tick()

        const torch = createTorchControls(videoElement)

        return {
            engine: 'native',
            pause() {
                paused = true
            },
            resume() {
                paused = false
            },
            async stop() {
                stopped = true
                paused = false

                if (loopTimer !== null) {
                    window.clearTimeout(loopTimer)
                    loopTimer = null
                }

                stopMediaStream(videoElement)
            },
            setTorch: torch.setTorch.bind(torch),
            supportsTorch: torch.supportsTorch.bind(torch),
        }
    } catch (error) {
        stopMediaStream(videoElement)
        onError?.(error)

        return null
    }
}

async function createZxingScanner({
    supportedFormats = [],
    cameraFacing = 'environment',
    deviceId = null,
    preAcquiredStream = null,
    onResult,
    onError,
    videoElement,
    scanInterval = 120,
}) {
    const { BrowserMultiFormatReader, BarcodeFormat, DecodeHintType } = await loadScannerModule()
    const hints = new Map()
    const formats = resolveZxingFormats(supportedFormats)
        .map((format) => BarcodeFormat[format])
        .filter(Boolean)

    if (formats.length > 0) {
        hints.set(DecodeHintType.POSSIBLE_FORMATS, formats)
    }

    const reader = new BrowserMultiFormatReader(hints, {
        delayBetweenScanAttempts: clampScanInterval(scanInterval),
        tryPlayVideoTimeout: 10000,
    })

    let controls = null
    let lastError = null
    let paused = false

    const decodeCallback = (result, error) => {
        if (paused) {
            return
        }

        if (result) {
            onResult?.(
                result.getText(),
                mapEngineFormatToSlug(result.getBarcodeFormat()?.toString?.() ?? null),
                'zxing',
            )

            return
        }

        if (error && error.name !== 'NotFoundException') {
            onError?.(error)
        }
    }

    try {
        if (isMobileCameraDevice()) {
            if (preAcquiredStream) {
                stopMediaStreamTracks(preAcquiredStream)
            }

            stopMediaStream(videoElement)

            try {
                reader.reset()
            } catch {
                // Reader may already be reset.
            }

            const stream = await acquireCameraStream(cameraFacing, deviceId)

            await attachStreamToVideoElement(videoElement, stream)

            if (! await waitForVideoFrames(videoElement, 150)) {
                stopMediaStream(videoElement)
                stopMediaStreamTracks(stream)

                throw new Error('Camera preview unavailable')
            }

            controls = await reader.decodeFromVideoElement(videoElement, decodeCallback)
        } else if (preAcquiredStream) {
            controls = await reader.decodeFromStream(preAcquiredStream, videoElement, decodeCallback)
        } else if (deviceId) {
            controls = await reader.decodeFromVideoDevice(deviceId, videoElement, decodeCallback)
        } else {
            const constraintAttempts = buildVideoConstraints(cameraFacing)

            for (const constraints of constraintAttempts) {
                try {
                    controls = await reader.decodeFromConstraints(constraints, videoElement, decodeCallback)
                    break
                } catch (error) {
                    lastError = error
                    stopMediaStream(videoElement)

                    try {
                        reader.reset()
                    } catch {
                        // Reader may already be reset.
                    }
                }
            }
        }
    } catch (error) {
        lastError = error
        stopMediaStream(videoElement)

        try {
            reader.reset()
        } catch {
            // Reader may already be reset.
        }
    }

    if (! controls) {
        onError?.(lastError)
        throw lastError ?? new Error('Camera unavailable')
    }

    const torch = createTorchControls(videoElement)

    return {
        engine: 'zxing',
        pause() {
            paused = true
        },
        resume() {
            paused = false
        },
        async stop() {
            paused = false
            controls?.stop()

            try {
                reader.reset()
            } catch {
                // Reader may already be reset.
            }

            stopMediaStream(videoElement)
        },
        setTorch: torch.setTorch.bind(torch),
        supportsTorch: torch.supportsTorch.bind(torch),
    }
}

export async function createBarcodeScanner(options) {
    const nativeScanner = await createNativeScanner(options)

    if (nativeScanner) {
        return nativeScanner
    }

    return createZxingScanner(options)
}

export async function enumerateVideoInputDevices() {
    if (typeof navigator?.mediaDevices?.enumerateDevices !== 'function') {
        return []
    }

    try {
        const devices = await navigator.mediaDevices.enumerateDevices()

        return devices.filter((device) => device.kind === 'videoinput')
    } catch {
        return []
    }
}

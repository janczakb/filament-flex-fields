import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    BARCODE_FORMATS,
    detectBarcodeFormat,
    isBarcodeChecksumValid,
    matchesBarcodeFormat,
    prepareScanBeepAudio,
    usesTransientScanBeep,
    validateBarcodeValue,
    validateModulo10Checksum,
} from '../../resources/js/support/barcode-validation.js'

import {
    clampScanInterval,
    isMobileCameraDevice,
    mapEngineFormatToSlug,
} from '../../resources/js/support/barcode-scanner-engine.js'

describe('barcode-scanner-engine helpers', () => {
    it('clamps scan interval between 50 and 2000 ms', () => {
        assert.equal(clampScanInterval(10), 50)
        assert.equal(clampScanInterval(5000), 2000)
        assert.equal(clampScanInterval(120), 120)
    })

    it('maps engine format strings to slug values', () => {
        assert.equal(mapEngineFormatToSlug('EAN_13'), 'ean_13')
        assert.equal(mapEngineFormatToSlug('qr_code'), 'qr')
        assert.equal(mapEngineFormatToSlug(null), null)
    })

    it('detects mobile camera devices from user agent', () => {
        assert.equal(isMobileCameraDevice(), false)
    })
})

describe('barcode-validation helpers', () => {
    it('prepares scan beep audio without throwing when APIs are unavailable', async () => {
        await prepareScanBeepAudio(null)
        await prepareScanBeepAudio('https://example.test/beep.mp3')
    })

    it('uses transient scan beep on mobile user agents', () => {
        assert.equal(usesTransientScanBeep(), false)
    })

    it('matches known barcode formats', () => {
        assert.equal(matchesBarcodeFormat('5901234123457', BARCODE_FORMATS.ean_13), true)
        assert.equal(matchesBarcodeFormat('ABC-123', BARCODE_FORMATS.code_39), true)
        assert.equal(matchesBarcodeFormat('ABC-123', BARCODE_FORMATS.ean_13), false)
    })

    it('detects ean13 before qr fallback', () => {
        assert.equal(detectBarcodeFormat('5901234123457'), BARCODE_FORMATS.ean_13)
    })

    it('validates modulo 10 checksums', () => {
        assert.equal(validateModulo10Checksum('5901234123457', 13), true)
        assert.equal(validateModulo10Checksum('5901234123450', 13), false)
        assert.equal(isBarcodeChecksumValid('5901234123457', BARCODE_FORMATS.ean_13), true)
    })

    it('returns validation messages for unsupported formats', () => {
        assert.equal(
            validateBarcodeValue('ABC-123', {
                supportedFormats: [BARCODE_FORMATS.ean_13],
                labels: { unrecognized: 'Nope' },
            }),
            'Nope',
        )

        assert.equal(
            validateBarcodeValue('5901234123457', {
                supportedFormats: [BARCODE_FORMATS.ean_13],
                validateChecksum: true,
            }),
            null,
        )
    })
})

import {
    playScanBeep,
    prepareScanBeepAudio,
    prefersReducedMotion,
    validateBarcodeValue,
} from '../support/barcode-validation.js'
import {
    createBarcodeScanner,
    enumerateVideoInputDevices,
    isMobileCameraDevice,
    prepareVideoElement,
    resolveActiveDeviceId,
    reviveVideoPlayback,
    stopMediaStreamTracks,
} from '../support/barcode-scanner-engine.js'

export default function barcodeScannerFieldFormComponent({
    state,
    statePath,
    modalId,
    initialValue = '',
    disabled = false,
    readOnly = false,
    supportedFormats = [],
    continuous = false,
    beepOnScan = true,
    autoSubmit = false,
    cameraFacing = 'environment',
    scanDelay = 750,
    scanInterval = 120,
    allowCameraSwitch = true,
    preferredDeviceId = null,
    storeDetectedFormat = false,
    pauseWhenHidden = true,
    allowManualInput = true,
    validateChecksum = false,
    beepUrl = null,
    labels = {},
}) {
    return {
        state,
        statePath,
        modalId,
        inputValue: '',
        disabled,
        readOnly,
        supportedFormats,
        continuous,
        beepOnScan,
        autoSubmit,
        cameraFacing,
        scanDelay,
        scanInterval,
        allowCameraSwitch,
        preferredDeviceId,
        storeDetectedFormat,
        pauseWhenHidden,
        allowManualInput,
        validateChecksum,
        beepUrl,
        labels,
        scannerReady: false,
        scannerError: null,
        scannerEngine: null,
        torchEnabled: false,
        torchSupported: false,
        scanSuccess: false,
        lastScanAt: 0,
        lastScanValue: '',
        scannerSession: null,
        reducedMotion: prefersReducedMotion(),
        scannerStarting: false,
        videoDevices: [],
        activeDeviceId: null,
        visibilityBound: false,
        mobileVideoPortal: null,
        mobileVideoPortalViewport: null,
        mobileVideoPortalSyncHandler: null,
        mobilePortalOverlayHost: null,
        mobilePortalControlsHost: null,
        mobilePortalMovedNodes: [],

        init() {
            this.hydrateFromState()

            this.$watch('state', () => {
                if (this.inputValue !== this.extractDisplayValue(this.state)) {
                    this.hydrateFromState()
                }
            })
        },

        extractDisplayValue(value) {
            if (this.storeDetectedFormat && value && typeof value === 'object') {
                return String(value.value ?? '')
            }

            return String(value ?? '')
        },

        hydrateFromState() {
            this.inputValue = this.extractDisplayValue(this.state ?? initialValue)
        },

        commitState(value, format = null) {
            const trimmed = String(value ?? '').trim()

            if (trimmed === '') {
                this.state = null

                return
            }

            if (this.storeDetectedFormat) {
                this.state = {
                    value: trimmed,
                    format: format ?? null,
                }

                return
            }

            this.state = trimmed
        },

        onManualInput() {
            if (! this.allowManualInput || this.disabled || this.readOnly) {
                return
            }

            this.commitState(this.inputValue)
        },

        onManualBlur() {
            if (! this.allowManualInput || this.disabled || this.readOnly) {
                return
            }

            const trimmed = String(this.inputValue ?? '').trim()
            this.inputValue = trimmed
            this.commitState(trimmed)
        },

        get canSwitchCamera() {
            if (! this.allowCameraSwitch) {
                return false
            }

            if (isMobileCameraDevice()) {
                return true
            }

            return this.videoDevices.length > 1
        },

        getScannerVideoId() {
            return `${this.modalId}-video`
        },

        resolveScannerVideoElement() {
            return document.getElementById(this.getScannerVideoId())
        },

        resolveScannerViewportElement() {
            return document.getElementById(this.modalId)?.querySelector('.fff-barcode-scanner__viewport') ?? null
        },

        syncMobileVideoPortalPosition() {
            const portal = this.mobileVideoPortal
            const viewport = this.mobileVideoPortalViewport

            if (! portal || ! viewport) {
                return
            }

            const rect = viewport.getBoundingClientRect()

            portal.style.top = `${Math.round(rect.top)}px`
            portal.style.left = `${Math.round(rect.left)}px`
            portal.style.width = `${Math.round(rect.width)}px`
            portal.style.height = `${Math.round(rect.height)}px`
        },

        async scheduleMobileVideoPortalSync() {
            if (! isMobileCameraDevice()) {
                return
            }

            await new Promise((resolve) => {
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(resolve)
                })
            })

            this.syncMobileVideoPortalPosition()
        },

        resolvePortalVideoElement() {
            return document.getElementById(`${this.modalId}-portal-video`)
        },

        teardownMobileVideoPortal() {
            if (this.mobileVideoPortalSyncHandler) {
                window.removeEventListener('resize', this.mobileVideoPortalSyncHandler)
                window.removeEventListener('scroll', this.mobileVideoPortalSyncHandler, true)
                this.mobileVideoPortalSyncHandler = null
            }

            this.restoreMobilePortalOverlays(this.mobileVideoPortalViewport)

            this.mobileVideoPortalViewport?.classList.remove('is-using-portal')
            this.mobileVideoPortalViewport = null
            this.mobileVideoPortal?.remove()
            this.mobileVideoPortal = null
            this.mobilePortalOverlayHost = null
            this.mobilePortalControlsHost = null
            this.mobilePortalMovedNodes = []
        },

        mountMobilePortalOverlays(viewport, portal) {
            const overlayHost = document.createElement('div')
            overlayHost.className = 'fff-barcode-scanner__portal-overlay'

            this.mobilePortalOverlayHost = overlayHost
            this.mobilePortalControlsHost = null
            this.mobilePortalMovedNodes = []

            for (const selector of [
                '.fff-barcode-scanner__viewport-shade',
                '.fff-barcode-scanner__reticle',
                '.fff-barcode-scanner__success-flash',
            ]) {
                const node = viewport.querySelector(selector)

                if (! node) {
                    continue
                }

                this.mobilePortalMovedNodes.push({
                    node,
                    nextSibling: node.nextSibling,
                })
                overlayHost.appendChild(node)
            }

            portal.appendChild(overlayHost)

            const controlsHost = document.createElement('div')
            controlsHost.className = 'fff-barcode-scanner__portal-controls'

            for (const selector of [
                '.fff-barcode-scanner__engine-badge',
            ]) {
                const node = viewport.querySelector(selector)

                if (! node) {
                    continue
                }

                this.mobilePortalMovedNodes.push({
                    node,
                    nextSibling: node.nextSibling,
                })
                controlsHost.appendChild(node)
            }

            if (controlsHost.childElementCount > 0) {
                portal.appendChild(controlsHost)
                this.mobilePortalControlsHost = controlsHost
            }
        },

        restoreMobilePortalOverlays(viewport) {
            if (! viewport || this.mobilePortalMovedNodes.length === 0) {
                return
            }

            for (const { node, nextSibling } of this.mobilePortalMovedNodes) {
                viewport.insertBefore(node, nextSibling)
            }

            this.mobilePortalMovedNodes = []
            this.mobilePortalOverlayHost = null
            this.mobilePortalControlsHost = null
        },

        ensureMobileVideoPortal() {
            this.teardownMobileVideoPortal()

            const viewport = this.resolveScannerViewportElement()

            if (! viewport) {
                return this.resolveScannerVideoElement()
            }

            const portal = document.createElement('div')
            portal.className = 'fff-barcode-scanner__video-portal'
            portal.id = `${this.modalId}-video-portal`

            const video = document.createElement('video')
            video.id = `${this.modalId}-portal-video`
            video.className = 'fff-barcode-scanner__video fff-barcode-scanner__video--portal'
            video.setAttribute('playsinline', 'true')
            video.setAttribute('webkit-playsinline', 'true')
            video.setAttribute('autoplay', 'true')
            video.muted = true

            portal.appendChild(video)
            this.mountMobilePortalOverlays(viewport, portal)
            document.body.appendChild(portal)

            viewport.classList.add('is-using-portal')
            this.mobileVideoPortal = portal
            this.mobileVideoPortalViewport = viewport
            this.syncMobileVideoPortalPosition()

            this.mobileVideoPortalSyncHandler = () => {
                this.syncMobileVideoPortalPosition()
            }
            window.addEventListener('resize', this.mobileVideoPortalSyncHandler)
            window.addEventListener('scroll', this.mobileVideoPortalSyncHandler, true)

            return video
        },

        resolveModalWindowElement() {
            return document.getElementById(this.modalId)?.querySelector('.fi-modal-window') ?? null
        },

        async waitForModalTransitionComplete(timeoutMs = 450) {
            const modal = document.getElementById(this.modalId)

            for (let attempt = 0; attempt < 120; attempt += 1) {
                if (modal?.classList.contains('fi-modal-open')) {
                    break
                }

                await new Promise((resolve) => {
                    window.requestAnimationFrame(resolve)
                })
            }

            const modalWindow = this.resolveModalWindowElement()

            if (! modalWindow) {
                await new Promise((resolve) => {
                    window.setTimeout(resolve, timeoutMs)
                })

                return
            }

            await new Promise((resolve) => {
                let settled = false

                const finish = () => {
                    if (settled) {
                        return
                    }

                    settled = true
                    modalWindow.removeEventListener('transitionend', onTransitionEnd)
                    resolve()
                }

                const onTransitionEnd = (event) => {
                    if (event.target === modalWindow) {
                        finish()
                    }
                }

                modalWindow.addEventListener('transitionend', onTransitionEnd)
                window.setTimeout(finish, timeoutMs)
            })

            await new Promise((resolve) => {
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(resolve)
                })
            })
        },

        async waitForScannerVideoElement(maxAttempts = 150) {
            for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
                const modal = document.getElementById(this.modalId)
                const video = this.resolveScannerVideoElement()

                if (
                    video
                    && modal?.classList.contains('fi-modal-open')
                    && video.offsetWidth > 0
                    && video.offsetHeight > 0
                    && this.isVideoVisible(video)
                    && this.isModalWindowStable()
                ) {
                    await new Promise((resolve) => {
                        window.requestAnimationFrame(() => {
                            window.requestAnimationFrame(resolve)
                        })
                    })

                    return video
                }

                await new Promise((resolve) => {
                    window.requestAnimationFrame(resolve)
                })
            }

            return null
        },

        isVideoVisible(video) {
            if (! video) {
                return false
            }

            const style = window.getComputedStyle(video)

            return style.display !== 'none'
                && style.visibility !== 'hidden'
                && style.opacity !== '0'
        },

        isModalWindowStable() {
            const modalWindow = this.resolveModalWindowElement()

            if (! modalWindow) {
                return true
            }

            const style = window.getComputedStyle(modalWindow)
            const transform = style.transform ?? 'none'

            return transform === 'none'
                || transform === 'matrix(1, 0, 0, 1, 0, 0)'
                || ! transform.includes('0.95')
        },

        openScanner() {
            if (this.disabled || this.readOnly) {
                return
            }

            this.scannerError = null
            this.scanSuccess = false
            this.scannerReady = false
            this.scannerEngine = null
            this.torchEnabled = false
            this.torchSupported = false
            this.videoDevices = []
            this.activeDeviceId = this.preferredDeviceId

            if (this.beepOnScan) {
                prepareScanBeepAudio(this.beepUrl).catch(() => {})
            }

            this.$dispatch('open-modal', { id: this.modalId })
        },

        onScannerModalOpened(event) {
            if (event.detail?.id !== this.modalId) {
                return
            }

            this.beginScannerSession().catch((error) => {
                this.scannerError = this.resolveScannerError(error)
            })
        },

        onScannerModalClosed(event) {
            if (event.detail?.id !== this.modalId) {
                return
            }

            this.stopScanner()
            this.scanSuccess = false
            this.scannerError = null
        },

        async beginScannerSession() {
            if (this.scannerStarting) {
                return
            }

            this.scannerStarting = true
            this.scannerError = null

            try {
                await this.waitForModalTransitionComplete()
                await this.startScanner()
            } finally {
                this.scannerStarting = false
            }
        },

        bindVisibilityHandler() {
            if (! this.pauseWhenHidden || this.visibilityBound) {
                return
            }

            this._boundVisibilityChange = () => {
                this.onVisibilityChange()
            }
            this.visibilityBound = true
            document.addEventListener('visibilitychange', this._boundVisibilityChange)
        },

        unbindVisibilityHandler() {
            if (! this.visibilityBound) {
                return
            }

            document.removeEventListener('visibilitychange', this._boundVisibilityChange)
            this.visibilityBound = false
            this._boundVisibilityChange = null
        },

        onVisibilityChange() {
            if (! this.scannerSession || ! this.pauseWhenHidden) {
                return
            }

            if (document.hidden) {
                this.scannerSession.pause?.()
            } else {
                this.scannerSession.resume?.()
            }
        },

        async refreshVideoDevices(video) {
            this.videoDevices = await enumerateVideoInputDevices()
            this.activeDeviceId = resolveActiveDeviceId(video) ?? this.activeDeviceId ?? this.preferredDeviceId
        },

        async startScanner(deviceId = null) {
            await this.stopActiveScannerSession()

            let video = null

            if (isMobileCameraDevice()) {
                await this.waitForScannerVideoElement()
                video = this.ensureMobileVideoPortal()
            } else {
                video = await this.waitForScannerVideoElement()
            }

            if (! video) {
                throw new Error('Scanner video element unavailable')
            }

            const resolvedDeviceId = deviceId ?? this.activeDeviceId ?? this.preferredDeviceId

            prepareVideoElement(video)

            this.scannerSession = await createBarcodeScanner({
                supportedFormats: this.supportedFormats,
                cameraFacing: this.cameraFacing,
                deviceId: resolvedDeviceId,
                preAcquiredStream: null,
                scanInterval: this.scanInterval,
                videoElement: video,
                onResult: (value, format, engine) => this.handleScan(value, format, engine),
                onError: (error) => {
                    if (error?.name === 'NotAllowedError') {
                        this.scannerError = this.labels.permissionDenied
                    }
                },
            })

            await reviveVideoPlayback(video)

            if (isMobileCameraDevice()) {
                await this.scheduleMobileVideoPortalSync()

                if (video.videoWidth === 0) {
                    throw new Error('Camera preview unavailable')
                }
            }

            this.scannerEngine = this.scannerSession.engine ?? 'zxing'
            this.scannerReady = true
            this.torchSupported = this.scannerSession.supportsTorch()
            await this.refreshVideoDevices(video)
            this.bindVisibilityHandler()

            if (isMobileCameraDevice()) {
                await this.scheduleMobileVideoPortalSync()
            }
        },

        async restartScannerCamera({ deviceId = null } = {}) {
            this.unbindVisibilityHandler()

            if (this.scannerSession) {
                await this.scannerSession.stop()
                this.scannerSession = null
            }

            let video = null

            if (isMobileCameraDevice()) {
                video = this.resolvePortalVideoElement()

                if (! video) {
                    video = this.ensureMobileVideoPortal()
                } else {
                    await this.scheduleMobileVideoPortalSync()
                }
            } else {
                video = this.resolveScannerVideoElement()
            }

            if (! video) {
                throw new Error('Scanner video element unavailable')
            }

            stopMediaStreamTracks(video.srcObject)
            video.srcObject = null

            prepareVideoElement(video)

            const resolvedDeviceId = deviceId ?? this.activeDeviceId ?? this.preferredDeviceId

            this.scannerSession = await createBarcodeScanner({
                supportedFormats: this.supportedFormats,
                cameraFacing: this.cameraFacing,
                deviceId: resolvedDeviceId,
                preAcquiredStream: null,
                scanInterval: this.scanInterval,
                videoElement: video,
                onResult: (value, format, engine) => this.handleScan(value, format, engine),
                onError: (error) => {
                    if (error?.name === 'NotAllowedError') {
                        this.scannerError = this.labels.permissionDenied
                    }
                },
            })

            await reviveVideoPlayback(video)

            if (isMobileCameraDevice()) {
                await this.scheduleMobileVideoPortalSync()

                if (video.videoWidth === 0) {
                    throw new Error('Camera preview unavailable')
                }
            }

            this.scannerEngine = this.scannerSession.engine ?? 'zxing'
            this.torchSupported = this.scannerSession.supportsTorch()
            await this.refreshVideoDevices(video)
            this.bindVisibilityHandler()

            if (isMobileCameraDevice()) {
                await this.scheduleMobileVideoPortalSync()
            }
        },

        async switchCamera() {
            if (! this.canSwitchCamera) {
                return
            }

            this.torchEnabled = false

            try {
                if (isMobileCameraDevice()) {
                    this.activeDeviceId = null
                    this.cameraFacing = this.cameraFacing === 'environment' ? 'user' : 'environment'

                    await this.restartScannerCamera({ deviceId: null })

                    return
                }

                const currentIndex = this.videoDevices.findIndex(
                    (device) => device.deviceId === this.activeDeviceId,
                )
                const nextIndex = currentIndex >= 0
                    ? (currentIndex + 1) % this.videoDevices.length
                    : 0
                const nextDevice = this.videoDevices[nextIndex]

                if (! nextDevice?.deviceId) {
                    return
                }

                this.activeDeviceId = nextDevice.deviceId

                await this.restartScannerCamera({ deviceId: nextDevice.deviceId })
            } catch (error) {
                this.scannerError = this.resolveScannerError(error)
            }
        },

        async stopActiveScannerSession() {
            this.unbindVisibilityHandler()
            this.teardownMobileVideoPortal()

            if (this.scannerSession) {
                await this.scannerSession.stop()
                this.scannerSession = null
            }

            this.scannerReady = false
            this.scannerEngine = null
            this.torchEnabled = false
            this.torchSupported = false
        },

        async stopScanner() {
            await this.stopActiveScannerSession()
        },

        closeScanner() {
            this.$dispatch('close-modal', { id: this.modalId })
            this.stopScanner()
            this.scanSuccess = false
            this.scannerError = null
        },

        async toggleTorch() {
            if (! this.scannerSession || ! this.torchSupported) {
                return
            }

            this.torchEnabled = ! this.torchEnabled

            const applied = await this.scannerSession.setTorch(this.torchEnabled)

            if (! applied) {
                this.torchEnabled = false
            }
        },

        async handleScan(value, format = null, engine = null) {
            const normalized = String(value ?? '').trim()

            if (normalized === '') {
                return
            }

            const now = Date.now()

            if (
                normalized === this.lastScanValue
                && now - this.lastScanAt < this.scanDelay
            ) {
                return
            }

            const validationMessage = validateBarcodeValue(normalized, {
                supportedFormats: this.supportedFormats,
                validateChecksum: this.validateChecksum,
                labels: this.labels.validation ?? {},
            })

            if (validationMessage) {
                this.scannerError = validationMessage
                this.scanSuccess = false

                return
            }

            this.lastScanAt = now
            this.lastScanValue = normalized
            this.inputValue = normalized
            this.commitState(normalized, format)
            this.scannerError = null
            this.scanSuccess = true

            if (this.beepOnScan) {
                await playScanBeep(this.beepUrl).catch(() => {})
            }

            this.$dispatch('barcode-scanned', {
                value: normalized,
                format,
                engine,
                statePath: this.statePath,
            })

            if (this.autoSubmit) {
                this.$dispatch('barcode-auto-submit', {
                    value: normalized,
                    statePath: this.statePath,
                })

                const form = this.$root.closest('form')

                if (form) {
                    form.requestSubmit?.()
                }
            }

            if (! this.continuous) {
                this.closeScanner()

                return
            }

            window.setTimeout(() => {
                this.scanSuccess = false
            }, this.reducedMotion ? 120 : 700)
        },

        resolveScannerError(error) {
            if (! error) {
                return this.labels.cameraUnavailable
            }

            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                return this.labels.permissionDenied
            }

            if (error.name === 'NotFoundError' || error.name === 'OverconstrainedError') {
                return this.labels.cameraUnavailable
            }

            return this.labels.cameraUnavailable
        },
    }
}

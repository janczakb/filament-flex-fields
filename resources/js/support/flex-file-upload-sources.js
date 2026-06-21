/**
 * Shared segment-control Alpine behaviors used by SegmentTabs and upload source tabs.
 */
import {
    acquireCameraStream,
    isMobileCameraDevice,
    prepareVideoElement,
    reviveVideoPlayback,
    stopMediaStreamTracks,
    waitForVideoFrames,
} from './camera-stream.js'

export function createSegmentControlBehavior({
    activeTab,
    optionKeys = [],
    separators = false,
    onTabChange = null,
}) {
    return {
        tab: activeTab,
        optionKeys,
        separators,
        indicatorStyle: '',
        indicatorAnimated: false,
        resizeObserver: null,

        normalize(value) {
            return value === null || value === undefined ? null : String(value)
        },

        isSelected(value) {
            return this.normalize(this.tab) === this.normalize(value)
        },

        select(value) {
            this.tab = value
            onTabChange?.(value)
            this.$nextTick(() => this.updateIndicator())
        },

        selectedIndex() {
            const current = this.normalize(this.tab)

            return this.optionKeys.findIndex((key) => this.normalize(key) === current)
        },

        showSeparator(separatorIndex) {
            if (! this.separators) {
                return false
            }

            const selectedIndex = this.selectedIndex()

            if (selectedIndex === -1) {
                return true
            }

            return separatorIndex !== selectedIndex - 1 && separatorIndex !== selectedIndex
        },

        separatorClass(separatorIndex) {
            return this.showSeparator(separatorIndex) ? '' : 'is-hidden'
        },

        updateIndicator() {
            const track = this.$refs.sourceTrack ?? this.$refs.track

            if (! track) {
                return
            }

            const selected = track.querySelector('[data-segment-selected="true"]')

            if (! selected) {
                this.indicatorStyle = 'opacity: 0;'

                return
            }

            this.indicatorStyle =
                `width: ${selected.offsetWidth}px;` +
                `height: ${selected.offsetHeight}px;` +
                `transform: translate3d(${selected.offsetLeft}px, ${selected.offsetTop}px, 0);` +
                'opacity: 1;'
        },

        enableIndicatorAnimation() {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.indicatorAnimated = true
                })
            })
        },

        initSegmentControl() {
            if (! this.tab || ! this.optionKeys.includes(this.tab)) {
                this.tab = this.optionKeys[0] ?? null
            }

            this.$watch('tab', () => {
                this.$nextTick(() => this.updateIndicator())
            })

            this.$nextTick(() => {
                this.updateIndicator()
                this.enableIndicatorAnimation()
            })

            if (typeof ResizeObserver !== 'undefined') {
                const track = this.$refs.sourceTrack ?? this.$refs.track

                if (track) {
                    this.resizeObserver = new ResizeObserver(() => this.updateIndicator())
                    this.resizeObserver.observe(track)
                }
            }
        },

        destroySegmentControl() {
            this.resizeObserver?.disconnect()
            this.resizeObserver = null
        },
    }
}

export function createUploadSourcesBehavior({
    schemaComponentKey = null,
    statePath = null,
    isMultiple = false,
    isPreviewable = true,
    shouldAppendFiles = false,
    isDisabled = false,
    requireReplaceConfirmation = false,
    replaceConfirmationMessage = 'Replace the current file?',
    webcamFacingMode: initialWebcamFacingMode = 'environment',
    webcamModalId = null,
    labels = {},
}) {
    return {
        uploadSource: 'file',
        urlValue: '',
        urlImporting: false,
        urlError: null,
        instantPreviewUrl: null,
        webcamModalId,
        webcamStream: null,
        webcamStarting: false,
        webcamError: null,
        webcamReady: false,
        webcamFacingMode: initialWebcamFacingMode,
        webcamCanFlip: false,
        webcamCanUseFlash: false,
        webcamFlashEnabled: false,
        webcamCaptureStep: 'camera',
        webcamPendingPreviewUrl: null,
        webcamPendingBlob: null,
        webcamConfirming: false,
        mobileVideoPortal: null,
        mobileVideoPortalViewport: null,
        mobileVideoPortalSyncHandler: null,

        isUploadSource(source) {
            return this.uploadSource === source
        },

        selectUploadSource(source) {
            if (this.uploadSource === source) {
                return
            }

            if (this.uploadSource === 'webcam') {
                this.closeWebcamModal()
            }

            this.uploadSource = source

            if (source === 'webcam') {
                this.$nextTick(() => this.syncWebcamPreviewFromUpload?.())
            }
        },

        resolveUploadErrorMessage(error) {
            const payload = error?.response?.data ?? error?.data ?? error

            if (payload?.errors) {
                const first = Object.values(payload.errors).flat()[0]

                if (typeof first === 'string' && first !== '') {
                    return first
                }
            }

            if (typeof payload?.message === 'string' && payload.message !== '') {
                return payload.message
            }

            return null
        },

        async addAlternateSourceFileToFilePond(file) {
            return await this.addFileToFilamentPond?.(file) ?? false
        },

        async buildFileFromStagingPayload(payload) {
            const previewUrl = payload?.previewUrl

            if (typeof previewUrl !== 'string' || previewUrl === '') {
                return null
            }

            const blob = await this.getFileUploadComponent?.()?.fetchUploadPreviewBlob?.(previewUrl)

            if (! blob) {
                return null
            }

            const name = typeof payload?.name === 'string' && payload.name !== ''
                ? payload.name
                : `imported-${Date.now()}.jpg`

            const type = typeof payload?.type === 'string' && payload.type !== ''
                ? payload.type
                : blob.type

            return new File([blob], name, { type })
        },

        async importFromUrl() {
            if (isDisabled || this.urlImporting) {
                return
            }

            const url = this.urlValue?.trim()

            if (! url) {
                this.urlError = labels.urlRequired ?? 'Enter a file URL to import.'

                return
            }

            if (requireReplaceConfirmation && ! isMultiple) {
                const uploadComponent = this.getFileUploadComponent?.()
                const hasExisting = uploadComponent?.pond?.getFiles?.().some((file) => file.origin !== 1)

                if (hasExisting && ! window.confirm(replaceConfirmationMessage)) {
                    return
                }
            }

            this.urlImporting = true
            this.urlError = null
            this.instantPreviewUrl = null

            let stagingFilename = null

            try {
                const pondTarget = await this.ensureFilePondReady?.()

                if (! pondTarget?.pond) {
                    throw new Error(
                        labels.urlSyncFailed
                            ?? 'The file uploader is not ready yet. Switch to the File tab and try again.',
                    )
                }

                const payload = await this.$wire.callSchemaComponentMethod(
                    schemaComponentKey,
                    'importUploadedFileFromUrl',
                    { url },
                )

                stagingFilename = payload?.stagingFilename ?? null

                if (! stagingFilename) {
                    throw new Error('Staging upload missing')
                }

                const file = await this.buildFileFromStagingPayload(payload)

                if (! file) {
                    throw new Error('Preview unavailable')
                }

                const added = await this.addFileToFilamentPond?.(file)

                if (! added) {
                    this.urlError = labels.urlSyncFailed
                        ?? 'The file was imported but could not be shown in the uploader.'

                    return
                }

                try {
                    await this.$wire.callSchemaComponentMethod(
                        schemaComponentKey,
                        'discardAlternateSourceStagingUpload',
                        { stagingFilename },
                    )
                } catch {
                    // Staging cleanup is best-effort; Livewire also expires temp files.
                }

                stagingFilename = null

                this.refreshSummaryLater?.()
                this.tagFileIcons?.()
                this.selectUploadSource?.('file')
                this.urlValue = ''
            } catch (error) {
                this.urlError = this.resolveUploadErrorMessage(error)
                    ?? labels.urlFetchFailed
                    ?? 'The remote file could not be downloaded.'
            } finally {
                if (stagingFilename) {
                    try {
                        await this.$wire.callSchemaComponentMethod(
                            schemaComponentKey,
                            'discardAlternateSourceStagingUpload',
                            { stagingFilename },
                        )
                    } catch {
                        // Staging cleanup is best-effort; Livewire also expires temp files.
                    }
                }

                this.urlImporting = false
            }
        },

        resolveWebcamVideoElement() {
            if (isMobileCameraDevice()) {
                return document.getElementById(`${webcamModalId}-portal-video`)
                    ?? this.$refs.webcamVideo
            }

            return this.$refs.webcamVideo
                    ?? document.getElementById(`${webcamModalId}-video`)
        },

        resolveWebcamViewportElement() {
            return document.getElementById(webcamModalId)?.querySelector('.fff-flex-file-upload__webcam-stage--modal') ?? null
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

        teardownMobileVideoPortal() {
            if (this.mobileVideoPortalSyncHandler) {
                window.removeEventListener('resize', this.mobileVideoPortalSyncHandler)
                window.removeEventListener('scroll', this.mobileVideoPortalSyncHandler, true)
                this.mobileVideoPortalSyncHandler = null
            }

            this.mobileVideoPortalViewport?.classList.remove('is-using-portal')
            this.mobileVideoPortalViewport = null
            this.mobileVideoPortal?.remove()
            this.mobileVideoPortal = null
        },

        ensureMobileVideoPortal() {
            this.teardownMobileVideoPortal()

            const viewport = this.resolveWebcamViewportElement()

            if (! viewport) {
                return this.$refs.webcamVideo ?? document.getElementById(`${webcamModalId}-video`)
            }

            const portal = document.createElement('div')
            portal.className = 'fff-flex-file-upload__webcam-video-portal'
            portal.id = `${webcamModalId}-video-portal`

            const video = document.createElement('video')
            video.id = `${webcamModalId}-portal-video`
            video.className = 'fff-flex-file-upload__webcam-video fff-flex-file-upload__webcam-video--portal'
            prepareVideoElement(video)

            portal.appendChild(video)
            document.body.appendChild(portal)

            viewport.classList.add('is-using-portal')
            this.mobileVideoPortal = portal
            this.mobileVideoPortalViewport = viewport
            this.syncMobileVideoPortalPosition()

            let isSyncing = false
            this.mobileVideoPortalSyncHandler = () => {
                if (isSyncing) return
                isSyncing = true
                window.requestAnimationFrame(() => {
                    this.syncMobileVideoPortalPosition()
                    isSyncing = false
                })
            }
            window.addEventListener('resize', this.mobileVideoPortalSyncHandler, { passive: true })
            window.addEventListener('scroll', this.mobileVideoPortalSyncHandler, { passive: true, capture: true })

            return video
        },

        resolveModalWindowElement() {
            return document.getElementById(webcamModalId)?.querySelector('.fi-modal-window') ?? null
        },

        async waitForWebcamModalTransitionComplete(timeoutMs = 450) {
            const modal = document.getElementById(webcamModalId)

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
        },

        async detectWebcamCapabilities() {
            let videoInputs = []

            if (typeof navigator?.mediaDevices?.enumerateDevices === 'function') {
                const devices = await navigator.mediaDevices.enumerateDevices()
                videoInputs = devices.filter((device) => device.kind === 'videoinput')
            }

            this.webcamCanFlip = isMobileCameraDevice() || videoInputs.length >= 2

            const track = this.webcamStream?.getVideoTracks?.()?.[0]
            const capabilities = track?.getCapabilities?.()

            this.webcamCanUseFlash = capabilities?.torch === true

            if (! this.webcamCanUseFlash) {
                this.webcamFlashEnabled = false
            }
        },

        async startWebcam() {
            if (isDisabled || this.webcamStarting) {
                return
            }

            if (typeof navigator?.mediaDevices?.getUserMedia !== 'function') {
                this.webcamError = labels.webcamUnavailable ?? 'No usable camera was found on this device.'

                return
            }

            this.webcamStarting = true
            this.webcamError = null
            this.webcamReady = false
            this.webcamCanFlip = false
            this.webcamCanUseFlash = false
            this.webcamFlashEnabled = false

            try {
                let video = this.resolveWebcamVideoElement()

                if (isMobileCameraDevice()) {
                    video = this.ensureMobileVideoPortal()
                }

                if (! video) {
                    throw new Error('Webcam video element unavailable')
                }

                prepareVideoElement(video)

                this.webcamStream = await acquireCameraStream(this.webcamFacingMode)

                video.srcObject = this.webcamStream
                await reviveVideoPlayback(video)

                if (isMobileCameraDevice()) {
                    await this.scheduleMobileVideoPortalSync()
                    await waitForVideoFrames(video)
                }

                this.webcamReady = true
                await this.detectWebcamCapabilities()

                if (isMobileCameraDevice()) {
                    await this.scheduleMobileVideoPortalSync()
                }
            } catch (error) {
                this.webcamError = error?.name === 'NotAllowedError'
                    ? (labels.webcamPermissionDenied ?? 'Camera permission was denied.')
                    : (labels.webcamUnavailable ?? 'No usable camera was found on this device.')
                this.stopWebcam()
            } finally {
                this.webcamStarting = false
            }
        },

        async flipWebcam() {
            if (! this.webcamCanFlip || this.webcamStarting || isDisabled) {
                return
            }

            this.webcamFacingMode = this.webcamFacingMode === 'user' ? 'environment' : 'user'
            await this.restartWebcam()
        },

        async restartWebcam() {
            this.stopWebcam()
            await this.startWebcam()
        },

        async toggleWebcamFlash() {
            if (! this.webcamCanUseFlash || ! this.webcamReady || isDisabled) {
                return
            }

            const track = this.webcamStream?.getVideoTracks?.()?.[0]

            if (! track) {
                return
            }

            const nextValue = ! this.webcamFlashEnabled

            try {
                await track.applyConstraints({ advanced: [{ torch: nextValue }] })
                this.webcamFlashEnabled = nextValue
            } catch {
                this.webcamFlashEnabled = false
            }
        },

        stopWebcam() {
            stopMediaStreamTracks(this.webcamStream)
            this.webcamStream = null
            this.webcamReady = false
            this.webcamCanFlip = false
            this.webcamCanUseFlash = false
            this.webcamFlashEnabled = false

            const video = this.resolveWebcamVideoElement()

            if (video) {
                stopMediaStreamTracks(video.srcObject)
                video.srcObject = null
            }

            this.teardownMobileVideoPortal()
        },

        resetWebcamCaptureSession() {
            this.revokeWebcamPendingPreview()
            this.webcamCaptureStep = 'camera'
            this.webcamError = null
        },

        revokeWebcamPendingPreview() {
            if (this.webcamPendingPreviewUrl) {
                URL.revokeObjectURL(this.webcamPendingPreviewUrl)
            }

            this.webcamPendingPreviewUrl = null
            this.webcamPendingBlob = null
        },



        openWebcamModal() {
            if (isDisabled || ! webcamModalId) {
                return
            }

            if (requireReplaceConfirmation && ! isMultiple) {
                const uploadComponent = this.getFileUploadComponent?.()
                const hasExisting = uploadComponent?.pond?.getFiles?.().some((file) => file.origin !== 1)

                if (hasExisting && ! window.confirm(replaceConfirmationMessage)) {
                    return
                }
            }

            this.resetWebcamCaptureSession()
            this.stopWebcam()
            this.$dispatch('open-modal', { id: webcamModalId })
        },

        onWebcamModalOpened(event) {
            if (event.detail?.id !== webcamModalId) {
                return
            }

            this.beginWebcamSession().catch(() => {})
        },

        onWebcamModalClosed(event) {
            if (event.detail?.id !== webcamModalId) {
                return
            }

            this.stopWebcam()
            this.resetWebcamCaptureSession()
        },

        closeWebcamModal() {
            if (! webcamModalId) {
                return
            }

            this.$dispatch('close-modal', { id: webcamModalId })
            this.stopWebcam()
            this.resetWebcamCaptureSession()
        },

        async beginWebcamSession() {
            await this.waitForWebcamModalTransitionComplete()
            await this.startWebcam()
        },

        async captureWebcamPhoto() {
            if (isDisabled || ! this.webcamReady) {
                return
            }

            const video = this.resolveWebcamVideoElement()
            const canvas = this.$refs.webcamCanvas

            if (! video || ! canvas || video.videoWidth === 0) {
                return
            }

            canvas.width = video.videoWidth
            canvas.height = video.videoHeight

            const context = canvas.getContext('2d')

            if (! context) {
                return
            }

            context.drawImage(video, 0, 0, canvas.width, canvas.height)

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92))

            if (! blob) {
                return
            }

            this.revokeWebcamPendingPreview()
            this.webcamPendingBlob = blob
            this.webcamPendingPreviewUrl = URL.createObjectURL(blob)
            this.webcamCaptureStep = 'review'
            this.stopWebcam()
        },

        retakeWebcamPhoto() {
            this.revokeWebcamPendingPreview()
            this.webcamCaptureStep = 'camera'
            this.webcamError = null
            this.beginWebcamSession().catch(() => {})
        },

        async confirmWebcamPhoto() {
            if (isDisabled || ! this.webcamPendingBlob || this.webcamConfirming) {
                return
            }

            this.webcamConfirming = true
            this.webcamError = null

            try {
                const file = new File(
                    [this.webcamPendingBlob],
                    `webcam-${Date.now()}.jpg`,
                    { type: 'image/jpeg' },
                )

                const added = await this.addAlternateSourceFileToFilePond(file)

                if (! added) {
                    this.webcamError = labels.urlSyncFailed
                        ?? 'The photo was captured but could not be shown in the uploader.'

                    return
                }

                this.webcamPendingPreviewUrl = null
                this.webcamPendingBlob = null
                
                this.closeWebcamModal()
                this.selectUploadSource?.('file')
            } catch (error) {
                this.webcamError = this.resolveUploadErrorMessage(error)
                    ?? labels.webcamUnavailable
                    ?? 'The photo could not be uploaded.'
            } finally {
                this.webcamConfirming = false
            }
        },

        destroyUploadSources() {
            this.closeWebcamModal()
            this.revokeWebcamPendingPreview()
        },
    }
}

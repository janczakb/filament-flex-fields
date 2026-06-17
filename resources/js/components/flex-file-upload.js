import { createSegmentControlBehavior, createUploadSourcesBehavior } from '../support/flex-file-upload-sources.js'

export default function flexFileUploadFormComponent({
    showUploadSummary = false,
    requireReplaceConfirmation = false,
    replaceConfirmationMessage = 'Replace the current file?',
    summaryTemplate = ':count file(s), :size KB total',
    remainingSlotsLabel = null,
    showFileIcon = false,
    isMultiple = false,
    initialSummaryLabel = '',
    hasUploadSourceTabs = false,
    uploadSourceTabKeys = ['file'],
    defaultUploadSource = 'file',
    allowUrlUpload = false,
    allowWebcamUpload = false,
    schemaComponentKey = null,
    statePath = null,
    isPreviewable = true,
    shouldAppendFiles = false,
    isDisabled = false,
    webcamFacingMode = 'environment',
    webcamModalId = null,
    labels = {},
}) {
    const segmentControl = hasUploadSourceTabs
        ? createSegmentControlBehavior({
            activeTab: defaultUploadSource,
            optionKeys: uploadSourceTabKeys,
            separators: false,
            onTabChange: null,
        })
        : {}

    const uploadSources = hasUploadSourceTabs
        ? createUploadSourcesBehavior({
            schemaComponentKey,
            statePath,
            isMultiple,
            isPreviewable,
            shouldAppendFiles,
            isDisabled,
            requireReplaceConfirmation,
            replaceConfirmationMessage,
            webcamFacingMode,
            webcamModalId,
            labels,
        })
        : {}

    return {
        showUploadSummary: Boolean(showUploadSummary),
        requireReplaceConfirmation: Boolean(requireReplaceConfirmation),
        replaceConfirmationMessage,
        summaryTemplate,
        remainingSlotsLabel,
        showFileIcon: Boolean(showFileIcon),
        isMultiple: Boolean(isMultiple),
        initialSummaryLabel: initialSummaryLabel ?? '',
        summaryLabel: initialSummaryLabel ?? '',
        hasUploadSourceTabs: Boolean(hasUploadSourceTabs),
        allowUrlUpload: Boolean(allowUrlUpload),
        allowWebcamUpload: Boolean(allowWebcamUpload),
        statePath: statePath ?? null,
        labels,
        isDisabled: Boolean(isDisabled),
        displayReady: false,
        urlError: null,
        instantPreviewUrl: null,
        observer: null,
        readyObserver: null,
        readyStableChecks: 0,
        filePondReadyHandler: null,

        onWebcamModalOpened() {},
        onWebcamModalClosed() {},
        closeWebcamModal() {},
        openWebcamModal() {},
        importFromUrl() {},
        removeWebcamPhoto() {},
        syncWebcamPreviewFromUpload() {},
        flipWebcam() {},
        toggleWebcamFlash() {},
        captureWebcamPhoto() {},
        retakeWebcamPhoto() {},
        confirmWebcamPhoto() {},
        destroyUploadSources() {},

        ...segmentControl,
        ...uploadSources,

        init() {
            this.urlError ??= null

            if (this.hasUploadSourceTabs) {
                this.initSegmentControl?.()

                this.$watch('tab', (value) => {
                    this.handleUploadSourceChange?.(value)
                })
            }

            this.$nextTick(() => {
                this.watchReady()
                this.bindFileUpload()
                this.refreshSummary()
            })
        },

        handleUploadSourceChange(source) {
            if (source === 'file') {
                this.$nextTick(async () => {
                    const uploadComponent = this.getFileUploadComponent()

                    if (uploadComponent?.init && ! uploadComponent.pond && this.isLiveUploadRootVisible()) {
                        await uploadComponent.init()
                    }
                })
            } else if (source === 'webcam') {
                this.$nextTick(() => this.syncWebcamPreviewFromUpload?.())
            } else {
                this.closeWebcamModal?.()
            }
        },

        isUploadSource(source) {
            return this.hasUploadSourceTabs ? this.isSelected?.(source) : source === 'file'
        },

        selectUploadSource(source) {
            this.select?.(source)
        },

        watchReady() {
            if (this.displayReady) {
                return
            }

            const root = this.getLiveRoot()

            if (! root || this.readyObserver !== null) {
                return
            }

            const markReady = () => {
                if (this.displayReady) {
                    this.stopReadyWatch()

                    return true
                }

                if (! this.hasStableFilePond(root)) {
                    this.readyStableChecks = 0

                    return false
                }

                this.readyStableChecks++

                if (this.readyStableChecks < 2) {
                    return false
                }

                this.displayReady = true
                this.stopReadyWatch()

                return true
            }

            if (markReady()) {
                return
            }

            this.bindFilePondReadyEvents(markReady)

            this.readyObserver = new MutationObserver(() => {
                markReady()
            })

            this.readyObserver.observe(root, {
                childList: true,
                subtree: true,
            })
        },

        bindFilePondReadyEvents(markReady) {
            const uploadComponent = this.getFileUploadComponent()
            const pond = uploadComponent?.pond

            if (! pond || this.filePondReadyHandler) {
                return
            }

            const events = ['init', 'addfile', 'processfile']

            this.filePondReadyHandler = () => {
                markReady()
            }

            events.forEach((eventName) => {
                pond.on(eventName, this.filePondReadyHandler)
            })
        },



        hasStableFilePond(root) {
            if (! root.querySelector('.filepond--root')) {
                return false
            }

            const uploadComponent = this.getFileUploadComponent()

            return Boolean(uploadComponent?.pond)
        },

        stopReadyWatch() {
            const uploadComponent = this.getFileUploadComponent()
            const pond = uploadComponent?.pond

            if (pond && this.filePondReadyHandler) {
                ;['init', 'addfile', 'processfile'].forEach((eventName) => {
                    pond.off(eventName, this.filePondReadyHandler)
                })
            }

            this.filePondReadyHandler = null
            this.readyObserver?.disconnect()
            this.readyObserver = null
            this.readyStableChecks = 0
        },

        getLiveRoot() {
            return this.$el.querySelector('.fff-flex-file-upload__live')
        },

        bindFileUpload() {
            const root = this.getLiveRoot()

            if (! root) {
                return
            }

            this.observer = new MutationObserver(() => {
                this.refreshSummary()
                this.tagFileIcons()
                this.bindReplaceConfirmation()
            })
            this.observer.observe(root, { childList: true, subtree: true })

            this.bindReplaceConfirmation()
            this.tagFileIcons()
        },

        bindReplaceConfirmation() {
            if (! this.requireReplaceConfirmation || this.isMultiple) {
                return
            }

            const root = this.getLiveRoot()
            const input = root?.querySelector('input[type="file"]')
            const uploadComponent = this.getFileUploadComponent()

            if (! input || ! uploadComponent?.pond || input.dataset.flexReplaceBound === 'true') {
                return
            }

            input.dataset.flexReplaceBound = 'true'

            input.addEventListener('change', (event) => {
                const files = event.target.files

                if (! files || files.length === 0) {
                    return
                }

                const hasExisting = uploadComponent.pond.getFiles().some((file) => file.origin !== 1)

                if (! hasExisting) {
                    this.refreshSummaryLater()

                    return
                }

                if (! window.confirm(this.replaceConfirmationMessage)) {
                    event.target.value = ''
                    event.preventDefault()
                    event.stopImmediatePropagation()
                } else {
                    this.refreshSummaryLater()
                }
            }, true)
        },

        isLiveUploadRootVisible() {
            const root = this.getLiveRoot()

            if (! root) {
                return false
            }

            if (this.hasUploadSourceTabs && ! this.isUploadSource?.('file')) {
                return false
            }

            return (
                root.offsetParent !== null
                && getComputedStyle(root).visibility !== 'hidden'
            )
        },

        getFilamentFileUploadLiveRoot() {
            return this.getLiveRoot()
        },

        filamentUploadComponent: null,
        filamentPond: null,

        registerFilePond(detail) {
            if (detail?.component) {
                this.filamentUploadComponent = detail.component
            }
            if (detail?.pond) {
                this.filamentPond = detail.pond
            }
        },

        getFileUploadComponent() {
            if (this.filamentUploadComponent) {
                return this.filamentUploadComponent
            }

            const liveRoot = this.getLiveRoot()

            if (liveRoot) {
                try {
                    let data = null
                    
                    if (liveRoot._x_dataStack && liveRoot._x_dataStack.length > 0) {
                        data = liveRoot._x_dataStack[0]
                    }

                    if (data && data.pond !== undefined) {
                        this.filamentUploadComponent = data
                        return data
                    }
                } catch (e) {
                    // Ignore
                }
            }

            return null
        },

        resolveFilamentFilePond() {
            const pond = this.filamentPond
            const uploadComponent = this.filamentUploadComponent || {}

            if (! pond) {
                return null
            }

            return { uploadComponent, pond, input: null }
        },

        async ensureFilePondReady(timeoutMs = 15000) {
            const uploadComponent = this.getFileUploadComponent()

            if (uploadComponent?.init && ! uploadComponent.pond) {
                try {
                    await uploadComponent.init()
                } catch {
                    // Best-effort initialization
                }
            }

            const initialResolved = this.resolveFilamentFilePond()

            if (initialResolved?.pond) {
                return initialResolved
            }

            return new Promise((resolve) => {
                const timeout = setTimeout(() => {
                    this.$el.removeEventListener('register-file-pond', listener)
                    resolve(this.resolveFilamentFilePond())
                }, timeoutMs)

                const listener = () => {
                    clearTimeout(timeout)
                    this.$el.removeEventListener('register-file-pond', listener)
                    
                    // Allow the local registerFilePond method to run and populate this.filamentPond
                    setTimeout(() => resolve(this.resolveFilamentFilePond()), 0)
                }

                this.$el.addEventListener('register-file-pond', listener)
            })
        },

        async waitForFileUploadPond(timeoutMs = 8000) {
            const resolved = await this.ensureFilePondReady(timeoutMs)

            return resolved?.uploadComponent ?? null
        },

        async addFileToFilamentPond(file) {
            const target = await this.ensureFilePondReady()

            if (! target?.pond) {
                return false
            }

            const { uploadComponent, pond } = target

            uploadComponent.shouldUpdateState = true

            if (! this.isMultiple) {
                const existingFiles = pond.getFiles() ?? []

                for (const pondFile of existingFiles) {
                    await pond.removeFile(pondFile.id)
                }
            }

            let pondFile = null

            try {
                pondFile = await pond.addFile(file)
            } catch (error) {
                const editorIntercepted = Boolean(
                    uploadComponent.isEditorOpen
                    || uploadComponent.isEditorOpenedForAspectRatio,
                )

                if (! editorIntercepted) {
                    throw error
                }

                return true
            }

            if (! pondFile) {
                return false
            }

            try {
                await this.waitForPondFileProcessing(uploadComponent, pondFile.id)
            } catch (error) {
                const currentFile = pond
                    .getFiles()
                    .find((item) => item.id === pondFile.id)

                if (! currentFile || ! this.pondFileHasProcessedUpload(currentFile)) {
                    throw error
                }
            }

            this.displayReady = true
            this.refreshSummaryLater?.()

            return true
        },

        async waitForAnimationFrames(frameCount = 2) {
            for (let frame = 0; frame < frameCount; frame += 1) {
                await new Promise((resolve) => {
                    window.requestAnimationFrame(resolve)
                })
            }
        },

        readWireUploadState() {
            if (! this.statePath || typeof this.$wire?.get !== 'function') {
                return null
            }

            try {
                return this.$wire.get(this.statePath)
            } catch {
                return null
            }
        },

        uploadStateHasTemporaryUploads(state) {
            if (state === null || state === undefined) {
                return false
            }

            return Object.values(state).some(
                (file) => typeof file === 'string' && file.startsWith('livewire-file:'),
            )
        },

        fieldStateHasTemporaryUploads(uploadComponent) {
            return this.uploadStateHasTemporaryUploads(uploadComponent?.state)
        },

        syncUploadStateFromWire(uploadComponent) {
            const wireState = this.readWireUploadState()

            if (wireState === undefined) {
                return false
            }

            uploadComponent.state = wireState

            return this.uploadStateHasTemporaryUploads(wireState)
        },

        stampFileUploadLastState(uploadComponent) {
            uploadComponent.lastState = JSON.stringify(uploadComponent.state ?? null)
        },

        async waitForCommittedUploadState(uploadComponent, timeoutMs = 10000) {
            const deadline = Date.now() + timeoutMs

            while (Date.now() < deadline) {
                this.syncUploadStateFromWire(uploadComponent)

                if (
                    this.fieldStateHasTemporaryUploads(uploadComponent)
                    || this.uploadStateHasTemporaryUploads(this.readWireUploadState())
                ) {
                    return true
                }

                await this.$nextTick()
                await this.waitForAnimationFrames()
            }

            return false
        },

        async waitForPondFilesToSettle(uploadComponent, timeoutMs = 15000) {
            const pond = uploadComponent?.pond

            if (! pond) {
                return false
            }

            const FileStatus = window.FilePond?.FileStatus
            const processing = FileStatus?.PROCESSING ?? 3
            const processingQueued = FileStatus?.PROCESSING_QUEUED ?? 9
            const loading = FileStatus?.LOADING ?? 7

            const isSettled = () => {
                const files = pond.getFiles()
                if (files.length === 0) return false
                return ! files.some((file) => [processing, processingQueued, loading].includes(file.status))
            }

            if (isSettled()) return true

            return new Promise((resolve) => {
                let check
                const timeout = setTimeout(() => {
                    clearInterval(check)
                    resolve(pond.getFiles().length > 0)
                }, timeoutMs)

                check = setInterval(() => {
                    if (isSettled()) {
                        clearInterval(check)
                        clearTimeout(timeout)
                        resolve(true)
                    }
                }, 100)
            })
        },

        async waitForFieldTemporaryUploadState(uploadComponent, timeoutMs = 10000) {
            if (this.fieldStateHasTemporaryUploads(uploadComponent)) {
                return true
            }

            return new Promise((resolve) => {
                let unwatch
                let check
                
                const timeout = setTimeout(() => {
                    unwatch?.()
                    clearInterval(check)
                    resolve(false)
                }, timeoutMs)

                const checkState = () => {
                    if (this.fieldStateHasTemporaryUploads(uploadComponent)) {
                        unwatch?.()
                        clearInterval(check)
                        clearTimeout(timeout)
                        resolve(true)
                    }
                }

                if (typeof uploadComponent?.$watch === 'function') {
                    unwatch = uploadComponent.$watch('state', checkState)
                }

                check = setInterval(checkState, 100)
            })
        },

        serverUploadedFilesAreReady(uploadComponent) {
            return Object.values(uploadComponent?.fileKeyIndex ?? {}).some(
                (file) => typeof file?.url === 'string' && file.url !== '',
            )
        },

        async waitForServerUploadedFilesReady(uploadComponent, timeoutMs = 15000) {
            if (this.serverUploadedFilesAreReady(uploadComponent)) {
                return true
            }

            return new Promise((resolve) => {
                let unwatch
                let check
                
                const timeout = setTimeout(() => {
                    unwatch?.()
                    clearInterval(check)
                    resolve(false)
                }, timeoutMs)

                const checkState = () => {
                    if (this.serverUploadedFilesAreReady(uploadComponent)) {
                        unwatch?.()
                        clearInterval(check)
                        clearTimeout(timeout)
                        resolve(true)
                    }
                }

                if (typeof uploadComponent?.$watch === 'function') {
                    unwatch = uploadComponent.$watch('fileKeyIndex', checkState)
                }

                check = setInterval(checkState, 100)
            })
        },

        async waitForServerUploadedFiles(uploadComponent, timeoutMs = 10000) {
            if (this.serverUploadedFilesAreReady(uploadComponent)) {
                return true
            }

            return new Promise((resolve) => {
                let unwatch
                let check

                const timeout = setTimeout(() => {
                    unwatch?.()
                    clearInterval(check)
                    resolve(false)
                }, timeoutMs)

                const checkState = () => {
                    if (typeof uploadComponent?.getUploadedFiles === 'function') {
                        uploadComponent.getUploadedFiles()
                    }
                    if (this.serverUploadedFilesAreReady(uploadComponent)) {
                        unwatch?.()
                        clearInterval(check)
                        clearTimeout(timeout)
                        resolve(true)
                    }
                }

                checkState()

                if (typeof uploadComponent?.$watch === 'function') {
                    unwatch = uploadComponent.$watch('fileKeyIndex', checkState)
                }

                check = setInterval(checkState, 100)
            })
        },

        async fetchUploadPreviewBlob(url) {
            if (typeof url !== 'string' || url === '') {
                return null
            }

            try {
                const response = await fetch(url, {
                    cache: 'no-store',
                    credentials: 'same-origin',
                })

                if (! response.ok) {
                    return null
                }

                return await response.blob()
            } catch {
                return null
            }
        },

        async applyNativeFilesToPond(uploadComponent) {
            const pond = uploadComponent?.pond

            if (! pond || typeof uploadComponent.getFiles !== 'function') {
                return false
            }

            if (typeof uploadComponent.getUploadedFiles === 'function') {
                await uploadComponent.getUploadedFiles()
            }

            this.syncUploadStateFromWire?.(uploadComponent)

            uploadComponent.lastState = null
            pond.files = await uploadComponent.getFiles()
            this.stampFileUploadLastState?.(uploadComponent)

            document.dispatchEvent(new Event('visibilitychange'))

            await this.waitForPondFilesToSettle?.(uploadComponent)

            return pond.getFiles().length > 0
        },

        async waitForPondFileUiReady(uploadComponent, timeoutMs = 10000) {
            const pond = uploadComponent?.pond

            if (! pond) {
                return false
            }

            const deadline = Date.now() + timeoutMs

            while (Date.now() < deadline) {
                const item = pond.element?.querySelector('.filepond--item')

                if (item) {
                    const info = item.querySelector('.filepond--file-info-main')
                    const remove = item.querySelector('.filepond--action-remove-item')

                    if (
                        info
                        && remove
                        && getComputedStyle(info).display !== 'none'
                        && getComputedStyle(remove).display !== 'none'
                    ) {
                        return true
                    }
                }

                await this.waitForPondFilesToSettle?.(uploadComponent, 500)
                await this.waitForAnimationFrames(2)
            }

            return false
        },

        buildPondFileItemsFromServerIndex(uploadComponent) {
            const items = []
            const fileKeys = []

            for (const [fileKey, uploadedFile] of Object.entries(uploadComponent?.fileKeyIndex ?? {})) {
                if (typeof uploadedFile?.url !== 'string' || uploadedFile.url === '') {
                    continue
                }

                fileKeys.push(fileKey)
                items.push({
                    source: uploadedFile.url,
                    options: {
                        type: 'local',
                        metadata: {
                            openableUrl: uploadedFile.openableUrl,
                            downloadableUrl: uploadedFile.downloadableUrl,
                        },
                    },
                })
            }

            return { items, fileKeys }
        },

        async preparePondFileItemsFromServer(uploadComponent, { fallbackPreviewUrl = null } = {}) {
            if (typeof uploadComponent?.getUploadedFiles === 'function') {
                await uploadComponent.getUploadedFiles()
            }

            const entries = Object.entries(uploadComponent?.fileKeyIndex ?? {}).filter(
                ([, uploadedFile]) => typeof uploadedFile?.url === 'string' && uploadedFile.url !== '',
            )

            if (entries.length > 0) {
                return this.buildPondFileItemsFromServerIndex(uploadComponent)
            }

            if (typeof fallbackPreviewUrl !== 'string' || fallbackPreviewUrl === '') {
                return { items: [], fileKeys: [] }
            }

            return {
                items: [{
                    source: fallbackPreviewUrl,
                    options: { type: 'local' },
                }],
                fileKeys: [],
            }
        },

        applyPreparedFilesToPond(uploadComponent, prepared) {
            const pond = uploadComponent?.pond
            const { items, fileKeys } = prepared ?? {}

            if (! pond || ! items?.length) {
                return false
            }

            pond.files = items.map((item, index) => {
                const fileKey = fileKeys[index]

                return {
                    ...item,
                    options: {
                        ...item.options,
                        metadata: {
                            ...item.options?.metadata,
                            ...(fileKey ? { serverId: fileKey } : {}),
                        },
                    },
                }
            })

            return pond.getFiles().length > 0
        },

        async refreshWireUploadState() {
            await this.$nextTick()

            await new Promise((resolve) => {
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(resolve)
                })
            })
        },

        pondFileIsUploaded(pondFile) {
            if (! pondFile) {
                return false
            }

            const FileStatus = window.FilePond?.FileStatus
            const processingComplete = FileStatus?.PROCESSING_COMPLETE ?? 5

            return Boolean(
                pondFile.serverId
                || pondFile.origin === window.FilePond?.FileOrigin?.LOCAL
                || pondFile.status === processingComplete,
            )
        },

        pondFileHasProcessedUpload(pondFile) {
            if (! pondFile) {
                return false
            }

            const FileStatus = window.FilePond?.FileStatus
            const processingComplete = FileStatus?.PROCESSING_COMPLETE ?? 5
            const processing = FileStatus?.PROCESSING ?? 3
            const processingQueued = FileStatus?.PROCESSING_QUEUED ?? 9

            return Boolean(
                pondFile.status === processingComplete
                || (
                    pondFile.serverId
                    && pondFile.status !== processing
                    && pondFile.status !== processingQueued
                ),
            )
        },

        lockFileUploadStateUpdates(uploadComponent) {
            if (uploadComponent) {
                uploadComponent.shouldUpdateState = false
            }
        },

        releaseFileUploadStateUpdates(uploadComponent, previousShouldUpdateState) {
            if (! uploadComponent) {
                return
            }

            this.syncUploadStateFromWire(uploadComponent)
            this.stampFileUploadLastState(uploadComponent)
            uploadComponent.shouldUpdateState = previousShouldUpdateState
        },

        async waitForPondFileProcessing(uploadComponent, pondFileId, timeoutMs = 120000) {
            const pond = uploadComponent?.pond

            if (! pond || ! pondFileId) {
                return null
            }

            const FileStatus = window.FilePond?.FileStatus
            const processingComplete = FileStatus?.PROCESSING_COMPLETE ?? 5
            const processing = FileStatus?.PROCESSING ?? 3
            const processingQueued = FileStatus?.PROCESSING_QUEUED ?? 9

            return new Promise((resolve, reject) => {
                const deadline = Date.now() + timeoutMs
                let settled = false

                const findFile = () => pond.getFiles().find((file) => file.id === pondFileId)

                const finish = (file) => {
                    if (settled) {
                        return
                    }

                    settled = true
                    pond.off('processfile', onProcess)
                    pond.off('processfileabort', onAbort)
                    window.clearInterval(intervalId)
                    resolve(file)
                }

                const fail = (error) => {
                    if (settled) {
                        return
                    }

                    settled = true
                    pond.off('processfile', onProcess)
                    pond.off('processfileabort', onAbort)
                    window.clearInterval(intervalId)
                    reject(error)
                }

                const check = () => {
                    const file = findFile()

                    if (! file) {
                        fail(new Error('File was removed from the uploader.'))

                        return
                    }

                    if (
                        file.status === processingComplete
                        || (
                            file.serverId
                            && file.status !== processing
                            && file.status !== processingQueued
                        )
                    ) {
                        finish(file)

                        return
                    }

                    if (Date.now() > deadline) {
                        fail(new Error('File upload timed out.'))
                    }
                }

                const onProcess = () => check()
                const onAbort = () => check()

                pond.on('processfile', onProcess)
                pond.on('processfileabort', onAbort)

                const intervalId = window.setInterval(check, 100)

                check()
            })
        },

        refreshSummaryLater() {
            window.setTimeout(() => this.refreshSummary(), 120)
        },

        refreshSummary() {
            if (! this.showUploadSummary) {
                this.summaryLabel = ''

                return
            }

            const uploadComponent = this.getFileUploadComponent()
            const files = uploadComponent?.pond?.getFiles?.() ?? []
            const persisted = files.filter((file) => file.origin !== 1)
            const count = persisted.length
            const totalBytes = persisted.reduce((carry, file) => carry + (file.fileSize ?? file.file?.size ?? 0), 0)
            const totalKb = Math.round((totalBytes / 1024) * 10) / 10

            this.summaryLabel = this.summaryTemplate
                .replace(':count', String(count))
                .replace(':size', String(totalKb))
        },

        tagFileIcons() {
            if (! this.showFileIcon) {
                return
            }

            const root = this.getLiveRoot()

            if (! root) {
                return
            }

            root.querySelectorAll('.filepond--file-info-main').forEach((element) => {
                const name = element.textContent?.trim() ?? ''
                const extension = name.includes('.') ? name.split('.').pop()?.toLowerCase() : 'file'

                if (extension) {
                    element.dataset.fileType = extension
                }
            })
        },

        destroy() {
            this.observer?.disconnect()
            this.stopReadyWatch()
            this.destroySegmentControl?.()
            this.destroyUploadSources?.()
        },
    }
}

import { createSegmentControlBehavior, createUploadSourcesBehavior } from '../support/flex-file-upload-sources.js'
import { createDropzoneBehavior } from './flex-file-upload/dropzone.js'
import { createPondBridgeBehavior } from './flex-file-upload/pond-bridge.js'
import { createReadyWatchBehavior } from './flex-file-upload/ready-watch.js'
import { createSummaryBehavior } from './flex-file-upload/summary.js'

export { createDropzoneBehavior } from './flex-file-upload/dropzone.js'
export { createPondBridgeBehavior, syncFilePondCompactLayout } from './flex-file-upload/pond-bridge.js'
export { createReadyWatchBehavior } from './flex-file-upload/ready-watch.js'
export { createSummaryBehavior } from './flex-file-upload/summary.js'

export default function flexFileUploadFormComponent({
    showUploadSummary = false,
    requireReplaceConfirmation = false,
    replaceConfirmationMessage = 'Replace the current file?',
    summaryTemplate = ':count file(s), :size KB total',
    remainingSlotsTemplate = null,
    maxFiles = null,
    showFileIcon = false,
    isMultiple = false,
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
        remainingSlotsTemplate,
        maxFiles,
        remainingSlotsLabel: '',
        showFileIcon: Boolean(showFileIcon),
        isMultiple: Boolean(isMultiple),
        summaryLabel: '',
        hasUploadSourceTabs: Boolean(hasUploadSourceTabs),
        allowUrlUpload: Boolean(allowUrlUpload),
        allowWebcamUpload: Boolean(allowWebcamUpload),
        statePath: statePath ?? null,
        labels,
        isDisabled: Boolean(isDisabled),
        urlError: null,
        instantPreviewUrl: null,

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

        ...createReadyWatchBehavior(),
        ...createDropzoneBehavior(),
        ...createPondBridgeBehavior(),
        ...createSummaryBehavior(),
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

        destroy() {
            this.observer?.disconnect()
            this.stopReadyWatch()
            this.destroySegmentControl?.()
            this.destroyUploadSources?.()
        },
    }
}

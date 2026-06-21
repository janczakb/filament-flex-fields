/**
 * Upload summary label and file-type icon tagging for flex-file-upload.
 */
export function createSummaryBehavior() {
    return {
        refreshSummaryLater() {
            window.setTimeout(() => this.refreshSummary(), 120)
        },

        refreshSummary() {
            const uploadComponent = this.getFileUploadComponent()
            const files = uploadComponent?.pond?.getFiles?.() ?? []
            const uploadedFiles = files.filter((file) => this.countsTowardUploadSummary(file))
            const uploadedCount = uploadedFiles.length

            this.refreshRemainingSlots(files.length)

            if (! this.showUploadSummary) {
                this.summaryLabel = ''

                return
            }

            if (uploadedCount === 0) {
                this.summaryLabel = ''

                return
            }

            const totalBytes = uploadedFiles.reduce(
                (carry, file) => carry + (file.fileSize ?? file.file?.size ?? 0),
                0,
            )
            const totalKb = Math.round((totalBytes / 1024) * 10) / 10

            this.summaryLabel = this.summaryTemplate
                .replace(':count', String(uploadedCount))
                .replace(':size', String(totalKb))
        },

        countsTowardUploadSummary(file) {
            const processingComplete = 5
            const localOrigin = 3

            return Boolean(
                file?.serverId
                || file?.origin === localOrigin
                || file?.status === processingComplete,
            )
        },

        refreshRemainingSlots(usedSlots) {
            if (! this.remainingSlotsTemplate || this.maxFiles == null) {
                this.remainingSlotsLabel = ''

                return
            }

            const remaining = Math.max(this.maxFiles - usedSlots, 0)

            this.remainingSlotsLabel = this.remainingSlotsTemplate
                .replace(':remaining', String(remaining))
                .replace(':max', String(this.maxFiles))
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
    }
}

/**
 * FilePond per-file status and processing waits.
 */
export function createPondFileStatusBehavior() {
    return {
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
    }
}

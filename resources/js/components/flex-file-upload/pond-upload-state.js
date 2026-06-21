/**
 * Livewire / FilePond upload state synchronization helpers.
 */
export function createPondUploadStateBehavior() {
    return {
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
    }
}

/**
 * Resolves Filament FilePond component instances for flex-file-upload.
 */
export function createPondComponentResolverBehavior() {
    return {
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

            const liveRoot = this.getLiveRoot?.()

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
                } catch {
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

                    setTimeout(() => resolve(this.resolveFilamentFilePond()), 0)
                }

                this.$el.addEventListener('register-file-pond', listener)
            })
        },

        async waitForFileUploadPond(timeoutMs = 8000) {
            const resolved = await this.ensureFilePondReady(timeoutMs)

            return resolved?.uploadComponent ?? null
        },

        async waitForAnimationFrames(frameCount = 2) {
            for (let frame = 0; frame < frameCount; frame += 1) {
                await new Promise((resolve) => {
                    window.requestAnimationFrame(resolve)
                })
            }
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
    }
}

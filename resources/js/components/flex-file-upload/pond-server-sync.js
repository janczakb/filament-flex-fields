/**
 * Server-side file index → FilePond UI synchronization.
 */
export function createPondServerSyncBehavior() {
    return {
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
    }
}

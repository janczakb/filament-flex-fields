export default function flexFileUploadFormComponent({
    showUploadSummary = false,
    requireReplaceConfirmation = false,
    replaceConfirmationMessage = 'Replace the current file?',
    summaryTemplate = ':count file(s), :size KB total',
    remainingSlotsLabel = null,
    showFileIcon = false,
    isMultiple = false,
    initialSummaryLabel = '',
}) {
    return {
        showUploadSummary: Boolean(showUploadSummary),
        requireReplaceConfirmation: Boolean(requireReplaceConfirmation),
        replaceConfirmationMessage,
        summaryTemplate,
        remainingSlotsLabel,
        showFileIcon: Boolean(showFileIcon),
        isMultiple: Boolean(isMultiple),
        summaryLabel: initialSummaryLabel ?? '',
        displayReady: false,
        observer: null,
        readyObserver: null,
        readyStableChecks: 0,
        filePondReadyHandler: null,
        morphHookRegistered: false,

        init() {
            this.$nextTick(() => {
                this.watchReady()
                this.bindFileUpload()
                this.refreshSummary()
            })
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
            this.bindLivewireMorphHook(markReady)

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

        bindLivewireMorphHook(markReady) {
            if (this.morphHookRegistered || typeof Livewire === 'undefined') {
                return
            }

            this.morphHookRegistered = true

            Livewire.hook('morph.updated', ({ el }) => {
                const root = this.getLiveRoot()

                if (! root || ! root.contains(el)) {
                    return
                }

                markReady()
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

        getFileUploadComponent() {
            const root = this.getLiveRoot()

            if (! root) {
                return null
            }

            if (typeof Alpine !== 'undefined' && typeof Alpine.$data === 'function') {
                try {
                    return Alpine.$data(root)
                } catch {
                    // Fall back to legacy Alpine stack access.
                }
            }

            return root._x_dataStack?.[0] ?? null
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
        },
    }
}

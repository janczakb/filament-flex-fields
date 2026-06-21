/**
 * Binds flex-file-upload dropzone observers and replace-confirmation guards.
 */
export function createDropzoneBehavior() {
    return {
        observer: null,

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
    }
}

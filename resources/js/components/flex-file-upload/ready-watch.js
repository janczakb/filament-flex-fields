/**
 * Waits for Filament FilePond to mount before revealing flex-file-upload chrome.
 */
export function createReadyWatchBehavior() {
    return {
        displayReady: false,
        readyObserver: null,
        readyStableChecks: 0,
        filePondReadyHandler: null,

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
    }
}

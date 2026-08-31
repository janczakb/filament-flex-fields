import Alpine from 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/module.esm.js'

Alpine.data('overlayMatrixField', () => ({
    open: false,
    ready: false,
    reopenCount: 0,

    toggle() {
        this.open = ! this.open

        if (this.open) {
            this.$nextTick(() => {
                this.ready = true
            })
        } else {
            this.ready = false
        }
    },

    close() {
        this.open = false
        this.ready = false
    },

    reopen() {
        this.reopenCount += 1
        this.open = true
        this.$nextTick(() => {
            this.ready = true
        })
    },
}))

Alpine.data('overlayMatrixModal', () => ({
    modalOpen: false,

    openModal() {
        this.modalOpen = true
    },

    closeModal() {
        this.modalOpen = false
    },
}))

window.Alpine = Alpine
Alpine.start()

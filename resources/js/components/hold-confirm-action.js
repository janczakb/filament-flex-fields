export default function holdConfirmActionFormComponent({
    duration = 2000,
    releaseDuration = 200,
    sweep = 'right',
    disabled = false,
}) {
    return {
        duration,
        releaseDuration,
        sweep,
        disabled,
        progress: 0,
        holding: false,
        raf: null,
        lastTs: null,
        trigger: null,
        overlay: null,

        init() {
            this.trigger = this.$el.querySelector('.fff-hold-confirm-action')

            if (! this.trigger) {
                return
            }

            this.overlay = this.trigger.querySelector('.fff-hold-confirm-action__overlay')
            this.ensureLayers()
            this.overlay = this.trigger.querySelector('.fff-hold-confirm-action__overlay')
            this.applyProgress(0)

            this.trigger.addEventListener('pointerdown', (event) => this.onPointerDown(event))
            window.addEventListener('pointerup', this.onPointerUpBound = () => this.onPointerUp())
            window.addEventListener('pointercancel', this.onPointerUpBound)
            this.trigger.addEventListener('pointerleave', () => this.onPointerUp())
            this.trigger.addEventListener('click', (event) => event.preventDefault())
            this.trigger.addEventListener('contextmenu', (event) => event.preventDefault())
        },

        destroy() {
            if (this.onPointerUpBound) {
                window.removeEventListener('pointerup', this.onPointerUpBound)
                window.removeEventListener('pointercancel', this.onPointerUpBound)
            }
        },

        ensureLayers() {
            if (
                this.trigger.dataset.fffHoldLayersReady === 'true'
                && this.trigger.querySelector('.fff-hold-confirm-action__base')
                && this.trigger.querySelector('.fff-hold-confirm-action__overlay')
            ) {
                return
            }

            const skipSelector = '[wire\\:loading], [x-cloak], .fff-hold-confirm-action__overlay, .fff-hold-confirm-action__base'
            const base = document.createElement('span')
            base.className = 'fff-hold-confirm-action__base'
            base.dataset.slot = 'hold-confirm-base'

            ;[...this.trigger.childNodes].forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE && node.matches?.(skipSelector)) {
                    return
                }

                if (node.nodeType === Node.COMMENT_NODE) {
                    return
                }

                if (node.nodeType === Node.TEXT_NODE && node.textContent?.trim() === '') {
                    return
                }

                base.appendChild(node)
            })

            const overlay = document.createElement('span')
            overlay.className = 'fff-hold-confirm-action__overlay'
            overlay.dataset.slot = 'hold-confirm-overlay'
            overlay.dataset.sweep = this.sweep
            overlay.setAttribute('aria-hidden', 'true')
            overlay.innerHTML = base.innerHTML

            this.trigger.prepend(overlay)
            this.trigger.appendChild(base)

            this.trigger.dataset.fffHoldLayersReady = 'true'
            this.trigger.dataset.sweep = this.sweep
        },

        clipPathFor(progress) {
            const percent = Math.max(0, Math.min(1, progress)) * 100
            const remaining = 100 - percent

            switch (this.sweep) {
                case 'left':
                    return `inset(0 0 0 ${remaining}%)`
                case 'up':
                    return `inset(${remaining}% 0 0 0)`
                case 'down':
                    return `inset(0 0 ${remaining}% 0)`
                default:
                    return `inset(0 ${remaining}% 0 0)`
            }
        },

        applyProgress(progress) {
            if (! this.trigger) {
                return
            }

            const clipPath = this.clipPathFor(progress)

            this.progress = progress
            this.trigger.style.setProperty('--fff-hold-progress', String(progress))
            this.trigger.style.setProperty('--fff-hold-confirm-clip', clipPath)
            this.trigger.dataset.holding = this.holding ? 'true' : 'false'
            this.trigger.dataset.filled = progress > 0 ? 'true' : 'false'

            if (this.overlay) {
                this.overlay.style.clipPath = clipPath
            }
        },

        activeDuration() {
            return this.holding ? this.duration : this.releaseDuration
        },

        tick(timestamp) {
            if (this.lastTs === null) {
                this.lastTs = timestamp
            }

            const delta = timestamp - this.lastTs
            this.lastTs = timestamp
            const step = delta / this.activeDuration()

            if (this.holding) {
                const next = this.progress + step

                if (next >= 1) {
                    this.applyProgress(1)
                    this.holding = false
                    this.stopAnimationFrame()

                    this.$el.dispatchEvent(new CustomEvent('fff-hold-complete', { bubbles: true }))

                    if (window.navigator?.vibrate) {
                        window.navigator.vibrate(12)
                    }

                    this.applyProgress(0)

                    return
                }

                this.applyProgress(next)
            } else {
                const next = this.progress - step

                if (next <= 0) {
                    this.applyProgress(0)
                    this.stopAnimationFrame()

                    return
                }

                this.applyProgress(next)
            }

            this.raf = requestAnimationFrame((frame) => this.tick(frame))
        },

        startAnimationFrame() {
            if (this.raf !== null) {
                return
            }

            this.lastTs = null
            this.raf = requestAnimationFrame((frame) => this.tick(frame))
        },

        stopAnimationFrame() {
            if (this.raf !== null) {
                cancelAnimationFrame(this.raf)
            }

            this.raf = null
            this.lastTs = null
        },

        onPointerDown(event) {
            if (this.disabled || event.button !== 0) {
                return
            }

            event.preventDefault()
            event.stopPropagation()
            this.holding = true
            this.startAnimationFrame()
        },

        onPointerUp() {
            if (! this.holding) {
                return
            }

            this.holding = false

            if (this.progress < 1) {
                this.startAnimationFrame()
            }
        },
    }
}

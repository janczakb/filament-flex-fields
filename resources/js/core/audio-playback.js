export function createAudioPlaybackMixin({
    canInteract = () => true,
    resolveDuration = null,
} = {}) {
    return {
        playing: false,
        currentTime: 0,
        duration: 0,
        seeking: false,

        bindAudioElement(audio) {
            if (! audio) {
                return
            }

            audio.addEventListener('loadedmetadata', () => {
                this.duration = audio.duration || 0
            })

            audio.addEventListener('timeupdate', () => {
                if (! this.seeking) {
                    this.currentTime = audio.currentTime || 0
                }
            })

            audio.addEventListener('play', () => {
                this.playing = true
            })

            audio.addEventListener('pause', () => {
                this.playing = false
            })

            audio.addEventListener('ended', () => {
                this.playing = false
                this.currentTime = 0
            })

            if (audio.readyState >= 1) {
                this.duration = audio.duration || 0
            }
        },

        get progressRatio() {
            const resolved = typeof resolveDuration === 'function'
                ? resolveDuration.call(this)
                : this.duration

            if (! resolved) {
                return 0
            }

            return Math.max(0, Math.min(1, this.currentTime / resolved))
        },

        toggleAudioPlayback() {
            if (! canInteract.call(this)) {
                return
            }

            const audio = this.$refs.audio

            if (! audio) {
                return
            }

            if (audio.paused) {
                audio.play().then(() => {
                    this.playing = true
                }).catch(() => {
                    this.playing = false
                })
            } else {
                audio.pause()
                this.playing = false
            }
        },

        seekAudioTo(ratio) {
            if (! canInteract.call(this)) {
                return
            }

            const audio = this.$refs.audio
            const resolved = typeof resolveDuration === 'function'
                ? resolveDuration.call(this)
                : this.duration

            if (! audio || ! resolved) {
                return
            }

            const next = Math.max(0, Math.min(resolved, ratio * resolved))

            audio.currentTime = next
            this.currentTime = next
        },

        onWaveformPointerDown(event) {
            if (! canInteract.call(this)) {
                return
            }

            const audio = this.$refs.audio

            if (! audio) {
                return
            }

            const resolved = typeof resolveDuration === 'function'
                ? resolveDuration.call(this)
                : this.duration

            if (! resolved && Number.isFinite(audio.duration) && audio.duration > 0) {
                this.duration = audio.duration
            }

            const duration = typeof resolveDuration === 'function'
                ? resolveDuration.call(this)
                : this.duration

            if (! duration) {
                return
            }

            event.preventDefault()

            this.seeking = true
            this.seekFromWaveformPointerEvent(event)

            const onMove = (moveEvent) => {
                this.seekFromWaveformPointerEvent(moveEvent)
            }

            const onUp = () => {
                this.seeking = false
                window.removeEventListener('pointermove', onMove)
                window.removeEventListener('pointerup', onUp)
                window.removeEventListener('pointercancel', onUp)
            }

            window.addEventListener('pointermove', onMove)
            window.addEventListener('pointerup', onUp)
            window.addEventListener('pointercancel', onUp)
        },

        seekFromWaveformPointerEvent(event) {
            const waveform = this.$refs.waveform

            if (! waveform) {
                return
            }

            const rect = waveform.getBoundingClientRect()

            if (! rect.width) {
                return
            }

            const ratio = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width))

            this.seekAudioTo(ratio)
        },
    }
}

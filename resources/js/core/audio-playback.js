/**
 * Shared HTMLAudioElement playback + waveform scrubbing for AudioField / VoiceNote.
 *
 * Visual progress uses a CSS custom property (--fff-audio-progress) updated via rAF
 * so the playhead / played clip stay smooth without thrashing Alpine every frame.
 */
export function createAudioPlaybackMixin({
    canInteract = () => true,
    resolveDuration = null,
} = {}) {
    return {
        playing: false,
        currentTime: 0,
        duration: 0,
        seeking: false,
        _boundAudio: null,
        _audioAbort: null,
        _seekAbort: null,
        _progressRaf: null,
        _lastLabelMs: 0,

        bindAudioElement(audio) {
            this.unbindAudioElement()

            if (! audio) {
                return
            }

            this._boundAudio = audio
            this._audioAbort = new AbortController()
            const { signal } = this._audioAbort

            audio.addEventListener('loadedmetadata', () => {
                this.duration = Number.isFinite(audio.duration) ? audio.duration : 0
                this.syncVisualProgress({ forceLabel: true })
            }, { signal })

            audio.addEventListener('durationchange', () => {
                this.duration = Number.isFinite(audio.duration) ? audio.duration : 0
                this.syncVisualProgress({ forceLabel: true })
            }, { signal })

            audio.addEventListener('timeupdate', () => {
                if (! this.seeking && ! this._progressRaf) {
                    this.syncVisualProgress({ forceLabel: true })
                }
            }, { signal })

            audio.addEventListener('play', () => {
                this.playing = true
                this.startProgressLoop()
            }, { signal })

            audio.addEventListener('pause', () => {
                this.playing = false

                if (! this.seeking) {
                    this.stopProgressLoop()
                    this.syncVisualProgress({ forceLabel: true })
                }
            }, { signal })

            audio.addEventListener('ended', () => {
                this.playing = false
                this.currentTime = 0
                this.stopProgressLoop()
                this.syncVisualProgress({ forceLabel: true })
            }, { signal })

            if (audio.readyState >= 1) {
                this.duration = Number.isFinite(audio.duration) ? audio.duration : 0
                this.syncVisualProgress({ forceLabel: true })
            }
        },

        unbindAudioElement() {
            this.stopSeekListeners()
            this.stopProgressLoop()
            this._audioAbort?.abort()
            this._audioAbort = null
            this._boundAudio = null
        },

        resolvePlaybackDuration() {
            if (typeof resolveDuration === 'function') {
                const resolved = resolveDuration.call(this)

                if (Number.isFinite(resolved) && resolved > 0) {
                    return resolved
                }
            }

            if (Number.isFinite(this.duration) && this.duration > 0) {
                return this.duration
            }

            const audio = this._boundAudio ?? this.$refs?.audio

            if (audio && Number.isFinite(audio.duration) && audio.duration > 0) {
                this.duration = audio.duration

                return audio.duration
            }

            return 0
        },

        get progressRatio() {
            const resolved = this.resolvePlaybackDuration()

            if (! resolved) {
                return 0
            }

            return Math.max(0, Math.min(1, this.currentTime / resolved))
        },

        /** Playhead is visible only while audio is playing (hidden when paused). */
        get playheadVisible() {
            return this.playing
        },

        syncVisualProgress({ forceLabel = false } = {}) {
            const audio = this._boundAudio ?? this.$refs?.audio
            const waveform = this.$refs?.waveform
            const resolved = this.resolvePlaybackDuration()
            let time = this.currentTime

            if (audio && ! this.seeking) {
                time = Number.isFinite(audio.currentTime) ? audio.currentTime : 0
            }

            const ratio = resolved
                ? Math.max(0, Math.min(1, time / resolved))
                : 0

            if (waveform) {
                waveform.style.setProperty('--fff-audio-progress', String(ratio))
                waveform.classList.toggle('is-scrubbing', this.seeking)
                waveform.classList.toggle('is-playing', this.playing)
            }

            const now = typeof performance !== 'undefined' ? performance.now() : Date.now()

            if (forceLabel || now - this._lastLabelMs >= 48) {
                this._lastLabelMs = now
                this.currentTime = time
            }
        },

        startProgressLoop() {
            if (this._progressRaf) {
                return
            }

            const tick = () => {
                this.syncVisualProgress()

                if (this.playing || this.seeking) {
                    this._progressRaf = requestAnimationFrame(tick)
                } else {
                    this._progressRaf = null
                    this.syncVisualProgress({ forceLabel: true })
                }
            }

            this._progressRaf = requestAnimationFrame(tick)
        },

        stopProgressLoop() {
            if (! this._progressRaf) {
                return
            }

            cancelAnimationFrame(this._progressRaf)
            this._progressRaf = null
        },

        stopSeekListeners() {
            this._seekAbort?.abort()
            this._seekAbort = null
            this.seeking = false
        },

        toggleAudioPlayback() {
            if (! canInteract.call(this)) {
                return
            }

            const audio = this._boundAudio ?? this.$refs?.audio

            if (! audio) {
                return
            }

            if (audio.paused) {
                audio.play().then(() => {
                    this.playing = true
                    this.startProgressLoop()
                }).catch(() => {
                    this.playing = false
                    this.stopProgressLoop()
                })
            } else {
                audio.pause()
                this.playing = false
                this.stopProgressLoop()
                this.syncVisualProgress({ forceLabel: true })
            }
        },

        seekAudioTo(ratio) {
            if (! canInteract.call(this)) {
                return
            }

            const audio = this._boundAudio ?? this.$refs?.audio
            const resolved = this.resolvePlaybackDuration()

            if (! audio || ! resolved) {
                return
            }

            const next = Math.max(0, Math.min(resolved, ratio * resolved))

            try {
                audio.currentTime = next
            } catch {
                return
            }

            this.currentTime = next
            this.syncVisualProgress({ forceLabel: true })
        },

        onWaveformPointerDown(event) {
            if (! canInteract.call(this)) {
                return
            }

            const audio = this._boundAudio ?? this.$refs?.audio
            const waveform = this.$refs?.waveform

            if (! audio || ! waveform || ! this.resolvePlaybackDuration()) {
                return
            }

            if (event.button != null && event.button !== 0) {
                return
            }

            event.preventDefault()

            this.stopSeekListeners()
            this.seeking = true
            this.startProgressLoop()
            this.seekFromWaveformPointerEvent(event)

            try {
                waveform.setPointerCapture?.(event.pointerId)
            } catch {
                // Older browsers / failed capture — window listeners still work.
            }

            this._seekAbort = new AbortController()
            const { signal } = this._seekAbort

            const onMove = (moveEvent) => {
                this.seekFromWaveformPointerEvent(moveEvent)
            }

            const onUp = (upEvent) => {
                try {
                    waveform.releasePointerCapture?.(upEvent.pointerId)
                } catch {
                    // no-op
                }

                this.stopSeekListeners()

                if (! this.playing) {
                    this.stopProgressLoop()
                }

                this.syncVisualProgress({ forceLabel: true })
            }

            window.addEventListener('pointermove', onMove, { signal })
            window.addEventListener('pointerup', onUp, { signal })
            window.addEventListener('pointercancel', onUp, { signal })
        },

        seekFromWaveformPointerEvent(event) {
            const waveform = this.$refs?.waveform

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

export function formatVideoTime(seconds) {
    if (! Number.isFinite(seconds) || seconds < 0) {
        return '0:00'
    }

    const total = Math.floor(seconds)
    const hours = Math.floor(total / 3600)
    const minutes = Math.floor((total % 3600) / 60)
    const secs = total % 60

    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
    }

    return `${minutes}:${String(secs).padStart(2, '0')}`
}

let youtubeApiPromise = null

function loadYoutubeIframeApi() {
    if (window.YT?.Player) {
        return Promise.resolve(window.YT)
    }

    if (youtubeApiPromise) {
        return youtubeApiPromise
    }

    youtubeApiPromise = new Promise((resolve) => {
        const previous = window.onYouTubeIframeAPIReady

        window.onYouTubeIframeAPIReady = () => {
            previous?.()
            resolve(window.YT)
        }

        const script = document.createElement('script')
        script.src = 'https://www.youtube.com/iframe_api'
        document.head.appendChild(script)
    })

    return youtubeApiPromise
}

export default function videoFieldFormComponent({
    state,
    staticSrc = null,
    provider = 'html5',
    youtubeId = null,
    youtubeNoCookie = true,
    youtubePlayerVars = {},
    youtubeThumbnail = null,
    poster = null,
    title = null,
    subtitle = null,
    showControls = true,
    nativeControls = false,
    autoplay = false,
    loop = false,
    muted = false,
    playsInline = true,
    readOnly = false,
    fullscreenable = true,
    autoHideControls = true,
    pictureInPictureable = false,
    volumeControl = true,
    labels = {},
}) {
    return {
        state,
        staticSrc,
        provider,
        youtubeId,
        youtubeNoCookie,
        youtubePlayerVars,
        youtubeThumbnail,
        poster,
        title,
        subtitle,
        showControls,
        nativeControls,
        autoplay,
        loop,
        muted,
        playsInline,
        readOnly,
        fullscreenable,
        autoHideControls,
        pictureInPictureable,
        volumeControl,
        labels,
        playing: false,
        currentTime: 0,
        duration: 0,
        bufferedRatio: 0,
        showUi: true,
        uiTransitionsEnabled: false,
        hideUiTimeout: null,
        seeking: false,
        scrubPosition: 0,
        volume: muted ? 0 : 1,
        volumeBeforeMute: 1,
        volumeOpen: false,
        isFullscreen: false,
        fullscreenListener: null,
        isPictureInPicture: false,
        pictureInPictureSupported: false,
        pictureInPictureListener: null,
        documentPictureInPictureListener: null,
        facadeActive: provider === 'youtube' && ! autoplay,
        youtubePlayer: null,
        youtubeProgressFrame: null,
        youtubeReady: false,

        init() {
            this.fullscreenListener = () => {
                this.isFullscreen = document.fullscreenElement === this.$refs.frame
            }

            document.addEventListener('fullscreenchange', this.fullscreenListener)

            if (this.provider === 'youtube') {
                if (this.autoplay && ! this.readOnly) {
                    this.ensureYoutubePlayer().then(() => {
                        this.youtubePlayer?.playVideo?.()
                    }).catch(() => {})
                }

                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        this.uiTransitionsEnabled = true
                    })
                })

                return
            }

            const video = this.$refs.video

            if (! video) {
                return
            }

            video.volume = this.muted ? 0 : 1
            this.volume = video.muted ? 0 : video.volume

            video.addEventListener('loadedmetadata', () => {
                this.duration = video.duration || 0
                this.scrubPosition = Math.round(this.progressRatio * 1000)
            })

            video.addEventListener('timeupdate', () => {
                if (! this.seeking) {
                    this.currentTime = video.currentTime || 0
                    this.scrubPosition = Math.round(this.progressRatio * 1000)
                }
            })

            video.addEventListener('progress', () => {
                this.bufferedRatio = this.resolveBufferedRatio(video)
            })

            video.addEventListener('play', () => {
                this.playing = true

                if (this.autoHideControls) {
                    this.scheduleHideUi()
                }
            })

            video.addEventListener('pause', () => {
                this.playing = false
                this.showUi = true
                this.clearHideUiTimeout()
            })

            video.addEventListener('ended', () => {
                this.playing = false
                this.showUi = true
                this.clearHideUiTimeout()
            })

            video.addEventListener('volumechange', () => {
                this.muted = video.muted || video.volume === 0
                this.volume = video.muted ? 0 : video.volume
            })

            this.pictureInPictureSupported = this.pictureInPictureable
                && typeof document !== 'undefined'
                && document.pictureInPictureEnabled !== false
                && typeof video.requestPictureInPicture === 'function'
                && ! video.disablePictureInPicture

            this.syncPictureInPictureState = () => {
                this.isPictureInPicture = document.pictureInPictureElement === video

                if (this.isPictureInPicture) {
                    this.showUi = true
                    this.clearHideUiTimeout()
                }
            }

            this.pictureInPictureListener = () => {
                this.syncPictureInPictureState()
            }

            this.documentPictureInPictureListener = () => {
                this.syncPictureInPictureState()
            }

            video.addEventListener('enterpictureinpicture', this.pictureInPictureListener)
            video.addEventListener('leavepictureinpicture', this.pictureInPictureListener)
            document.addEventListener('enterpictureinpicture', this.documentPictureInPictureListener)
            document.addEventListener('leavepictureinpicture', this.documentPictureInPictureListener)

            this.syncPictureInPictureState()

            if (this.autoplay && ! this.readOnly) {
                video.play().catch(() => {
                    this.playing = false
                })
            }

            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.uiTransitionsEnabled = true
                })
            })
        },

        destroy() {
            if (this.fullscreenListener) {
                document.removeEventListener('fullscreenchange', this.fullscreenListener)
            }

            const video = this.$refs.video

            if (video && this.pictureInPictureListener) {
                video.removeEventListener('enterpictureinpicture', this.pictureInPictureListener)
                video.removeEventListener('leavepictureinpicture', this.pictureInPictureListener)
            }

            if (this.documentPictureInPictureListener) {
                document.removeEventListener('enterpictureinpicture', this.documentPictureInPictureListener)
                document.removeEventListener('leavepictureinpicture', this.documentPictureInPictureListener)
            }

            if (this.youtubeProgressFrame) {
                cancelAnimationFrame(this.youtubeProgressFrame)
                this.youtubeProgressFrame = null
            }

            this.youtubePlayer?.destroy?.()
            this.youtubePlayer = null
            this.clearHideUiTimeout()
        },

        get videoSrc() {
            return this.staticSrc || this.state || ''
        },

        get progressRatio() {
            if (! this.duration) {
                return 0
            }

            return Math.max(0, Math.min(1, this.currentTime / this.duration))
        },

        get progressInputValue() {
            if (this.seeking) {
                return this.scrubPosition
            }

            return Math.round(this.progressRatio * 1000)
        },

        get displayProgressRatio() {
            if (this.seeking && this.duration) {
                return Math.max(0, Math.min(1, this.scrubPosition / 1000))
            }

            return this.progressRatio
        },

        get canScrub() {
            return this.canInteract && this.duration > 0
        },

        get currentLabel() {
            return formatVideoTime(this.currentTime)
        },

        get remainingLabel() {
            return `-${formatVideoTime(Math.max(0, this.duration - this.currentTime))}`
        },

        get durationRangeLabel() {
            return `${this.currentLabel} / ${formatVideoTime(this.duration)}`
        },

        get playLabel() {
            return this.playing ? (this.labels.pause ?? 'Pause') : (this.labels.play ?? 'Play')
        },

        get canInteract() {
            if (this.readOnly) {
                return false
            }

            if (this.provider === 'youtube') {
                return !! this.youtubeId
            }

            return this.videoSrc !== ''
        },

        resolveBufferedRatio(video) {
            if (! video.duration || ! video.buffered?.length) {
                return 0
            }

            return Math.max(0, Math.min(1, video.buffered.end(video.buffered.length - 1) / video.duration))
        },

        async ensureYoutubePlayer() {
            if (this.youtubePlayer || ! this.youtubeId) {
                return this.youtubePlayer
            }

            this.facadeActive = false

            const YT = await loadYoutubeIframeApi()
            const host = this.youtubeNoCookie
                ? 'https://www.youtube-nocookie.com'
                : 'https://www.youtube.com'

            await new Promise((resolve) => {
                this.youtubePlayer = new YT.Player(this.$refs.youtubePlayer, {
                    videoId: this.youtubeId,
                    host,
                    playerVars: this.youtubePlayerVars,
                    events: {
                        onReady: (event) => {
                            this.youtubeReady = true
                            this.duration = event.target.getDuration() || 0
                            this.bufferedRatio = event.target.getVideoLoadedFraction?.() || 0

                            if (this.muted || this.volume === 0) {
                                event.target.mute()
                            } else {
                                event.target.setVolume(Math.round(this.volume * 100))
                            }

                            resolve()
                        },
                        onStateChange: (event) => {
                            const state = event.data
                            this.playing = state === YT.PlayerState.PLAYING || state === YT.PlayerState.BUFFERING

                            if (state === YT.PlayerState.ENDED) {
                                this.playing = false
                                this.showUi = true
                                this.clearHideUiTimeout()
                                this.stopYoutubeProgressLoop()

                                return
                            }

                            if (this.playing) {
                                this.startYoutubeProgressLoop()

                                if (this.autoHideControls) {
                                    this.scheduleHideUi()
                                }
                            } else {
                                this.showUi = true
                                this.clearHideUiTimeout()
                                this.stopYoutubeProgressLoop()
                                this.syncYoutubeProgress()
                            }
                        },
                    },
                })
            })

            return this.youtubePlayer
        },

        syncYoutubeProgress() {
            if (! this.youtubePlayer?.getCurrentTime) {
                return
            }

            this.currentTime = this.youtubePlayer.getCurrentTime() || 0
            this.duration = this.youtubePlayer.getDuration() || this.duration

            if (! this.seeking) {
                this.scrubPosition = Math.round(this.progressRatio * 1000)
            }

            this.bufferedRatio = this.youtubePlayer.getVideoLoadedFraction?.() || 0
        },

        startYoutubeProgressLoop() {
            this.stopYoutubeProgressLoop()

            const tick = () => {
                if (! this.youtubePlayer?.getCurrentTime) {
                    this.youtubeProgressFrame = requestAnimationFrame(tick)

                    return
                }

                if (this.playing && ! this.seeking) {
                    this.syncYoutubeProgress()
                }

                if (this.playing || this.seeking) {
                    this.youtubeProgressFrame = requestAnimationFrame(tick)
                }
            }

            this.youtubeProgressFrame = requestAnimationFrame(tick)
        },

        stopYoutubeProgressLoop() {
            if (this.youtubeProgressFrame) {
                cancelAnimationFrame(this.youtubeProgressFrame)
                this.youtubeProgressFrame = null
            }
        },

        async togglePlay() {
            if (! this.canInteract) {
                return
            }

            if (this.provider === 'youtube') {
                await this.ensureYoutubePlayer()

                const YT = window.YT
                const state = this.youtubePlayer?.getPlayerState?.()

                if (state === YT.PlayerState.PLAYING || state === YT.PlayerState.BUFFERING) {
                    this.youtubePlayer.pauseVideo()
                } else {
                    this.youtubePlayer.playVideo()
                }

                this.revealUi()

                return
            }

            const video = this.$refs.video

            if (! video) {
                return
            }

            if (video.paused) {
                video.play()
            } else {
                video.pause()
            }

            this.revealUi()
        },

        toggleMute() {
            if (! this.canInteract || ! this.volumeControl) {
                return
            }

            if (this.muted || this.volume === 0) {
                this.setVolume(this.volumeBeforeMute > 0 ? this.volumeBeforeMute : 1)
            } else {
                this.volumeBeforeMute = this.volume
                this.setVolume(0)
            }
        },

        setVolume(value) {
            if (! this.canInteract || ! this.volumeControl) {
                return
            }

            const next = Math.max(0, Math.min(1, Number(value)))

            if (this.provider === 'youtube') {
                this.volume = next
                this.muted = next === 0

                if (this.youtubePlayer?.setVolume) {
                    if (next === 0) {
                        this.youtubePlayer.mute()
                    } else {
                        this.youtubePlayer.unMute()
                        this.youtubePlayer.setVolume(Math.round(next * 100))
                    }
                }

                if (! this.muted) {
                    this.volumeBeforeMute = next
                }

                this.revealUi()

                return
            }

            const video = this.$refs.video

            if (! video) {
                return
            }

            video.volume = next
            video.muted = next === 0
            this.muted = video.muted
            this.volume = video.muted ? 0 : video.volume

            if (! video.muted) {
                this.volumeBeforeMute = video.volume
            }

            this.revealUi()
        },

        onVolumeInput(event) {
            this.setVolume(Number(event.target.value) / 100)
        },

        toggleVolumePanel() {
            if (! this.canInteract || ! this.volumeControl) {
                return
            }

            this.volumeOpen = ! this.volumeOpen
            this.revealUi()
        },

        closeVolumePanel() {
            this.volumeOpen = false
        },

        toggleFullscreen() {
            if (! this.canInteract || ! this.fullscreenable) {
                return
            }

            const frame = this.$refs.frame

            if (! frame) {
                return
            }

            if (! document.fullscreenElement) {
                frame.requestFullscreen?.().catch(() => {})
            } else {
                document.exitFullscreen?.().catch(() => {})
            }

            this.revealUi()
        },

        async togglePictureInPicture() {
            if (! this.canInteract || ! this.pictureInPictureSupported) {
                return
            }

            const video = this.$refs.video

            if (! video) {
                return
            }

            try {
                if (document.pictureInPictureElement === video) {
                    await document.exitPictureInPicture()
                } else {
                    await video.requestPictureInPicture()
                }
            } catch {
                // Browser blocked PiP or feature unavailable.
            }

            this.revealUi()
        },

        async seekTo(ratio) {
            if (! this.canInteract || ! this.duration) {
                return
            }

            const next = Math.max(0, Math.min(this.duration, ratio * this.duration))

            if (this.provider === 'youtube') {
                await this.ensureYoutubePlayer()
                this.youtubePlayer?.seekTo?.(next, true)
                this.currentTime = next
                this.revealUi()

                return
            }

            const video = this.$refs.video

            if (! video) {
                return
            }

            video.currentTime = next
            this.currentTime = next
            this.revealUi()
        },

        onScrubInput(event) {
            if (! this.canScrub) {
                return
            }

            this.seeking = true
            this.scrubPosition = Number(event.target.value)
            this.currentTime = (this.scrubPosition / 1000) * this.duration
            this.revealUi()
        },

        async onScrubChange(event) {
            if (! this.canScrub) {
                this.seeking = false

                return
            }

            const ratio = Number(event.target.value) / 1000

            await this.seekTo(ratio)
            this.seeking = false
            this.scrubPosition = Math.round(ratio * 1000)
        },

        revealUi() {
            this.showUi = true

            if (this.autoHideControls) {
                this.scheduleHideUi()
            }
        },

        scheduleHideUi() {
            if (! this.autoHideControls || ! this.showControls || ! this.playing || this.readOnly || this.volumeOpen) {
                return
            }

            this.clearHideUiTimeout()

            this.hideUiTimeout = window.setTimeout(() => {
                if (this.playing && ! this.volumeOpen) {
                    this.showUi = false
                }
            }, 3200)
        },

        clearHideUiTimeout() {
            if (this.hideUiTimeout) {
                window.clearTimeout(this.hideUiTimeout)
                this.hideUiTimeout = null
            }
        },

        onFrameMove() {
            if (this.playing && this.showControls && this.autoHideControls) {
                this.revealUi()
            }
        },

        onFrameLeave() {
            if (this.playing && this.showControls && this.autoHideControls && ! this.volumeOpen) {
                this.scheduleHideUi()
            }
        },
    }
}

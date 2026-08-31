import { createExclusiveDropdownMixin, wireExclusiveFlexDropdown } from '../core/flex-dropdown-coordinator.js'
import { getAvailableMenuHeight, positionControlTooltip as layoutControlTooltip } from '../core/video-field-popup-position.js'
import {
    beginTapGesture,
    createTapGestureTracker,
    resolveTapGesture,
} from '../core/video-field-gestures.js'
import { resolveVideoHotkeyAction } from '../core/video-field-hotkeys.js'
import {
    VideoPreviewFrameCache,
    createVideoProgressSlider,
    getSliderPreviewLeft,
    resolvePreviewCacheKey,
} from '../core/video-field-progress-slider.js'
import {
    SETTINGS_MENU_MIN_WIDTH_PX,
    SETTINGS_MENU_POPOVER_TRANSITION_MS,
    clearSettingsMenuViewportSize,
    getSettingsSubmenuTransitionAttrs,
    isSettingsRootInactive as resolveSettingsRootInactive,
    runSettingsMenuPopoverFrame,
    syncSettingsMenuViewportFromPanel,
    syncSettingsMenuViewTransition,
    waitForSettingsMenuAnimations,
} from '../core/video-field-settings-menu-transition.js'

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

export function resolveCoarsePointer(mediaQueryList = null) {
    if (mediaQueryList === null) {
        if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
            return false
        }

        mediaQueryList = window.matchMedia('(hover: none) and (pointer: coarse)')
    }

    return mediaQueryList?.matches ?? false
}

export function formatPlaybackRateLabel(rate) {
    if (! Number.isFinite(rate)) {
        return '1x'
    }

    if (rate === 1) {
        return '1x'
    }

    const normalized = Number.isInteger(rate) ? String(rate) : String(rate).replace(/0+$/, '').replace(/\.$/, '')

    return `${normalized}x`
}

export function resolveDefaultQualityKey(options) {
    if (! Array.isArray(options) || options.length === 0) {
        return null
    }

    const selected = options.find((option) => option?.default)

    return selected?.key ?? options[0]?.key ?? null
}

const volumeDropdown = createExclusiveDropdownMixin({
    openKey: 'volumeOpen',
    closeMethod: 'closeVolumePanel',
    ownerIdPrefix: 'fff-video-field-volume',
})

export function resolveProgressHoverRatio(clientX, rectLeft, rectWidth) {
    if (! Number.isFinite(rectWidth) || rectWidth <= 0) {
        return null
    }

    return Math.max(0, Math.min(1, (clientX - rectLeft) / rectWidth))
}

export function resolveScrubDuration(duration, seekableEnd) {
    if (Number.isFinite(duration) && duration > 0) {
        return duration
    }

    if (Number.isFinite(seekableEnd) && seekableEnd > 0) {
        return seekableEnd
    }

    return 0
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
    settingsMenu = false,
    tooltips = true,
    castable = false,
    airPlayable = false,
    playbackRates = [],
    qualityOptions = [],
    labels = {},
}) {
    const defaultQualityKey = resolveDefaultQualityKey(qualityOptions)

    return {
        ...volumeDropdown,
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
        settingsMenu,
        tooltips,
        castable,
        airPlayable,
        playbackRates,
        qualityOptions,
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
        progressPointing: false,
        progressDragging: false,
        progressPointerPercent: 0,
        previewHoverTime: 0,
        previewFrameReady: false,
        previewFrameLoading: false,
        previewSeekToken: 0,
        previewSeekRaf: null,
        pendingPreviewRatio: 0,
        previewLastSeekKey: null,
        previewFrameCache: null,
        progressPreviewWidth: 0,
        progressPreviewObserver: null,
        progressSlider: null,
        volumeSlider: null,
        volumeDragging: false,
        volumePointerPercent: 0,
        coarsePointer: resolveCoarsePointer(),
        volume: muted ? 0 : 1,
        volumeBeforeMute: 1,
        volumeOpen: false,
        volumeHoverOpenTimeout: null,
        volumeHoverCloseTimeout: null,
        controlTooltipOpenTimeout: null,
        tapGesture: createTapGestureTracker(),
        settingsOpen: false,
        settingsMenuPopoverPhase: null,
        settingsMenuCloseTransitionId: 0,
        settingsView: 'root',
        settingsViewDirection: 'forward',
        settingsActiveSubmenu: null,
        settingsQualityPhase: 'hidden',
        settingsSpeedPhase: 'hidden',
        settingsTransitionId: 0,
        settingsMenuPositionCleanup: null,
        playbackRate: 1,
        selectedQualityKey: defaultQualityKey,
        remotePlaybackMode: null,
        remotePlaybackSupported: false,
        remotePlaybackActive: false,
        remotePlaybackConnecting: false,
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
            this.wireExclusiveFlexDropdown()

            wireExclusiveFlexDropdown(this, {
                openKey: 'settingsOpen',
                closeMethod: 'closeSettingsMenu',
                ownerIdPrefix: 'fff-video-field-settings',
            })

            if (this.settingsMenu && defaultQualityKey) {
                this.selectedQualityKey = defaultQualityKey
            }

            this.fullscreenListener = () => {
                this.isFullscreen = document.fullscreenElement === this.$refs.frame
                this.dismissAllControlTooltips()
            }

            document.addEventListener('fullscreenchange', this.fullscreenListener)

            if (this.provider === 'youtube') {
                if (this.autoplay && ! this.readOnly) {
                    this.ensureYoutubePlayer().then(() => {
                        this.youtubePlayer?.playVideo?.()
                    }).catch(() => {})
                }

                this.$nextTick(() => {
                    this.initProgressSlider()
                    this.initVolumeSlider()
                    this.initProgressPreviewObserver()

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
            this.playbackRate = video.playbackRate || 1
            this.previewFrameCache = new VideoPreviewFrameCache()
            this.initRemotePlaybackSupport(video)

            const syncFromVideo = () => this.syncVideoMetadata(video)

            video.addEventListener('loadedmetadata', syncFromVideo)
            video.addEventListener('durationchange', syncFromVideo)
            video.addEventListener('loadeddata', syncFromVideo)

            syncFromVideo()

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
                const previewVideo = this.$refs.previewVideo

                if (previewVideo && this.provider === 'html5' && this.videoSrc) {
                    previewVideo.src = this.videoSrc
                }

                this.initProgressSlider()
                this.initVolumeSlider()
                this.initProgressPreviewObserver()

                requestAnimationFrame(() => {
                    this.uiTransitionsEnabled = true
                })
            })
        },

        initProgressSlider() {
            this.progressSlider?.destroy?.()

            const wrap = this.$refs.progressWrap

            if (! wrap) {
                return
            }

            wrap.style.touchAction = 'none'
            wrap.style.userSelect = 'none'

            this.progressSlider = createVideoProgressSlider({
                getElement: () => this.$refs.progressWrap,
                isDisabled: () => ! this.canScrub,
                onStateChange: (state) => {
                    this.progressPointing = state.pointing
                    this.progressDragging = state.dragging
                    this.progressPointerPercent = state.pointerPercent
                    this.previewHoverTime = (state.pointerPercent / 100) * this.duration

                    if (state.pointing && this.canScrub && this.provider === 'html5') {
                        this.schedulePreviewFrame(state.pointerPercent / 100)
                    }

                    if (! state.pointing) {
                        this.previewFrameLoading = false
                    }
                },
                onDragStart: () => this.revealUi(),
                onValueChange: (percent) => {
                    if (! this.canScrub) {
                        return
                    }

                    this.seeking = true
                    this.scrubPosition = Math.round((percent / 100) * 1000)
                    this.currentTime = (percent / 100) * this.duration
                    this.revealUi()
                },
                onValueCommit: async (percent) => {
                    if (! this.canScrub) {
                        this.seeking = false

                        return
                    }

                    await this.seekTo(percent / 100)
                    this.seeking = false
                    this.scrubPosition = Math.round((percent / 100) * 1000)
                },
            })
        },

        initVolumeSlider() {
            this.volumeSlider?.destroy?.()

            const slider = this.$refs.volumeSlider

            if (! slider || ! this.volumeControl || this.coarsePointer) {
                return
            }

            slider.style.touchAction = 'none'
            slider.style.userSelect = 'none'

            this.volumeSlider = createVideoProgressSlider({
                orientation: 'vertical',
                getElement: () => this.$refs.volumeSlider,
                isDisabled: () => ! this.canInteract || ! this.volumeControl,
                onStateChange: (state) => {
                    this.volumeDragging = state.dragging
                    this.volumePointerPercent = state.pointerPercent
                },
                onDragStart: () => this.revealUi(),
                onValueChange: (percent) => {
                    this.setVolume(percent / 100)
                },
                onValueCommit: (percent) => {
                    this.setVolume(percent / 100)
                },
            })
        },

        initProgressPreviewObserver() {
            this.progressPreviewObserver?.disconnect?.()

            const thumbnail = this.$refs.progressThumbnail

            if (! thumbnail || typeof ResizeObserver === 'undefined') {
                return
            }

            this.progressPreviewObserver = new ResizeObserver(([entry]) => {
                this.progressPreviewWidth = entry.contentRect.width
            })
            this.progressPreviewObserver.observe(thumbnail)
        },

        syncVideoMetadata(video) {
            if (! video) {
                return
            }

            const seekableEnd = video.seekable?.length
                ? video.seekable.end(video.seekable.length - 1)
                : 0

            this.duration = resolveScrubDuration(video.duration, seekableEnd)
            this.bufferedRatio = this.resolveBufferedRatio(video)

            if (! this.seeking) {
                this.currentTime = video.currentTime || 0
                this.scrubPosition = Math.round(this.progressRatio * 1000)
            }

            if (this.duration > 0) {
                this.primeFirstFrame(video)
            }
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
            this.clearPreviewSeekRaf()
            this.progressPreviewObserver?.disconnect?.()
            this.progressPreviewObserver = null
            this.progressSlider?.destroy?.()
            this.progressSlider = null
            this.volumeSlider?.destroy?.()
            this.volumeSlider = null
            this.previewFrameCache?.clear?.()
            this.previewFrameCache = null
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
            if (! this.canInteract) {
                return false
            }

            if (this.duration > 0) {
                return true
            }

            const video = this.$refs.video

            if (! video?.seekable?.length) {
                return false
            }

            return video.seekable.end(video.seekable.length - 1) > 0
        },

        get panelVisible() {
            return this.showUi || ! this.playing
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

        get previewTimeLabel() {
            return formatVideoTime(this.previewHoverTime)
        },

        get progressSliderCssVars() {
            const fillPercent = (this.displayProgressRatio * 100).toFixed(3)
            const pointerPercent = this.progressPointerPercent.toFixed(3)
            const bufferPercent = (this.bufferedRatio * 100).toFixed(3)

            return `--fff-video-slider-fill: ${fillPercent}%; --fff-video-slider-pointer: ${pointerPercent}%; --fff-video-slider-buffer: ${bufferPercent}%;`
        },

        get volumeSliderCssVars() {
            const fillPercent = this.volumeDragging
                ? this.volumePointerPercent.toFixed(3)
                : (this.volume * 100).toFixed(3)
            const pointerPercent = this.volumePointerPercent.toFixed(3)

            return `--fff-video-volume-fill: ${fillPercent}%; --fff-video-volume-pointer: ${pointerPercent}%;`
        },

        get progressThumbnailStyle() {
            if (! this.progressPointing) {
                return ''
            }

            const width = this.progressPreviewWidth > 0 ? this.progressPreviewWidth : 176

            return `left: ${getSliderPreviewLeft(this.progressPointerPercent, width)};`
        },

        get playbackRateLabel() {
            return formatPlaybackRateLabel(this.playbackRate)
        },

        get selectedQualityLabel() {
            const option = this.qualityOptions.find((item) => item.key === this.selectedQualityKey)

            return option?.label ?? this.labels.quality ?? 'Quality'
        },

        formatPlaybackRateLabel(rate) {
            return formatPlaybackRateLabel(rate)
        },

        get volumeLevel() {
            if (this.muted || this.volume === 0) {
                return 'off'
            }

            if (this.volume <= 0.5) {
                return 'low'
            }

            return 'high'
        },

        get remotePlaybackTooltipLabel() {
            if (this.remotePlaybackMode === 'airplay') {
                if (this.remotePlaybackActive) {
                    return this.labels.stopAirplay ?? 'Stop AirPlay'
                }

                if (this.remotePlaybackConnecting) {
                    return this.labels.connecting ?? 'Connecting'
                }

                return this.labels.airplay ?? 'Start AirPlay'
            }

            if (this.remotePlaybackActive) {
                return this.labels.stopCast ?? 'Stop casting'
            }

            if (this.remotePlaybackConnecting) {
                return this.labels.connecting ?? 'Connecting'
            }

            return this.labels.cast ?? 'Start casting'
        },

        get remotePlaybackAriaLabel() {
            return this.remotePlaybackTooltipLabel
        },

        initRemotePlaybackSupport(video) {
            this.remotePlaybackMode = null
            this.remotePlaybackSupported = false
            this.remotePlaybackActive = false
            this.remotePlaybackConnecting = false

            const canAirPlay = this.airPlayable
                && typeof video.webkitShowPlaybackTargetPicker === 'function'
            const canCast = this.castable
                && video.remote
                && typeof video.remote.prompt === 'function'

            if (canAirPlay && this.prefersWebKitAirPlay()) {
                this.remotePlaybackMode = 'airplay'

                const syncAvailability = (event) => {
                    this.remotePlaybackSupported = event?.availability === 'available'
                }

                const syncConnection = () => {
                    this.remotePlaybackActive = video.webkitCurrentPlaybackTargetIsWireless === true
                    this.remotePlaybackConnecting = false
                }

                video.addEventListener('webkitplaybacktargetavailabilitychanged', syncAvailability)
                video.addEventListener('webkitcurrentplaybacktargetiswirelesschanged', syncConnection)
                syncConnection()

                return
            }

            if (canCast) {
                this.remotePlaybackMode = 'cast'

                video.remote.watchAvailability((available) => {
                    this.remotePlaybackSupported = available
                }).catch(() => {
                    this.remotePlaybackSupported = false
                })

                video.remote.addEventListener?.('connecting', () => {
                    this.remotePlaybackConnecting = true
                })

                video.remote.addEventListener?.('connect', () => {
                    this.remotePlaybackActive = true
                    this.remotePlaybackConnecting = false
                })

                video.remote.addEventListener?.('disconnect', () => {
                    this.remotePlaybackActive = false
                    this.remotePlaybackConnecting = false
                })
            }
        },

        prefersWebKitAirPlay() {
            return typeof window !== 'undefined'
                && 'WebKitPlaybackTargetAvailabilityEvent' in window
        },

        toggleSettingsMenu() {
            if (! this.canInteract || ! this.settingsMenu) {
                return
            }

            if (this.settingsOpen) {
                this.closeSettingsMenu()
            } else {
                this.openSettingsMenu()
            }

            this.revealUi()
        },

        openSettingsMenu() {
            if (this.settingsMenuPopoverPhase === 'ending') {
                return
            }

            this.settingsMenuCloseTransitionId++
            this.settingsOpen = true
            this.settingsMenuPopoverPhase = 'starting'

            this.$nextTick(() => {
                this.syncSettingsMenuAvailableHeight()
                this.syncSettingsMenuViewport()
                this.startSettingsMenuSizeWatch()

                const menu = this.$refs.settingsMenu

                void menu?.offsetHeight

                runSettingsMenuPopoverFrame(() => {
                    if (! this.settingsOpen) {
                        return
                    }

                    this.settingsMenuPopoverPhase = null
                })
            })
        },

        closeSettingsMenu() {
            if (! this.settingsOpen || this.settingsMenuPopoverPhase === 'ending') {
                return
            }

            this.settingsMenuCloseTransitionId++
            const transitionId = this.settingsMenuCloseTransitionId
            const menu = this.$refs.settingsMenu

            this.settingsMenuPopoverPhase = 'ending'

            runSettingsMenuPopoverFrame(async () => {
                if (transitionId !== this.settingsMenuCloseTransitionId) {
                    return
                }

                await waitForSettingsMenuAnimations(menu, SETTINGS_MENU_POPOVER_TRANSITION_MS)

                if (transitionId !== this.settingsMenuCloseTransitionId) {
                    return
                }

                this.settingsOpen = false
                this.settingsMenuPopoverPhase = null
                this.stopSettingsMenuSizeWatch()
                this.resetSettingsMenuNavigation()
            })
        },

        onSettingsMenuClickOutside(event) {
            if (this.$refs.settingsTrigger?.contains(event.target)) {
                return
            }

            this.closeSettingsMenu()
        },

        startSettingsMenuSizeWatch() {
            this.stopSettingsMenuSizeWatch()

            const menu = this.$refs.settingsMenu

            if (! menu) {
                return
            }

            const onTransitionEnd = (event) => {
                if (event.target !== menu) {
                    return
                }

                if (event.propertyName === 'height' || event.propertyName === 'width') {
                    this.syncSettingsMenuAvailableHeight()
                }
            }

            menu.addEventListener('transitionend', onTransitionEnd)

            this.settingsMenuPositionCleanup = () => {
                menu.removeEventListener('transitionend', onTransitionEnd)
            }
        },

        stopSettingsMenuSizeWatch() {
            this.settingsMenuPositionCleanup?.()
            this.settingsMenuPositionCleanup = null
        },

        resetSettingsMenuNavigation() {
            this.settingsTransitionId++
            this.settingsView = 'root'
            this.settingsViewDirection = 'forward'
            this.settingsActiveSubmenu = null
            this.settingsQualityPhase = 'hidden'
            this.settingsSpeedPhase = 'hidden'
            this.settingsMenuPopoverPhase = null
            clearSettingsMenuViewportSize(this.$refs.settingsMenu)
        },

        getSettingsSubmenuPhase(view) {
            if (view === 'quality') {
                return this.settingsQualityPhase
            }

            if (view === 'speed') {
                return this.settingsSpeedPhase
            }

            return 'hidden'
        },

        setSettingsSubmenuPhase(view, phase) {
            if (view === 'quality') {
                this.settingsQualityPhase = phase
            } else if (view === 'speed') {
                this.settingsSpeedPhase = phase
            }
        },

        isSettingsRootInactive() {
            if (! this.settingsActiveSubmenu) {
                return false
            }

            return resolveSettingsRootInactive(
                this.settingsActiveSubmenu,
                this.getSettingsSubmenuPhase(this.settingsActiveSubmenu),
            )
        },

        settingsSubmenuAttrs(view) {
            return getSettingsSubmenuTransitionAttrs(
                this.getSettingsSubmenuPhase(view),
                this.settingsViewDirection,
            )
        },

        getSettingsPanelRef(view) {
            if (view === 'quality') {
                return this.$refs.settingsPanelQuality
            }

            if (view === 'speed') {
                return this.$refs.settingsPanelSpeed
            }

            return this.$refs.settingsPanelRoot
        },

        syncSettingsSubmenuTransition(view, phase, direction) {
            syncSettingsMenuViewTransition(
                this.$refs.settingsMenu,
                this.getSettingsPanelRef(view),
                { phase, direction },
                this.$refs.settingsPanelRoot,
                SETTINGS_MENU_MIN_WIDTH_PX,
            )
        },

        syncSettingsMenuRoot() {
            const activeSubmenu = this.settingsActiveSubmenu
            const isActiveSubmenu = activeSubmenu !== null
                && this.getSettingsSubmenuPhase(activeSubmenu) === 'active'
            const panel = isActiveSubmenu
                ? this.getSettingsPanelRef(activeSubmenu)
                : this.$refs.settingsPanelRoot

            syncSettingsMenuViewportFromPanel(
                this.$refs.settingsMenu,
                panel,
                SETTINGS_MENU_MIN_WIDTH_PX,
            )
        },

        syncSettingsMenuAvailableHeight() {
            const menu = this.$refs.settingsMenu
            const trigger = this.$refs.settingsTrigger
            const boundary = this.$refs.frame

            if (! menu || ! trigger || ! boundary || ! this.settingsOpen) {
                return
            }

            const available = getAvailableMenuHeight(
                trigger.getBoundingClientRect(),
                boundary.getBoundingClientRect(),
                {
                    side: 'top',
                    sideOffset: 8,
                    boundaryOffset: 8,
                },
            )

            if (available > 0) {
                menu.style.setProperty('--fff-video-field-menu-max-height', `${Math.floor(available)}px`)
            }
        },

        syncSettingsMenuViewport() {
            this.syncSettingsMenuAvailableHeight()
            this.syncSettingsMenuRoot()
        },

        completeSettingsItemSelection() {
            this.navigateSettingsBack()
        },

        openSettingsView(view) {
            if (view === 'root') {
                this.navigateSettingsBack()

                return
            }

            if (
                view === this.settingsActiveSubmenu
                && this.getSettingsSubmenuPhase(view) === 'active'
            ) {
                return
            }

            this.settingsViewDirection = 'forward'
            this.settingsView = view
            this.settingsActiveSubmenu = view
            this.setSettingsSubmenuPhase(view, 'entering')
            this.settingsTransitionId++
            const transitionId = this.settingsTransitionId

            this.$nextTick(() => {
                this.$nextTick(() => {
                    this.syncSettingsSubmenuTransition(view, 'entering', 'forward')

                    const panel = this.getSettingsPanelRef(view)

                    void panel?.offsetHeight

                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            if (transitionId !== this.settingsTransitionId) {
                                return
                            }

                            void panel?.offsetHeight
                            this.setSettingsSubmenuPhase(view, 'active')
                            this.syncSettingsSubmenuTransition(view, 'active', 'forward')
                        })
                    })
                })
            })

            this.revealUi()
        },

        navigateSettingsBack() {
            const submenu = this.settingsActiveSubmenu

            if (! submenu || this.getSettingsSubmenuPhase(submenu) === 'hidden') {
                this.settingsView = 'root'
                this.syncSettingsMenuRoot()

                return
            }

            this.settingsViewDirection = 'back'
            this.settingsView = 'root'
            this.setSettingsSubmenuPhase(submenu, 'exiting')
            this.settingsTransitionId++
            const transitionId = this.settingsTransitionId
            const panel = this.getSettingsPanelRef(submenu)

            this.$nextTick(() => {
                this.syncSettingsSubmenuTransition(submenu, 'exiting', 'back')
            })

            requestAnimationFrame(async () => {
                void panel?.offsetHeight

                await waitForSettingsMenuAnimations(panel)

                if (transitionId !== this.settingsTransitionId) {
                    return
                }

                this.setSettingsSubmenuPhase(submenu, 'hidden')
                this.settingsActiveSubmenu = null

                this.$nextTick(() => {
                    if (transitionId !== this.settingsTransitionId) {
                        return
                    }

                    this.syncSettingsMenuRoot()
                })
            })

            this.revealUi()
        },

        showControlTooltip(wrapperEl) {
            if (! wrapperEl || this.coarsePointer || ! this.tooltips) {
                return
            }

            if (wrapperEl.classList.contains('is-tooltip-suppressed')) {
                return
            }

            if (this.controlTooltipOpenTimeout) {
                window.clearTimeout(this.controlTooltipOpenTimeout)
            }

            this.controlTooltipOpenTimeout = window.setTimeout(() => {
                if (wrapperEl.classList.contains('is-tooltip-suppressed')) {
                    return
                }

                wrapperEl.classList.add('is-tooltip-visible')
                this.positionControlTooltip(wrapperEl)
            }, 600)
        },

        hideControlTooltip(wrapperEl) {
            if (! wrapperEl) {
                return
            }

            if (this.controlTooltipOpenTimeout) {
                window.clearTimeout(this.controlTooltipOpenTimeout)
                this.controlTooltipOpenTimeout = null
            }

            wrapperEl.classList.remove('is-tooltip-visible')
            wrapperEl.classList.remove('is-tooltip-suppressed')
        },

        suppressControlTooltip(wrapperEl) {
            if (! wrapperEl) {
                return
            }

            if (this.controlTooltipOpenTimeout) {
                window.clearTimeout(this.controlTooltipOpenTimeout)
                this.controlTooltipOpenTimeout = null
            }

            wrapperEl.classList.add('is-tooltip-suppressed')
            wrapperEl.classList.remove('is-tooltip-visible')
        },

        dismissAllControlTooltips() {
            this.$refs.frame?.querySelectorAll('.fff-video-field__control-tip.is-tooltip-visible')
                .forEach((element) => {
                    element.classList.remove('is-tooltip-visible')
                })
        },

        positionControlTooltip(wrapperEl) {
            if (! wrapperEl || this.coarsePointer || ! this.tooltips) {
                return
            }

            layoutControlTooltip(wrapperEl, this.$refs.frame, {
                sideOffset: 12,
                boundaryOffset: 8,
            })
        },

        async setPlaybackRate(rate) {
            if (! this.canInteract || ! Array.isArray(this.playbackRates) || this.playbackRates.length === 0) {
                return
            }

            const next = Number(rate)

            if (! Number.isFinite(next)) {
                return
            }

            if (this.provider === 'youtube') {
                await this.ensureYoutubePlayer()
                this.youtubePlayer?.setPlaybackRate?.(next)
            } else {
                const video = this.$refs.video

                if (video) {
                    video.playbackRate = next
                }
            }

            this.playbackRate = next
            this.completeSettingsItemSelection()
            this.revealUi()
        },

        async selectQuality(option) {
            if (! this.canInteract || ! option?.src || this.provider !== 'html5') {
                return
            }

            const video = this.$refs.video

            if (! video) {
                return
            }

            const wasPlaying = ! video.paused
            const currentTime = video.currentTime || 0

            this.selectedQualityKey = option.key
            this.staticSrc = option.src

            video.src = option.src
            video.load()

            await new Promise((resolve) => {
                if (video.readyState >= HTMLMediaElement.HAVE_METADATA) {
                    resolve()

                    return
                }

                video.addEventListener('loadedmetadata', resolve, { once: true })
            })

            try {
                video.currentTime = currentTime
            } catch {
                // Ignore early seek failures while the new source loads.
            }

            const previewVideo = this.$refs.previewVideo

            if (previewVideo) {
                previewVideo.src = option.src
            }

            this.syncVideoMetadata(video)

            if (wasPlaying) {
                video.play().catch(() => {})
            }

            this.completeSettingsItemSelection()
            this.revealUi()
        },

        async toggleRemotePlayback() {
            if (! this.canInteract || ! this.remotePlaybackSupported) {
                return
            }

            const video = this.$refs.video
            const frame = this.$refs.frame

            if (! video) {
                return
            }

            if (this.remotePlaybackMode === 'airplay') {
                if (document.fullscreenElement === frame) {
                    await document.exitFullscreen?.().catch(() => {})
                }

                video.webkitShowPlaybackTargetPicker?.()
                this.revealUi()

                return
            }

            if (this.remotePlaybackMode === 'cast' && video.remote?.prompt) {
                if (document.fullscreenElement === frame) {
                    await document.exitFullscreen?.().catch(() => {})
                }

                try {
                    await video.remote.prompt()
                    this.remotePlaybackActive = video.remote.state === 'connected'
                    this.remotePlaybackConnecting = video.remote.state === 'connecting'
                } catch {
                    // Browser blocked casting or no devices available.
                }
            }

            this.revealUi()
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

        /**
         * With `preload="metadata"` and no `poster`, Chrome paints the first
         * frame once metadata is ready — Safari keeps the frame black until
         * playback starts. Nudging `currentTime` forces Safari to decode and
         * paint a frame without actually starting playback.
         */
        primeFirstFrame(video) {
            if (this.poster || ! video.paused || video.currentTime !== 0) {
                return
            }

            try {
                video.currentTime = 0.001
            } catch {
                // Ignore — some browsers throw if the seek happens too early.
            }
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

                            const availableRates = event.target.getAvailablePlaybackRates?.() ?? []

                            if (Array.isArray(this.playbackRates) && this.playbackRates.length > 0 && availableRates.length > 0) {
                                const preferred = this.playbackRates.includes(1)
                                    ? 1
                                    : this.playbackRates[0]

                                event.target.setPlaybackRate?.(preferred)
                                this.playbackRate = preferred
                            }

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

        toggleCompactVolumeControl() {
            this.toggleVolumeControl()
        },

        toggleVolumeControl() {
            if (! this.canInteract || ! this.volumeControl) {
                return
            }

            if (this.coarsePointer) {
                this.toggleVolumePanel()

                return
            }

            this.toggleMute()
        },

        openVolumePopoverHover() {
            if (this.coarsePointer || ! this.volumeControl) {
                return
            }

            if (this.volumeHoverCloseTimeout) {
                window.clearTimeout(this.volumeHoverCloseTimeout)
                this.volumeHoverCloseTimeout = null
            }

            if (this.volumeHoverOpenTimeout) {
                return
            }

            this.volumeHoverOpenTimeout = window.setTimeout(() => {
                this.volumeHoverOpenTimeout = null
                this.volumeOpen = true
            }, 200)
        },

        closeVolumePopoverHover() {
            if (this.coarsePointer || ! this.volumeControl) {
                return
            }

            if (this.volumeHoverOpenTimeout) {
                window.clearTimeout(this.volumeHoverOpenTimeout)
                this.volumeHoverOpenTimeout = null
            }

            if (this.volumeHoverCloseTimeout) {
                window.clearTimeout(this.volumeHoverCloseTimeout)
            }

            this.volumeHoverCloseTimeout = window.setTimeout(() => {
                this.volumeHoverCloseTimeout = null
                this.volumeOpen = false
                this.clearVolumePopoverStyles()
            }, 100)
        },

        clearVolumePopoverStyles() {
            const popover = this.$refs.volumePopover

            if (! popover) {
                return
            }

            popover.style.removeProperty('position')
            popover.style.removeProperty('left')
            popover.style.removeProperty('top')
            popover.style.removeProperty('right')
            popover.style.removeProperty('bottom')
            popover.style.removeProperty('transform')
            popover.style.removeProperty('--fff-video-field-menu-max-height')
        },

        onVolumeWheel(event) {
            if (! this.canInteract || ! this.volumeControl) {
                return
            }

            const delta = event.deltaY < 0 ? 0.05 : -0.05

            this.setVolume(this.volume + delta)
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

        onVolumePointerDown(event) {
            this.volumeSlider?.handlePointerDown(event)
        },

        onVolumePointerMove(event) {
            this.volumeSlider?.handlePointerMove(event)
        },

        onVolumePointerUp(event) {
            this.volumeSlider?.handlePointerUp(event)
        },

        onVolumePointerLeave() {
            this.volumeSlider?.handlePointerLeave()
        },

        onVolumeLostPointerCapture(event) {
            this.volumeSlider?.handleLostPointerCapture(event)
        },

        toggleVolumePanel() {
            if (! this.canInteract || ! this.volumeControl) {
                return
            }

            this.volumeOpen = ! this.volumeOpen

            if (! this.volumeOpen) {
                this.clearVolumePopoverStyles()
            }

            this.revealUi()
        },

        closeVolumePanel() {
            this.volumeOpen = false
            this.clearVolumePopoverStyles()
        },

        toggleFullscreen() {
            if (! this.canInteract || ! this.fullscreenable) {
                return
            }

            const frame = this.$refs.frame

            if (! frame) {
                return
            }

            this.dismissAllControlTooltips()

            if (document.fullscreenElement === frame) {
                document.exitFullscreen?.().catch(() => {})
            } else {
                frame.requestFullscreen?.().catch(() => {})
            }

            this.revealUi()
        },

        async seekStep(seconds) {
            if (! this.canInteract) {
                return
            }

            const delta = Number(seconds)

            if (! Number.isFinite(delta)) {
                return
            }

            if (this.provider === 'youtube') {
                await this.ensureYoutubePlayer()

                const current = this.youtubePlayer?.getCurrentTime?.() ?? this.currentTime
                const duration = this.youtubePlayer?.getDuration?.() ?? this.duration
                const next = Math.max(0, Math.min(duration || 0, current + delta))

                this.youtubePlayer?.seekTo?.(next, true)
                this.currentTime = next
                this.revealUi()

                return
            }

            const video = this.$refs.video

            if (! video || ! this.duration) {
                return
            }

            video.currentTime = Math.max(0, Math.min(this.duration, (video.currentTime || 0) + delta))
            this.revealUi()
        },

        async onFrameKeydown(event) {
            if (! this.canInteract || this.nativeControls) {
                return
            }

            const action = resolveVideoHotkeyAction(event)

            if (! action) {
                return
            }

            switch (action) {
                case 'togglePaused':
                    event.preventDefault()
                    await this.togglePlay()
                    break
                case 'toggleMuted':
                    event.preventDefault()
                    this.toggleMute()
                    break
                case 'toggleFullscreen':
                    event.preventDefault()
                    this.toggleFullscreen()
                    break
                case 'togglePictureInPicture':
                    event.preventDefault()
                    await this.togglePictureInPicture()
                    break
                case 'seekBackShort':
                    event.preventDefault()
                    await this.seekStep(-5)
                    break
                case 'seekForwardShort':
                    event.preventDefault()
                    await this.seekStep(5)
                    break
                case 'volumeUp':
                    event.preventDefault()
                    this.setVolume(this.volume + 0.05)
                    break
                case 'volumeDown':
                    event.preventDefault()
                    this.setVolume(this.volume - 0.05)
                    break
                case 'escape':
                    this.closeVolumePanel()
                    this.closeSettingsMenu()
                    break
                default:
                    return
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

        onProgressPointerDown(event) {
            this.progressSlider?.handlePointerDown(event)
        },

        onProgressPointerMove(event) {
            this.progressSlider?.handlePointerMove(event)
        },

        onProgressPointerUp(event) {
            this.progressSlider?.handlePointerUp(event)
        },

        onProgressPointerLeave() {
            this.progressSlider?.handlePointerLeave()
        },

        onProgressLostPointerCapture(event) {
            this.progressSlider?.handleLostPointerCapture(event)
        },

        schedulePreviewFrame(ratio) {
            this.pendingPreviewRatio = ratio
            this.clearPreviewSeekRaf()

            this.previewSeekRaf = requestAnimationFrame(() => {
                this.previewSeekRaf = null
                this.capturePreviewFrame(this.pendingPreviewRatio)
            })
        },

        clearPreviewSeekRaf() {
            if (this.previewSeekRaf) {
                cancelAnimationFrame(this.previewSeekRaf)
                this.previewSeekRaf = null
            }
        },

        drawCachedPreviewFrame(cachedCanvas) {
            const canvas = this.$refs.previewCanvas

            if (! canvas || ! cachedCanvas) {
                return
            }

            canvas.width = cachedCanvas.width
            canvas.height = cachedCanvas.height
            canvas.getContext('2d')?.drawImage(cachedCanvas, 0, 0)
            this.previewFrameReady = true
            this.previewFrameLoading = false
        },

        capturePreviewFrame(ratio) {
            if (! this.progressPointing || ! this.duration || this.provider !== 'html5') {
                return
            }

            const cacheKey = resolvePreviewCacheKey(ratio * this.duration)
            const cached = this.previewFrameCache?.get(cacheKey)

            if (cached) {
                this.drawCachedPreviewFrame(cached)

                return
            }

            if (this.previewLastSeekKey === cacheKey && this.previewFrameLoading) {
                return
            }

            const token = ++this.previewSeekToken
            const previewVideo = this.$refs.previewVideo

            if (! previewVideo) {
                this.previewFrameReady = false

                return
            }

            if (! previewVideo.src && this.videoSrc) {
                previewVideo.src = this.videoSrc
            }

            const targetTime = Math.max(0, Math.min(this.duration, ratio * this.duration))
            this.previewLastSeekKey = cacheKey
            this.previewFrameLoading = true

            const drawFrame = () => {
                if (token !== this.previewSeekToken || ! this.progressPointing) {
                    return
                }

                const canvas = this.$refs.previewCanvas

                if (! canvas) {
                    this.previewFrameReady = false
                    this.previewFrameLoading = false

                    return
                }

                const context = canvas.getContext('2d')

                if (! context) {
                    this.previewFrameReady = false
                    this.previewFrameLoading = false

                    return
                }

                const videoWidth = previewVideo.videoWidth
                const videoHeight = previewVideo.videoHeight

                if (! videoWidth || ! videoHeight) {
                    this.previewFrameReady = false
                    this.previewFrameLoading = false

                    return
                }

                const targetWidth = 176
                const targetHeight = Math.max(1, Math.round(targetWidth * (videoHeight / videoWidth)))

                canvas.width = targetWidth
                canvas.height = targetHeight
                context.drawImage(previewVideo, 0, 0, targetWidth, targetHeight)
                this.previewFrameCache?.set(cacheKey, canvas)
                this.previewFrameReady = true
                this.previewFrameLoading = false
            }

            if (
                Math.abs(previewVideo.currentTime - targetTime) < 0.12
                && previewVideo.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA
            ) {
                drawFrame()

                return
            }

            const onSeeked = () => {
                previewVideo.removeEventListener('seeked', onSeeked)
                drawFrame()
            }

            previewVideo.addEventListener('seeked', onSeeked, { once: true })

            try {
                previewVideo.currentTime = targetTime
            } catch {
                previewVideo.removeEventListener('seeked', onSeeked)
                this.previewFrameReady = false
                this.previewFrameLoading = false
            }
        },

        revealUi() {
            this.showUi = true

            if (this.autoHideControls) {
                this.scheduleHideUi()
            }
        },

        scheduleHideUi() {
            if (! this.autoHideControls || ! this.showControls || ! this.playing || this.readOnly || this.volumeOpen || this.settingsOpen) {
                return
            }

            this.clearHideUiTimeout()

            this.hideUiTimeout = window.setTimeout(() => {
                if (this.playing && ! this.volumeOpen && ! this.settingsOpen) {
                    this.showUi = false
                    this.dismissAllControlTooltips()
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

        onFrameLeave(event) {
            if (event?.pointerType === 'touch') {
                return
            }

            if (this.playing && this.showControls && this.autoHideControls && ! this.volumeOpen && ! this.settingsOpen) {
                this.scheduleHideUi()
            }
        },

        onFramePointerDown(event) {
            if (! this.canInteract || ! this.showControls || this.nativeControls) {
                return
            }

            if (event.target.closest(
                'button, input, [role="slider"], [role="menu"], .fff-video-field__settings-menu, .fff-video-field__volume-popover',
            )) {
                return
            }

            beginTapGesture(this.tapGesture, event)
        },

        onFramePointerUp(event) {
            if (! this.canInteract || ! this.showControls || this.nativeControls) {
                return
            }

            if (this.progressDragging || this.seeking) {
                return
            }

            const interactiveTarget = event.target.closest(
                'button, input, [role="slider"], [role="menu"], .fff-video-field__settings-menu, .fff-video-field__volume-popover',
            )

            if (interactiveTarget) {
                return
            }

            const videoSurface = event.target.closest('.fff-video-field__video, .fff-video-field__empty, .fff-video-field__placeholder')

            if (! videoSurface) {
                return
            }

            const frame = this.$refs.frame

            if (! frame) {
                return
            }

            const gesture = resolveTapGesture(this.tapGesture, event, frame.getBoundingClientRect())

            if (! gesture) {
                return
            }

            const isTouch = event.pointerType === 'touch' || event.pointerType === 'pen'

            if (gesture.isDoubleTap) {
                if (gesture.region === 'left') {
                    this.seekStep(-10)
                } else if (gesture.region === 'right') {
                    this.seekStep(10)
                } else {
                    this.toggleFullscreen()
                }

                return
            }

            if (isTouch) {
                if (this.playing && this.autoHideControls && ! this.showUi) {
                    this.revealUi()

                    return
                }

                if (this.playing && this.autoHideControls && this.showUi) {
                    this.showUi = false
                    this.dismissAllControlTooltips()

                    return
                }
            }

            if (! isTouch && gesture.region === 'center') {
                this.togglePlay()
            }
        },
    }
}

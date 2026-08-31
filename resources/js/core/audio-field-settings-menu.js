import {
    clearSettingsMenuViewportSize,
    isSettingsRootInactive as resolveSettingsRootInactive,
    runSettingsMenuPopoverFrame,
    SETTINGS_MENU_MIN_WIDTH_PX,
    SETTINGS_MENU_POPOVER_TRANSITION_MS,
    syncSettingsMenuViewTransition,
    syncSettingsMenuViewportFromPanel,
    waitForSettingsMenuAnimations,
    waitForSettingsMenuPopoverAnimation,
} from './video-field-settings-menu-transition.js'

const SUBMENU_VIEWS = ['model', 'language', 'task']

/**
 * Settings popover behavior for AudioField Whisper transcription (video-field pattern).
 */
export function createAudioFieldSettingsMenuMixin() {
    const phaseKey = (view) => `settings${view.charAt(0).toUpperCase()}${view.slice(1)}Phase`

    return {
        settingsOpen: false,
        settingsMenuPopoverPhase: null,
        settingsMenuCloseTransitionId: 0,
        settingsTransitionId: 0,
        settingsView: 'root',
        settingsViewDirection: 'forward',
        settingsActiveSubmenu: null,
        settingsModelPhase: 'hidden',
        settingsLanguagePhase: 'hidden',
        settingsTaskPhase: 'hidden',
        settingsMenuPositionCleanup: null,

        get settingsMenu() {
            return this.$refs?.sttSettingsMenu ?? null
        },

        get selectedModelLabel() {
            const match = this.transcriptionModelOptions.find((model) => model.id === this.sttModel)

            return match?.label ?? this.sttModel
        },

        get selectedLanguageLabel() {
            const match = this.transcriptionLanguages.find((language) => (language.code ?? '') === (this.sttLanguage ?? ''))

            return match?.label ?? (this.labels.transcription?.language_auto ?? 'Auto detect')
        },

        get selectedTaskLabel() {
            return this.sttTask === 'translate'
                ? (this.labels.transcription?.task_translate ?? 'Translate')
                : (this.labels.transcription?.task_transcribe ?? 'Transcribe')
        },

        toggleSettingsMenu() {
            if (! this.transcriptionEnabled || ! this.settingsMenu) {
                return
            }

            if (this.settingsOpen) {
                this.closeSettingsMenu()
            } else {
                this.openSettingsMenu()
            }
        },

        openSettingsMenu() {
            if (this.settingsMenuPopoverPhase === 'ending') {
                return
            }

            this.settingsMenuCloseTransitionId++
            this.settingsOpen = true
            this.settingsMenuPopoverPhase = 'starting'

            this.$nextTick(() => {
                this.syncSettingsMenuViewport()
                this.startSettingsMenuSizeWatch()

                void this.settingsMenu?.offsetHeight

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
            const menu = this.settingsMenu

            this.settingsMenuPopoverPhase = 'ending'

            runSettingsMenuPopoverFrame(async () => {
                if (transitionId !== this.settingsMenuCloseTransitionId) {
                    return
                }

                await waitForSettingsMenuPopoverAnimation(menu, SETTINGS_MENU_POPOVER_TRANSITION_MS)

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
            if (this.$refs?.sttSettingsTrigger?.contains(event.target)) {
                return
            }

            this.closeSettingsMenu()
        },

        startSettingsMenuSizeWatch() {
            this.stopSettingsMenuSizeWatch()

            const menu = this.settingsMenu

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

            for (const view of SUBMENU_VIEWS) {
                this[phaseKey(view)] = 'hidden'
            }

            clearSettingsMenuViewportSize(this.settingsMenu)
        },

        getSettingsSubmenuPhase(view) {
            return this[phaseKey(view)] ?? 'hidden'
        },

        setSettingsSubmenuPhase(view, phase) {
            this[phaseKey(view)] = phase
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

        getSettingsPanelRef(view) {
            if (view === 'model') {
                return this.$refs?.sttSettingsPanelModel ?? null
            }

            if (view === 'language') {
                return this.$refs?.sttSettingsPanelLanguage ?? null
            }

            if (view === 'task') {
                return this.$refs?.sttSettingsPanelTask ?? null
            }

            return this.$refs?.sttSettingsPanelRoot ?? null
        },

        syncSettingsSubmenuTransition(view, phase, direction) {
            syncSettingsMenuViewTransition(
                this.settingsMenu,
                this.getSettingsPanelRef(view),
                { phase, direction },
                this.$refs?.sttSettingsPanelRoot ?? null,
                SETTINGS_MENU_MIN_WIDTH_PX,
            )
        },

        syncSettingsMenuRoot() {
            const activeSubmenu = this.settingsActiveSubmenu
            const isActiveSubmenu = activeSubmenu !== null
                && this.getSettingsSubmenuPhase(activeSubmenu) === 'active'
            const panel = isActiveSubmenu
                ? this.getSettingsPanelRef(activeSubmenu)
                : this.$refs?.sttSettingsPanelRoot ?? null

            syncSettingsMenuViewportFromPanel(
                this.settingsMenu,
                panel,
                SETTINGS_MENU_MIN_WIDTH_PX,
            )
        },

        syncSettingsMenuAvailableHeight() {
            const menu = this.settingsMenu
            const trigger = this.$refs?.sttSettingsTrigger
            const boundary = this.$refs?.sttSettingsBoundary

            if (! menu || ! trigger || ! boundary || ! this.settingsOpen) {
                return
            }

            const triggerRect = trigger.getBoundingClientRect()
            const boundaryRect = boundary.getBoundingClientRect()
            const spaceBelow = boundaryRect.bottom - triggerRect.bottom - 8
            const available = Math.max(spaceBelow, 160)

            menu.style.setProperty('--fff-video-field-menu-max-height', `${Math.floor(available)}px`)
        },

        syncSettingsMenuViewport() {
            this.syncSettingsMenuAvailableHeight()
            this.syncSettingsMenuRoot()
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
                this.syncSettingsMenuRoot()
            })
        },

        selectSttModel(modelId) {
            this.sttModel = modelId
            this.ensureValidSttModel?.()
            this.navigateSettingsBack()
        },

        selectSttLanguage(languageCode) {
            this.sttLanguage = languageCode ?? ''
            this.navigateSettingsBack()
        },

        selectSttTask(task) {
            this.sttTask = task
            this.navigateSettingsBack()
        },

        toggleSttMultilingual() {
            this.sttMultilingual = ! this.sttMultilingual

            if (! this.sttMultilingual) {
                this.sttLanguage = ''
            }

            this.ensureValidSttModel?.()
        },

        toggleSttQuantized() {
            this.sttQuantized = ! this.sttQuantized
            this.ensureValidSttModel?.()
        },
    }
}

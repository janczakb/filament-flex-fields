import {
    buildRichEditorFooterMetrics,
    cancelIdleWork,
    countImagesMissingAlt,
    createDebouncedScheduler,
    createRafScheduler,
    getEditorPlainText,
    isEmptyRichEditorState,
    runWhenIdle,
    shouldEnableRichEditorChromeSync,
    shouldTrackRichEditorFooterStats,
} from '../core/rich-editor-chrome.js'
import { loadFilamentRichEditorFormComponent } from '../support/load-filament-rich-editor.js'
import { mergeAlpineComponentData } from '../support/merge-alpine-component-data.js'

function isImageNodeSelected(editor) {
    const { selection } = editor.state

    if (selection?.node?.type?.name === 'image') {
        return true
    }

    return editor.isActive('image')
}

function getSelectedImageAnchor(editor, content) {
    if (! editor || ! content) {
        return null
    }

    const { selection } = editor.state

    if (selection?.node?.type?.name === 'image') {
        const nodeDom = editor.view.nodeDOM(selection.from)

        if (nodeDom instanceof HTMLElement) {
            if (nodeDom.tagName === 'IMG') {
                return nodeDom
            }

            const nestedImage = nodeDom.querySelector('img')

            if (nestedImage instanceof HTMLImageElement) {
                return nestedImage
            }

            return nodeDom
        }
    }

    const selectedNode = content.querySelector('.ProseMirror-selectednode')

    if (selectedNode instanceof HTMLElement) {
        if (selectedNode.tagName === 'IMG') {
            return selectedNode
        }

        const nestedImage = selectedNode.querySelector('img')

        if (nestedImage instanceof HTMLImageElement) {
            return nestedImage
        }

        return selectedNode
    }

    const selectedImage = content.querySelector('img.ProseMirror-selectednode')

    if (selectedImage instanceof HTMLImageElement) {
        return selectedImage
    }

    return null
}

function shouldHideParagraphFloatingToolbar(editor) {
    return isImageNodeSelected(editor)
}

export default function flexRichEditorFormComponent(options) {
    const richEditorSrc = options.richEditorSrc

    if (! richEditorSrc) {
        throw new Error('FlexRichEditor requires the Filament rich editor runtime source URL.')
    }

    const footerConfig = options.footerConfig ?? {}
    const distractionFreeHiddenTools = options.distractionFreeHiddenTools ?? []
    const pasteCleanupMode = options.pasteCleanupMode ?? null
    const floatingToolbars = options.floatingToolbars ?? {}
    const componentKey = options.key
    const canAttachFiles = options.canAttachFiles ?? false
    const youtubeConfig = options.youtubeConfig ?? null

    delete options.footerConfig
    delete options.distractionFreeHiddenTools
    delete options.pasteCleanupMode
    delete options.richEditorSrc
    delete options.youtubeConfig

    if (pasteCleanupMode) {
        window.__flexRichEditorPasteCleanupMode = pasteCleanupMode
    }

    if (youtubeConfig) {
        window.__flexRichEditorYoutubeConfig = youtubeConfig
    }

    let chromeSyncScheduler = null
    let overlaySyncScheduler = null
    let altTextSyncScheduler = null
    let teardownToolbarA11y = null
    let boundEditor = null
    let editorUpdateChromeHandler = null
    let visibilityChromeHandler = null
    let autosaveIdleHandle = null
    let imageOverlayResizeObserver = null
    let lastParagraphToolbarHidden = null
    let lastOverlayGeometryKey = null
    const trackFooterStats = shouldTrackRichEditorFooterStats(footerConfig)
    const enableChromeSync = shouldEnableRichEditorChromeSync(footerConfig)
    const enforceHardLimits = footerConfig.limitBehavior === 'hard'
    const trackAltText = footerConfig.altTextRequired === true
    const hasParagraphFloatingToolbar = 'paragraph' in floatingToolbars

    const flexExtensions = {
        footerConfig,
        distractionFreeHiddenTools,
        isRichEditorFullscreen: false,
        autosaveSavedAt: null,
        autosaveTimer: null,
        autosaveDebounceTimer: null,
        autosaveSavedClearTimer: null,
        autosaveSavingMinTimer: null,
        autosaveSavingStartedAt: null,
        autosaveLastSerializedState: null,
        autosaveStatus: 'idle',
        autosaveReady: false,
        autosaveLastEditorUpdatedAt: null,
        footerStats: footerConfig.labels?.empty ?? '',
        footerLimitStatus: 'ok',
        footerAltMissingCount: 0,
        imageOverlayVisible: false,
        imageOverlayScrollTarget: null,
        imageOverlayOnScroll: null,
        imageOverlayOnResize: null,

        scheduleRichEditorChromeSync() {
            if (! enableChromeSync) {
                return
            }

            if (document.hidden && ! enforceHardLimits) {
                return
            }

            if (! chromeSyncScheduler) {
                chromeSyncScheduler = createRafScheduler(() => {
                    this.syncRichEditorChrome()
                })
            }

            chromeSyncScheduler.schedule()
        },

        shouldDeferChromeSync() {
            return document.hidden && ! enforceHardLimits
        },

        setupEditorChromeSync(editor) {
            if (! enableChromeSync || editorUpdateChromeHandler) {
                return
            }

            boundEditor = editor
            editorUpdateChromeHandler = () => {
                this.scheduleRichEditorChromeSync()
            }

            editor.on('update', editorUpdateChromeHandler)

            if (trackFooterStats && ! enforceHardLimits) {
                visibilityChromeHandler = () => {
                    if (! document.hidden) {
                        this.scheduleRichEditorChromeSync()
                    }
                }

                document.addEventListener('visibilitychange', visibilityChromeHandler)
            }
        },

        teardownEditorChromeSync(editor) {
            if (editor && editorUpdateChromeHandler) {
                editor.off('update', editorUpdateChromeHandler)
            }

            editorUpdateChromeHandler = null
            boundEditor = null

            if (visibilityChromeHandler) {
                document.removeEventListener('visibilitychange', visibilityChromeHandler)
                visibilityChromeHandler = null
            }
        },

        scheduleImageOverlaySync(editor) {
            if (! canAttachFiles) {
                return
            }

            if (! overlaySyncScheduler) {
                overlaySyncScheduler = createRafScheduler(() => {
                    this.syncImageOverlay(editor)
                })
            }

            overlaySyncScheduler.schedule()
        },

        scheduleAutosaveFromEdit() {
            if (! footerConfig.autosave || ! this.autosaveReady) {
                return
            }

            if (this.editorUpdatedAt === this.autosaveLastEditorUpdatedAt) {
                return
            }

            this.autosaveLastEditorUpdatedAt = this.editorUpdatedAt

            clearTimeout(this.autosaveDebounceTimer)

            if (isEmptyRichEditorState(this.state)) {
                this.autosaveStatus = 'idle'

                return
            }

            const serialized = JSON.stringify(this.state)

            if (serialized === this.autosaveLastSerializedState) {
                return
            }

            const intervalMs = Math.max(5, Number(footerConfig.autosaveInterval || 30)) * 1000
            const debounceMs = Math.min(2000, Math.max(750, Math.round(intervalMs / 4)))

            this.autosaveDebounceTimer = setTimeout(() => {
                this.persistAutosaveDraft()
            }, debounceMs)
        },

        seedAutosaveBaseline() {
            if (! footerConfig.autosave) {
                return
            }

            if (isEmptyRichEditorState(this.state)) {
                this.autosaveLastSerializedState = null

                return
            }

            const currentSerialized = JSON.stringify(this.state)
            const storageKey = `fff-rich-editor:${footerConfig.autosaveKey}`
            const rawDraft = localStorage.getItem(storageKey)

            if (rawDraft) {
                try {
                    const draft = JSON.parse(rawDraft)

                    if (draft?.state != null && JSON.stringify(draft.state) === currentSerialized) {
                        this.autosaveLastSerializedState = currentSerialized
                        this.autosaveSavedAt = draft.savedAt ?? null

                        return
                    }
                } catch {
                    // Ignore invalid drafts.
                }
            }

            this.autosaveLastSerializedState = currentSerialized
        },

        syncRichEditorChrome() {
            if (trackFooterStats) {
                this.updateRichEditorFooterStats()
            }

            this.scheduleAutosaveFromEdit()
        },

        async setupRichEditorToolbarA11y() {
            if (! this.$refs.toolbar) {
                return
            }

            const { setupToolbarKeyboardNavigation } = await import('../core/rich-editor-toolbar-a11y.js')

            teardownToolbarA11y?.()
            teardownToolbarA11y = setupToolbarKeyboardNavigation(this.$refs.toolbar)
        },

        patchFloatingToolbarPlugins() {
            const editor = typeof this.$getEditor === 'function' ? this.$getEditor() : null

            if (! editor || this.floatingToolbarPluginsPatched) {
                return
            }

            this.floatingToolbarPluginsPatched = true
            boundEditor = editor

            this.setupEditorChromeSync(editor)

            const syncSelectionChrome = () => {
                if (hasParagraphFloatingToolbar) {
                    this.syncParagraphFloatingToolbar(editor)
                }

                if (canAttachFiles) {
                    this.scheduleImageOverlaySync(editor)
                }
            }

            if (hasParagraphFloatingToolbar || canAttachFiles) {
                editor.on('selectionUpdate', syncSelectionChrome)
                editor.on('focus', syncSelectionChrome)
                editor.on('blur', syncSelectionChrome)
            }

            if (canAttachFiles && ! this.imageOverlayOnScroll) {
                const scrollTarget = this.$refs.editor

                if (scrollTarget) {
                    this.imageOverlayScrollTarget = scrollTarget
                    this.imageOverlayOnScroll = () => {
                        this.scheduleImageOverlaySync(editor)
                    }
                    scrollTarget.addEventListener('scroll', this.imageOverlayOnScroll, { passive: true })
                }
            }

            if (canAttachFiles) {
                syncSelectionChrome()
            } else if (hasParagraphFloatingToolbar) {
                this.syncParagraphFloatingToolbar(editor)
            }
        },

        syncParagraphFloatingToolbar(editor) {
            if (! hasParagraphFloatingToolbar) {
                return
            }

            const element = this.$refs['floatingToolbar::paragraph']

            if (! element) {
                return
            }

            const shouldHide = shouldHideParagraphFloatingToolbar(editor)

            if (lastParagraphToolbarHidden === shouldHide) {
                return
            }

            lastParagraphToolbarHidden = shouldHide

            element.classList.toggle('fff-rich-editor__bubble-menu--hidden', shouldHide)

            if (shouldHide) {
                element.style.setProperty('display', 'none', 'important')
                element.style.visibility = 'hidden'

                return
            }

            element.style.removeProperty('display')
            element.style.removeProperty('visibility')
        },

        observeImageOverlayResize(editor, imageAnchor) {
            if (typeof ResizeObserver === 'undefined' || ! imageAnchor) {
                return
            }

            imageOverlayResizeObserver?.disconnect()
            imageOverlayResizeObserver = new ResizeObserver(() => {
                this.scheduleImageOverlaySync(editor)
            })
            imageOverlayResizeObserver.observe(imageAnchor)
        },

        disconnectImageOverlayResizeObserver() {
            imageOverlayResizeObserver?.disconnect()
            imageOverlayResizeObserver = null
        },

        syncImageOverlay(editor) {
            if (! canAttachFiles) {
                return
            }

            const overlay = this.$refs.imageOverlay
            const content = this.$refs.editor
            const main = content?.parentElement

            if (! overlay || ! content || ! main) {
                return
            }

            const imageAnchor = getSelectedImageAnchor(editor, content)
            const shouldShow = imageAnchor != null && isImageNodeSelected(editor)

            this.imageOverlayVisible = shouldShow

            if (! shouldShow) {
                overlay.classList.remove('fff-rich-editor__image-overlay--visible')
                overlay.style.display = 'none'
                lastOverlayGeometryKey = null
                this.disconnectImageOverlayResizeObserver()

                return
            }

            const scrollTop = this.imageOverlayScrollTarget?.scrollTop ?? 0
            const geometryKey = `${editor.state.selection.from}:${imageAnchor.offsetTop}:${imageAnchor.offsetLeft}:${imageAnchor.offsetWidth}:${imageAnchor.offsetHeight}:${scrollTop}`

            if (geometryKey === lastOverlayGeometryKey) {
                return
            }

            lastOverlayGeometryKey = geometryKey

            const mainRect = main.getBoundingClientRect()
            const imageRect = imageAnchor.getBoundingClientRect()

            overlay.classList.add('fff-rich-editor__image-overlay--visible')
            overlay.style.display = 'block'
            overlay.style.left = `${imageRect.left - mainRect.left}px`
            overlay.style.top = `${imageRect.top - mainRect.top}px`
            overlay.style.width = `${imageRect.width}px`
            overlay.style.height = `${imageRect.height}px`

            this.observeImageOverlayResize(editor, imageAnchor)
        },

        editSelectedImage() {
            const editor = typeof this.$getEditor === 'function' ? this.$getEditor() : null

            if (! editor) {
                return
            }

            const attributes = editor.getAttributes('image') ?? {}

            this.$wire.mountAction(
                'attachFiles',
                {
                    editorSelection: this.editorSelection,
                    alt: attributes.alt,
                    id: attributes.id,
                    src: attributes.src,
                },
                { schemaComponent: componentKey },
            )
        },

        deleteSelectedImage() {
            const editor = typeof this.$getEditor === 'function' ? this.$getEditor() : null

            if (! editor) {
                return
            }

            editor.chain().focus().deleteSelection().run()
        },

        markAutosaveSaving() {
            clearTimeout(this.autosaveSavingMinTimer)

            if (this.autosaveStatus !== 'saving') {
                this.autosaveSavingStartedAt = Date.now()
            }

            this.autosaveStatus = 'saving'
        },

        finishAutosaveSaving(savedAt, serialized) {
            const storageKey = `fff-rich-editor:${footerConfig.autosaveKey}`
            const savingStartedAt = this.autosaveSavingStartedAt ?? Date.now()
            const minSavingMs = 500

            const showSaved = () => {
                this.autosaveLastSerializedState = serialized
                this.autosaveSavedAt = savedAt
                this.autosaveStatus = 'saved'
                this.autosaveSavingStartedAt = null
                this.clearAutosaveSavedIndicatorSoon()
            }

            try {
                const payload = JSON.stringify({
                    state: this.state,
                    savedAt,
                })

                cancelIdleWork(autosaveIdleHandle)

                autosaveIdleHandle = runWhenIdle(() => {
                    autosaveIdleHandle = null

                    try {
                        localStorage.setItem(storageKey, payload)
                    } catch {
                        this.autosaveStatus = 'idle'
                        this.autosaveSavingStartedAt = null

                        return
                    }

                    const remaining = minSavingMs - (Date.now() - savingStartedAt)

                    if (remaining > 0) {
                        this.autosaveSavingMinTimer = setTimeout(showSaved, remaining)

                        return
                    }

                    showSaved()
                })
            } catch {
                this.autosaveStatus = 'idle'
                this.autosaveSavingStartedAt = null
            }
        },

        clearAutosaveSavedIndicatorSoon() {
            clearTimeout(this.autosaveSavedClearTimer)

            this.autosaveSavedClearTimer = setTimeout(() => {
                if (this.autosaveStatus === 'saved') {
                    this.autosaveStatus = 'idle'
                }
            }, 3000)
        },

        persistAutosaveDraft() {
            if (! footerConfig.autosave) {
                return
            }

            if (isEmptyRichEditorState(this.state)) {
                this.autosaveStatus = 'idle'

                return
            }

            const serialized = JSON.stringify(this.state)

            if (serialized === this.autosaveLastSerializedState) {
                if (this.autosaveStatus === 'saving') {
                    this.autosaveStatus = 'idle'
                    this.autosaveSavingStartedAt = null
                }

                return
            }

            this.markAutosaveSaving()
            this.finishAutosaveSaving(new Date().toISOString(), serialized)
        },

        isDistractionFreeToolbarButton(buttonName) {
            if (! this.isRichEditorFullscreen || ! this.footerConfig.distractionFree) {
                return false
            }

            return this.distractionFreeHiddenTools.includes(buttonName)
        },

        toggleRichEditorFullscreen() {
            this.isRichEditorFullscreen = ! this.isRichEditorFullscreen
        },

        updateRichEditorFooterStats() {
            const editor = boundEditor ?? (typeof this.$getEditor === 'function' ? this.$getEditor() : null)

            if (! editor) {
                this.footerStats = footerConfig.labels?.empty ?? ''
                this.footerLimitStatus = 'ok'
                this.footerAltMissingCount = 0

                return
            }

            const metrics = buildRichEditorFooterMetrics(getEditorPlainText(editor), footerConfig)

            if (this.footerStats !== metrics.footerStats) {
                this.footerStats = metrics.footerStats
            }

            if (this.footerLimitStatus !== metrics.footerLimitStatus) {
                this.footerLimitStatus = metrics.footerLimitStatus
            }

            if (trackAltText) {
                if (! altTextSyncScheduler) {
                    altTextSyncScheduler = createDebouncedScheduler(() => {
                        const activeEditor = typeof this.$getEditor === 'function' ? this.$getEditor() : null
                        const missing = activeEditor ? countImagesMissingAlt(activeEditor) : 0

                        if (this.footerAltMissingCount !== missing) {
                            this.footerAltMissingCount = missing
                        }
                    })
                }

                altTextSyncScheduler.schedule()
            } else if (this.footerAltMissingCount !== 0) {
                this.footerAltMissingCount = 0
            }

            if (footerConfig.limitBehavior === 'hard') {
                this.enforceHardContentLimits(editor, {
                    characters: metrics.characters,
                    words: metrics.words,
                })
            }
        },

        enforceHardContentLimits(editor, { characters, words }) {
            const maxCharacters = footerConfig.maxCharacters
            const maxWords = footerConfig.maxWords

            if (maxCharacters == null && maxWords == null) {
                return
            }

            const exceedsCharacters = maxCharacters != null && characters > maxCharacters
            const exceedsWords = maxWords != null && words > maxWords

            if (! exceedsCharacters && ! exceedsWords) {
                return
            }

            editor.commands.undo()
        },

        restoreAutosaveDraftIfNeeded() {
            if (! footerConfig.autosave) {
                return
            }

            const storageKey = `fff-rich-editor:${footerConfig.autosaveKey}`
            const rawDraft = localStorage.getItem(storageKey)

            if (! rawDraft) {
                return
            }

            let draft

            try {
                draft = JSON.parse(rawDraft)
            } catch {
                return
            }

            if (! draft?.savedAt || draft.state == null) {
                return
            }

            if (! isEmptyRichEditorState(this.state)) {
                return
            }

            const shouldRestore = window.confirm(footerConfig.labels?.autosaveRestorePrompt ?? '')

            if (! shouldRestore) {
                return
            }

            this.state = draft.state
            this.autosaveSavedAt = draft.savedAt
            this.autosaveLastSerializedState = JSON.stringify(draft.state)
        },

        startAutosaveTimer() {
            if (! footerConfig.autosave) {
                return
            }

            const intervalMs = Math.max(5, Number(footerConfig.autosaveInterval || 30)) * 1000

            this.autosaveTimer = setInterval(() => {
                this.persistAutosaveDraft()
            }, intervalMs)
        },
    }

    return mergeAlpineComponentData(
        {
            state: options.state,
            activePanel: null,
            editorSelection: { type: 'text', anchor: 1, head: 1 },
            isUploadingFile: false,
            fileValidationMessage: null,
            shouldUpdateState: true,
            editorUpdatedAt: Date.now(),
            _filamentRichEditorBootstrapped: false,
            _baseDestroy: null,

            async init() {
                if (this._filamentRichEditorBootstrapped) {
                    return
                }

                const richEditorFormComponent = await loadFilamentRichEditorFormComponent(richEditorSrc)
                const base = richEditorFormComponent(options)
                const baseInit = base.init

                this._baseDestroy = base.destroy

                for (const key of Reflect.ownKeys(base)) {
                    if (key === 'init' || key === 'destroy') {
                        continue
                    }

                    const descriptor = Object.getOwnPropertyDescriptor(base, key)

                    if (descriptor) {
                        Object.defineProperty(this, key, descriptor)
                    }
                }

                if (typeof baseInit === 'function') {
                    await baseInit.call(this)
                }

                this._filamentRichEditorBootstrapped = true

                this.restoreAutosaveDraftIfNeeded()
                this.seedAutosaveBaseline()
                this.autosaveLastEditorUpdatedAt = this.editorUpdatedAt
                this.autosaveReady = true
                this.patchFloatingToolbarPlugins()

                requestAnimationFrame(() => {
                    this.setupRichEditorToolbarA11y()
                })

                this.startAutosaveTimer()

                if (trackFooterStats) {
                    this.scheduleRichEditorChromeSync()
                }
            },

            destroy() {
                chromeSyncScheduler?.cancel()
                overlaySyncScheduler?.cancel()
                altTextSyncScheduler?.cancel()
                cancelIdleWork(autosaveIdleHandle)
                autosaveIdleHandle = null
                this.teardownEditorChromeSync(boundEditor)
                this.disconnectImageOverlayResizeObserver()
                teardownToolbarA11y?.()
                teardownToolbarA11y = null

                if (this.autosaveTimer) {
                    clearInterval(this.autosaveTimer)
                }

                clearTimeout(this.autosaveDebounceTimer)
                clearTimeout(this.autosaveSavedClearTimer)
                clearTimeout(this.autosaveSavingMinTimer)

                if (this.imageOverlayScrollTarget && this.imageOverlayOnScroll) {
                    this.imageOverlayScrollTarget.removeEventListener('scroll', this.imageOverlayOnScroll)
                }

                if (typeof this._baseDestroy === 'function') {
                    this._baseDestroy.call(this)
                }

                this._baseDestroy = null
                this._filamentRichEditorBootstrapped = false
            },
        },
        flexExtensions,
    )
}

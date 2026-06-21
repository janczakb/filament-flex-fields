import { createExclusiveDropdownMixin } from '../core/flex-dropdown-coordinator.js'

let sharedEmojiPickerModule = null
let sharedEmojiPickerPromise = null

async function loadSharedEmojiPicker() {
    if (sharedEmojiPickerModule) {
        return sharedEmojiPickerModule
    }

    if (! sharedEmojiPickerPromise) {
        sharedEmojiPickerPromise = import('../core/shared-emoji-picker.js').then((module) => {
            sharedEmojiPickerModule = module

            return module
        })
    }

    return sharedEmojiPickerPromise
}

const exclusiveDropdown = createExclusiveDropdownMixin({
    openKey: 'emojiPickerOpen',
    closeMethod: 'closeEmojiPicker',
    ownerIdPrefix: 'fff-emoji-picker',
})

export default function flexTextareaFormComponent({
    state,
    statePath,
    initialHeightRem,
    shouldAutosize,
    animatedAutosize,
    maxHeight,
    characterLimit,
    showCharacterCounter,
    speechDictation,
    speechDictationLanguage,
    speechDictationLabel,
    speechDictationStopLabel,
    emojiPicker,
    emojiPickerLocale,
    emojiPickerLabel,
    initialState,
    initialCharacterCount,
}) {
    const fallbackState = String(initialState ?? '').trim()

    if (fallbackState !== '' && String(state ?? '').trim() === '') {
        state = fallbackState
    }

    return {
        ...exclusiveDropdown,
        state,
        statePath,
        initialState: initialState ?? '',
        initialCharacterCount: Number(initialCharacterCount) || 0,
        stateHydrated: false,
        initialHeightRem,
        shouldAutosize,
        animatedAutosize,
        maxHeight,
        characterLimit,
        showCharacterCounter,
        speechDictation,
        speechDictationLanguage,
        speechDictationLabel,
        speechDictationStopLabel,
        emojiPicker,
        emojiPickerLocale,
        emojiPickerLabel,
        isListening: false,
        speechSupported: false,
        recognition: null,
        dictationBase: '',
        dictationCommitted: '',
        dictationInterim: '',
        pendingDictationInsert: false,
        dictationInsertAttempt: 0,
        dictationStatus: '',
        dictationStatusTimeout: null,
        insertTimeout: null,
        emojiPickerOpen: false,
        emojiPickerReady: false,

        init() {
            this.wireExclusiveFlexDropdown()

            const textarea = this.$refs.textarea
            const seededValue = String(textarea?.value ?? '').trim()
            const fallbackState = String(this.initialState ?? '').trim()
            const currentState = String(this.state ?? '').trim()

            if (seededValue !== '' && currentState === '') {
                this.state = textarea.value
                this.stateHydrated = true
            } else if (currentState === '' && fallbackState !== '') {
                this.state = fallbackState
                this.stateHydrated = true
            } else if (currentState !== '') {
                this.stateHydrated = true
            } else if (this.initialCharacterCount === 0) {
                this.stateHydrated = true
            } else {
                this.$watch('state', (value) => {
                    if (String(value ?? '').length > 0) {
                        this.stateHydrated = true
                    }
                })
            }

            this.$nextTick(() => this.resize())

            if (this.speechDictation) {
                this.speechSupported = Boolean(
                    window.SpeechRecognition || window.webkitSpeechRecognition,
                )
            }

            if (this.emojiPicker) {
                void loadSharedEmojiPicker().then(({ sharedEmojiPicker }) => {
                    sharedEmojiPicker.preload(this.emojiPickerLocale)
                })
            }

            if (this.shouldAutosize) {
                this.$watch('state', () => {
                    this.$nextTick(() => this.resize())
                })
            }

            const handleWindowResize = () => this.resize()
            window.addEventListener('resize', handleWindowResize)

            return async () => {
                window.removeEventListener('resize', handleWindowResize)
                this.clearInsertTimeout()
                this.clearDictationStatusTimeout()
                this.abortDictation()

                const { sharedEmojiPicker } = await loadSharedEmojiPicker()

                if (sharedEmojiPicker.isOpenFor(this)) {
                    sharedEmojiPicker.close()
                }
            }
        },

        resolveDictationLanguage() {
            if (this.speechDictationLanguage) {
                return this.speechDictationLanguage
            }

            const browserLanguage = navigator.language ?? ''

            if (browserLanguage.toLowerCase().startsWith('pl')) {
                return 'pl-PL'
            }

            return browserLanguage || 'pl-PL'
        },

        buildRecognition() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition

            if (! SpeechRecognition) {
                this.speechSupported = false

                return null
            }

            const recognition = new SpeechRecognition()
            recognition.continuous = true
            recognition.interimResults = true
            recognition.lang = this.resolveDictationLanguage()

            recognition.onresult = (event) => {
                this.handleDictationResult(event)
            }

            recognition.onerror = (event) => {
                this.handleDictationError(event)
            }

            recognition.onend = () => {
                this.handleDictationEnd()
            }

            return recognition
        },

        handleDictationResult(event) {
            let interim = ''

            for (let index = event.resultIndex; index < event.results.length; index++) {
                const result = event.results[index]
                const transcript = result[0]?.transcript ?? ''

                if (result.isFinal) {
                    this.dictationCommitted += transcript
                } else {
                    interim += transcript
                }
            }

            this.dictationInterim = interim

            if (! this.isListening && this.pendingDictationInsert) {
                this.scheduleInsertDictationTranscript(250)
            }
        },

        currentDictationTranscript() {
            return `${this.dictationCommitted}${this.dictationInterim}`.trim()
        },

        handleDictationError(event) {
            if (event.error === 'aborted' || event.error === 'no-speech') {
                return
            }

            if (this.pendingDictationInsert) {
                return
            }

            this.isListening = false

            const messages = {
                'not-allowed': 'Microphone access denied.',
                'audio-capture': 'No microphone found.',
                network: 'Speech recognition requires an internet connection.',
            }

            this.showDictationStatus(messages[event.error] ?? 'Speech recognition failed.')
            this.cleanupRecognition()
        },

        handleDictationEnd() {
            if (this.isListening) {
                try {
                    this.recognition?.start()
                } catch (error) {
                    this.isListening = false

                    if (this.pendingDictationInsert) {
                        this.scheduleInsertDictationTranscript()
                    }
                }

                return
            }

            if (this.pendingDictationInsert) {
                this.scheduleInsertDictationTranscript()
            }
        },

        toggleDictation() {
            if (! this.speechSupported) {
                this.showDictationStatus('Speech recognition is not supported in this browser.')

                return
            }

            if (this.isListening) {
                this.stopDictation()

                return
            }

            this.startDictation()
        },

        startDictation() {
            this.cleanupRecognition()

            const current = String(this.$refs.textarea?.value ?? this.state ?? '')
            const separator = current.length > 0 && ! current.endsWith(' ') ? ' ' : ''

            this.dictationBase = `${current}${separator}`
            this.dictationCommitted = ''
            this.dictationInterim = ''
            this.pendingDictationInsert = false
            this.dictationInsertAttempt = 0
            this.dictationStatus = ''
            this.recognition = this.buildRecognition()

            if (! this.recognition) {
                this.showDictationStatus('Speech recognition is not supported in this browser.')

                return
            }

            this.isListening = true

            try {
                this.recognition.start()
            } catch (error) {
                this.isListening = false
                this.recognition = null
                this.showDictationStatus('Could not start speech recognition.')
            }
        },

        stopDictation() {
            if (! this.isListening) {
                return
            }

            this.isListening = false
            this.pendingDictationInsert = true
            this.dictationInsertAttempt = 0

            if (! this.recognition) {
                this.scheduleInsertDictationTranscript()

                return
            }

            try {
                this.recognition.stop()
            } catch (error) {
                this.scheduleInsertDictationTranscript()
            }
        },

        cleanupRecognition() {
            this.clearInsertTimeout()

            if (! this.recognition) {
                return
            }

            this.recognition.onresult = null
            this.recognition.onerror = null
            this.recognition.onend = null

            try {
                this.recognition.abort()
            } catch (error) {
                // Ignore abort errors when recognition is already inactive.
            }

            this.recognition = null
        },

        abortDictation() {
            this.isListening = false
            this.pendingDictationInsert = false
            this.dictationInsertAttempt = 0
            this.cleanupRecognition()
        },

        scheduleInsertDictationTranscript(delay = 600) {
            this.clearInsertTimeout()

            this.insertTimeout = setTimeout(() => {
                this.insertTimeout = null
                this.flushDictationInsert()
            }, delay)
        },

        clearInsertTimeout() {
            if (this.insertTimeout !== null) {
                clearTimeout(this.insertTimeout)
                this.insertTimeout = null
            }
        },

        flushDictationInsert() {
            if (! this.pendingDictationInsert) {
                return
            }

            const transcript = this.currentDictationTranscript()

            if (transcript === '' && this.dictationInsertAttempt < 4) {
                this.dictationInsertAttempt += 1
                this.scheduleInsertDictationTranscript(350)

                return
            }

            this.pendingDictationInsert = false
            this.insertDictationTranscript()
            this.cleanupRecognition()
        },

        insertDictationTranscript() {
            const transcript = this.currentDictationTranscript()

            if (transcript === '') {
                this.showDictationStatus('No speech detected. Speak clearly, then click stop.')

                return
            }

            let nextState = `${this.dictationBase}${transcript}`

            if (this.characterLimit && nextState.length > this.characterLimit) {
                nextState = nextState.slice(0, this.characterLimit)
            }

            this.dictationCommitted = ''
            this.dictationInterim = ''
            this.applyStateToTextarea(nextState)
            this.dictationBase = nextState
        },

        applyStateToTextarea(nextState) {
            const textarea = this.$refs.textarea

            if (! textarea) {
                this.state = nextState

                if (this.$wire && this.statePath) {
                    this.$wire.set(this.statePath, nextState, true)
                }

                this.$nextTick(() => this.resize())

                return
            }

            textarea.value = nextState
            textarea.dispatchEvent(new Event('input', { bubbles: true }))

            if (this.$wire && this.statePath) {
                this.$wire.set(this.statePath, nextState, true)
            }

            this.$nextTick(() => this.resize())
        },

        async toggleEmojiPicker() {
            const anchor = this.$refs.emojiTrigger
            const { sharedEmojiPicker } = await loadSharedEmojiPicker()

            if (sharedEmojiPicker.shouldSuppressToggle(anchor)) {
                this.emojiPickerOpen = false

                return
            }

            if (sharedEmojiPicker.isOpenForAnchor(anchor) || this.emojiPickerOpen) {
                this.closeEmojiPicker()

                return
            }

            sharedEmojiPicker.open({
                context: this,
                anchor,
                locale: this.emojiPickerLocale,
                getPrimaryColor: () => this.resolvePrimaryColor(),
                onSelect: (unicode) => this.insertAtCursor(unicode),
                onClose: () => {
                    this.emojiPickerOpen = false
                },
                onReadyChange: (ready) => {
                    this.emojiPickerReady = ready
                },
            })

            this.emojiPickerOpen = true
            this.emojiPickerReady = sharedEmojiPicker.isReady(this.emojiPickerLocale)
        },

        async closeEmojiPicker() {
            const anchor = this.$refs.emojiTrigger
            const { sharedEmojiPicker } = await loadSharedEmojiPicker()

            if (sharedEmojiPicker.isOpenForAnchor(anchor)) {
                sharedEmojiPicker.close()
            }

            this.emojiPickerOpen = false
        },

        resolvePrimaryColor() {
            const field = this.$el?.closest('.fff-flex-textarea')
            const primary = field
                ? getComputedStyle(field).getPropertyValue('--fff-flex-textarea-primary').trim()
                : ''

            return primary || 'rgb(59 130 246)'
        },

        insertAtCursor(text) {
            const textarea = this.$refs.textarea
            const currentValue = String(textarea?.value ?? this.state ?? '')
            const start = textarea?.selectionStart ?? currentValue.length
            const end = textarea?.selectionEnd ?? currentValue.length
            let nextState = `${currentValue.slice(0, start)}${text}${currentValue.slice(end)}`

            if (this.characterLimit && nextState.length > this.characterLimit) {
                nextState = nextState.slice(0, this.characterLimit)
            }

            this.applyStateToTextarea(nextState)

            if (! textarea) {
                return
            }

            this.$nextTick(() => {
                const cursor = Math.min(start + text.length, nextState.length)
                textarea.focus()
                textarea.setSelectionRange(cursor, cursor)
            })
        },

        showDictationStatus(message) {
            this.dictationStatus = message
            this.clearDictationStatusTimeout()

            this.dictationStatusTimeout = setTimeout(() => {
                this.dictationStatus = ''
                this.dictationStatusTimeout = null
            }, 5000)
        },

        clearDictationStatusTimeout() {
            if (this.dictationStatusTimeout !== null) {
                clearTimeout(this.dictationStatusTimeout)
                this.dictationStatusTimeout = null
            }
        },

        resize() {
            if (! this.shouldAutosize || ! this.$refs.textarea) {
                return
            }

            const textarea = this.$refs.textarea
            const minHeightPx = this.initialHeightRem * parseFloat(getComputedStyle(document.documentElement).fontSize)

            textarea.style.height = 'auto'

            let nextHeight = Math.max(textarea.scrollHeight, minHeightPx)

            if (this.maxHeight) {
                const probe = document.createElement('div')
                probe.style.position = 'absolute'
                probe.style.visibility = 'hidden'
                probe.style.height = this.maxHeight
                document.body.appendChild(probe)
                const maxHeightPx = probe.offsetHeight
                probe.remove()

                nextHeight = Math.min(nextHeight, maxHeightPx)
            }

            if (Math.abs(textarea.offsetHeight - nextHeight) <= 1) {
                return
            }

            textarea.style.height = `${nextHeight}px`
        },

        get canSubmit() {
            return String(this.state ?? '').trim().length > 0
        },

        get characterCount() {
            const current = String(this.state ?? '').length

            if (! this.stateHydrated && this.initialCharacterCount > 0) {
                return this.initialCharacterCount
            }

            return current
        },

        get counterLabel() {
            if (! this.showCharacterCounter) {
                return ''
            }

            if (this.characterLimit) {
                return `${this.characterCount}/${this.characterLimit}`
            }

            return String(this.characterCount)
        },
    }
}

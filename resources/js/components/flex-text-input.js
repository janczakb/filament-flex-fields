import { sharedEmojiPicker } from '../core/shared-emoji-picker.js'

export default function flexTextInputFormComponent({
    state,
    statePath,
    characterLimit,
    showCharacterCounter,
    clearable,
    showPasswordStrength,
    passwordStrengthLabels,
    speechDictation,
    speechDictationLanguage,
    speechDictationLabel,
    speechDictationStopLabel,
    emojiPicker,
    emojiPickerLocale,
    emojiPickerLabel,
    isPasswordRevealable,
    hasPersistentActions,
    loadingInGroup,
    initialCharacterCount,
    initialState,
}) {
    const fallbackState = String(initialState ?? '').trim()

    if (fallbackState !== '' && String(state ?? '').trim() === '') {
        state = fallbackState
    }

    return {
        state,
        statePath,
        initialState: initialState ?? '',
        hasPersistentActions: Boolean(hasPersistentActions),
        loadingInGroup: Boolean(loadingInGroup),
        loadingVisible: false,
        initialCharacterCount: Number(initialCharacterCount) || 0,
        stateHydrated: false,
        characterLimit,
        showCharacterCounter: Boolean(showCharacterCounter),
        clearable: Boolean(clearable),
        showPasswordStrength: Boolean(showPasswordStrength),
        passwordStrengthLabels: Array.isArray(passwordStrengthLabels) && passwordStrengthLabels.length === 5
            ? passwordStrengthLabels
            : ['Very weak', 'Weak', 'Fair', 'Good', 'Strong'],
        speechDictation,
        speechDictationLanguage,
        speechDictationLabel,
        speechDictationStopLabel,
        emojiPicker,
        emojiPickerLocale,
        emojiPickerLabel,
        isPasswordRevealable: Boolean(isPasswordRevealable),
        isPasswordRevealed: false,
        isListening: false,
        speechSupported: speechDictation
            ? Boolean(window.SpeechRecognition || window.webkitSpeechRecognition)
            : false,
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
        focusFromPointer: false,
        isInputUpdating: false,

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

        get canClear() {
            return this.clearable && this.characterCount > 0
        },

        get shouldShowActionGroup() {
            if (this.hasPersistentActions) {
                return true
            }

            if (this.clearable && this.characterCount > 0) {
                return true
            }

            if (this.loadingInGroup && this.loadingVisible) {
                return true
            }

            return false
        },

        get passwordStrengthMeta() {
            return this.calculatePasswordStrength(String(this.state ?? ''))
        },

        calculatePasswordStrength(password) {
            if (! this.showPasswordStrength || password === '') {
                return {
                    score: 0,
                    label: '',
                    percent: 0,
                }
            }

            let score = 0

            if (password.length >= 8) {
                score++
            }

            if (password.length >= 12) {
                score++
            }

            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) {
                score++
            }

            if (/\d/.test(password)) {
                score++
            }

            if (/[^a-zA-Z0-9]/.test(password)) {
                score++
            }

            score = Math.min(4, score)

            const labels = this.passwordStrengthLabels

            return {
                score,
                label: labels[score] ?? '',
                percent: (score / 4) * 100,
            }
        },

        clearInput() {
            this.stateHydrated = true
            this.applyStateToInput('')
            this.$refs.input?.focus()
        },

        focusInputFromAffix() {
            const input = this.$refs.input

            if (! input) {
                return
            }

            this.focusInputAtEnd(input)
        },

        focusInputAtEnd(input = this.$refs.input) {
            if (! input) {
                return
            }

            input.focus()

            const length = input.value.length

            if (length > 0) {
                input.setSelectionRange(length, length)
            }
        },

        onInput(event) {
            this.stateHydrated = true
            this.isInputUpdating = true
            this.state = event.target.value

            this.$nextTick(() => {
                this.isInputUpdating = false
            })
        },

        bindInputFocusHandlers() {
            const input = this.$refs.input

            if (! input) {
                return
            }

            input.addEventListener('pointerdown', () => {
                this.focusFromPointer = true
            })

            input.addEventListener('focus', () => {
                const length = input.value.length

                if (length === 0) {
                    return
                }

                const repositionCaretToEnd = () => {
                    if (document.activeElement !== input) {
                        return
                    }

                    input.setSelectionRange(length, length)
                }

                if (this.focusFromPointer) {
                    this.focusFromPointer = false

                    requestAnimationFrame(() => {
                        if (input.selectionStart === 0 && input.selectionEnd === 0) {
                            repositionCaretToEnd()
                        }
                    })

                    return
                }

                repositionCaretToEnd()

                requestAnimationFrame(repositionCaretToEnd)
            })
        },

        syncInputFromState() {
            const input = this.$refs.input

            if (! input) {
                return
            }

            const nextState = String(this.state ?? '')

            if (input.value !== nextState) {
                input.value = nextState

                if (document.activeElement === input && nextState.length > 0) {
                    const length = nextState.length
                    input.setSelectionRange(length, length)
                }
            }
        },

        hydrateStateFromInput() {
            const input = this.$refs.input
            const seededValue = String(input?.value ?? '').trim()
            const fallbackState = String(this.initialState ?? '').trim()
            const currentState = String(this.state ?? '').trim()

            if (seededValue !== '' && currentState === '') {
                this.state = input.value
                this.stateHydrated = true
            } else if (currentState === '' && fallbackState !== '') {
                this.state = fallbackState
                this.syncInputFromState()
                this.stateHydrated = true
            } else if (currentState !== '') {
                this.syncInputFromState()
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
        },

        bindLoadingIndicator() {
            const loading = this.$el?.querySelector('.fff-flex-text-input__loading')

            if (! loading) {
                return
            }

            const sync = () => {
                this.loadingVisible = loading.classList.contains('is-visible')
            }

            sync()

            this.loadingObserver = new MutationObserver(sync)
            this.loadingObserver.observe(loading, {
                attributes: true,
                attributeFilter: ['class'],
            })
        },

        init() {
            if (this.speechDictation && ! this.speechSupported) {
                this.speechSupported = Boolean(
                    window.SpeechRecognition || window.webkitSpeechRecognition,
                )
            }

            if (this.emojiPicker) {
                sharedEmojiPicker.preload(this.emojiPickerLocale)
            }

            if (this.loadingInGroup) {
                this.bindLoadingIndicator()
            }

            this.hydrateStateFromInput()
            this.bindInputFocusHandlers()

            this.$nextTick(() => {
                const input = this.$refs.input

                if (! input || document.activeElement !== input) {
                    return
                }

                const length = input.value.length

                if (length > 0) {
                    input.setSelectionRange(length, length)
                }
            })

            this.$watch('state', () => {
                if (this.isInputUpdating) {
                    return
                }

                this.syncInputFromState()
            })

            return () => {
                this.loadingObserver?.disconnect()
                this.clearInsertTimeout()
                this.clearDictationStatusTimeout()
                this.abortDictation()

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

            const current = String(this.$refs.input?.value ?? this.state ?? '')
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
            this.applyStateToInput(nextState)
            this.dictationBase = nextState
        },

        applyStateToInput(nextState) {
            const input = this.$refs.input

            if (! input) {
                this.state = nextState

                if (this.$wire && this.statePath) {
                    this.$wire.set(this.statePath, nextState, true)
                }

                return
            }

            input.value = nextState
            input.dispatchEvent(new Event('input', { bubbles: true }))

            if (this.$wire && this.statePath) {
                this.$wire.set(this.statePath, nextState, true)
            }
        },

        toggleEmojiPicker() {
            if (this.emojiPickerOpen) {
                this.closeEmojiPicker()

                return
            }

            sharedEmojiPicker.open({
                context: this,
                anchor: this.$refs.emojiTrigger,
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

        closeEmojiPicker() {
            if (sharedEmojiPicker.isOpenFor(this)) {
                sharedEmojiPicker.close()
            }

            this.emojiPickerOpen = false
        },

        resolvePrimaryColor() {
            const field = this.$el?.closest('.fff-flex-text-input')
            const primary = field
                ? getComputedStyle(field).getPropertyValue('--fff-flex-text-input-primary').trim()
                : ''

            return primary || 'rgb(59 130 246)'
        },

        insertAtCursor(text) {
            const input = this.$refs.input
            const currentValue = String(input?.value ?? this.state ?? '')
            const start = input?.selectionStart ?? currentValue.length
            const end = input?.selectionEnd ?? currentValue.length
            let nextState = `${currentValue.slice(0, start)}${text}${currentValue.slice(end)}`

            if (this.characterLimit && nextState.length > this.characterLimit) {
                nextState = nextState.slice(0, this.characterLimit)
            }

            this.applyStateToInput(nextState)

            if (! input) {
                return
            }

            this.$nextTick(() => {
                const cursor = Math.min(start + text.length, nextState.length)
                input.focus()
                input.setSelectionRange(cursor, cursor)
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
    }
}

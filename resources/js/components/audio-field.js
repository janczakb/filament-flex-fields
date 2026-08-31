import { createAudioFieldSettingsMenuMixin } from '../core/audio-field-settings-menu.js'
import { createAudioPlaybackMixin } from '../core/audio-playback.js'
import { formatAudioTime } from '../core/format-time.js'
import { createWaveformBarsMixin } from '../core/waveform-bars.js'
import { mergeAlpineComponentData } from '../support/merge-alpine-component-data.js'
import {
    AUDIO_WAVEFORM_SAMPLE_COUNT,
    extractWaveformFromUrl,
    generateWaveformFromFingerprint,
} from '../core/audio-waveform.js'
import {
    filterWhisperModels,
    formatWhisperModelLabel,
    pickWhisperModelSelection,
} from '../core/whisper-model-catalog.js'
import {
    modelSupportsMultilingual,
    resolveWhisperModelId,
    transcribeAudioUrl,
} from '../core/whisper-transcription.js'

export { formatAudioTime }

export default function audioFieldFormComponent({
    state,
    staticSrc = null,
    waveform = [],
    waveformIsCustom = false,
    loop = false,
    readOnly = false,
    labels = {},
    transcription = null,
}) {
    const playback = createAudioPlaybackMixin({
        canInteract() {
            return ! this.readOnly && this.audioSrc !== ''
        },
    })

    const waveformBars = createWaveformBarsMixin()
    const settingsMenu = createAudioFieldSettingsMenuMixin()

    return mergeAlpineComponentData({
        state,
        staticSrc,
        initialWaveform: waveform,
        sourceWaveform: waveform,
        waveformIsCustom,
        loop,
        readOnly,
        labels,
        transcription,
        waveformAnalysisToken: 0,
        transcribing: false,
        transcript: '',
        transcriptionError: null,
        transcriptionPhase: null,
        transcriptionProgress: null,
        sttModel: transcription?.model ?? 'Xenova/whisper-tiny',
        sttQuantized: transcription?.quantized ?? true,
        sttMultilingual: transcription?.multilingual ?? true,
        sttLanguage: transcription?.language ?? '',
        sttTask: transcription?.task ?? 'transcribe',

        init() {
            this.bindAudioElement(this.$refs.audio)
            this.ensureValidSttModel()

            this.$watch('audioSrc', (src) => {
                this.onAudioSrcChanged(src)
            })

            this.$watch('sttMultilingual', (multilingual) => {
                if (! multilingual) {
                    this.sttLanguage = ''
                }

                this.ensureValidSttModel()
            })

            this.$watch('sttQuantized', () => {
                this.ensureValidSttModel()
            })

            this.$nextTick(() => {
                this.setupWaveformObserver()
                this.onAudioSrcChanged(this.audioSrc)
            })
        },

        destroy() {
            this.unbindAudioElement?.()
            this.disconnectWaveformObserver()
            this.stopSettingsMenuSizeWatch?.()
            this.closeSettingsMenu?.()
        },

        onAudioSrcChanged(src) {
            if (this.waveformIsCustom) {
                this.sourceWaveform = [...this.initialWaveform]
                this.updateWaveformBars()

                return
            }

            this.sourceWaveform = generateWaveformFromFingerprint(src, AUDIO_WAVEFORM_SAMPLE_COUNT)
            this.updateWaveformBars()
            this.loadAnalyzedWaveform(src)
        },

        async loadAnalyzedWaveform(src) {
            if (! src || this.waveformIsCustom) {
                return
            }

            const token = ++this.waveformAnalysisToken
            const peaks = await extractWaveformFromUrl(src, AUDIO_WAVEFORM_SAMPLE_COUNT)

            if (token !== this.waveformAnalysisToken || src !== this.audioSrc || ! peaks?.length) {
                return
            }

            this.sourceWaveform = peaks
            this.updateWaveformBars()
        },

        get transcriptionEnabled() {
            return this.transcription !== null
        },

        get transcriptionSettingsVisible() {
            return this.transcription?.settingsVisible ?? false
        },

        get transcriptionModels() {
            return this.transcription?.models ?? []
        },

        get visibleTranscriptionModels() {
            return filterWhisperModels(
                this.transcriptionModels,
                this.sttMultilingual,
                this.sttQuantized,
            )
        },

        get transcriptionModelOptions() {
            return this.visibleTranscriptionModels.map((model) => ({
                id: model.id,
                label: formatWhisperModelLabel(model, this.sttMultilingual, this.sttQuantized),
            }))
        },

        get transcriptionLanguages() {
            return this.transcription?.languages ?? []
        },

        get resolvedWhisperModel() {
            return resolveWhisperModelId(this.sttModel, this.sttMultilingual, this.transcriptionModels)
        },

        get canTranscribe() {
            return this.transcriptionEnabled
                && ! this.readOnly
                && this.audioSrc !== ''
                && ! this.transcribing
        },

        get transcriptionStatusLabel() {
            if (this.transcriptionError) {
                return this.transcriptionError
            }

            if (this.transcribing && this.transcriptionPhase === 'loading_model' && this.transcriptionProgress?.progress != null) {
                const fileName = (this.transcriptionProgress.file ?? '').split('/').pop() || 'model'
                const base = this.labels.transcription?.loading_model ?? 'Loading Whisper model…'

                return `${base} (${fileName} ${this.transcriptionProgress.progress}%)`
            }

            if (this.transcribing && this.transcriptionPhase) {
                return this.labels.transcription?.[this.transcriptionPhase] ?? this.transcriptionPhase
            }

            return ''
        },

        isModelMultilingual(model) {
            return modelSupportsMultilingual(model, this.transcriptionModels)
        },

        ensureValidSttModel() {
            this.sttModel = pickWhisperModelSelection(
                this.transcriptionModels,
                this.sttModel,
                this.sttMultilingual,
                this.sttQuantized,
            )
        },

        get audioSrc() {
            return this.staticSrc || this.state || ''
        },

        get canInteract() {
            return ! this.readOnly && this.audioSrc !== ''
        },

        get timeLabel() {
            if (this.currentTime > 0) {
                return formatAudioTime(this.currentTime)
            }

            if (this.duration) {
                return formatAudioTime(this.duration)
            }

            return '0:00'
        },

        togglePlay() {
            this.toggleAudioPlayback()
        },

        seekTo(ratio) {
            this.seekAudioTo(ratio)
        },

        async transcribeAudio() {
            if (! this.canTranscribe) {
                return
            }

            this.transcribing = true
            this.transcriptionError = null
            this.transcriptionPhase = 'loading_model'
            this.transcriptionProgress = null
            this.transcript = ''

            try {
                const text = await transcribeAudioUrl(this.audioSrc, {
                    model: this.sttModel,
                    quantized: this.sttQuantized,
                    multilingual: this.sttMultilingual,
                    language: this.sttLanguage || null,
                    task: this.sttTask,
                    models: this.transcriptionModels,
                    runtimeModuleUrl: this.transcription?.runtimeModuleUrl ?? '',
                    runtimeWasmBaseUrl: this.transcription?.runtimeWasmBaseUrl ?? '',
                }, (phase, detail) => {
                    this.transcriptionPhase = phase

                    if (detail) {
                        this.transcriptionProgress = detail
                    }
                })

                this.transcript = text

                if (! text) {
                    this.transcriptionError = this.labels.transcription?.empty ?? 'No speech detected.'
                }
            } catch (error) {
                console.error('[AudioField] Whisper transcription failed:', error)

                const code = error instanceof Error
                    ? error.message.split(':')[0]
                    : 'transcription_failed'

                this.transcriptionError = this.labels.transcription?.errors?.[code]
                    ?? this.labels.transcription?.errors?.transcription_failed
                    ?? 'Transcription failed.'
            } finally {
                this.transcribing = false
                this.transcriptionPhase = null
                this.transcriptionProgress = null
            }
        },
    }, playback, waveformBars, settingsMenu)
}

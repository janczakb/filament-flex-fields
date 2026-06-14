import { createAudioPlaybackMixin } from '../core/audio-playback.js'
import { formatAudioTime } from '../core/format-time.js'
import { createWaveformBarsMixin } from '../core/waveform-bars.js'
import {
    AUDIO_WAVEFORM_SAMPLE_COUNT,
    extractWaveformFromUrl,
    generateWaveformFromFingerprint,
} from '../core/audio-waveform.js'

export { formatAudioTime }

export default function audioFieldFormComponent({
    state,
    staticSrc = null,
    waveform = [],
    waveformIsCustom = false,
    loop = false,
    readOnly = false,
    labels = {},
}) {
    const playback = createAudioPlaybackMixin({
        canInteract() {
            return ! this.readOnly && this.audioSrc !== ''
        },
    })

    const waveformBars = createWaveformBarsMixin()

    return {
        state,
        staticSrc,
        initialWaveform: waveform,
        sourceWaveform: waveform,
        waveformIsCustom,
        loop,
        readOnly,
        labels,
        waveformAnalysisToken: 0,

        ...playback,
        ...waveformBars,

        init() {
            this.bindAudioElement(this.$refs.audio)

            this.$watch('audioSrc', (src) => {
                this.onAudioSrcChanged(src)
            })

            this.$nextTick(() => {
                this.setupWaveformObserver()
                this.onAudioSrcChanged(this.audioSrc)
            })
        },

        destroy() {
            this.disconnectWaveformObserver()
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
    }
}

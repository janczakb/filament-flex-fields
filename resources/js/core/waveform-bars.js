import { cssVarPx, resolveBarCount, resamplePeaks } from './dynamic-bars.js'

export function createWaveformBarsMixin({
    barWidthVar = '--fff-audio-field-bar-w',
    barWidthFallback = 2.5,
    gapVar = '--fff-audio-field-bar-gap',
    gapFallback = 1,
    minBars = 16,
    minPeak = 8,
    maxBars = null,
} = {}) {
    return {
        displayWaveform: [],
        waveformResizeObserver: null,

        setupWaveformObserver() {
            const waveform = this.$refs.waveform

            if (! waveform || this.waveformResizeObserver) {
                return
            }

            this.waveformResizeObserver = new ResizeObserver(() => {
                this.updateWaveformBars()
            })

            this.waveformResizeObserver.observe(waveform)
        },

        disconnectWaveformObserver() {
            this.waveformResizeObserver?.disconnect()
            this.waveformResizeObserver = null
        },

        resolveWaveformBarCount(width) {
            const idealBarWidthPx = cssVarPx(this.$refs.waveform, barWidthVar, barWidthFallback)
            const gapPx = cssVarPx(this.$refs.waveform, gapVar, gapFallback)
            const resolvedMaxBars = typeof maxBars === 'function'
                ? maxBars.call(this, width)
                : (maxBars ?? Math.max(this.sourceWaveform?.length ?? 0, 96))

            return resolveBarCount(width, {
                idealBarWidthPx,
                gapPx,
                minBars,
                maxBars: resolvedMaxBars,
            })
        },

        updateWaveformBars() {
            const waveform = this.$refs.waveform

            if (! waveform) {
                return
            }

            const width = waveform.clientWidth

            if (! width) {
                return
            }

            const count = this.resolveWaveformBarCount(width)

            this.displayWaveform = resamplePeaks(this.sourceWaveform ?? [], count, { minPeak })

            if (this.displayWaveform.length) {
                waveform.classList.add('is-ready')
            }
        },

        barIsPlayed(index) {
            if (! this.displayWaveform?.length) {
                return false
            }

            return (index + 1) / this.displayWaveform.length <= this.progressRatio
        },
    }
}

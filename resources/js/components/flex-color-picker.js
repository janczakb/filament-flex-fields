import {
    clamp,
    formatColor,
    getDefaultColorGrid,
    hsvToHex,
    hsvToRgb,
    opacityPercent,
    parseColor,
    rgbToHsv,
} from '../support/color-math.js'

export default function flexColorPickerFormComponent({
    state,
    layout,
    format,
    alphaEnabled,
    eyedropperEnabled,
    gridColumns,
    gridRows,
    gridColors,
    readOnly,
    labels,
}) {
    return {
        state,
        layout,
        format,
        alphaEnabled,
        eyedropperEnabled,
        gridColumns,
        gridRows,
        gridColors,
        readOnly,
        labels,
        panelOpen: false,
        hsva: { h: 260, s: 65, v: 85, a: 1 },
        inputFormat: format,
        valueInput: '',
        opacityInput: '100%',
        gridPalette: [],
        eyedropperSupported: typeof window.EyeDropper !== 'undefined',
        isDraggingSaturation: false,

        init() {
            this.gridPalette = this.gridColors?.length
                ? this.gridColors
                : getDefaultColorGrid()

            this.applyState(this.state, false)

            this.$watch('state', (value) => {
                if (value !== this.serializeState()) {
                    this.applyState(value, false)
                }
            })
        },

        get availableFormats() {
            const formats = ['hex', 'rgb', 'hsl']

            if (this.alphaEnabled) {
                formats.push('rgba')
            }

            return formats
        },

        get previewColor() {
            return hsvToHex(this.hsva.h, this.hsva.s, this.hsva.v, this.hsva.a)
        },

        get solidColor() {
            return hsvToHex(this.hsva.h, this.hsva.s, this.hsva.v, 1)
        },

        get hueThumbColor() {
            return hsvToHex(this.hsva.h, 100, 100)
        },

        get showsTransparencyPattern() {
            return this.alphaEnabled && this.hsva.a < 0.999
        },

        get swatchColor() {
            return this.showsTransparencyPattern ? this.previewColor : this.solidColor
        },

        get hueGradient() {
            return 'linear-gradient(to right, #ff0000, #ffff00, #00ff00, #00ffff, #0000ff, #ff00ff, #ff0000)'
        },

        get saturationBackground() {
            return `linear-gradient(to top, #000, transparent), linear-gradient(to right, #fff, hsl(${this.hsva.h}, 100%, 50%))`
        },

        get alphaGradient() {
            const { r, g, b } = hsvToRgb(this.hsva.h, this.hsva.s, this.hsva.v)

            return `linear-gradient(to right, rgba(${r}, ${g}, ${b}, 0), rgba(${r}, ${g}, ${b}, 1))`
        },

        get saturationHandleStyle() {
            return {
                left: `${this.hsva.s}%`,
                top: `${100 - this.hsva.v}%`,
                backgroundColor: this.solidColor,
            }
        },

        get hueHandleStyle() {
            return {
                left: `${(this.hsva.h / 360) * 100}%`,
                backgroundColor: this.solidColor,
            }
        },

        get alphaHandleStyle() {
            return {
                left: `${this.hsva.a * 100}%`,
                backgroundColor: this.previewColor,
            }
        },

        closeSiblingPanels() {
            if (typeof window.Alpine?.$data !== 'function') {
                return
            }

            document.querySelectorAll('.fff-flex-color-picker').forEach((root) => {
                if (root === this.$el) {
                    return
                }

                const data = window.Alpine.$data(root)

                if (data?.panelOpen) {
                    data.panelOpen = false
                }
            })
        },

        togglePanel() {
            if (this.readOnly) {
                return
            }

            if (this.panelOpen) {
                this.panelOpen = false

                return
            }

            this.closeSiblingPanels()
            this.panelOpen = true
        },

        closePanel() {
            this.panelOpen = false
        },

        applyState(value, commit = true) {
            this.hsva = parseColor(value)
            this.inputFormat = this.detectFormat(value) ?? this.format
            this.syncInputs()

            if (commit) {
                this.commitState()
            }
        },

        detectFormat(value) {
            const input = String(value ?? '').trim()

            if (input.startsWith('rgba')) {
                return 'rgba'
            }

            if (input.startsWith('rgb')) {
                return 'rgb'
            }

            if (input.startsWith('hsla')) {
                return 'rgba'
            }

            if (input.startsWith('hsl')) {
                return 'hsl'
            }

            if (input.startsWith('#')) {
                return input.length > 7 ? 'rgba' : 'hex'
            }

            return null
        },

        syncFromHsva() {
            this.syncInputs()
            this.commitState()
        },

        syncInputs() {
            this.valueInput = formatColor(
                this.hsva.h,
                this.hsva.s,
                this.hsva.v,
                this.alphaEnabled ? this.hsva.a : 1,
                this.inputFormat === 'rgba' ? 'rgba' : this.inputFormat,
            )
            this.opacityInput = opacityPercent(this.hsva.a)
        },

        serializeState() {
            return formatColor(
                this.hsva.h,
                this.hsva.s,
                this.hsva.v,
                this.alphaEnabled ? this.hsva.a : 1,
                this.format,
            )
        },

        commitState() {
            this.state = this.serializeState()
        },

        setInputFormat(nextFormat) {
            this.inputFormat = nextFormat
            this.syncInputs()
        },

        onValueInput() {
            this.applyState(this.valueInput)
        },

        onOpacityInput() {
            const numeric = Number.parseFloat(String(this.opacityInput).replace('%', '').trim())

            if (Number.isNaN(numeric)) {
                return
            }

            this.hsva.a = clamp(numeric, 0, 100) / 100
            this.syncFromHsva()
        },

        onSaturationPointer(event) {
            if (this.readOnly) {
                return
            }

            const rect = this.$refs.saturationArea.getBoundingClientRect()
            const x = clamp(((event.clientX - rect.left) / rect.width) * 100, 0, 100)
            const y = clamp(((event.clientY - rect.top) / rect.height) * 100, 0, 100)

            this.hsva.s = x
            this.hsva.v = 100 - y
            this.syncFromHsva()
        },

        startSaturationDrag(event) {
            if (this.readOnly) {
                return
            }

            this.isDraggingSaturation = true
            this.onSaturationPointer(event)

            const onMove = (moveEvent) => this.onSaturationPointer(moveEvent)
            const onUp = () => {
                this.isDraggingSaturation = false
                window.removeEventListener('pointermove', onMove)
                window.removeEventListener('pointerup', onUp)
            }

            window.addEventListener('pointermove', onMove)
            window.addEventListener('pointerup', onUp)
        },

        onHueInput(event) {
            this.hsva.h = Number(event.target.value)
            this.syncFromHsva()
        },

        onAlphaInput(event) {
            this.hsva.a = Number(event.target.value) / 100
            this.syncFromHsva()
        },

        selectGridColor(color) {
            if (this.readOnly) {
                return
            }

            const { h, s, v, a } = parseColor(color)
            this.hsva = { h, s, v, a: this.alphaEnabled ? a : 1 }
            this.syncFromHsva()
        },

        isGridColorSelected(color) {
            return this.solidColor.toUpperCase() === String(color).toUpperCase()
        },

        async pickFromScreen() {
            if (! this.eyedropperSupported || this.readOnly) {
                return
            }

            try {
                const dropper = new window.EyeDropper()
                const result = await dropper.open()
                this.applyState(result.sRGBHex)
            } catch (error) {
                if (error?.name !== 'AbortError') {
                    console.error(error)
                }
            }
        },
    }
}

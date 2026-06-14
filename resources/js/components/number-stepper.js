import '../core/number-flow.js'

export default function numberStepperFormComponent({
    state,
    min,
    max,
    step,
    integer,
    nullable,
    disabled,
    nullLabel,
    prefix,
    suffix,
    decimalPlaces,
    wheelAnimated,
    widthAnchor,
}) {
    return {
        state,
        min,
        max,
        step,
        integer,
        nullable,
        disabled,
        nullLabel,
        prefix,
        suffix,
        decimalPlaces,
        wheelAnimated,
        widthAnchor,
        flowReady: false,
        flow: null,

        pickWidest(...texts) {
            return texts.reduce(
                (widest, text) => (String(text).length > String(widest).length ? text : widest),
                '',
            )
        },

        formatBucketedDisplay(value) {
            const numeric = Math.abs(Number(value))

            if (Number.isNaN(numeric)) {
                return this.formatBucketedDisplay(88)
            }

            const intPart = String(Math.floor(numeric))
            const bucketSize = intPart.length <= 2 ? 2 : intPart.length
            let formatted = '8'.repeat(bucketSize)

            if (this.decimalPlaces !== null) {
                formatted += '.' + '8'.repeat(this.decimalPlaces)
            }

            if (this.prefix) {
                formatted = this.prefix + formatted
            }

            return formatted
        },

        bucketDisplayText(displayText) {
            if (displayText === this.nullLabel) {
                return displayText
            }

            if (! this.hasValue && this.nullable) {
                return this.nullLabel
            }

            const numeric = this.numericState

            if (numeric === null) {
                return displayText
            }

            return this.formatBucketedDisplay(numeric)
        },

        buildCurrentSizerText() {
            if (! this.hasValue) {
                return this.nullLabel
            }

            const bucketedMain = this.bucketDisplayText(this.formatMain(this.numericState))
            const displayMain = this.formatMain(this.numericState)

            const bucketedText = this.suffix
                ? `${bucketedMain}\u00a0${this.suffix}`
                : bucketedMain

            const displayText = this.suffix
                ? `${displayMain}\u00a0${this.suffix}`
                : displayMain

            return this.pickWidest(bucketedText, displayText)
        },

        normalize(value) {
            if (value === null || value === '') {
                return null
            }

            const numeric = Number(value)

            if (Number.isNaN(numeric)) {
                return null
            }

            return this.integer ? Math.round(numeric) : numeric
        },

        get numericState() {
            return this.normalize(this.state)
        },

        get valueSizerText() {
            return this.pickWidest(this.widthAnchor, this.buildCurrentSizerText())
        },

        get hasValue() {
            return this.numericState !== null
        },

        formatMain(value) {
            if (value === null || value === undefined) {
                return this.nullLabel
            }

            let numeric = Number(value)

            if (Number.isNaN(numeric)) {
                return this.nullLabel
            }

            if (this.integer) {
                numeric = Math.round(numeric)
            }

            let formatted = this.decimalPlaces === null
                ? String(numeric)
                : numeric.toFixed(this.decimalPlaces)

            if (this.prefix) {
                formatted = this.prefix + formatted
            }

            return formatted
        },

        buildFlowFormat() {
            if (this.decimalPlaces !== null) {
                return {
                    minimumFractionDigits: this.decimalPlaces,
                    maximumFractionDigits: this.decimalPlaces,
                }
            }

            if (this.integer) {
                return { maximumFractionDigits: 0 }
            }

            return {}
        },

        configureFlow() {
            if (! this.flow) {
                return
            }

            this.flow.format = this.buildFlowFormat()
            this.flow.numberPrefix = this.prefix || ''
            this.flow.numberSuffix = this.suffix ? `\u00a0${this.suffix}` : ''
            this.flow.animated = this.wheelAnimated
        },

        updateFlow(animate = true) {
            if (! this.flow || ! this.hasValue) {
                return
            }

            this.configureFlow()

            if (! animate) {
                this.flow.animated = false
            }

            this.flow.update(this.numericState)

            if (! animate) {
                this.flow.animated = this.wheelAnimated
            }
        },

        applyState(nextValue) {
            this.state = nextValue

            this.$nextTick(() => {
                this.updateFlow()
            })
        },

        get canDecrement() {
            if (this.disabled) {
                return false
            }

            if (! this.hasValue) {
                return false
            }

            if (this.min === null) {
                return this.nullable || this.numericState > 0
            }

            if (this.nullable && this.numericState <= this.min) {
                return true
            }

            return this.numericState > this.min
        },

        get canIncrement() {
            if (this.disabled) {
                return false
            }

            if (! this.hasValue) {
                return true
            }

            if (this.max === null) {
                return true
            }

            return this.numericState < this.max
        },

        decrement() {
            if (! this.canDecrement) {
                return
            }

            if (! this.hasValue) {
                return
            }

            if (this.nullable && this.min !== null && this.numericState <= this.min) {
                this.applyState(null)

                return
            }

            let next = this.numericState - this.step

            if (this.min !== null) {
                next = Math.max(next, this.min)
            }

            next = this.integer ? Math.round(next) : next

            this.applyState(next)
        },

        increment() {
            if (! this.canIncrement) {
                return
            }

            let next = this.hasValue
                ? this.numericState + this.step
                : (this.min ?? this.step)

            if (this.max !== null) {
                next = Math.min(next, this.max)
            }

            next = this.integer ? Math.round(next) : next

            this.applyState(next)
        },

        setupFlow() {
            this.flow = this.$refs.numberFlow

            this.$nextTick(() => {
                this.updateFlow(false)

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        this.flowReady = true
                    })
                })
            })

            this.$watch('state', () => {
                this.updateFlow(this.wheelAnimated)
            })
        },

        init() {
            const start = () => this.$nextTick(() => this.setupFlow())

            if (customElements.get('number-flow')) {
                start()
            } else {
                customElements.whenDefined('number-flow').then(start)
            }
        },
    }
}

import {
    getCalculatorPanelState,
    openCalculatorPanel,
    registerCalculatorField,
    seedExpressionFromField,
} from '../core/calculator-coordinator.js'
import { createCalculatorPanelBehavior } from '../core/calculator-panel-behavior.js'
import {
    normalizeNumericFieldValue,
    sanitizeNumericInput,
} from '../core/numeric-field-normalizer.js'

const PANEL_HOST_SELECTOR = '[data-fff-calculator-panel-host]'

export default function calculatorFieldFormComponent({
    fieldId,
    state,
    label,
    disabled,
    readOnly,
    min,
    max,
    step,
    integer,
    decimalPlaces,
    maxLength,
    roundingMode,
    calculatorLabel,
    panelLabels,
}) {
    const panelBehavior = panelLabels ? createCalculatorPanelBehavior(panelLabels) : null

    const numericOptions = () => ({
        integer,
        decimalPlaces,
        roundingMode: roundingMode ?? 'truncate',
        min,
        max,
        step,
    })

    return {
        fieldId,
        state,
        label,
        disabled,
        readOnly,
        min,
        max,
        step,
        integer,
        decimalPlaces,
        maxLength,
        roundingMode: roundingMode ?? 'truncate',
        calculatorLabel,
        inputValue: '',
        unregister: null,
        isPanelTarget: false,
        isCalculatorPanelHost: false,
        ...(panelBehavior ?? {}),

        init() {
            this.isCalculatorPanelHost = this.$el?.querySelector(PANEL_HOST_SELECTOR) !== null

            this.syncInputFromState()
            this.unregister = registerCalculatorField(this.fieldId, {
                getLabel: () => this.label,
                getInputValue: () => this.inputValue,
                getDecimalPlaces: () => this.decimalPlaces,
                getNumericOptions: () => numericOptions(),
                getAnchorElement: () => this.$refs.calculatorTrigger,
                applyValue: (value) => this.applyValue(value),
                onPanelOpen: ({ switchedField }) => {
                    this.isPanelTarget = true

                    if (! switchedField) {
                        seedExpressionFromField(this.fieldId)
                    }
                },
                onPanelClose: () => {
                    this.isPanelTarget = false
                },
            })

            if (this.isCalculatorPanelHost && panelBehavior) {
                panelBehavior.initCalculatorPanel.call(this)
            }

            this.$watch('state', () => this.syncInputFromState())
        },

        destroy() {
            if (this.isCalculatorPanelHost && panelBehavior) {
                panelBehavior.destroyCalculatorPanel.call(this)
            }

            this.unregister?.()
        },

        syncInputFromState() {
            if (this.state === null || this.state === undefined || this.state === '') {
                this.inputValue = ''

                return
            }

            const normalized = normalizeNumericFieldValue(this.state, numericOptions())

            this.inputValue = normalized?.display ?? String(this.state)
        },

        onInput(event) {
            const sanitized = sanitizeNumericInput(event.target.value, {
                maxLength: this.maxLength,
            })

            this.inputValue = sanitized
            event.target.value = sanitized
            this.commitState()
        },

        commitState() {
            const normalized = normalizeNumericFieldValue(this.inputValue, numericOptions())

            if (normalized === null || normalized.value === null) {
                if (String(this.inputValue ?? '').trim() === '') {
                    this.state = null
                }

                return
            }

            this.state = normalized.value
        },

        applyValue(value) {
            const normalized = normalizeNumericFieldValue(value, numericOptions())

            if (normalized === null || normalized.value === null) {
                this.inputValue = ''
                this.state = null

                return
            }

            this.inputValue = normalized.display
            this.state = normalized.value
        },

        openCalculator() {
            if (this.disabled || this.readOnly) {
                return
            }

            seedExpressionFromField(this.fieldId)
            openCalculatorPanel(this.fieldId, this.$refs.calculatorTrigger)
        },

        onInputFocus() {
            if (this.disabled || this.readOnly) {
                return
            }

            const { isOpen, activeFieldId } = getCalculatorPanelState()

            if (! isOpen || activeFieldId === this.fieldId) {
                return
            }

            seedExpressionFromField(this.fieldId)
            openCalculatorPanel(this.fieldId, this.$refs.calculatorTrigger)
        },

        get isLocked() {
            return this.disabled || this.readOnly
        },
    }
}

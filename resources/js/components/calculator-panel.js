import { createCalculatorPanelBehavior } from '../core/calculator-panel-behavior.js'

export { createCalculatorPanelBehavior }

export default function calculatorPanelComponent(labels) {
    const behavior = createCalculatorPanelBehavior(labels)

    return {
        ...behavior,
        init() {
            behavior.initCalculatorPanel.call(this)
        },
        destroy() {
            behavior.destroyCalculatorPanel.call(this)
        },
    }
}

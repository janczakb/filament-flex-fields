import {
    applyCalculatorResult,
    CALCULATOR_MOBILE_QUERY,
    closeCalculatorPanel,
    getActiveFieldApi,
    getCalculatorPanelState,
    getFieldSession,
    setCalculatorPanelPosition,
    subscribeCalculatorPanel,
    updateFieldSession,
} from './calculator-coordinator.js'
import {
    applyPercentToExpression,
    appendCalculatorToken,
    computeCalculatorDisplay,
    toggleSignOnExpression,
} from './calculator-engine.js'
import { getCalculatorKeyIconSvg } from './calculator-keypad-icons.js'
import {
    applyCalculatorPanelTheme,
    watchCalculatorPanelTheme,
} from './calculator-panel-theme.js'
import { resolveCalculatorPanelZIndex } from './theme-utils.js'

const KEYPAD = [
    [
        { key: '⌫', type: 'function', icon: 'delete' },
        { key: 'AC', type: 'function' },
        { key: '%', type: 'function', icon: 'percent' },
        { key: '÷', type: 'operator', icon: 'divide' },
    ],
    [
        { key: '7', type: 'digit' },
        { key: '8', type: 'digit' },
        { key: '9', type: 'digit' },
        { key: '×', type: 'operator', icon: 'multiply' },
    ],
    [
        { key: '4', type: 'digit' },
        { key: '5', type: 'digit' },
        { key: '6', type: 'digit' },
        { key: '−', type: 'operator', icon: 'minus' },
    ],
    [
        { key: '1', type: 'digit' },
        { key: '2', type: 'digit' },
        { key: '3', type: 'digit' },
        { key: '+', type: 'operator', icon: 'plus' },
    ],
    [
        { key: '±', type: 'function', icon: 'plusMinus' },
        { key: '0', type: 'digit' },
        { key: '.', type: 'digit' },
        { key: '=', type: 'operator', icon: 'equal' },
    ],
]

function mapKeyLabel(label) {
    switch (label) {
        case '÷':
            return '/'
        case '×':
            return '*'
        case '−':
            return '-'
        case '⌫':
            return 'backspace'
        case 'AC':
            return 'clear'
        case '±':
            return 'toggleSign'
        case '%':
            return 'percent'
        default:
            return label
    }
}

export function createCalculatorPanelBehavior({
    applyLabel,
    closeLabel,
    panelTitle,
}) {
    return {
        applyLabel,
        closeLabel,
        panelTitle,
        isOpen: false,
        isVisible: false,
        isMobile: false,
        isDragging: false,
        isAnimating: false,
        isSwitchingContext: false,
        panelReady: false,
        activeFieldId: null,
        activeLabel: '',
        expression: '',
        displayResult: '',
        livePreview: null,
        panelX: null,
        panelY: null,
        dragOffsetX: 0,
        dragOffsetY: 0,
        panelUnsubscribe: null,
        mobileMedia: null,
        contextSwitchTimer: null,
        themeObserverDisconnect: null,

        initCalculatorPanel() {
            this.onDragMove = this.onDragMove.bind(this)
            this.onDragEnd = this.onDragEnd.bind(this)
            this.onWindowKeydown = this.onWindowKeydown.bind(this)

            this.mobileMedia = window.matchMedia(CALCULATOR_MOBILE_QUERY)
            this.isMobile = this.mobileMedia.matches
            this.mobileMedia.addEventListener('change', () => {
                this.isMobile = this.mobileMedia.matches
                this.syncFromCoordinator()
            })

            this.panelUnsubscribe = subscribeCalculatorPanel(() => this.syncFromCoordinator())
            this.syncFromCoordinator()

            window.addEventListener('keydown', this.onWindowKeydown)

            this.$nextTick(() => {
                this.withPanelRef((panel) => {
                    applyCalculatorPanelTheme(panel)
                    this.applyPanelStackingOrder(panel)
                    this.themeObserverDisconnect = watchCalculatorPanelTheme(panel, () => {})
                })
            })
        },

        destroyCalculatorPanel() {
            this.panelUnsubscribe?.()
            this.themeObserverDisconnect?.()
            window.clearTimeout(this.contextSwitchTimer)
            window.removeEventListener('keydown', this.onWindowKeydown)
        },

        onWindowKeydown(event) {
            if (! this.isOpen) {
                return
            }

            const target = event.target
            const tag = target?.tagName?.toLowerCase()

            if (tag === 'textarea' || target?.isContentEditable) {
                return
            }

            if (event.key === 'Escape') {
                event.preventDefault()
                this.close()

                return
            }

            if (event.key === 'Enter' || event.key === '=') {
                event.preventDefault()
                this.appendToken('=')

                return
            }

            if (event.key === 'Backspace') {
                event.preventDefault()
                this.appendToken('⌫')

                return
            }

            if (event.key === 'Delete') {
                event.preventDefault()
                this.appendToken('AC')

                return
            }

            if (/^[0-9.]$/.test(event.key)) {
                event.preventDefault()
                this.appendToken(event.key)

                return
            }

            const operatorKeys = {
                '+': '+',
                '-': '−',
                '*': '×',
                '/': '÷',
                '%': '%',
            }

            if (operatorKeys[event.key]) {
                event.preventDefault()
                this.appendToken(operatorKeys[event.key])
            }
        },

        syncFromCoordinator() {
            const state = getCalculatorPanelState()
            const wasOpen = this.isOpen
            const previousFieldId = this.activeFieldId

            this.isOpen = state.isOpen
            this.activeFieldId = state.activeFieldId

            if (state.isOpen && state.activeFieldId) {
                const session = getFieldSession(state.activeFieldId)
                const api = getActiveFieldApi()
                const switchedField = wasOpen && previousFieldId !== state.activeFieldId

                this.activeLabel = session.label || api?.getLabel?.() || ''

                if (switchedField && previousFieldId) {
                    updateFieldSession(previousFieldId, {
                        expression: this.expression,
                        result: this.displayResult !== ''
                            ? this.displayResult
                            : (this.livePreview ?? null),
                    })
                }

                if (switchedField || this.expression !== (session.expression ?? '')) {
                    this.loadSessionExpression(session.expression ?? '', { animate: switchedField })
                }

                if (switchedField && ! this.isMobile) {
                    this.panelX = null
                    this.panelY = null
                    this.positionNearActiveAnchor()
                    this.animateContextSwitch()
                } else if (state.panelPosition) {
                    this.panelX = state.panelPosition.x
                    this.panelY = state.panelPosition.y
                }

                if (! wasOpen) {
                    this.openWithAnimation()
                }
            } else if (wasOpen) {
                this.closeWithAnimation()
            }
        },

        loadSessionExpression(expression, { animate = false } = {}) {
            this.expression = expression
            this.recalculate()

            if (animate) {
                this.animateContextSwitch()
            }
        },

        animateContextSwitch() {
            this.isSwitchingContext = true
            window.clearTimeout(this.contextSwitchTimer)
            this.contextSwitchTimer = window.setTimeout(() => {
                this.isSwitchingContext = false
            }, 320)
        },

        openWithAnimation() {
            this.isVisible = true
            this.isAnimating = true
            this.panelReady = false
            this.ensurePanelPosition()

            const finishOpen = (panel) => {
                if (panel) {
                    applyCalculatorPanelTheme(panel)
                    this.applyPanelStackingOrder(panel)
                }

                requestAnimationFrame(() => {
                    this.panelReady = true
                    this.isAnimating = false
                })
            }

            if (this.isMobile) {
                this.$nextTick(() => {
                    this.withPanelRef((panel) => finishOpen(panel))
                })

                return
            }

            this.$nextTick(() => {
                this.$nextTick(() => {
                    this.withPanelRef((panel) => finishOpen(panel))
                })
            })
        },

        ensurePanelPosition() {
            if (this.isMobile) {
                return
            }

            if (this.panelX !== null && this.panelY !== null) {
                return
            }

            this.positionNearActiveAnchor()

            if (this.panelX === null || this.panelY === null) {
                const panelWidth = 264
                const panelHeight = 400

                this.panelX = Math.max(12, window.innerWidth - panelWidth - 24)
                this.panelY = Math.max(12, Math.round((window.innerHeight - panelHeight) / 2))
                setCalculatorPanelPosition({ x: this.panelX, y: this.panelY })
            }
        },

        resolvePanelElement() {
            return this.$refs.panel ?? document.querySelector('.fff-calculator-panel')
        },

        applyPanelStackingOrder(panel) {
            const zIndex = String(resolveCalculatorPanelZIndex())

            panel.style.zIndex = zIndex

            const portal = panel.closest('.fff-calculator-panel-portal')

            if (portal) {
                portal.style.zIndex = zIndex
            }

            const backdrop = portal?.querySelector('.fff-calculator-panel__backdrop')

            if (backdrop) {
                backdrop.style.zIndex = String(Number(zIndex) - 1)
            }
        },

        withPanelRef(callback, attempts = 24) {
            const panel = this.resolvePanelElement()

            if (panel) {
                callback(panel)

                return
            }

            if (attempts <= 0) {
                this.panelReady = true
                this.isAnimating = false

                return
            }

            requestAnimationFrame(() => this.withPanelRef(callback, attempts - 1))
        },

        closeWithAnimation() {
            this.isAnimating = true
            this.panelReady = false

            window.setTimeout(() => {
                this.isVisible = false
                this.isAnimating = false
            }, this.isMobile ? 280 : 360)
        },

        positionNearActiveAnchor() {
            const api = getActiveFieldApi()
            const anchor = api?.getAnchorElement?.()

            if (! anchor) {
                return
            }

            const rect = anchor.getBoundingClientRect()
            const panelWidth = 264
            const panelHeight = 400
            const gap = 12

            let x = rect.right - panelWidth
            let y = rect.bottom + gap

            if (x < 12) {
                x = 12
            }

            if (x + panelWidth > window.innerWidth - 12) {
                x = window.innerWidth - panelWidth - 12
            }

            if (y + panelHeight > window.innerHeight - 12) {
                y = rect.top - panelHeight - gap
            }

            if (y < 12) {
                y = 12
            }

            this.panelX = x
            this.panelY = y
            setCalculatorPanelPosition({ x, y })
        },

        recalculate() {
            const { result, preview } = computeCalculatorDisplay(this.expression)

            this.displayResult = result ?? ''
            this.livePreview = preview

            if (this.activeFieldId) {
                updateFieldSession(this.activeFieldId, {
                    expression: this.expression,
                    result: result ?? preview,
                })
            }
        },

        primaryDisplayValue() {
            if (this.displayResult !== '') {
                return this.displayResult
            }

            if (this.livePreview !== null && this.livePreview !== '') {
                return this.livePreview
            }

            if (this.expression !== '') {
                return this.expression
            }

            return '0'
        },

        secondaryDisplayValue() {
            if (this.expression === '') {
                return ''
            }

            if (this.displayResult !== '' && ! this.livePreview) {
                return this.expression
            }

            return this.expression
        },

        showLivePreview() {
            return this.livePreview !== null
                && this.livePreview !== ''
                && this.displayResult === ''
        },

        appendToken(label) {
            const token = mapKeyLabel(label)

            if (token === '=') {
                this.evaluateEquals()

                return
            }

            if (token === 'clear') {
                this.expression = ''
                this.recalculate()

                return
            }

            if (token === 'backspace') {
                this.expression = this.expression.slice(0, -1)
                this.recalculate()

                return
            }

            if (token === 'toggleSign') {
                this.expression = toggleSignOnExpression(this.expression)
                this.recalculate()

                return
            }

            if (token === 'percent') {
                this.expression = applyPercentToExpression(this.expression)
                this.recalculate()

                return
            }

            this.expression = appendCalculatorToken(this.expression, token)
            this.recalculate()
        },

        evaluateEquals() {
            if (this.displayResult !== '' && this.displayResult !== null) {
                this.expression = String(this.displayResult)
                this.recalculate()
            }
        },

        apply() {
            const value = this.displayResult !== ''
                ? this.displayResult
                : this.livePreview

            if (! this.activeFieldId || value === null || value === '') {
                return
            }

            updateFieldSession(this.activeFieldId, {
                expression: this.expression,
                result: value,
            })

            applyCalculatorResult(this.activeFieldId)
        },

        close() {
            closeCalculatorPanel()
        },

        startDrag(event) {
            if (this.isMobile) {
                return
            }

            const panel = this.resolvePanelElement()

            if (! panel) {
                return
            }

            this.isDragging = true
            const rect = panel.getBoundingClientRect()

            this.dragOffsetX = event.clientX - rect.left
            this.dragOffsetY = event.clientY - rect.top

            window.addEventListener('pointermove', this.onDragMove)
            window.addEventListener('pointerup', this.onDragEnd)
        },

        onDragMove(event) {
            if (! this.isDragging) {
                return
            }

            this.panelX = Math.max(8, Math.min(window.innerWidth - 272, event.clientX - this.dragOffsetX))
            this.panelY = Math.max(8, Math.min(window.innerHeight - 408, event.clientY - this.dragOffsetY))
            setCalculatorPanelPosition({ x: this.panelX, y: this.panelY })
        },

        onDragEnd() {
            this.isDragging = false
            window.removeEventListener('pointermove', this.onDragMove)
            window.removeEventListener('pointerup', this.onDragEnd)
        },

        keyClasses(cell) {
            return {
                'is-digit': cell.type === 'digit',
                'is-operator': cell.type === 'operator',
                'is-function': cell.type === 'function',
                'is-wide': cell.span > 1,
                'has-icon': Boolean(cell.icon),
            }
        },

        keyIconSvg(cell) {
            if (! cell.icon) {
                return ''
            }

            return getCalculatorKeyIconSvg(cell.icon) ?? ''
        },

        keyAriaLabel(cell) {
            if (cell.icon === 'delete') {
                return 'Backspace'
            }

            if (cell.icon === 'plusMinus') {
                return 'Toggle sign'
            }

            return cell.key
        },

        closeIconSvg() {
            return getCalculatorKeyIconSvg('xmark') ?? ''
        },

        panelClasses() {
            return {
                'is-visible': this.isVisible,
                'is-open': this.isVisible && this.isOpen && this.panelReady,
                'is-closing': this.isAnimating && ! this.isOpen,
                'is-mobile': this.isMobile,
                'is-dragging': this.isDragging,
                'is-switching-context': this.isSwitchingContext,
            }
        },

        panelStyle() {
            if (this.isMobile) {
                return {}
            }

            if (this.panelX === null || this.panelY === null) {
                return {}
            }

            return {
                left: `${this.panelX}px`,
                top: `${this.panelY}px`,
            }
        },

        keypadRows: KEYPAD,
    }
}

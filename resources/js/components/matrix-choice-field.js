import { createMatrixKeyboardController } from '../core/matrix-keyboard.js'

export default function matrixChoiceFieldFormComponent({
    state,
    mode,
    rowKeys,
    columnKeys,
    disabledRows,
    disabledCells,
    conditionalDisableRules,
    disabledColumns,
    disabled,
}) {
    return {
        state,
        mode,
        rowKeys,
        columnKeys,
        disabledRows,
        disabledCells,
        conditionalDisableRules,
        disabledColumns,
        disabled,
        matrixKeyboard: null,

        init() {
            this.ensureState()
            this.pruneDisabledSelections()
            this.$watch('state', () => this.pruneDisabledSelections())

            this.matrixKeyboard = createMatrixKeyboardController({
                rows: this.rowKeys.length,
                cols: this.columnKeys.length,
                onNavigate: (focus) => this.focusCell(focus.row, focus.col),
                onToggle: (focus) => {
                    const rowKey = this.rowKeys[focus.row]
                    const columnKey = this.columnKeys[focus.col]

                    if (rowKey === undefined || columnKey === undefined) {
                        return
                    }

                    this.interact(rowKey, columnKey)
                },
            })
        },

        normalize(value) {
            return String(value)
        },

        ensureState() {
            const next = { ...(this.state ?? {}) }

            this.rowKeys.forEach((rowKey) => {
                if (! (rowKey in next)) {
                    next[rowKey] = this.mode === 'checkbox' ? [] : null
                }
            })

            this.state = next
        },

        rowSelection(rowKey) {
            const value = (this.state ?? {})[this.normalize(rowKey)]

            if (this.mode === 'checkbox') {
                return Array.isArray(value)
                    ? value.map((item) => this.normalize(item))
                    : []
            }

            if (value === null || value === undefined || value === '') {
                return null
            }

            return this.normalize(value)
        },

        matchesConditionalRule(rule) {
            const whenSelection = this.rowSelection(rule.when_row)
            const whenColumns = (rule.when_columns ?? []).map((column) => this.normalize(column))

            if (whenColumns.length === 0) {
                return false
            }

            if (this.mode === 'checkbox') {
                return Array.isArray(whenSelection)
                    && whenColumns.some((column) => whenSelection.includes(column))
            }

            return whenSelection !== null && whenColumns.includes(whenSelection)
        },

        isRowConditionallyDisabled(rowKey) {
            const row = this.normalize(rowKey)

            return this.conditionalDisableRules.some((rule) => {
                return rule.type === 'row'
                    && this.normalize(rule.row) === row
                    && this.matchesConditionalRule(rule)
            })
        },

        isCellConditionallyDisabled(rowKey, columnKey) {
            const row = this.normalize(rowKey)
            const column = this.normalize(columnKey)

            return this.conditionalDisableRules.some((rule) => {
                return rule.type === 'cell'
                    && this.normalize(rule.row) === row
                    && this.normalize(rule.column) === column
                    && this.matchesConditionalRule(rule)
            })
        },

        isRowDisabled(rowKey) {
            return this.disabled
                || (this.disabledRows[this.normalize(rowKey)] ?? false)
                || this.isRowConditionallyDisabled(rowKey)
        },

        isColumnDisabled(columnKey) {
            return this.disabledColumns[this.normalize(columnKey)] ?? false
        },

        isCellDisabled(rowKey, columnKey) {
            if (this.isRowDisabled(rowKey) || this.isColumnDisabled(columnKey)) {
                return true
            }

            const cells = this.disabledCells[this.normalize(rowKey)] ?? []

            if (cells.includes(this.normalize(columnKey))) {
                return true
            }

            return this.isCellConditionallyDisabled(rowKey, columnKey)
        },

        pruneDisabledSelections() {
            const next = { ...(this.state ?? {}) }
            let changed = false

            this.rowKeys.forEach((rowKey) => {
                const row = this.normalize(rowKey)

                if (this.isRowDisabled(row)) {
                    const current = next[row]

                    if (this.mode === 'checkbox') {
                        if (Array.isArray(current) && current.length > 0) {
                            next[row] = []
                            changed = true
                        }

                        return
                    }

                    if (current !== null && current !== undefined && current !== '') {
                        next[row] = null
                        changed = true
                    }

                    return
                }

                if (this.mode === 'checkbox') {
                    const current = Array.isArray(next[row]) ? [...next[row]] : []
                    const filtered = current.filter((columnKey) => ! this.isCellDisabled(row, columnKey))

                    if (filtered.length !== current.length) {
                        next[row] = filtered
                        changed = true
                    }

                    return
                }

                if (this.isCellDisabled(row, next[row])) {
                    next[row] = null
                    changed = true
                }
            })

            if (changed) {
                this.state = next
            }
        },

        isSelected(rowKey, columnKey) {
            const row = this.normalize(rowKey)
            const column = this.normalize(columnKey)
            const selection = this.rowSelection(row)

            if (this.mode === 'checkbox') {
                return Array.isArray(selection) && selection.includes(column)
            }

            return selection === column
        },

        selectRadio(rowKey, columnKey) {
            if (this.isCellDisabled(rowKey, columnKey)) {
                return
            }

            this.ensureState()
            this.state = {
                ...this.state,
                [this.normalize(rowKey)]: this.normalize(columnKey),
            }
        },

        toggleCheckbox(rowKey, columnKey) {
            if (this.isCellDisabled(rowKey, columnKey)) {
                return
            }

            this.ensureState()

            const row = this.normalize(rowKey)
            const column = this.normalize(columnKey)
            const current = Array.isArray(this.state[row]) ? [...this.state[row]] : []
            const index = current.indexOf(column)

            if (index >= 0) {
                current.splice(index, 1)
            } else {
                current.push(column)
            }

            this.state = {
                ...this.state,
                [row]: current,
            }
        },

        interact(rowKey, columnKey) {
            if (this.mode === 'checkbox') {
                this.toggleCheckbox(rowKey, columnKey)
            } else {
                this.selectRadio(rowKey, columnKey)
            }

            this.pruneDisabledSelections()
        },

        focusCell(row, col) {
            this.$nextTick(() => {
                const cell = this.$el.querySelector(
                    `[data-matrix-row="${row}"][data-matrix-col="${col}"]`,
                )

                cell?.focus?.()
            })
        },

        syncKeyboardFocusFromEvent(event) {
            const cell = event?.target?.closest?.('[data-matrix-row][data-matrix-col]')

            if (! cell || ! this.matrixKeyboard) {
                return
            }

            this.matrixKeyboard.setFocus({
                row: Number(cell.dataset.matrixRow),
                col: Number(cell.dataset.matrixCol),
            })
        },

        onMatrixKeydown(event) {
            if (this.disabled || ! this.matrixKeyboard) {
                return
            }

            this.syncKeyboardFocusFromEvent(event)
            this.matrixKeyboard.handleKeydown(event)
        },

        onCellSpaceKeydown(event, rowKey, columnKey) {
            if (event.shiftKey) {
                return
            }

            event.preventDefault()
            this.interact(rowKey, columnKey)
        },
    }
}

import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'

export function normalizeLocale(locale) {
    if (typeof locale !== 'string' || locale.trim() === '') {
        return 'en-US'
    }

    return locale.trim().replace(/_/g, '-')
}

export function getLocaleSeparators(locale) {
    const normalizedLocale = normalizeLocale(locale)

    try {
        const parts = new Intl.NumberFormat(normalizedLocale).formatToParts(1234567.89)

        return {
            decimal: parts.find((part) => part.type === 'decimal')?.value ?? '.',
            group: parts.find((part) => part.type === 'group')?.value ?? ' ',
        }
    } catch {
        const parts = new Intl.NumberFormat('en-US').formatToParts(1234567.89)

        return {
            decimal: parts.find((part) => part.type === 'decimal')?.value ?? '.',
            group: parts.find((part) => part.type === 'group')?.value ?? ',',
        }
    }
}

export function groupWholeDigits(wholeDigits, groupSep) {
    const digits = String(wholeDigits ?? '').replace(/\D/g, '')

    if (digits === '') {
        return ''
    }

    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, groupSep)
}

export function editStateFromMinor(minor, decimals) {
    if (minor === null || minor === undefined || minor === '') {
        return { wholeDigits: '', fracDigits: '', inDecimal: false, negative: false }
    }

    let value = Math.abs(parseInt(minor, 10))

    if (Number.isNaN(value)) {
        return { wholeDigits: '', fracDigits: '', inDecimal: false, negative: false }
    }

    if (decimals === 0) {
        return {
            wholeDigits: String(value),
            fracDigits: '',
            inDecimal: false,
            negative: minor < 0,
        }
    }

    const factor = 10 ** decimals
    const whole = Math.floor(value / factor)
    const fracValue = value % factor
    const fracDigits = fracValue === 0 ? '' : String(fracValue).padStart(decimals, '0').replace(/0+$/, '')

    return {
        wholeDigits: whole === 0 && fracDigits !== '' ? '0' : String(whole),
        fracDigits,
        inDecimal: false,
        negative: minor < 0,
    }
}

export function minorFromEditState({ wholeDigits, fracDigits, decimals, negative }) {
    const whole = String(wholeDigits ?? '').replace(/\D/g, '')
    const frac = String(fracDigits ?? '').replace(/\D/g, '')

    if (whole === '' && frac === '') {
        return null
    }

    const paddedFrac = decimals === 0 ? '' : frac.padEnd(decimals, '0').slice(0, decimals)
    const combined = decimals === 0 ? (whole || '0') : `${whole || '0'}${paddedFrac}`
    const numeric = parseInt(combined, 10)

    if (Number.isNaN(numeric)) {
        return null
    }

    return negative ? -numeric : numeric
}

export function hasDecimalSection(edit, decimals) {
    return decimals > 0 && (edit.inDecimal || edit.fracDigits !== '')
}

export function getLogicalLength(edit, decimals) {
    const wholeLen = edit.wholeDigits.length

    if (! hasDecimalSection(edit, decimals)) {
        return wholeLen
    }

    return wholeLen + 1 + edit.fracDigits.length
}

export function getNavigableCursorPositions(edit, decimals) {
    const wholeLen = edit.wholeDigits.length
    const positions = []

    for (let index = 0; index <= wholeLen; index += 1) {
        positions.push(index)
    }

    if (! hasDecimalSection(edit, decimals)) {
        return positions
    }

    for (let index = 1; index <= edit.fracDigits.length + 1; index += 1) {
        positions.push(wholeLen + index)
    }

    return positions
}

export function snapCursor(cursorPos, edit, decimals) {
    const positions = getNavigableCursorPositions(edit, decimals)

    if (positions.length === 0) {
        return 0
    }

    if (positions.includes(cursorPos)) {
        return cursorPos
    }

    return positions.reduce((closest, position) => {
        return Math.abs(position - cursorPos) < Math.abs(closest - cursorPos)
            ? position
            : closest
    }, positions[0])
}

export function moveCursorByDelta(cursorPos, delta, edit, decimals) {
    const positions = getNavigableCursorPositions(edit, decimals)

    if (positions.length === 0) {
        return 0
    }

    const currentIndex = Math.max(0, positions.indexOf(snapCursor(cursorPos, edit, decimals)))
    const nextIndex = Math.max(0, Math.min(currentIndex + delta, positions.length - 1))

    return positions[nextIndex]
}

/**
 * @param {{ wholeDigits: string, fracDigits: string, inDecimal: boolean, negative: boolean }} edit
 * @return {{ wholeDigits: string, fracDigits: string, inDecimal: boolean, negative: boolean }}
 */
export function applyTypeDigit(edit, digit, decimals) {
    const cursorPos = getLogicalLength(edit, decimals)

    return applyTypeDigitAtCursor(edit, digit, cursorPos, decimals).edit
}

export function animatedDigitKeyForWholeInsert(beforeWhole, afterWhole, insertPos, digit) {
    const unstripped = `${beforeWhole.slice(0, insertPos)}${digit}${beforeWhole.slice(insertPos)}`

    if (unstripped === afterWhole) {
        return `w-${insertPos}`
    }

    const stripped = unstripped.replace(/^0+(?=\d)/, '')

    if (stripped === afterWhole) {
        const removed = unstripped.length - stripped.length

        return `w-${Math.max(0, insertPos - removed)}`
    }

    return `w-${insertPos}`
}

export function applyTypeDigitAtCursor(edit, digit, cursorPos, decimals) {
    const next = { ...edit }
    const wholeLen = next.wholeDigits.length
    const hasDecimal = hasDecimalSection(next, decimals)
    const pos = snapCursor(cursorPos, next, decimals)
    const wholeBefore = next.wholeDigits

    if (decimals === 0) {
        const before = next.wholeDigits.slice(0, pos)
        const after = next.wholeDigits.slice(pos)
        next.wholeDigits = `${before}${digit}${after}`.replace(/^0+(?=\d)/, '')

        return {
            edit: next,
            cursorPos: Math.min(pos + 1, next.wholeDigits.length),
            animatedDigitKey: animatedDigitKeyForWholeInsert(wholeBefore, next.wholeDigits, pos, digit),
        }
    }

    if (pos < wholeLen) {
        const before = next.wholeDigits.slice(0, pos)
        const after = next.wholeDigits.slice(pos)
        next.wholeDigits = `${before}${digit}${after}`

        if (next.wholeDigits.length > 1) {
            next.wholeDigits = next.wholeDigits.replace(/^0+(?=\d)/, '')
        }

        return {
            edit: next,
            cursorPos: pos + 1,
            animatedDigitKey: animatedDigitKeyForWholeInsert(wholeBefore, next.wholeDigits, pos, digit),
        }
    }

    if (pos === wholeLen && ! hasDecimal) {
        next.wholeDigits = next.wholeDigits === '0'
            ? digit
            : `${next.wholeDigits}${digit}`

        if (next.wholeDigits.length > 1) {
            next.wholeDigits = next.wholeDigits.replace(/^0+(?=\d)/, '')
        }

        return {
            edit: next,
            cursorPos: pos + 1,
            animatedDigitKey: animatedDigitKeyForWholeInsert(wholeBefore, next.wholeDigits, pos, digit),
        }
    }

    if (pos === wholeLen && hasDecimal) {
        next.wholeDigits = `${next.wholeDigits}${digit}`

        if (next.wholeDigits.length > 1) {
            next.wholeDigits = next.wholeDigits.replace(/^0+(?=\d)/, '')
        }

        return {
            edit: next,
            cursorPos: pos + 1,
            animatedDigitKey: animatedDigitKeyForWholeInsert(wholeBefore, next.wholeDigits, pos, digit),
        }
    }

    if (! hasDecimal) {
        next.wholeDigits = `${next.wholeDigits}${digit}`.replace(/^0+(?=\d)/, '')

        return {
            edit: next,
            cursorPos: next.wholeDigits.length,
            animatedDigitKey: animatedDigitKeyForWholeInsert(wholeBefore, next.wholeDigits, pos, digit),
        }
    }

    if (pos === wholeLen + 1) {
        if (next.fracDigits.length >= decimals) {
            return { edit: next, cursorPos: pos, animatedDigitKey: null }
        }

        next.inDecimal = true
        next.fracDigits = `${digit}${next.fracDigits}`.slice(0, decimals)

        return { edit: next, cursorPos: pos + 1, animatedDigitKey: 'f-0' }
    }

    const fracInsertIndex = pos - wholeLen - 1

    if (next.fracDigits.length >= decimals) {
        return { edit: next, cursorPos: pos, animatedDigitKey: null }
    }

    const before = next.fracDigits.slice(0, fracInsertIndex)
    const after = next.fracDigits.slice(fracInsertIndex)
    next.inDecimal = true
    next.fracDigits = `${before}${digit}${after}`.slice(0, decimals)

    return {
        edit: next,
        cursorPos: pos + 1,
        animatedDigitKey: `f-${fracInsertIndex}`,
    }
}

export function deleteBeforeCursor(edit, cursorPos, decimals) {
    const next = { ...edit }
    const wholeLen = next.wholeDigits.length
    const hasDecimal = hasDecimalSection(next, decimals)
    const pos = snapCursor(cursorPos, next, decimals)

    if (pos === 0) {
        if (next.negative) {
            next.negative = false
        }

        return { edit: next, cursorPos: 0 }
    }

    if (pos <= wholeLen) {
        next.wholeDigits = next.wholeDigits.slice(0, pos - 1) + next.wholeDigits.slice(pos)

        return { edit: next, cursorPos: pos - 1 }
    }

    if (hasDecimal && pos === wholeLen + 1) {
        next.inDecimal = false
        next.fracDigits = ''

        return { edit: next, cursorPos: wholeLen }
    }

    if (hasDecimal && pos > wholeLen + 1) {
        const fracDeleteIndex = pos - wholeLen - 2
        next.fracDigits = next.fracDigits.slice(0, fracDeleteIndex) + next.fracDigits.slice(fracDeleteIndex + 1)

        if (next.fracDigits === '') {
            next.inDecimal = false
        }

        return { edit: next, cursorPos: pos - 1 }
    }

    return { edit: next, cursorPos: pos }
}

export function deleteAtCursor(edit, cursorPos, decimals) {
    const next = { ...edit }
    const wholeLen = next.wholeDigits.length
    const hasDecimal = hasDecimalSection(next, decimals)
    const pos = snapCursor(cursorPos, next, decimals)
    const max = getLogicalLength(next, decimals)

    if (pos >= max) {
        return { edit: next, cursorPos: pos }
    }

    if (pos < wholeLen) {
        next.wholeDigits = next.wholeDigits.slice(0, pos) + next.wholeDigits.slice(pos + 1)

        return { edit: next, cursorPos: pos }
    }

    if (hasDecimal && pos === wholeLen) {
        next.inDecimal = false
        next.fracDigits = ''

        return { edit: next, cursorPos: pos }
    }

    if (hasDecimal && pos === wholeLen + 1 && next.fracDigits === '') {
        next.inDecimal = false

        return { edit: next, cursorPos: pos }
    }

    if (hasDecimal && pos > wholeLen) {
        const fracDeleteIndex = pos - wholeLen - 1
        next.fracDigits = next.fracDigits.slice(0, fracDeleteIndex) + next.fracDigits.slice(fracDeleteIndex + 1)

        if (next.fracDigits === '') {
            next.inDecimal = false
        }

        return { edit: next, cursorPos: pos }
    }

    return { edit: next, cursorPos: pos }
}

export function buildDisplaySegments(edit, meta, { showGhost = false } = {}) {
    const decimals = meta.decimals ?? 0
    const locale = meta.locale ?? 'en_US'
    const { group, decimal: decimalSep } = getLocaleSeparators(locale)
    const segments = []
    let wholeDigitIndex = 0
    const wholeGrouped = groupWholeDigits(edit.wholeDigits, group)

    for (const char of wholeGrouped) {
        if (char === group) {
            segments.push({
                type: 'separator',
                char,
                key: `sep-g-${wholeDigitIndex}`,
                ghost: false,
            })
        } else {
            segments.push({
                type: 'digit',
                char,
                key: `w-${wholeDigitIndex}`,
                ghost: false,
            })
            wholeDigitIndex += 1
        }
    }

    if (decimals === 0) {
        return segments
    }

    if (edit.inDecimal || edit.fracDigits !== '') {
        segments.push({
            type: 'separator',
            char: decimalSep,
            key: 'sep-decimal',
            ghost: false,
        })

        const fracDigits = String(edit.fracDigits ?? '')

        for (let index = 0; index < fracDigits.length; index += 1) {
            segments.push({
                type: 'digit',
                char: fracDigits[index],
                key: `f-${index}`,
                ghost: false,
            })
        }

        if (showGhost) {
            const ghostCount = Math.max(0, decimals - fracDigits.length)

            for (let index = 0; index < ghostCount; index += 1) {
                segments.push({
                    type: 'digit',
                    char: '0',
                    key: `g-${index}`,
                    ghost: true,
                })
            }
        }
    }

    return segments
}

export function buildDisplayItems(segments, cursorPos, decimals, edit) {
    const items = []
    const wholeLen = edit.wholeDigits.length
    const hasDecimal = hasDecimalSection(edit, decimals)
    const snappedCursor = snapCursor(cursorPos, edit, decimals)
    const visibleSegments = segments.filter((segment) => ! (segment.type === 'digit' && segment.ghost))
    let wholeDigitCount = 0
    let fracDigitCount = 0

    for (const segment of visibleSegments) {
        if (segment.type === 'digit' && segment.key.startsWith('w-')) {
            if (snappedCursor === wholeDigitCount) {
                items.push({ type: 'caret', key: `caret-before-w-${wholeDigitCount}` })
            }

            items.push({
                ...segment,
                cursorBefore: wholeDigitCount,
                cursorAfter: wholeDigitCount + 1,
            })
            wholeDigitCount += 1

            if (snappedCursor === wholeLen && wholeDigitCount === wholeLen) {
                items.push({ type: 'caret', key: `caret-after-w-${wholeLen}` })
            }

            continue
        }

        if (segment.type === 'separator' && segment.key === 'sep-decimal') {
            items.push({
                ...segment,
                cursorBefore: wholeLen,
                cursorAfter: wholeLen + 1,
            })

            if (snappedCursor === wholeLen + 1 && edit.fracDigits === '') {
                items.push({ type: 'caret', key: 'caret-after-decimal' })
            }

            continue
        }

        if (segment.type === 'digit' && segment.key.startsWith('f-')) {
            const beforeFracCursor = wholeLen + 1 + fracDigitCount
            const afterFracCursor = wholeLen + 1 + fracDigitCount + 1

            if (snappedCursor === beforeFracCursor) {
                items.push({ type: 'caret', key: `caret-before-f-${fracDigitCount}` })
            }

            items.push({
                ...segment,
                cursorBefore: beforeFracCursor,
                cursorAfter: afterFracCursor,
            })
            fracDigitCount += 1

            const isLastFracDigit = fracDigitCount === edit.fracDigits.length

            if (isLastFracDigit && snappedCursor === afterFracCursor) {
                items.push({ type: 'caret', key: `caret-after-f-${fracDigitCount}` })
            }

            continue
        }

        items.push(segment)
    }

    const isEditEmpty = edit.wholeDigits === ''
        && edit.fracDigits === ''
        && ! edit.inDecimal

    if (isEditEmpty && snappedCursor === 0 && ! items.some((item) => item.type === 'caret')) {
        items.push({ type: 'caret', key: 'caret-empty', cursorPos: 0 })
    }

    return items
}

export function cursorPosFromClientX(clientX, edit, decimals, liveDisplayElement) {
    const positions = getNavigableCursorPositions(edit, decimals)

    if (positions.length === 0) {
        return 0
    }

    const isEditEmpty = edit.wholeDigits === ''
        && edit.fracDigits === ''
        && ! edit.inDecimal

    if (isEditEmpty) {
        return 0
    }

    if (! liveDisplayElement) {
        return null
    }

    let closest = positions[0]
    let minDistance = Infinity

    liveDisplayElement.querySelectorAll('[data-fff-cursor-before], [data-fff-cursor-after]').forEach((element) => {
        const rect = element.getBoundingClientRect()

        if (element.dataset.fffCursorBefore !== undefined) {
            const position = Number.parseInt(element.dataset.fffCursorBefore, 10)
            const distance = Math.abs(clientX - rect.left)

            if (distance < minDistance) {
                minDistance = distance
                closest = position
            }
        }

        if (element.dataset.fffCursorAfter !== undefined) {
            const position = Number.parseInt(element.dataset.fffCursorAfter, 10)
            const distance = Math.abs(clientX - rect.right)

            if (distance < minDistance) {
                minDistance = distance
                closest = position
            }
        }
    })

    if (minDistance === Infinity) {
        return getLogicalLength(edit, decimals)
    }

    return snapCursor(closest, edit, decimals)
}

const currencySelectMenu = createSearchableSelectMenuMixin({
    openKey: 'currencyOpen',
    readyKey: 'currencyMenuReady',
    scrollHandlerKey: 'currencyMenuScrollHandler',
    resizeHandlerKey: 'currencyMenuResizeHandler',
    triggerRef: 'currencyTrigger',
    menuRef: 'currencyMenu',
    matchTriggerWidth: false,
    closeMethod: 'closeCurrencyMenu',
    ownerIdPrefix: 'fff-currency',
})

export default function currencyFieldFormComponent({
    state,
    statePath,
    currencies,
    defaultCurrency,
    hasCurrencySelect,
    locale,
    minMinor,
    maxMinor,
    allowNegative,
    animated,
    commitDecimalsOnBlur,
    searchable,
    disabled,
    readOnly,
    placeholder,
    currencyLabel,
    searchPlaceholder,
}) {
    return {
        state,
        statePath,
        currencies,
        defaultCurrency,
        hasCurrencySelect,
        locale,
        minMinor,
        maxMinor,
        allowNegative,
        animated,
        commitDecimalsOnBlur,
        searchable,
        disabled,
        readOnly,
        placeholder,
        currencyLabel,
        searchPlaceholder,
        currencyOpen: false,
        currencySearch: '',
        currencyMenuReady: false,
        currencyMenuScrollHandler: null,
        currencyMenuResizeHandler: null,
        isFocused: false,
        edit: { wholeDigits: '', fracDigits: '', inDecimal: false, negative: false },
        cursorPos: 0,
        displaySegments: [],
        displayItems: [],
        previousDigitChars: {},
        enteringDigitKeys: new Set(),
        isEditing: false,
        displayReady: false,
        reducedMotion: false,
        pointerPositionedCursor: false,
        pendingPointerClientX: null,
        pendingCommit: null,
        ...currencySelectMenu,

        get isLocked() {
            return this.disabled || this.readOnly
        },

        get activeCurrency() {
            const code = this.hasCurrencySelect
                ? (this.state?.currency ?? this.defaultCurrency)
                : this.defaultCurrency

            return this.currencies.find((currency) => currency.code === code)
                ?? this.currencies[0]
                ?? { code: this.defaultCurrency, symbol: '', decimals: 2, locale: this.locale }
        },

        get amount() {
            if (this.hasCurrencySelect) {
                return this.state?.amount ?? null
            }

            return this.state ?? null
        },

        set amount(value) {
            if (this.hasCurrencySelect) {
                if (! this.state || typeof this.state !== 'object') {
                    this.state = { amount: value, currency: this.defaultCurrency }
                } else {
                    this.state.amount = value
                }

                return
            }

            this.state = value
        },

        get filteredCurrencies() {
            const query = this.currencySearch.trim().toLowerCase()

            if (! query) {
                return this.currencies
            }

            return this.currencies.filter((currency) => {
                return currency.name.toLowerCase().includes(query)
                    || currency.code.toLowerCase().includes(query)
                    || currency.symbol.toLowerCase().includes(query)
            })
        },

        get isEmpty() {
            return this.edit.wholeDigits === '' && this.edit.fracDigits === '' && ! this.edit.inDecimal
        },

        get activeSegments() {
            return this.displaySegments.filter((segment) => ! (segment.type === 'digit' && segment.ghost))
        },

        get ghostSegments() {
            return this.displaySegments.filter((segment) => segment.type === 'digit' && segment.ghost)
        },

        init() {
            this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
            this.ensureState()
            this.syncEditFromAmount(false)
            this.displayReady = true

            this.$watch('state', () => {
                if (this.isEditing || this.isFocused) {
                    return
                }

                if (this.pendingCommit !== null) {
                    if (this.amount !== this.pendingCommit && this.isValidMinorAmount(this.pendingCommit)) {
                        this.commitStateToWire(this.pendingCommit)
                    }

                    return
                }

                const decimals = this.activeCurrency.decimals ?? 0
                const fromEdit = minorFromEditState({
                    ...this.edit,
                    decimals,
                })

                if (this.amount === fromEdit) {
                    return
                }

                this.ensureState()
                this.syncEditFromAmount(false)
            })

            this.bindSelectMenuLifecycle()
        },

        ensureState() {
            if (this.hasCurrencySelect) {
                if (! this.state || typeof this.state !== 'object') {
                    this.state = { amount: null, currency: this.defaultCurrency }
                }

                if (! this.state.currency) {
                    this.state.currency = this.defaultCurrency
                }

                return
            }

            if (this.state === undefined) {
                this.state = null
            }
        },

        syncEditFromAmount(animate) {
            this.edit = editStateFromMinor(this.amount, this.activeCurrency.decimals ?? 0)
            this.cursorPos = getLogicalLength(this.edit, this.activeCurrency.decimals ?? 0)
            this.refreshDisplay(animate)
        },

        syncAmountFromEdit() {
            const next = minorFromEditState({
                ...this.edit,
                decimals: this.activeCurrency.decimals ?? 0,
            })

            if (! this.isValidMinorAmount(next)) {
                return false
            }

            this.pendingCommit = next
            this.commitStateToWire(next)
            this.amount = next

            return true
        },

        isValidMinorAmount(amount) {
            if (amount === null) {
                return true
            }

            if (this.minMinor !== null && amount < this.minMinor) {
                return false
            }

            if (this.maxMinor !== null && amount > this.maxMinor) {
                return false
            }

            return true
        },

        commitStateToWire(amount) {
            if (! this.$wire || ! this.statePath) {
                return
            }

            if (this.hasCurrencySelect) {
                this.$wire.set(this.statePath, {
                    amount,
                    currency: this.activeCurrency?.code ?? this.defaultCurrency,
                }, true)

                return
            }

            this.$wire.set(this.statePath, amount, true)
        },

        refreshDisplay(animate = true, { animateDigitKey = null } = {}) {
            const decimals = this.activeCurrency.decimals ?? 0
            this.cursorPos = snapCursor(this.cursorPos, this.edit, decimals)

            const segments = buildDisplaySegments(this.edit, {
                decimals,
                locale: normalizeLocale(this.locale || this.activeCurrency.locale),
            }, {
                showGhost: this.isFocused && this.commitDecimalsOnBlur && (this.edit.inDecimal || this.edit.fracDigits !== ''),
            })

            const nextDigits = {}
            const entering = new Set()

            segments.forEach((segment) => {
                if (segment.type !== 'digit' || segment.ghost) {
                    return
                }

                if (
                    animate
                    && this.animated
                    && ! this.reducedMotion
                    && animateDigitKey !== null
                    && segment.key === animateDigitKey
                ) {
                    entering.add(segment.key)
                }

                nextDigits[segment.key] = segment.char
            })

            this.enteringDigitKeys = entering
            this.displaySegments = segments
            this.displayItems = buildDisplayItems(segments, this.cursorPos, decimals, this.edit)
            this.displayReady = true

            this.$nextTick(() => {
                this.previousDigitChars = nextDigits

                if (entering.size > 0) {
                    window.setTimeout(() => {
                        this.enteringDigitKeys = new Set()
                    }, 200)
                }
            })
        },

        moveCursor(delta) {
            const decimals = this.activeCurrency.decimals ?? 0
            this.cursorPos = moveCursorByDelta(this.cursorPos, delta, this.edit, decimals)
            this.refreshDisplay(false)
        },

        onHiddenKeydown(event) {
            if (this.isLocked) {
                return
            }

            const { key } = event
            const decimals = this.activeCurrency.decimals ?? 0

            if (key === 'ArrowLeft') {
                event.preventDefault()
                this.moveCursor(-1)

                return
            }

            if (key === 'ArrowRight') {
                event.preventDefault()
                this.moveCursor(1)

                return
            }

            if (key === 'Home') {
                event.preventDefault()
                this.cursorPos = 0
                this.refreshDisplay(false)

                return
            }

            if (key === 'End') {
                event.preventDefault()
                this.cursorPos = getLogicalLength(this.edit, decimals)
                this.refreshDisplay(false)

                return
            }

            if (key >= '0' && key <= '9') {
                event.preventDefault()
                this.typeDigit(key)

                return
            }

            if (key === 'Backspace') {
                event.preventDefault()
                this.deleteDigit()

                return
            }

            if (key === 'Delete') {
                event.preventDefault()
                this.deleteForward()

                return
            }

            const separators = getLocaleSeparators(normalizeLocale(this.locale || this.activeCurrency.locale))

            if (key === separators.decimal || key === '.' || key === ',') {
                event.preventDefault()
                this.startDecimal()

                return
            }

            if (this.allowNegative && key === '-') {
                event.preventDefault()
                this.toggleNegative()
            }
        },

        toggleNegative() {
            this.edit.negative = ! this.edit.negative
            this.refreshDisplay(false)
        },

        typeDigit(digit) {
            const decimals = this.activeCurrency.decimals ?? 0
            const result = applyTypeDigitAtCursor(this.edit, digit, this.cursorPos, decimals)

            this.edit = result.edit
            this.cursorPos = result.cursorPos
            this.refreshDisplay(true, { animateDigitKey: result.animatedDigitKey ?? null })
        },

        deleteDigit() {
            const decimals = this.activeCurrency.decimals ?? 0
            const result = deleteBeforeCursor(this.edit, this.cursorPos, decimals)

            this.edit = result.edit
            this.cursorPos = result.cursorPos
            this.refreshDisplay()
        },

        deleteForward() {
            const decimals = this.activeCurrency.decimals ?? 0
            const result = deleteAtCursor(this.edit, this.cursorPos, decimals)

            this.edit = result.edit
            this.cursorPos = result.cursorPos
            this.refreshDisplay()
        },

        startDecimal() {
            const decimals = this.activeCurrency.decimals ?? 0

            if (decimals === 0) {
                return
            }

            this.edit.inDecimal = true
            this.cursorPos = snapCursor(this.edit.wholeDigits.length + 1, this.edit, decimals)
            this.refreshDisplay()
        },

        onFocus() {
            if (this.pendingCommit !== null && this.amount === this.pendingCommit) {
                this.pendingCommit = null
            } else if (this.pendingCommit !== null && this.isValidMinorAmount(this.pendingCommit)) {
                this.commitStateToWire(this.pendingCommit)
            }

            this.isFocused = true
            this.isEditing = true

            const decimals = this.activeCurrency.decimals ?? 0

            if (decimals > 0 && ! this.isEmpty) {
                this.edit.inDecimal = true
            }

            const shouldPositionFromPointer = this.pointerPositionedCursor
            const pendingClientX = this.pendingPointerClientX

            this.pointerPositionedCursor = false
            this.pendingPointerClientX = null

            if (shouldPositionFromPointer && pendingClientX !== null) {
                this.$nextTick(() => {
                    this.setCursorFromClientX(pendingClientX)
                    this.refreshDisplay(false)
                })

                return
            }

            this.cursorPos = getLogicalLength(this.edit, decimals)
            this.refreshDisplay(false)
        },

        onHiddenPointerDown(event) {
            if (this.isLocked) {
                return
            }

            this.pointerPositionedCursor = true
            this.pendingPointerClientX = event.clientX
        },

        setCursorFromClientX(clientX) {
            const decimals = this.activeCurrency.decimals ?? 0
            const liveDisplay = this.$el?.querySelector('.fff-currency-field__live-display.is-ready')
            const nextCursorPos = cursorPosFromClientX(
                clientX,
                this.edit,
                decimals,
                liveDisplay,
            )

            if (nextCursorPos !== null) {
                this.cursorPos = nextCursorPos

                return
            }

            this.cursorPos = getLogicalLength(this.edit, decimals)
        },

        onBlur(event) {
            if (this.$refs.currencyMenu?.contains(event?.relatedTarget)) {
                return
            }

            if (this.$refs.currencyTrigger?.contains(event?.relatedTarget)) {
                return
            }

            this.isFocused = false

            if (this.commitDecimalsOnBlur && (this.edit.inDecimal || this.edit.fracDigits !== '')) {
                const decimals = this.activeCurrency.decimals ?? 0
                this.edit.fracDigits = String(this.edit.fracDigits ?? '').padEnd(decimals, '0').slice(0, decimals)
                this.edit.inDecimal = false
            }

            const synced = this.syncAmountFromEdit()

            this.isEditing = false

            if (! synced) {
                this.pendingCommit = minorFromEditState({
                    ...this.edit,
                    decimals: this.activeCurrency.decimals ?? 0,
                })
                this.refreshDisplay(false)

                return
            }

            this.refreshDisplay(false)
        },

        focusInput() {
            if (this.isLocked) {
                return
            }

            this.$refs.hiddenInput?.focus({ preventScroll: true })
        },

        selectCurrency(code) {
            if (this.isLocked || ! this.hasCurrencySelect) {
                return
            }

            this.state.currency = code
            this.closeCurrencyMenu()
            this.syncEditFromAmount(true)

            this.$nextTick(() => {
                this.focusInput()
            })
        },

        toggleCurrencyMenu() {
            if (this.isLocked || ! this.hasCurrencySelect) {
                return
            }

            const willOpen = ! this.currencyOpen

            this.currencyOpen = willOpen

            if (! this.currencyOpen) {
                return
            }

            if (this.searchable) {
                this.$nextTick(() => {
                    this.$refs.currencySearch?.focus()
                })
            }
        },

        closeCurrencyMenu() {
            this.currencyOpen = false
            this.currencySearch = ''
        },

        shouldAnimateDigit(segment) {
            return this.enteringDigitKeys.has(segment.key)
        },
    }
}

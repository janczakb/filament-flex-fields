const OPERATORS = {
    '+': { precedence: 1, assoc: 'L', arity: 2 },
    '-': { precedence: 1, assoc: 'L', arity: 2 },
    '*': { precedence: 2, assoc: 'L', arity: 2 },
    '/': { precedence: 2, assoc: 'L', arity: 2 },
    '%': { precedence: 2, assoc: 'L', arity: 2 },
    'u-': { precedence: 3, assoc: 'R', arity: 1 },
}

function tokenize(expression) {
    const source = String(expression ?? '').replace(/\s+/g, '')
    const tokens = []
    let index = 0

    while (index < source.length) {
        const char = source[index]

        if (char >= '0' && char <= '9' || char === '.') {
            let number = char
            index += 1

            while (index < source.length && /[0-9.]/.test(source[index])) {
                number += source[index]
                index += 1
            }

            if ((number.match(/\./g) ?? []).length > 1) {
                throw new Error('Invalid number')
            }

            tokens.push({ type: 'number', value: Number(number) })

            continue
        }

        if ('+-*/%()'.includes(char)) {
            tokens.push({ type: 'operator', value: char })
            index += 1

            continue
        }

        throw new Error('Invalid character')
    }

    return tokens
}

function toRpn(tokens) {
    const output = []
    const stack = []

    for (let index = 0; index < tokens.length; index += 1) {
        const token = tokens[index]

        if (token.type === 'number') {
            output.push(token)

            continue
        }

        if (token.value === '(') {
            stack.push(token)

            continue
        }

        if (token.value === ')') {
            while (stack.length && stack[stack.length - 1].value !== '(') {
                output.push(stack.pop())
            }

            if (! stack.length) {
                throw new Error('Mismatched parentheses')
            }

            stack.pop()

            continue
        }

        let operator = token.value

        const previous = tokens[index - 1]
        const isUnary = index === 0
            || previous?.value === '('
            || (previous?.type === 'operator' && previous.value !== ')')

        if (operator === '-' && isUnary) {
            operator = 'u-'
        }

        const current = OPERATORS[operator]

        if (! current) {
            throw new Error('Unsupported operator')
        }

        while (stack.length) {
            const top = stack[stack.length - 1]

            if (top.value === '(') {
                break
            }

            const topOp = OPERATORS[top.value === '-' ? 'u-' : top.value] ?? OPERATORS[top.value]

            if (! topOp) {
                break
            }

            const shouldPop = current.assoc === 'L'
                ? current.precedence <= topOp.precedence
                : current.precedence < topOp.precedence

            if (! shouldPop) {
                break
            }

            output.push(stack.pop())
        }

        stack.push({ type: 'operator', value: operator })
    }

    while (stack.length) {
        const token = stack.pop()

        if (token.value === '(' || token.value === ')') {
            throw new Error('Mismatched parentheses')
        }

        output.push(token)
    }

    return output
}

function evaluateRpn(rpn) {
    const stack = []

    for (const token of rpn) {
        if (token.type === 'number') {
            stack.push(token.value)

            continue
        }

        const op = token.value

        if (op === 'u-') {
            const value = stack.pop()

            if (value === undefined) {
                throw new Error('Invalid expression')
            }

            stack.push(-value)

            continue
        }

        const right = stack.pop()
        const left = stack.pop()

        if (left === undefined || right === undefined) {
            throw new Error('Invalid expression')
        }

        switch (op) {
            case '+':
                stack.push(left + right)
                break
            case '-':
                stack.push(left - right)
                break
            case '*':
                stack.push(left * right)
                break
            case '/':
                if (right === 0) {
                    throw new Error('Division by zero')
                }

                stack.push(left / right)
                break
            case '%':
                if (right === 0) {
                    throw new Error('Division by zero')
                }

                stack.push(left % right)
                break
            default:
                throw new Error('Unsupported operator')
        }
    }

    if (stack.length !== 1) {
        throw new Error('Invalid expression')
    }

    return stack[0]
}

export function evaluateExpression(expression) {
    if (String(expression ?? '').trim() === '') {
        return null
    }

    const tokens = tokenize(expression)
    const rpn = toRpn(tokens)

    return evaluateRpn(rpn)
}

function trimTrailingDecimalZeros(value) {
    return String(value)
        .replace(/(\.\d*?)0+$/, '$1')
        .replace(/\.$/, '')
}

export function formatCalculatorResult(value, decimalPlaces = null) {
    if (value === null || Number.isNaN(value) || ! Number.isFinite(value)) {
        return null
    }

    const numeric = Number(value)

    if (decimalPlaces === null) {
        return trimTrailingDecimalZeros(String(numeric))
    }

    return trimTrailingDecimalZeros(numeric.toFixed(decimalPlaces))
}

/**
 * @returns {{ value: string, start: number, end: number, wrapped: boolean } | null}
 */
export function getLastNumberSpan(expression) {
    const source = String(expression ?? '')

    const wrappedMatch = source.match(/\((-?(?:\d+\.?\d*|\.\d+))\)$/)

    if (wrappedMatch) {
        return {
            value: wrappedMatch[1],
            start: source.length - wrappedMatch[0].length,
            end: source.length,
            wrapped: true,
        }
    }

    const plainMatch = source.match(/(-?(?:\d+\.?\d*|\.\d+))$/)

    if (! plainMatch) {
        return null
    }

    return {
        value: plainMatch[0],
        start: source.length - plainMatch[0].length,
        end: source.length,
        wrapped: false,
    }
}

function operandNeedsParentheses(expression, spanStart) {
    if (spanStart === 0) {
        return false
    }

    const before = expression[spanStart - 1]

    return before === '+' || before === '-' || before === '*' || before === '/' || before === '%'
}

function formatWrappedOperand(value) {
    if (value.startsWith('-')) {
        const absolute = value.slice(1) || '0'

        return `(-${absolute})`
    }

    return `(${value})`
}

/**
 * @returns {{ value: string, start: number, end: number, wrapped: boolean, open?: boolean } | null}
 */
function getEditableOperandSpan(expression) {
    const source = String(expression ?? '')

    if (source.endsWith('(-')) {
        return {
            value: '',
            start: source.length - 2,
            end: source.length,
            wrapped: true,
            open: true,
        }
    }

    const openNegativeMatch = source.match(/\((-?(?:\d+\.?\d*|\.\d+))$/)

    if (openNegativeMatch) {
        return {
            value: openNegativeMatch[1],
            start: source.length - openNegativeMatch[0].length,
            end: source.length,
            wrapped: true,
            open: true,
        }
    }

    return getLastNumberSpan(source)
}

function replaceOperandSpan(expression, span, nextValue) {
    if (span.wrapped && span.open) {
        if (nextValue.startsWith('-')) {
            const absolute = nextValue.slice(1) || '0'

            return `${expression.slice(0, span.start)}(-${absolute}`
        }

        return `${expression.slice(0, span.start)}(${nextValue}`
    }

    if (span.wrapped) {
        return expression.slice(0, span.start) + formatWrappedOperand(nextValue)
    }

    return expression.slice(0, span.start) + nextValue
}

export function toggleSignOnExpression(expression) {
    const source = String(expression ?? '')

    if (source.endsWith('(-')) {
        return source.slice(0, -2)
    }

    const span = getLastNumberSpan(source)

    if (! span) {
        if (source === '' || /[+\-*/%(]$/.test(source)) {
            return `${source}(-`
        }

        return source
    }

    const before = source.slice(0, span.start)
    const isNegative = span.value.startsWith('-')
    const absolute = isNegative ? (span.value.slice(1) || '0') : span.value

    if (span.wrapped) {
        return isNegative
            ? `${before}${absolute}`
            : `${before}${formatWrappedOperand(`-${absolute}`)}`
    }

    if (isNegative) {
        return `${before}${absolute}`
    }

    if (operandNeedsParentheses(source, span.start)) {
        return `${before}${formatWrappedOperand(`-${absolute}`)}`
    }

    return `${before}-${absolute}`
}

export function applyPercentToExpression(expression) {
    const span = getLastNumberSpan(expression)

    if (! span) {
        return expression
    }

    const numericValue = Number(span.value)

    if (! Number.isFinite(numericValue)) {
        return expression
    }

    const formatted = formatCalculatorResult(numericValue / 100, null)

    return replaceOperandSpan(expression, span, formatted)
}

const BINARY_OPERATORS = new Set(['+', '-', '*', '/'])

function getTrailingOperand(expression) {
    return getEditableOperandSpan(expression)?.value ?? null
}

function endsWithBinaryOperator(expression) {
    const last = String(expression ?? '').slice(-1)

    return BINARY_OPERATORS.has(last)
}

function cancelOpenNegativeGroup(expression) {
    if (expression.endsWith('(-')) {
        return expression.slice(0, -2)
    }

    return expression
}

function appendDigitToOperand(operand, digit) {
    if (operand === '0' || operand === '-0') {
        if (digit === '0') {
            return operand
        }

        return operand.startsWith('-') ? `-${digit}` : digit
    }

    return `${operand}${digit}`
}

function appendDigitToken(expression, digit) {
    if (expression.endsWith('(-')) {
        return `${expression}${digit})`
    }

    const span = getEditableOperandSpan(expression)

    if (span) {
        const nextValue = appendDigitToOperand(span.value === '' ? '0' : span.value, digit)

        if (span.wrapped && span.open) {
            return replaceOperandSpan(expression, span, nextValue)
        }

        if (span.wrapped && ! span.open && span.value.startsWith('-') === false && nextValue.startsWith('-')) {
            return replaceOperandSpan(expression, span, nextValue)
        }

        return replaceOperandSpan(expression, span, nextValue)
    }

    return appendDigitToOperand(expression, digit)
}

export function appendCalculatorToken(expression, token) {
    const current = String(expression ?? '')

    if (/^\d$/.test(token)) {
        return appendDigitToken(current, token)
    }

    if (token === '.') {
        return appendDecimalToken(current)
    }

    if (BINARY_OPERATORS.has(token)) {
        return appendOperatorToken(cancelOpenNegativeGroup(current), token)
    }

    return current + token
}

function appendDecimalToken(expression) {
    if (expression.endsWith('(-')) {
        return `${expression}0.)`
    }

    const span = getLastNumberSpan(expression)

    if (span) {
        if (span.value.includes('.')) {
            return expression
        }

        const nextValue = `${span.value}.`

        return replaceOperandSpan(expression, span, nextValue)
    }

    if (expression === '' || endsWithBinaryOperator(expression) || expression.endsWith('(')) {
        return `${expression}0.`
    }

    return `${expression}.`
}

function appendOperatorToken(expression, operator) {
    if (expression === '') {
        return operator === '-' ? '-' : expression
    }

    const last = expression[expression.length - 1]

    if (BINARY_OPERATORS.has(last)) {
        return expression.slice(0, -1) + operator
    }

    if (last === '.') {
        return expression.slice(0, -1) + operator
    }

    if (last === '(') {
        return operator === '-' ? `${expression}-` : expression
    }

    if (/[\d)]$/.test(last)) {
        return expression + operator
    }

    return expression
}

export function sanitizeExpressionInput(value) {
    return String(value ?? '')
        .replace(/[^0-9+\-*/().%\s]/g, '')
        .replace(/\s+/g, '')
}

export function isExpressionIncomplete(sanitized) {
    if (sanitized === '') {
        return false
    }

    if (sanitized.endsWith('(-')) {
        return true
    }

    if (/[+\-*/%(]$/.test(sanitized)) {
        return true
    }

    if (/[+\-*/%(][+*/%)]+/.test(sanitized)) {
        return true
    }

    if (/\(\)/.test(sanitized)) {
        return true
    }

    if (/\.$/.test(sanitized) || /\(\.$/.test(sanitized) || /[+\-*/%(]\.$/.test(sanitized)) {
        return true
    }

    const openCount = (sanitized.match(/\(/g) ?? []).length
    const closeCount = (sanitized.match(/\)/g) ?? []).length

    return openCount !== closeCount
}

export function stripTrailingOperators(sanitized) {
    return sanitized.replace(/[+\-*/%(]+$/g, '')
}

export function computeCalculatorDisplay(expression, decimalPlaces = null) {
    const sanitized = sanitizeExpressionInput(expression)

    if (sanitized === '') {
        return {
            result: '0',
            preview: null,
            error: null,
            incomplete: false,
        }
    }

    if (isExpressionIncomplete(sanitized)) {
        const prefix = stripTrailingOperators(sanitized)

        if (prefix && prefix !== sanitized) {
            try {
                const value = evaluateExpression(prefix)

                return {
                    result: null,
                    preview: formatCalculatorResult(value, decimalPlaces),
                    error: null,
                    incomplete: true,
                }
            } catch {
                return {
                    result: null,
                    preview: null,
                    error: null,
                    incomplete: true,
                }
            }
        }

        return {
            result: null,
            preview: null,
            error: null,
            incomplete: true,
        }
    }

    try {
        const value = evaluateExpression(sanitized)

        return {
            result: formatCalculatorResult(value, decimalPlaces),
            preview: null,
            error: null,
            incomplete: false,
        }
    } catch {
        return {
            result: null,
            preview: null,
            error: null,
            incomplete: true,
        }
    }
}

/**
 * Selection UX for SelectField dropdowns:
 * - stroke-draw check on select / undraw on deselect (list layouts)
 * - optional checklist mode that keeps selected options visible
 *
 * Hot path: createOptionElement + selectOption + renderOptions while the menu is open.
 */

import { prefersReducedMotion } from '../../core/theme-utils.js'

const CHECK_STROKE_MS = 300
const CHECK_STROKE_TIMEOUT_MS = CHECK_STROKE_MS + 50

const ANIMATED_CHECK_SVG_HTML = [
    '<svg class="fff-select-option-selected-check__svg" aria-hidden="true" fill="none" stroke="currentColor"',
    ' stroke-dasharray="22" stroke-dashoffset="22" stroke-linecap="round" stroke-linejoin="round"',
    ' stroke-width="2" viewBox="0 0 17 18" focusable="false">',
    '<polyline points="1 9 7 14 15 4"></polyline>',
    '</svg>',
].join('')

/** Shared empty array for read-only return paths (never assign to Filament state). */
const EMPTY_SELECTED_KEYS = Object.freeze([])

/** @type {HTMLTemplateElement | null} */
let animatedCheckTemplate = null

function getAnimatedCheckTemplate() {
    if (animatedCheckTemplate) {
        return animatedCheckTemplate
    }

    animatedCheckTemplate = document.createElement('template')
    animatedCheckTemplate.innerHTML = ANIMATED_CHECK_SVG_HTML

    return animatedCheckTemplate
}

/** Fresh empty array so Filament never mutates a shared frozen reference. */
function emptySelectionState() {
    return []
}

function escapeAttributeSelectorValue(value) {
    if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
        return CSS.escape(value)
    }

    return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"')
}

export function resolveSelectedValueKeys(selectInstance) {
    const state = selectInstance?.state

    if (selectInstance?.isMultiple) {
        if (! Array.isArray(state) || state.length === 0) {
            return EMPTY_SELECTED_KEYS
        }

        const keys = new Array(state.length)

        for (let index = 0; index < state.length; index++) {
            keys[index] = String(state[index])
        }

        return keys
    }

    if (state === null || state === undefined || state === '') {
        return EMPTY_SELECTED_KEYS
    }

    return [String(state)]
}

export function syncKnownSelectedFromState(selectInstance) {
    selectInstance.__fffKnownSelected = new Set(resolveSelectedValueKeys(selectInstance))
}

export function pruneKnownSelectedToState(selectInstance) {
    const known = selectInstance.__fffKnownSelected

    if (! known || known.size === 0) {
        return
    }

    if (! selectInstance.isMultiple) {
        const state = selectInstance.state
        const key = state === null || state === undefined || state === ''
            ? null
            : String(state)

        for (const knownKey of known) {
            if (knownKey !== key) {
                known.delete(knownKey)
            }
        }

        return
    }

    const state = selectInstance.state

    if (! Array.isArray(state) || state.length === 0) {
        known.clear()

        return
    }

    const current = new Set()

    for (let index = 0; index < state.length; index++) {
        current.add(String(state[index]))
    }

    for (const knownKey of known) {
        if (! current.has(knownKey)) {
            known.delete(knownKey)
        }
    }
}

function stateIncludesValue(state, value) {
    if (! Array.isArray(state) || state.length === 0) {
        return false
    }

    const key = String(value)

    for (let index = 0; index < state.length; index++) {
        const entry = state[index]

        if (entry === value || String(entry) === key) {
            return true
        }
    }

    return false
}

function findOptionElementByValue(selectInstance, value) {
    const dropdown = selectInstance.dropdown

    if (! dropdown) {
        return null
    }

    const key = String(value)

    return dropdown.querySelector(
        `.fi-select-input-option[data-value="${escapeAttributeSelectorValue(key)}"]`,
    )
}

function scheduleCheckEnter(check) {
    cancelCheckEnter(check)
    check.setAttribute('data-visible', 'false')

    if (prefersReducedMotion()) {
        check.setAttribute('data-visible', 'true')

        return
    }

    const generation = (check.__fffCheckEnterGeneration ?? 0) + 1
    check.__fffCheckEnterGeneration = generation

    const outer = requestAnimationFrame(() => {
        if (check.__fffCheckEnterGeneration !== generation) {
            return
        }

        const inner = requestAnimationFrame(() => {
            if (check.__fffCheckEnterGeneration !== generation) {
                return
            }

            check.__fffCheckEnterRafs = null

            if (check.isConnected) {
                check.setAttribute('data-visible', 'true')
            }
        })

        check.__fffCheckEnterRafs = [inner]
    })

    check.__fffCheckEnterRafs = [outer]
}

function cancelCheckEnter(check) {
    if (! check) {
        return
    }

    check.__fffCheckEnterGeneration = (check.__fffCheckEnterGeneration ?? 0) + 1

    const rafs = check.__fffCheckEnterRafs

    if (! rafs) {
        return
    }

    for (let index = 0; index < rafs.length; index++) {
        cancelAnimationFrame(rafs[index])
    }

    check.__fffCheckEnterRafs = null
}

function runAfterSelectedCheckExit(check, callback) {
    if (! check) {
        callback()

        return () => {}
    }

    cancelCheckEnter(check)

    let settled = false
    let timeoutId = 0
    let onTransitionEnd = null
    let svg = null

    const finish = () => {
        if (settled) {
            return
        }

        settled = true

        if (timeoutId) {
            clearTimeout(timeoutId)
            timeoutId = 0
        }

        if (svg && onTransitionEnd) {
            svg.removeEventListener('transitionend', onTransitionEnd)
        }

        check.removeAttribute('data-check-exiting')

        callback()
    }

    check.setAttribute('data-check-exiting', 'true')
    check.setAttribute('data-visible', 'false')

    svg = check.querySelector('.fff-select-option-selected-check__svg')

    if (prefersReducedMotion() || ! svg) {
        finish()

        return () => {}
    }

    onTransitionEnd = (event) => {
        if (event.target !== svg) {
            return
        }

        const property = event.propertyName

        if (property && property !== 'stroke-dashoffset') {
            return
        }

        finish()
    }

    svg.addEventListener('transitionend', onTransitionEnd)
    timeoutId = window.setTimeout(finish, CHECK_STROKE_TIMEOUT_MS)

    return () => {
        settled = true

        if (timeoutId) {
            clearTimeout(timeoutId)
        }

        if (svg && onTransitionEnd) {
            svg.removeEventListener('transitionend', onTransitionEnd)
        }
    }
}

function decorateSelectedOptionCheck(
    option,
    selectInstance,
    { animate = false, isGridLayout = false, selectedOptionCheckIconHtml = '' } = {},
) {
    if (! option.classList.contains('fi-selected')) {
        return
    }

    const labelSpan = option.querySelector(':scope > span')

    if (! labelSpan || labelSpan.classList.contains('fff-select-option-selected-row')) {
        return
    }

    const useAnimatedCheck = ! isGridLayout

    if (! useAnimatedCheck && ! selectedOptionCheckIconHtml) {
        return
    }

    const isHtmlAllowed = selectInstance.isHtmlAllowed
    const labelContent = isHtmlAllowed ? labelSpan.innerHTML : labelSpan.textContent

    labelSpan.replaceChildren()
    labelSpan.classList.add('fff-select-option-selected-row')

    const labelWrapper = document.createElement('span')
    labelWrapper.className = 'fff-select-option-selected-row__label'

    if (isHtmlAllowed) {
        labelWrapper.innerHTML = labelContent
    } else {
        labelWrapper.textContent = labelContent
    }

    const check = document.createElement('span')
    check.className = 'fff-select-option-selected-check'
    check.setAttribute('aria-hidden', 'true')

    if (useAnimatedCheck) {
        check.appendChild(getAnimatedCheckTemplate().content.cloneNode(true))
    } else {
        check.innerHTML = selectedOptionCheckIconHtml
    }

    labelSpan.append(labelWrapper, check)

    if (useAnimatedCheck) {
        if (animate) {
            scheduleCheckEnter(check)
        } else {
            check.setAttribute('data-visible', 'true')
        }
    }
}

/**
 * @param {object} select
 * @param {{
 *   isGridLayout?: boolean,
 *   keepSelectedOptionsInDropdown?: boolean,
 *   selectedOptionCheckIconHtml?: string,
 * }} options
 */
export function patchSelectFieldSelectionUx(select, {
    isGridLayout = false,
    keepSelectedOptionsInDropdown = false,
    selectedOptionCheckIconHtml = '',
} = {}) {
    if (! select || select.__fffSelectionUxPatched) {
        return
    }

    if (isGridLayout && ! selectedOptionCheckIconHtml && ! keepSelectedOptionsInDropdown) {
        return
    }

    select.__fffSelectionUxPatched = true
    syncKnownSelectedFromState(select)

    const originalCreateOptionElement = select.createOptionElement.bind(select)
    const originalSelectOption = select.selectOption.bind(select)
    const originalRenderOptions = typeof select.renderOptions === 'function'
        ? select.renderOptions.bind(select)
        : null

    const shouldKeepSelected = keepSelectedOptionsInDropdown && Boolean(select.isMultiple)

    select.createOptionElement = function createOptionElementWithSelectionUx(value, label) {
        const keepRender = this.__fffKeepSelectedRendering === true
        const realState = keepRender ? this.__fffRealStateDuringRender : null

        if (keepRender) {
            this.state = realState
        }

        let option

        try {
            option = originalCreateOptionElement(value, label)
        } finally {
            if (keepRender) {
                this.state = emptySelectionState()
            }
        }

        const known = this.__fffKnownSelected ??= new Set()
        const key = String(option.getAttribute('data-value') ?? value)
        const isSelected = option.classList.contains('fi-selected')
        const shouldAnimate = isSelected && ! known.has(key)

        decorateSelectedOptionCheck(option, this, {
            animate: shouldAnimate,
            isGridLayout,
            selectedOptionCheckIconHtml,
        })

        if (isSelected) {
            known.add(key)
        }

        return option
    }

    if (shouldKeepSelected && originalRenderOptions) {
        select.renderOptions = function renderOptionsKeepSelected() {
            this.__fffRealStateDuringRender = this.state
            this.__fffKeepSelectedRendering = true
            this.state = emptySelectionState()

            try {
                return originalRenderOptions()
            } finally {
                this.state = this.__fffRealStateDuringRender
                this.__fffKeepSelectedRendering = false
                this.__fffRealStateDuringRender = null
            }
        }
    }

    select.selectOption = function selectOptionWithCheckMotion(value) {
        const isDeselecting = this.isMultiple && stateIncludesValue(this.state, value)

        if (isDeselecting && ! isGridLayout) {
            const option = findOptionElementByValue(this, value)
            const check = option?.querySelector('.fff-select-option-selected-check')

            if (check?.getAttribute('data-visible') === 'true') {
                this.__fffCheckExitToken = (this.__fffCheckExitToken ?? 0) + 1
                this.__fffCancelCheckExit?.()

                // Fire check exit independently — never delay state/chips on it.
                this.__fffCancelCheckExit = runAfterSelectedCheckExit(check, () => {
                    this.__fffCancelCheckExit = null
                })

                originalSelectOption(value)
                pruneKnownSelectedToState(this)

                return
            }
        }

        this.__fffCheckExitToken = (this.__fffCheckExitToken ?? 0) + 1
        this.__fffCancelCheckExit?.()
        this.__fffCancelCheckExit = null

        originalSelectOption(value)
        pruneKnownSelectedToState(this)
    }
}

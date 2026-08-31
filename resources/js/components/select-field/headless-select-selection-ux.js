/**
 * Selection check UX for headless SelectField options (same stroke animation as patch runtime).
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

export function scheduleCheckEnter(check) {
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

export function setCheckVisibleInstant(check, visible) {
    if (! check) {
        return
    }

    cancelCheckEnter(check)

    const svg = check.querySelector('.fff-select-option-selected-check__svg')

    if (svg) {
        svg.style.setProperty('transition', 'none', 'important')
    }

    check.setAttribute('data-visible', visible ? 'true' : 'false')
    check.removeAttribute('data-check-exiting')

    if (svg) {
        void svg.offsetWidth
        svg.style.removeProperty('transition')
    }
}

export function cancelAllOptionCheckAnimations(container) {
    if (! container) {
        return
    }

    container.querySelectorAll('.fff-select-option-selected-check').forEach((check) => {
        cancelCheckEnter(check)
    })
}

export function runAfterSelectedCheckExit(check, callback) {
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

        if (event.propertyName && event.propertyName !== 'stroke-dashoffset') {
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

/**
 * @param {HTMLButtonElement} option
 * @param {{
 *   isSelected: boolean,
 *   animate?: boolean,
 *   isGridLayout?: boolean,
 *   isHtmlAllowed?: boolean,
 *   selectedOptionCheckIconHtml?: string,
 * }} config
 */
export function applyHeadlessOptionSelectionUx(option, {
    isSelected,
    animate = false,
    isGridLayout = false,
    isHtmlAllowed = false,
    selectedOptionCheckIconHtml = '',
} = {}) {
    if (! option) {
        return
    }

    option.classList.toggle('fi-selected', isSelected)

    const labelSpan = option.querySelector(':scope > span')

    if (! labelSpan) {
        return
    }

    const useAnimatedCheck = ! isGridLayout

    if (! isSelected) {
        if (labelSpan.classList.contains('fff-select-option-selected-row')) {
            const labelWrapper = labelSpan.querySelector('.fff-select-option-selected-row__label')

            if (labelWrapper) {
                if (isHtmlAllowed) {
                    labelSpan.innerHTML = labelWrapper.innerHTML
                } else {
                    labelSpan.textContent = labelWrapper.textContent
                }

                labelSpan.classList.remove('fff-select-option-selected-row')
            }
        }

        return
    }

    if (labelSpan.classList.contains('fff-select-option-selected-row')) {
        const check = labelSpan.querySelector('.fff-select-option-selected-check')

        if (check && useAnimatedCheck) {
            if (animate) {
                scheduleCheckEnter(check)
            } else {
                setCheckVisibleInstant(check, true)
            }
        }

        return
    }

    if (! useAnimatedCheck && ! selectedOptionCheckIconHtml) {
        return
    }

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
            setCheckVisibleInstant(check, true)
        }
    }
}

/**
 * @param {HTMLElement} optionsContainer
 * @param {{
 *   isOptionSelected: (value: string) => boolean,
 *   knownSelected: Set<string>,
 *   isGridLayout?: boolean,
 *   isHtmlAllowed?: boolean,
 *   selectedOptionCheckIconHtml?: string,
 * }} config
 */
export function syncHeadlessDropdownOptionChecks(optionsContainer, {
    isOptionSelected,
    knownSelected,
    isGridLayout = false,
    isHtmlAllowed = false,
    selectedOptionCheckIconHtml = '',
}) {
    if (! optionsContainer) {
        return
    }

    optionsContainer.querySelectorAll('.fi-select-input-option').forEach((option) => {
        const value = String(option.getAttribute('data-value') ?? '')
        const isSelected = isOptionSelected(value)
        const shouldAnimate = isSelected && ! knownSelected.has(value)

        applyHeadlessOptionSelectionUx(option, {
            isSelected,
            animate: shouldAnimate,
            isGridLayout,
            isHtmlAllowed,
            selectedOptionCheckIconHtml,
        })

        if (isSelected) {
            knownSelected.add(value)
        }
    })

    for (const key of [...knownSelected]) {
        if (! isOptionSelected(key)) {
            knownSelected.delete(key)
        }
    }
}

export function seedHeadlessKnownSelected(values) {
    return new Set(values.map((value) => String(value)))
}

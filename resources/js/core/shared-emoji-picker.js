import 'emoji-picker-element'

const PICKER_ELEMENT_CLASS = 'fff-emoji-picker__element'

const pickersByLocale = new Map()
let panel = null
let loadingElement = null
let hostElement = null
let activeSession = null
let listenersBound = false

function resolveLocale(locale) {
    if (locale) {
        return locale
    }

    return (navigator.language ?? 'en').split('-')[0]
}

function resolveIsDark() {
    return document.documentElement.classList.contains('dark')
        || document.body.classList.contains('dark')
}

function resolvePrimaryColor(anchor, fallbackPrimaryColor) {
    if (typeof fallbackPrimaryColor === 'function') {
        const resolved = fallbackPrimaryColor()

        if (resolved) {
            return resolved
        }
    }

    if (typeof fallbackPrimaryColor === 'string' && fallbackPrimaryColor !== '') {
        return fallbackPrimaryColor
    }

    const field = anchor?.closest('.fff-flex-textarea, .fff-flex-input, [data-fff-primary-color]')
    const primary = field
        ? getComputedStyle(field).getPropertyValue('--fff-flex-textarea-primary').trim()
            || getComputedStyle(field).getPropertyValue('--fff-primary-color').trim()
        : ''

    return primary || 'rgb(59 130 246)'
}

function themeVariables(isDark, primaryColor) {
    if (isDark) {
        return {
            '--background': 'transparent',
            '--border-size': '0',
            '--border-radius': '0',
            '--border-color': 'transparent',
            '--button-hover-background': 'rgb(63 63 70)',
            '--button-active-background': 'rgb(82 82 91)',
            '--category-font-color': 'rgb(161 161 170)',
            '--input-border-color': 'rgb(82 82 91)',
            '--input-border-radius': '0.75rem',
            '--input-font-color': 'rgb(244 244 245)',
            '--input-placeholder-color': 'rgb(113 113 122)',
            '--indicator-color': primaryColor,
            '--outline-color': primaryColor,
        }
    }

    return {
        '--background': 'transparent',
        '--border-size': '0',
        '--border-radius': '0',
        '--border-color': 'transparent',
        '--button-hover-background': 'rgb(244 244 245)',
        '--button-active-background': 'rgb(228 228 231)',
        '--category-font-color': 'rgb(113 113 122)',
        '--input-border-color': 'rgb(228 228 231)',
        '--input-border-radius': '0.75rem',
        '--input-font-color': 'rgb(24 24 27)',
        '--input-placeholder-color': 'rgb(161 161 170)',
        '--indicator-color': primaryColor,
        '--outline-color': primaryColor,
    }
}

function applyPickerShadowStyles(picker, isDark, primaryColor) {
    if (! picker?.shadowRoot) {
        return
    }

    let style = picker.shadowRoot.querySelector('[data-fff-emoji-theme]')

    if (! style) {
        style = document.createElement('style')
        style.setAttribute('data-fff-emoji-theme', '')
        picker.shadowRoot.appendChild(style)
    }

    const variables = themeVariables(isDark, primaryColor)
    const variableBlock = Object.entries(variables)
        .map(([name, value]) => `${name}: ${value} !important;`)
        .join('\n            ')
    const searchBackground = isDark ? 'rgb(39 39 42)' : 'rgb(244 244 245)'
    const navBorderColor = isDark ? 'rgb(63 63 70)' : 'rgb(228 228 231)'

    style.textContent = `
        :host,
        :host(.light),
        :host(.dark) {
            color-scheme: ${isDark ? 'dark' : 'light'};
            ${variableBlock}
        }

        @media (prefers-color-scheme: dark) {
            :host {
                ${variableBlock}
            }
        }

        .picker {
            border: none !important;
            background: transparent !important;
            border-radius: 0 !important;
        }

        .favorites {
            display: none !important;
        }

        .pad-top {
            display: none !important;
        }

        .search-row {
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 0.75rem 0.625rem !important;
        }

        .search-wrapper {
            flex: 1;
            min-width: 0;
        }

        input.search {
            width: 100%;
            padding: 0.5rem 0.75rem !important;
            border: 1px solid var(--input-border-color) !important;
            border-radius: var(--input-border-radius) !important;
            background: ${searchBackground} !important;
            color: var(--input-font-color) !important;
            font-size: calc(var(--input-font-size) * 0.875) !important;
            line-height: 1.4 !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        input.search::placeholder {
            color: var(--input-placeholder-color) !important;
        }

        input.search:focus {
            outline: none !important;
            border-color: ${primaryColor} !important;
            box-shadow: 0 0 0 2px color-mix(in srgb, ${primaryColor} 20%, transparent) !important;
        }

        .nav-button:hover,
        .nav-button:active {
            background: var(--button-hover-background) !important;
        }

        .indicator-wrapper {
            border-bottom-color: ${navBorderColor} !important;
        }

        .indicator {
            background-color: var(--indicator-color) !important;
        }

        .category-name,
        .category {
            color: var(--category-font-color) !important;
        }
    `
}

function syncPickerTheme(picker, anchor, getPrimaryColor) {
    const isDark = resolveIsDark()
    const primaryColor = resolvePrimaryColor(anchor, getPrimaryColor)

    picker.classList.toggle('dark', isDark)
    picker.classList.toggle('light', ! isDark)
    applyPickerShadowStyles(picker, isDark, primaryColor)
}

function ensurePanel() {
    if (panel) {
        return
    }

    panel = document.createElement('div')
    panel.className = 'fff-emoji-picker__panel'
    panel.hidden = true
    panel.setAttribute('data-fff-emoji-picker-panel', '')

    loadingElement = document.createElement('div')
    loadingElement.className = 'fff-emoji-picker__loading'
    loadingElement.hidden = true
    loadingElement.setAttribute('aria-hidden', 'true')

    hostElement = document.createElement('div')
    hostElement.className = 'fff-emoji-picker__host'

    panel.appendChild(loadingElement)
    panel.appendChild(hostElement)
    document.body.appendChild(panel)
}

function setLoading(isLoading) {
    if (! loadingElement) {
        return
    }

    loadingElement.hidden = ! isLoading
}

function notifyReady(locale, ready) {
    const entry = pickersByLocale.get(locale)

    if (entry) {
        entry.ready = ready
    }

    if (activeSession?.locale === locale) {
        activeSession.onReadyChange?.(ready)
        setLoading(! ready)
    }
}

function getOrCreatePicker(locale) {
    const resolvedLocale = resolveLocale(locale)

    if (pickersByLocale.has(resolvedLocale)) {
        return pickersByLocale.get(resolvedLocale)
    }

    const picker = document.createElement('emoji-picker')
    picker.classList.add(PICKER_ELEMENT_CLASS)
    picker.setAttribute('locale', resolvedLocale)

    const entry = {
        locale: resolvedLocale,
        picker,
        ready: false,
    }

    pickersByLocale.set(resolvedLocale, entry)

    picker.addEventListener('emoji-click', (event) => {
        if (! activeSession) {
            return
        }

        activeSession.onSelect?.(event.detail.unicode)
        sharedEmojiPicker.close()
    })

    picker.database.ready()
        .then(() => {
            notifyReady(resolvedLocale, true)
        })
        .catch(() => {
            notifyReady(resolvedLocale, true)
        })

    return entry
}

function mountPicker(entry) {
    if (! hostElement) {
        return
    }

    if (entry.picker.parentElement !== hostElement) {
        hostElement.replaceChildren(entry.picker)
    }
}

function positionPanel(anchor) {
    if (! panel || ! anchor) {
        return
    }

    panel.hidden = false

    const anchorRect = anchor.getBoundingClientRect()
    const panelRect = panel.getBoundingClientRect()
    const gap = 8
    const viewportPadding = 8

    let top = anchorRect.top - panelRect.height - gap
    let left = anchorRect.left

    if (top < viewportPadding) {
        top = anchorRect.bottom + gap
    }

    if (left + panelRect.width > window.innerWidth - viewportPadding) {
        left = window.innerWidth - panelRect.width - viewportPadding
    }

    if (left < viewportPadding) {
        left = viewportPadding
    }

    panel.style.top = `${Math.round(top)}px`
    panel.style.left = `${Math.round(left)}px`
}

function isClickInsidePanel(event) {
    return panel?.contains(event.target)
}

function isClickInsideAnchor(event) {
    return activeSession?.anchor?.contains(event.target)
}

function handleDocumentPointerDown(event) {
    if (! activeSession) {
        return
    }

    if (isClickInsidePanel(event)) {
        return
    }

    if (isClickInsideAnchor(event)) {
        sharedEmojiPicker.close()
        event.preventDefault()

        return
    }

    sharedEmojiPicker.close()
}

function handleDocumentKeyDown(event) {
    if (event.key === 'Escape' && activeSession) {
        sharedEmojiPicker.close()
    }
}

function isScrollInsidePanel(event) {
    if (! panel) {
        return false
    }

    return event.composedPath().includes(panel)
}

function handleDocumentScroll(event) {
    if (! activeSession) {
        return
    }

    if (isScrollInsidePanel(event)) {
        return
    }

    sharedEmojiPicker.close()
}

function bindGlobalListeners() {
    if (listenersBound) {
        return
    }

    document.addEventListener('pointerdown', handleDocumentPointerDown, true)
    document.addEventListener('keydown', handleDocumentKeyDown)
    document.addEventListener('scroll', handleDocumentScroll, true)
    window.addEventListener('resize', handleWindowResize)
    listenersBound = true
}

function handleWindowResize() {
    if (activeSession?.anchor) {
        positionPanel(activeSession.anchor)
    }
}

export const sharedEmojiPicker = {
    preload(locale) {
        ensurePanel()
        getOrCreatePicker(locale)
    },

    isOpenFor(context) {
        return activeSession?.context === context
    },

    isReady(locale) {
        return pickersByLocale.get(resolveLocale(locale))?.ready ?? false
    },

    open({
        context,
        anchor,
        locale,
        getPrimaryColor,
        onSelect,
        onClose,
        onReadyChange,
    }) {
        ensurePanel()
        bindGlobalListeners()

        const resolvedLocale = resolveLocale(locale)
        const entry = getOrCreatePicker(resolvedLocale)

        if (activeSession && activeSession.context !== context) {
            activeSession.onClose?.()
        }

        activeSession = {
            context,
            anchor,
            locale: resolvedLocale,
            getPrimaryColor,
            onSelect,
            onClose,
            onReadyChange,
        }

        mountPicker(entry)
        syncPickerTheme(entry.picker, anchor, getPrimaryColor)
        setLoading(! entry.ready)
        onReadyChange?.(entry.ready)

        panel.hidden = false
        requestAnimationFrame(() => {
            positionPanel(anchor)
        })
    },

    close() {
        if (! activeSession) {
            return
        }

        const session = activeSession
        activeSession = null

        if (panel) {
            panel.hidden = true
        }

        setLoading(false)
        session.onClose?.()
    },

    syncTheme() {
        for (const entry of pickersByLocale.values()) {
            syncPickerTheme(entry.picker, activeSession?.anchor, activeSession?.getPrimaryColor)
        }
    },
}

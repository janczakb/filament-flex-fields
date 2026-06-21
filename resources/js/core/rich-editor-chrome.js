export function countWords(text) {
    const trimmed = String(text ?? '').trim()

    if (trimmed === '') {
        return 0
    }

    return trimmed.split(/\s+/).filter(Boolean).length
}

export function countCharacters(text) {
    let count = 0

    for (const _ of String(text ?? '')) {
        count += 1
    }

    return count
}

/**
 * @returns {{ characters: number, words: number }}
 */
export function countTextMetrics(text) {
    const raw = String(text ?? '')
    let characters = 0
    let words = 0
    let inWord = false
    let seenNonWhitespace = false

    for (const char of raw) {
        characters += 1

        if (/\s/u.test(char)) {
            if (inWord) {
                words += 1
                inWord = false
            }

            continue
        }

        seenNonWhitespace = true
        inWord = true
    }

    if (inWord) {
        words += 1
    }

    if (! seenNonWhitespace) {
        words = 0
    }

    return { characters, words }
}

export function countImagesMissingAlt(editor) {
    if (! editor?.state?.doc) {
        return 0
    }

    let missing = 0
    let sawImage = false

    editor.state.doc.descendants((node) => {
        if (node.type.name !== 'image') {
            return
        }

        sawImage = true

        const alt = String(node.attrs?.alt ?? '').trim()

        if (alt === '') {
            missing += 1
        }
    })

    return sawImage ? missing : 0
}

export function getEditorPlainText(editor) {
    const doc = editor?.state?.doc

    if (! doc) {
        return ''
    }

    if (typeof doc.textBetween === 'function') {
        return doc.textBetween(0, doc.content.size, '\n', '\n')
    }

    return editor.getText?.() ?? ''
}

export function isEmptyRichEditorState(state) {
    if (state == null) {
        return true
    }

    if (typeof state === 'string') {
        return state.trim() === ''
    }

    if (typeof state === 'object') {
        const content = state.content ?? []

        return content.length === 0
    }

    return true
}

export function resolveLimitState({ characters, words, minCharacters, maxCharacters, maxWords }) {
    const checks = []

    if (maxCharacters != null) {
        checks.push({ current: characters, limit: maxCharacters, type: 'characters', direction: 'max' })
    }

    if (minCharacters != null) {
        checks.push({ current: characters, limit: minCharacters, type: 'characters', direction: 'min' })
    }

    if (maxWords != null) {
        checks.push({ current: words, limit: maxWords, type: 'words', direction: 'max' })
    }

    let ratio = 0
    let status = 'ok'

    for (const check of checks) {
        if (check.direction === 'max') {
            const checkRatio = check.limit > 0 ? check.current / check.limit : 0
            ratio = Math.max(ratio, checkRatio)

            if (check.current > check.limit) {
                status = 'danger'
            } else if (checkRatio >= 0.9 && status !== 'danger') {
                status = 'warning'
            }
        }

        if (check.direction === 'min' && check.current < check.limit && status === 'ok') {
            status = 'warning'
        }
    }

    return { ratio, status }
}

export function shouldTrackRichEditorFooterStats(footerConfig) {
    return footerConfig.showWordCount
        || footerConfig.readingTime
        || footerConfig.minCharacters != null
        || footerConfig.maxCharacters != null
        || footerConfig.maxWords != null
        || footerConfig.altTextRequired
        || footerConfig.limitBehavior === 'hard'
}

export function shouldEnableRichEditorChromeSync(footerConfig) {
    return Boolean(footerConfig.autosave) || shouldTrackRichEditorFooterStats(footerConfig)
}

/**
 * @returns {{ characters: number, words: number, footerStats: string, footerLimitStatus: string }}
 */
export function buildRichEditorFooterMetrics(text, footerConfig) {
    const { characters, words } = countTextMetrics(text)
    const parts = []

    if (footerConfig.showWordCount || footerConfig.minCharacters != null || footerConfig.maxCharacters != null) {
        parts.push(
            (footerConfig.labels?.line ?? '')
                .replace('__CHARACTERS__', String(characters))
                .replace('__WORDS__', String(words)),
        )
    }

    if (footerConfig.readingTime) {
        const minutes = Math.max(1, Math.ceil(words / (footerConfig.wordsPerMinute || 200)))

        parts.push(
            (footerConfig.labels?.readingTime ?? '')
                .replace('__MINUTES__', String(minutes)),
        )
    }

    const limitState = resolveLimitState({
        characters,
        words,
        minCharacters: footerConfig.minCharacters,
        maxCharacters: footerConfig.maxCharacters,
        maxWords: footerConfig.maxWords,
    })

    return {
        characters,
        words,
        footerStats: parts.filter(Boolean).join(' · '),
        footerLimitStatus: limitState.status,
    }
}

export function createDebouncedScheduler(callback, delayMs = 400) {
    let timer = null

    return {
        schedule(...args) {
            clearTimeout(timer)
            timer = setTimeout(() => {
                timer = null
                callback(...args)
            }, delayMs)
        },
        cancel() {
            clearTimeout(timer)
            timer = null
        },
    }
}

export function createRafScheduler(callback) {
    let rafId = null

    return {
        schedule() {
            if (rafId !== null) {
                return
            }

            rafId = requestAnimationFrame(() => {
                rafId = null
                callback()
            })
        },
        cancel() {
            if (rafId === null) {
                return
            }

            cancelAnimationFrame(rafId)
            rafId = null
        },
    }
}

export function runWhenIdle(callback, timeoutMs = 2000) {
    if (typeof requestIdleCallback === 'function') {
        return requestIdleCallback(callback, { timeout: timeoutMs })
    }

    return setTimeout(callback, 0)
}

export function cancelIdleWork(handle) {
    if (handle == null) {
        return
    }

    if (typeof cancelIdleCallback === 'function') {
        cancelIdleCallback(handle)

        return
    }

    clearTimeout(handle)
}

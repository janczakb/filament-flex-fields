/**
 * Checkbox spark + title strike motion (tick draw is CSS :checked + dashoffset).
 * 13.5 → 4.5 (delay 0.2, duration 0.2) then clearProps — no CSS transition reverse.
 */

function cancelRowMotion(rowEl) {
    if (! rowEl) {
        return
    }

    if (typeof rowEl.getAnimations === 'function') {
        for (const animation of rowEl.getAnimations()) {
            animation.cancel()
        }
    }

    if (rowEl._fffStrikeRaf) {
        cancelAnimationFrame(rowEl._fffStrikeRaf)
        rowEl._fffStrikeRaf = 0
    }

    if (rowEl._fffLinesTweenRaf) {
        cancelAnimationFrame(rowEl._fffLinesTweenRaf)
        rowEl._fffLinesTweenRaf = 0
    }

    if (rowEl._fffLinesTimer) {
        clearTimeout(rowEl._fffLinesTimer)
        rowEl._fffLinesTimer = 0
    }

    if (rowEl._fffLinesClearTimer) {
        clearTimeout(rowEl._fffLinesClearTimer)
        rowEl._fffLinesClearTimer = 0
    }
}

function clearLinesOffset(rowEl) {
    rowEl?.style?.removeProperty?.('--checkbox-lines-offset')
}

/** Tween CSS var px length (no CSS transition — avoids reverse flash on clear). */
function tweenLinesOffset(rowEl, fromPx, toPx, durationMs, onDone) {
    const start = performance.now()

    const step = (now) => {
        const t = Math.min(1, (now - start) / durationMs)
        const value = fromPx + (toPx - fromPx) * t
        rowEl.style.setProperty('--checkbox-lines-offset', `${value}px`)

        if (t < 1) {
            rowEl._fffLinesTweenRaf = requestAnimationFrame(step)
        } else {
            rowEl._fffLinesTweenRaf = 0
            rowEl.style.setProperty('--checkbox-lines-offset', `${toPx}px`)
            onDone?.()
        }
    }

    rowEl._fffLinesTweenRaf = requestAnimationFrame(step)
}

export function animateCheckOn(rowEl, { reducedMotion = false, onStrikeComplete = null } = {}) {
    if (! rowEl) {
        return
    }

    cancelRowMotion(rowEl)

    if (reducedMotion) {
        rowEl.style.setProperty('--text-line-scale', '1')
        rowEl.style.setProperty('--text-x', '0px')
        clearLinesOffset(rowEl)
        onStrikeComplete?.()

        return
    }

    rowEl.style.setProperty('--text-line-scale', '0')
    rowEl.style.setProperty('--text-x', '0px')
    clearLinesOffset(rowEl)

    // Delay 0.2 → tween to 4.5px over 0.2 → clear (instant hide).
    rowEl._fffLinesTimer = window.setTimeout(() => {
        tweenLinesOffset(rowEl, 13.5, 4.5, 200, () => {
            clearLinesOffset(rowEl)
        })
        rowEl._fffLinesTimer = 0
    }, 200)

    const start = performance.now()
    const duration = 300

    const step = (now) => {
        const t = Math.min(1, (now - start) / duration)

        // Same keyframe shape as demo: scale to 1 + nudge, then settle x
        if (t <= 0.5) {
            const p = t / 0.5
            rowEl.style.setProperty('--text-line-scale', String(p))
            rowEl.style.setProperty('--text-x', `${2 * p}px`)
        } else {
            const p = (t - 0.5) / 0.5
            rowEl.style.setProperty('--text-line-scale', '1')
            rowEl.style.setProperty('--text-x', `${2 * (1 - p)}px`)
        }

        if (t < 1) {
            rowEl._fffStrikeRaf = requestAnimationFrame(step)
        } else {
            rowEl._fffStrikeRaf = 0
            rowEl.style.setProperty('--text-line-scale', '1')
            rowEl.style.setProperty('--text-x', '0px')
            onStrikeComplete?.()
        }
    }

    rowEl._fffStrikeRaf = requestAnimationFrame(step)
}

export function animateCheckOff(rowEl, { reducedMotion = false, onComplete = null } = {}) {
    if (! rowEl) {
        return
    }

    cancelRowMotion(rowEl)
    clearLinesOffset(rowEl)

    if (reducedMotion) {
        rowEl.style.setProperty('--text-line-scale', '0')
        rowEl.style.setProperty('--text-x', '0px')
        onComplete?.()

        return
    }

    const startScale = Number.parseFloat(rowEl.style.getPropertyValue('--text-line-scale'))
        || Number.parseFloat(getComputedStyle(rowEl).getPropertyValue('--text-line-scale'))
        || 1
    const start = performance.now()
    const duration = 250

    const step = (now) => {
        const t = Math.min(1, (now - start) / duration)
        rowEl.style.setProperty('--text-line-scale', String(startScale * (1 - t)))
        rowEl.style.setProperty('--text-x', '0px')

        if (t < 1) {
            rowEl._fffStrikeRaf = requestAnimationFrame(step)
        } else {
            rowEl._fffStrikeRaf = 0
            rowEl.style.setProperty('--text-line-scale', '0')
            onComplete?.()
        }
    }

    rowEl._fffStrikeRaf = requestAnimationFrame(step)
}

/** Soft hand strike, sparse gentle waves (viewBox 0 0 100 8). */
export function handScribblePath() {
    return 'M0 4.2 C6 2.2 12 6.0 18 3.0 C24 6.1 30 2.4 36 4.6 C42 6.3 48 2.7 54 4.4 C60 5.9 66 2.8 72 4.5 C78 5.8 86 3.0 93 4.2 L100 4.05'
}

export function handScribblePaths() {
    return [handScribblePath()]
}

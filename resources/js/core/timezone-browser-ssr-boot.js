/**
 * Blocking browser-timezone SSR paint (before Alpine x-load).
 *
 * Classic/IIFE entry (inlined in Blade): runs via document.currentScript.
 * ESM import (asset injector): exports only — no auto-run on import.
 *
 * Paints the *package catalog* city label (e.g. "Warsaw"), never Intl generic
 * names ("Central European Time") — those caused a visible ~1s swap after x-load.
 *
 * Bundled to resources/dist/core/timezone-browser-ssr-boot.js for inline shells.
 */

/**
 * @param {Element} boot
 * @returns {Record<string, [string, string]>}
 */
function parseInlineCatalog(boot) {
    try {
        const raw = JSON.parse(boot.getAttribute('data-fff-timezone-catalog') || '{}')

        if (raw && typeof raw === 'object' && ! Array.isArray(raw)) {
            return raw
        }
    } catch {
        // Fall through.
    }

    return {}
}

/**
 * Shared TimezoneRegistry template (CountryRegistry pattern) — preferred when
 * the full IANA list is not inlined on the boot element.
 *
 * @returns {Record<string, [string, string]>}
 */
function parseRegistryCatalog() {
    if (typeof document === 'undefined') {
        return {}
    }

    const elements = document.querySelectorAll('#fff-timezone-registry-data')
    const element = elements.length > 0 ? elements[elements.length - 1] : null

    if (! element) {
        return {}
    }

    const text = element.tagName === 'TEMPLATE'
        ? element.innerHTML.trim()
        : (element.textContent?.trim() ?? '')

    if (! text) {
        return {}
    }

    try {
        const registry = JSON.parse(text)
        const pool = registry?.pools?.iana ?? {}
        const catalog = {}

        for (const [id, compact] of Object.entries(pool)) {
            if (compact?.l) {
                catalog[id] = [String(compact.l), String(compact.o ?? '')]
            }
        }

        return catalog
    } catch {
        return {}
    }
}

/**
 * @param {Element} boot
 * @returns {Record<string, [string, string]>}
 */
function parseCatalog(boot) {
    const inline = parseInlineCatalog(boot)

    if (Object.keys(inline).length > 0) {
        return inline
    }

    return parseRegistryCatalog()
}

/**
 * @param {Record<string, [string, string]>} catalog
 * @param {string} id
 * @returns {{ label: string, offset: string } | null}
 */
function catalogEntry(catalog, id) {
    const row = catalog[id]

    if (! Array.isArray(row) || ! row[0]) {
        return null
    }

    return {
        label: String(row[0]),
        offset: String(row[1] ?? ''),
    }
}

/**
 * Paint the official catalog label into the SSR trigger slots.
 *
 * @param {Element | null | undefined} boot
 * @returns {boolean}
 */
export function bootTimezoneBrowserSsrElement(boot) {
    if (! boot || boot.getAttribute('data-fff-timezone-boot-done') === '1') {
        return false
    }

    const root = boot.closest?.('.fff-timezone-field')

    if (! root || root.dataset.fffTzBooted === '1') {
        boot.setAttribute('data-fff-timezone-boot-done', '1')

        return false
    }

    let detected = null

    try {
        detected = Intl.DateTimeFormat().resolvedOptions().timeZone || null
    } catch {
        detected = null
    }

    const catalog = parseCatalog(boot)
    const entry = detected ? catalogEntry(catalog, detected) : null

    if (! detected || ! entry) {
        boot.setAttribute('data-fff-timezone-boot-done', '1')

        return false
    }

    root.dataset.fffTzBooted = '1'
    root.dataset.fffDetectedTimezone = detected
    boot.setAttribute('data-fff-timezone-boot-done', '1')

    const ssrLabel = root.querySelector('.fff-timezone-field__ssr-label')

    if (ssrLabel) {
        ssrLabel.textContent = entry.label
        ssrLabel.classList.remove('is-placeholder')
        ssrLabel.removeAttribute('data-fff-tz-ssr-provisional')
    }

    const ssrMeta = root.querySelector('.fff-timezone-field__ssr-meta')

    if (ssrMeta) {
        ssrMeta.textContent = entry.offset
    }

    return true
}

/**
 * @param {ParentNode | Document | Element | null | undefined} scope
 */
export function bootTimezoneBrowserSsrDefaults(scope = document) {
    const roots = scope?.querySelectorAll?.('[data-fff-timezone-boot]') ?? []

    for (const boot of roots) {
        bootTimezoneBrowserSsrElement(boot)
    }
}

// Classic script / inlined IIFE only — ESM imports must not auto-run.
const classicScript = typeof document !== 'undefined' ? document.currentScript : null

if (classicScript) {
    const localBoot = classicScript.previousElementSibling?.matches?.('[data-fff-timezone-boot]')
        ? classicScript.previousElementSibling
        : classicScript.closest?.('.fff-timezone-field')?.querySelector?.('[data-fff-timezone-boot]')

    if (localBoot) {
        bootTimezoneBrowserSsrElement(localBoot)
    } else {
        bootTimezoneBrowserSsrDefaults(document)
    }
}

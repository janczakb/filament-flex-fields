/**
 * SelectField playground e2e helpers (headless combobox on /select-field).
 */

/**
 * Live trigger button. Inline-search puts `id` on the inner input, not the button.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} statePath e.g. select__basic
 */
export function selectTrigger(page, statePath) {
    return page
        .locator(`button.fi-select-input-btn[id$="${statePath}"]`)
        .or(page.locator(`button.fi-select-input-btn:has(input[id$="${statePath}"])`))
        .first()
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} statePath
 */
export function selectMenu(page, statePath) {
    return page.locator(`#form\\.${statePath}-fff-headless-menu.is-open`)
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} statePath
 * @param {{ force?: boolean, preferChevron?: boolean }} [opts]
 */
export async function openSelect(page, statePath, { force = false, preferChevron = false } = {}) {
    const { expect } = await import('@playwright/test')
    const trigger = selectTrigger(page, statePath)
    await expect(trigger).toBeVisible({ timeout: 15_000 })
    await trigger.scrollIntoViewIfNeeded()

    const menu = selectMenu(page, statePath)

    if (await menu.isVisible().catch(() => false)) {
        return { trigger, menu }
    }

    const tryOpen = async (fn) => {
        await fn()
        try {
            await menu.waitFor({ state: 'visible', timeout: 1_800 })

            return true
        } catch {
            return false
        }
    }

    const clickCenter = async () => trigger.click({ force })
    const clickChevron = async () => {
        const box = await trigger.boundingBox()

        if (! box) {
            await trigger.click({ force: true })

            return
        }

        await page.mouse.click(box.x + Math.max(8, box.width - 14), box.y + box.height / 2)
    }
    const openViaKeyboard = async () => {
        await trigger.focus()
        await page.keyboard.press('ArrowDown')
    }
    const focusInlineInput = async () => {
        const input = trigger.locator('input').first()

        if (await input.count()) {
            await input.click()
        } else {
            await trigger.click({ force: true })
        }
    }

    const sequence = preferChevron
        ? [clickChevron, clickCenter, openViaKeyboard, focusInlineInput]
        : [clickCenter, focusInlineInput, clickChevron, openViaKeyboard]

    for (const attempt of sequence) {
        if (await tryOpen(attempt)) {
            return { trigger, menu }
        }
    }

    await menu.waitFor({ state: 'visible', timeout: 8_000 })

    return { trigger, menu }
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} statePath
 * @param {string} value
 */
export async function pickOptionByValue(page, statePath, value) {
    const { expect } = await import('@playwright/test')
    const menu = selectMenu(page, statePath)
    const option = menu.locator(`.fi-select-input-option[data-value="${value}"]`).first()
    await expect(option).toBeVisible({ timeout: 12_000 })
    await option.click()
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} statePath
 * @param {RegExp|string} text
 */
export async function pickOptionByText(page, statePath, text) {
    const { expect } = await import('@playwright/test')
    const menu = selectMenu(page, statePath)
    const option = menu.locator('.fi-select-input-option').filter({ hasText: text }).first()
    await expect(option).toBeVisible({ timeout: 12_000 })
    await option.click()
}

/**
 * Field wrapper that owns the trigger + clear ×.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} statePath
 */
export function selectFieldWrap(page, statePath) {
    return page
        .locator(`.fi-input-wrp:has([id$="${statePath}"]), .fff-select-field:has([id$="${statePath}"]), .fi-fo-field-wrp:has([id$="${statePath}"])`)
        .first()
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} statePath
 */
export function selectTriggerCtn(page, statePath) {
    return selectFieldWrap(page, statePath).locator('.fi-select-input-ctn').first()
}

/**
 * Clear (×) — sibling of the live trigger (skip SSR stub: pointer-events:none).
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} statePath
 */
export async function clearSelectViaX(page, statePath) {
    const { expect } = await import('@playwright/test')
    // Prefer the live Alpine clear control; SSR stub keeps pointer-events:none.
    const clicked = await page.evaluate((path) => {
        const wraps = [
            ...document.querySelectorAll(`.fi-input-wrp:has([id$="${path}"]), .fff-select-field:has([id$="${path}"])`),
        ]

        for (const wrap of wraps) {
            const buttons = [...wrap.querySelectorAll('.fi-select-input-value-remove-btn')].filter(
                (el) => ! el.closest('.fff-select-trigger-ssr'),
            )

            for (const btn of buttons) {
                const style = window.getComputedStyle(btn)

                if (style.display === 'none' || style.visibility === 'hidden') {
                    continue
                }

                btn.click()

                return true
            }

            // Fallback: force-click first live clear even if Alpine x-show is mid-update
            if (buttons[0]) {
                buttons[0].click()

                return true
            }
        }

        return false
    }, statePath)

    expect(clicked, `Clear × missing for ${statePath}`).toBeTruthy()
}

/**
 * Read Livewire form data for a state path (playground Filament page).
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} statePath
 */
export async function readLivewireData(page, statePath) {
    return page.evaluate((path) => {
        const roots = [...document.querySelectorAll('[wire\\:id]')]

        for (const root of roots) {
            const id = root.getAttribute('wire:id')

            if (! id || ! window.Livewire) {
                continue
            }

            try {
                const component = window.Livewire.find(id)
                const data = component?.get?.('data')

                if (data && Object.prototype.hasOwnProperty.call(data, path)) {
                    return data[path]
                }

                const dotted = component?.get?.(`data.${path}`)

                if (dotted !== undefined) {
                    return dotted
                }
            } catch {
                // try next root
            }
        }

        return undefined
    }, statePath)
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} statePath
 * @param {string} query
 */
export async function typeSelectSearch(page, statePath, query) {
    const menu = selectMenu(page, statePath)
    const inline = selectTrigger(page, statePath).locator('input').first()
    const search = menu.locator('input[type="search"], input[type="text"]').first()

    if (await inline.count()) {
        await inline.fill(query)
        await inline.dispatchEvent('input')

        return
    }

    if (await search.count()) {
        await search.fill(query)
    }
}

/**
 * Clear inline / menu search so all options are visible again.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} statePath
 */
export async function clearSelectSearch(page, statePath) {
    await typeSelectSearch(page, statePath, '')
}

/**
 * @param {import('@playwright/test').Page} page
 */
export async function closeSelectMenu(page) {
    await page.keyboard.press('Escape')
    await page.waitForTimeout(120)
}

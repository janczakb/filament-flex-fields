import { expect, test } from '@playwright/test'
import { gotoPlaygroundPage } from './global-setup.mjs'
import { waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'

/**
 * Regression: Assignee (UserSelect single) showed the SSR user, then after
 * handoff stuck on "Select a user" because Alpine x-html snapshotted the
 * placeholder before the user repository was ready.
 */
test('Assignee keeps selected user after reload handoff', async ({ page }) => {
    test.setTimeout(60_000)

    await gotoPlaygroundPage(page, 'user-select')
    await waitForSelectCoordinatorAttached(page)
    await page.reload({ waitUntil: 'domcontentloaded' })

    const btn = page.locator('#form\\.user_select__single')
    await expect(btn).toBeVisible({ timeout: 15_000 })

    // Wait until SSR is replaced (hydrated trigger visible).
    await page.waitForFunction(() => {
        const shell = document.getElementById('form.user_select__single')
            ?.closest('.fff-select-field__shell')
        const ssr = shell?.querySelector('.fff-select-trigger-ssr')

        return ! ssr || ssr.classList.contains('is-replaced')
            || getComputedStyle(ssr).display === 'none'
    }, null, { timeout: 10_000 })

    // Sample visible hydrated label for a short window — must never be the empty placeholder
    // once handoff completed while state is still jane.
    const samples = []
    const deadline = Date.now() + 1500

    while (Date.now() < deadline) {
        const sample = await page.evaluate(() => {
            const btnEl = document.getElementById('form.user_select__single')
            const shell = btnEl?.closest('.fff-select-field__shell')
            const ctn = shell?.querySelector('.fi-select-input-ctn:not(.fff-select-trigger-ssr)')
            const root = btnEl?.closest('[x-data]')
            const data = root && window.Alpine?.$data?.(root)

            return {
                text: (ctn?.textContent || '').replace(/\s+/g, ' ').trim(),
                selected: data?.comboboxSelectedValues ?? null,
                displayReady: data?.displayReady ?? null,
            }
        })

        samples.push(sample)
        await page.waitForTimeout(50)
    }

    const afterHandoff = samples.filter((s) => s.displayReady === true)

    expect(afterHandoff.length).toBeGreaterThan(0)

    for (const sample of afterHandoff) {
        expect(sample.selected).toEqual(['jane'])
        expect(sample.text).not.toBe('Select a user')
        expect(sample.text.toLowerCase()).toContain('jane')
    }
})

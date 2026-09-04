import { test, expect } from '@playwright/test'
import { gotoPlaygroundPage } from './global-setup.mjs'
import { waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'

test('optionView trigger does not remount HTML on open', async ({ page }) => {
    await gotoPlaygroundPage(page, 'select-field')
    await waitForSelectCoordinatorAttached(page)

    const trigger = page.locator('[id$="select__custom_value_user"].fi-select-input-btn').first()
    await trigger.scrollIntoViewIfNeeded()

    await page.evaluate(() => {
        window.__fffMutations = []
        const btn = document.querySelector('[id$="select__custom_value_user"].fi-select-input-btn')
        const target = btn?.querySelector('.fi-select-input-value-label') || btn
        const obs = new MutationObserver((muts) => {
            for (const m of muts) {
                if (m.type === 'childList' && (m.addedNodes.length || m.removedNodes.length)) {
                    window.__fffMutations.push({
                        t: performance.now(),
                        text: target.textContent.replace(/\s+/g, ' ').trim(),
                    })
                }
            }
        })
        obs.observe(target, { childList: true, subtree: true })
        window.__fffObs = obs
    })

    await trigger.click()
    await expect(page.locator('#form\\.select__custom_value_user-fff-headless-menu.is-open')).toBeVisible()
    await page.waitForTimeout(400)

    const mutations = await page.evaluate(() => {
        window.__fffObs?.disconnect()
        return window.__fffMutations || []
    })

    expect(mutations, `Trigger remounted on open: ${JSON.stringify(mutations)}`).toEqual([])
})

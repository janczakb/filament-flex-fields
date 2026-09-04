import { test, expect } from '@playwright/test'
import { gotoPlaygroundPage } from './global-setup.mjs'
import { waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'

test('smart suggest does not show empty Recent section', async ({ page }) => {
    await gotoPlaygroundPage(page, 'select-field')
    await waitForSelectCoordinatorAttached(page)

    const trigger = page.locator('[id$="select__create_with_sections"].fi-select-input-btn').first()
    await trigger.scrollIntoViewIfNeeded()
    await trigger.click()

    const menu = page.locator('#form\\.select__create_with_sections-fff-headless-menu.is-open')
    await expect(menu).toBeVisible()

    const dom = await page.evaluate(() => {
        return [...document.querySelectorAll('#form\\.select__create_with_sections-fff-headless-menu .fff-select-headless-dropdown-row')].map((n) => ({
            type: n.getAttribute('data-row-type'),
            text: n.textContent.replace(/\s+/g, ' ').trim(),
        }))
    })

    const recentIndex = dom.findIndex((row) => row.type === 'section' && row.text === 'Recent')
    const suggestedIndex = dom.findIndex((row) => row.type === 'section' && row.text === 'Suggested')

    expect(recentIndex).toBeGreaterThanOrEqual(0)
    expect(suggestedIndex).toBeGreaterThan(recentIndex)

    const between = dom.slice(recentIndex + 1, suggestedIndex)
    expect(between.some((row) => row.type === 'option'), `Recent had no options: ${JSON.stringify(dom)}`).toBe(true)
    expect(between.map((row) => row.text).join(',')).toMatch(/Alpine|Livewire/i)
})

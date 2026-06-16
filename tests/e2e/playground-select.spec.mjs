import { test, expect } from '@playwright/test'

import { trackConsoleErrors, waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'

const playgroundPaths = ['/select-field', '/user-select']

const selectFieldSmokeTargets = [
    { label: 'basic status', selector: '[id$="select__basic"] .fi-select-input-btn' },
    { label: 'searchable', selector: '[id$="select__searchable"] .fi-select-input-btn' },
    { label: 'multiple', selector: '[id$="select__multiple"] .fi-select-input-btn' },
    { label: 'grid theme', selector: '[id$="select__grid"] .fi-select-input-btn' },
    { label: 'rich options', selector: '[id$="select__rich"] .fi-select-input-btn' },
]

const userSelectSmokeTargets = [
    { label: 'single assignee', selector: '[id$="user_select__single"] .fi-select-input-btn' },
    { label: 'multiple team', selector: '[id$="user_select__multiple"] .fi-select-input-btn' },
]

test.describe('Flex Fields playground select fields', () => {
    test.skip(! process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Set FLEX_FIELDS_PLAYGROUND_URL to run playground E2E')

    for (const path of playgroundPaths) {
        test(`${path} loads without JS errors and attaches coordinators`, async ({ page }) => {
            const { errors, assertClean } = trackConsoleErrors(page)

            await page.goto(path)

            await expect(page.locator('.fi-select-input-btn').first()).toBeVisible()
            await waitForSelectCoordinatorAttached(page)

            const attachedCount = await page.locator('.fff-select-field__shell[data-fff-select-attached="true"]').count()
            const shellCount = await page.locator('.fff-select-field__shell').count()

            expect(attachedCount).toBeGreaterThan(0)
            expect(attachedCount).toBe(shellCount)
            assertClean()
        })
    }

    test('select-field playground opens key variants without JS errors', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/select-field')
        await waitForSelectCoordinatorAttached(page)

        for (const target of selectFieldSmokeTargets) {
            const trigger = page.locator(target.selector).first()

            await expect(trigger, `Missing trigger for ${target.label}`).toBeVisible()
            await trigger.click()
            await expect(page.locator('.fff-select-dropdown-panel, .fi-dropdown-panel').first()).toBeVisible()
            await page.keyboard.press('Escape')
        }

        assertClean()
    })

    test('user-select playground opens single and multiple fields without JS errors', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/user-select')
        await waitForSelectCoordinatorAttached(page)

        for (const target of userSelectSmokeTargets) {
            const trigger = page.locator(target.selector).first()

            await expect(trigger, `Missing trigger for ${target.label}`).toBeVisible()
            await trigger.click()
            await expect(page.locator('.fff-select-dropdown-panel, .fi-dropdown-panel').first()).toBeVisible()
            await page.keyboard.press('Escape')
        }

        assertClean()
    })
})

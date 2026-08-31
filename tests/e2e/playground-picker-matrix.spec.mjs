import { test, expect } from '@playwright/test'

import { trackConsoleErrors, waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'

const playgroundPaths = [
    { path: '/phone-field', trigger: '.fff-phone-field .fi-select-input-btn, .fff-phone-field button[aria-haspopup]' },
    { path: '/country-field', trigger: '.fff-country-field .fi-select-input-btn, .fff-country-field button[aria-haspopup]' },
    { path: '/timezone-field', trigger: '.fff-timezone-field .fi-select-input-btn, .fff-timezone-field button[aria-haspopup]' },
    { path: '/icon-picker-field', trigger: '.fff-icon-picker .fi-select-input-btn' },
    { path: '/address-field', trigger: '.fff-address-field input[type="search"], .fff-address-field .fi-input' },
]

test.describe('Picker matrix fixture (always on)', () => {
    test('overlay matrix mobile sheet class toggles on narrow viewport', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 })
        await page.goto('/tests/e2e/fixtures/overlay-matrix.html')

        await page.locator('#overlay-trigger').click()

        const panel = page.locator('body > #overlay-panel')

        await expect(panel).toBeVisible()
        await expect(panel).toHaveClass(/fff-teleported-menu/)
    })
})

test.describe('Flex Fields playground picker matrix', () => {

    for (const target of playgroundPaths) {
        test(`${target.path} opens overlay without JS errors`, async ({ page }) => {
            const { assertClean } = trackConsoleErrors(page)

            await page.goto(target.path)

            const trigger = page.locator(target.trigger).first()

            await expect(trigger).toBeVisible({ timeout: 15000 })
            await trigger.click()

            await expect(
                page.locator('body > .fff-select-dropdown-panel, body > .fff-teleported-menu, body > .fi-dropdown-panel').first(),
            ).toBeVisible()

            await page.keyboard.press('Escape')
            assertClean()
        })
    }

    test('select close then reopen on mobile viewport', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 })

        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/select-field')
        await waitForSelectCoordinatorAttached(page)

        const trigger = page.locator('[id$="select__basic"] .fi-select-input-btn').first()

        await trigger.click()

        const panel = page.locator('body > .fff-select-dropdown-panel, body > .fff-teleported-menu').first()

        await expect(panel).toBeVisible()
        await page.keyboard.press('Escape')
        await trigger.click()
        await expect(panel).toBeVisible()

        assertClean()
    })
})

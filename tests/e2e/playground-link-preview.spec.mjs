import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'

test.describe('Flex Fields playground link preview', () => {

    test('link-preview-field loads without JS errors', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/link-preview-field')

        await expect(page.locator('.fff-link-preview').first()).toBeVisible()
        await expect(page.locator('.fff-flex-text-input__shell').first()).toBeVisible()

        assertClean()
    })

    test('link-preview-field shows preview card area for prefilled url', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/link-preview-field')

        const card = page.locator('.fff-link-preview__card').first()

        await expect(card).toBeVisible({ timeout: 15_000 })

        assertClean()
    })
})

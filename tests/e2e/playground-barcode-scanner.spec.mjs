import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'

test.describe('Flex Fields playground barcode scanner', () => {
    test.skip(! process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Set FLEX_FIELDS_PLAYGROUND_URL to run playground E2E')

    test('barcode-scanner-field loads without JS errors', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/barcode-scanner-field')

        await expect(page.locator('.fff-barcode-scanner').first()).toBeVisible()
        await expect(page.locator('.fff-flex-text-input__shell').first()).toBeVisible()
        await expect(page.locator('.fff-barcode-scanner__scan-btn').first()).toBeVisible()

        assertClean()
    })
})

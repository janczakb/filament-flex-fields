import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'

test.describe('Flex Fields playground calculator field', () => {

    test('calculator-field opens shared panel on trigger click', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/calculator-field', { waitUntil: 'networkidle' })

        if (page.url().includes('/login')) {
            await page.getByLabel(/email/i).fill(process.env.FLEX_FIELDS_PLAYGROUND_EMAIL ?? 'admin@wyachts.com')
            await page.locator('#password').fill(process.env.FLEX_FIELDS_PLAYGROUND_PASSWORD ?? 'password')
            await page.getByRole('button', { name: /sign in|log in/i }).click()
            await page.waitForURL(/flex-fields-playground\/calculator-field/, { timeout: 15_000 })
        }

        await expect(page.locator('.fff-calculator-field__trigger').first()).toBeVisible()
        await expect(page.locator('[data-fff-calculator-panel-host]')).toHaveCount(1)

        await page.locator('.fff-calculator-field__trigger').first().click()

        const panel = page.locator('body .fff-calculator-panel')
        await expect(panel).toBeVisible()
        await expect(panel).toHaveClass(/is-open/)
        await expect(panel.locator('.fff-calculator-panel__key').first()).toBeVisible()

        assertClean()
    })
})

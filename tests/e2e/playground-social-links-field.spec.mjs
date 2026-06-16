import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'

test.describe('Flex Fields playground social links field', () => {
    test.skip(! process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Set FLEX_FIELDS_PLAYGROUND_URL to run playground E2E')

    test('social-links-field loads without JS errors', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/social-links-field')

        await expect(page.locator('.fff-social-links').first()).toBeVisible()
        await expect(page.locator('.fff-social-links__add-trigger').first()).toBeVisible()

        assertClean()
    })

    test('social-links-field exposes accessible platform picker controls', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/social-links-field')

        const addTrigger = page.locator('.fff-social-links__add-trigger').first()

        await expect(addTrigger).toBeVisible()
        await expect(addTrigger).toHaveAttribute('aria-haspopup', 'listbox')
        await expect(page.locator('[role="listbox"]').first()).toHaveCount(0)

        assertClean()
    })
})

import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'

test.describe('Flex Fields playground schedule field', () => {
    test.skip(! process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Set FLEX_FIELDS_PLAYGROUND_URL to run playground E2E')

    test('schedule-field loads without JS errors', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/schedule-field')

        await expect(page.locator('.fff-schedule-field').first()).toBeVisible()
        await expect(page.locator('.fff-schedule-field__day-row').first()).toBeVisible()

        assertClean()
    })

    test('schedule-field exposes add slot controls for enabled days', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/schedule-field')

        const addSlotButton = page.locator('.fff-schedule-field__action-btn').filter({ hasText: /add slot/i }).first()

        await expect(addSlotButton).toBeVisible()
        await expect(addSlotButton).toHaveAttribute('aria-label', /.+/)

        assertClean()
    })
})

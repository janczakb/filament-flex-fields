import { test, expect } from '@playwright/test'

test.describe('FFART asset modal-only uninstall', () => {
    test.skip(!process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Requires FLEX_FIELDS_PLAYGROUND_URL')

    test('REQ-4: modal-only CSS is gone after modal close', async ({ page }) => {
        const baseUrl = process.env.FLEX_FIELDS_PLAYGROUND_URL
        await page.goto(`${baseUrl}/switch`)

        const switchCssCount = async () => page.locator('link[href*="flex-fields-switch"]').count()

        const modalTrigger = page.locator('button', { hasText: /modal|action/i }).first()

        if (await modalTrigger.count()) {
            await modalTrigger.click()
            await page.waitForSelector('.fi-modal.fi-modal-open')
            expect(await switchCssCount()).toBeGreaterThanOrEqual(1)

            await page.keyboard.press('Escape')
            await page.waitForSelector('.fi-modal.fi-modal-open', { state: 'detached' }).catch(() => {})

            expect(await switchCssCount()).toBe(0)
        }
    })
})

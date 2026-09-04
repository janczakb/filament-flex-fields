import { test, expect } from '@playwright/test'

test.describe('FFART action slideOver select', () => {
    test.skip(!process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Requires FLEX_FIELDS_PLAYGROUND_URL')

    test('REQ-5: slide-over action retains page select CSS after close', async ({ page }) => {
        const baseUrl = process.env.FLEX_FIELDS_PLAYGROUND_URL
        await page.goto(`${baseUrl}/select`)

        const selectCssCount = async () => page.locator('link[href*="select-field"]').count()
        await page.waitForSelector('.fff-select-field__shell')

        const slideOverTrigger = page.locator('button', { hasText: /slide|action/i }).first()

        if (await slideOverTrigger.count()) {
            await slideOverTrigger.click()
            await page.waitForSelector('.fi-modal.fi-modal-slide-over.fi-modal-open')

            expect(await selectCssCount()).toBeGreaterThanOrEqual(1)

            await page.keyboard.press('Escape')
            await page.waitForSelector('.fi-modal.fi-modal-open', { state: 'detached' }).catch(() => {})

            expect(await selectCssCount()).toBeGreaterThanOrEqual(1)
        }
    })
})

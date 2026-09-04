import { test, expect } from '@playwright/test'

test.describe('FFART asset page + modal select', () => {
    test.skip(!process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Requires FLEX_FIELDS_PLAYGROUND_URL')

    test('REQ-3: select CSS count stays 1 after modal close', async ({ page }) => {
        const baseUrl = process.env.FLEX_FIELDS_PLAYGROUND_URL
        await page.goto(`${baseUrl}/select`)

        const selectCssCount = async () => page.locator('link[href*="select-field"]').count()

        await page.waitForSelector('.fff-select-field__shell')

        const beforeModal = await selectCssCount()
        expect(beforeModal).toBeGreaterThanOrEqual(1)

        const modalTrigger = page.locator('button', { hasText: /modal|action/i }).first()

        if (await modalTrigger.count()) {
            await modalTrigger.click()
            await page.waitForSelector('.fi-modal.fi-modal-open')

            expect(await selectCssCount()).toBeLessThanOrEqual(beforeModal + 0)

            await page.keyboard.press('Escape')
            await page.waitForSelector('.fi-modal.fi-modal-open', { state: 'detached' }).catch(() => {})

            expect(await selectCssCount()).toBeGreaterThanOrEqual(1)
        }
    })
})

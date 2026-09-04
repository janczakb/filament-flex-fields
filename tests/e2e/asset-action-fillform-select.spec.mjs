import { test, expect } from '@playwright/test'

test.describe('FFART action fillForm select', () => {
    test.skip(!process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Requires FLEX_FIELDS_PLAYGROUND_URL')

    test('fillForm modal close retains page select assets', async ({ page }) => {
        const baseUrl = process.env.FLEX_FIELDS_PLAYGROUND_URL
        await page.goto(`${baseUrl}/select`)

        const selectCssCount = async () => page.locator('link[href*="select-field"]').count()
        await page.waitForSelector('.fff-select-field__shell')
        const before = await selectCssCount()

        const fillTrigger = page.locator('button', { hasText: /fill|edit|action/i }).first()

        if (await fillTrigger.count()) {
            await fillTrigger.click()
            await page.waitForSelector('.fi-modal.fi-modal-open')

            await page.keyboard.press('Escape')
            await page.waitForSelector('.fi-modal.fi-modal-open', { state: 'detached' }).catch(() => {})

            expect(await selectCssCount()).toBeGreaterThanOrEqual(before)
        }
    })
})

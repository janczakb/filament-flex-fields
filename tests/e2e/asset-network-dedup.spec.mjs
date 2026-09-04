import { test, expect } from '@playwright/test'

test.describe('FFART asset network dedup', () => {
    test.skip(!process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Requires FLEX_FIELDS_PLAYGROUND_URL')

    test('REQ-1: select-field assets fetched at most once per URL', async ({ page }) => {
        const baseUrl = process.env.FLEX_FIELDS_PLAYGROUND_URL
        const seen = new Map()

        page.on('request', (request) => {
            const url = request.url()

            if (!url.includes('filament-flex-fields')) {
                return
            }

            if (!url.includes('select-field') && !url.includes('teleported-menu')) {
                return
            }

            seen.set(url, (seen.get(url) ?? 0) + 1)
        })

        await page.goto(`${baseUrl}/select`)
        await page.waitForSelector('.fff-select-field__shell')

        const modalTrigger = page.locator('button', { hasText: /modal|action/i }).first()

        if (await modalTrigger.count()) {
            await modalTrigger.click()
            await page.waitForSelector('.fi-modal.fi-modal-open')
            await page.keyboard.press('Escape')
        }

        for (const [url, count] of seen.entries()) {
            expect(count, url).toBeLessThanOrEqual(1)
        }
    })
})

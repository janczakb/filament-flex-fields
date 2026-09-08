import { test, expect } from '@playwright/test'

test.describe('Select overlay teardown on modal / slide-over close', () => {
    test.skip(!process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Requires FLEX_FIELDS_PLAYGROUND_URL')

    test('closing a modal with an open select leaves no stuck teleported menu or body scroll lock', async ({ page }) => {
        const baseUrl = process.env.FLEX_FIELDS_PLAYGROUND_URL
        await page.goto(`${baseUrl}/select-field`)

        await page.waitForSelector('.fff-select-field__shell')

        const modalTrigger = page.locator('button', { hasText: /modal|open/i }).first()

        if ((await modalTrigger.count()) === 0) {
            test.skip(true, 'No modal trigger on this playground host')
        }

        await modalTrigger.click()
        await page.waitForSelector('.fi-modal-open')

        const trigger = page.locator('.fi-modal-open .fff-select-field__shell').first()

        if ((await trigger.count()) === 0) {
            test.skip(true, 'No select inside open modal')
        }

        await trigger.click()
        await page.waitForSelector('body > .fff-select-headless-menu.is-open, body > .fff-teleported-menu.is-open', {
            timeout: 5_000,
        }).catch(() => {})

        await page.keyboard.press('Escape')
        await page.waitForSelector('.fi-modal-open', { state: 'detached' }).catch(() => {})

        await expect(page.locator('body > .fff-select-headless-menu.is-open')).toHaveCount(0)
        await expect(page.locator('body > .fff-teleported-menu.is-open')).toHaveCount(0)
        await expect(page.locator('html')).not.toHaveClass(/fff-overlay-scroll-locked/)
        await expect(page.locator('body')).not.toHaveClass(/fff-overlay-scroll-locked/)
    })

    test('closing a slide-over with an open select leaves no stuck overlay', async ({ page }) => {
        const baseUrl = process.env.FLEX_FIELDS_PLAYGROUND_URL
        await page.goto(`${baseUrl}/select`)

        const slideOverTrigger = page.locator('button', { hasText: /slide|action/i }).first()

        if ((await slideOverTrigger.count()) === 0) {
            test.skip(true, 'No slide-over trigger')
        }

        await slideOverTrigger.click()
        await page.waitForSelector('.fi-modal.fi-modal-slide-over.fi-modal-open')

        const shell = page.locator('.fi-modal-slide-over.fi-modal-open .fff-select-field__shell').first()

        if ((await shell.count()) > 0) {
            await shell.click()
            await page.waitForTimeout(300)
        }

        await page.keyboard.press('Escape')
        await page.waitForSelector('.fi-modal.fi-modal-open', { state: 'detached' }).catch(() => {})

        await expect(page.locator('body > .fff-select-headless-menu.is-open')).toHaveCount(0)
        await expect(page.locator('html')).not.toHaveClass(/fff-overlay-scroll-locked/)
    })
})

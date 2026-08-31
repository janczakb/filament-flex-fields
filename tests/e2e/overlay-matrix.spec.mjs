import { test, expect } from '@playwright/test'

const fixturePath = '/tests/e2e/fixtures/overlay-matrix.html'

test.describe('Overlay matrix fixture (no playground URL required)', () => {
    test('teleported menu close then reopen stays visible', async ({ page }) => {
        await page.goto(fixturePath)

        const trigger = page.locator('#overlay-trigger')
        const panel = page.locator('body > #overlay-panel')

        await trigger.click()
        await expect(panel).toBeVisible()
        await expect(panel).toHaveClass(/is-open/)

        await page.keyboard.press('Escape')
        await trigger.click()
        await expect(panel).toBeVisible()
        await expect(panel).toHaveClass(/is-open/)
    })

    test('rapid reopen control keeps teleported panel mounted', async ({ page }) => {
        await page.goto(fixturePath)

        await page.locator('#overlay-trigger').click()
        await page.locator('#overlay-reopen').click()

        const panel = page.locator('body > #overlay-panel')

        await expect(panel).toBeVisible()
        await expect(page.locator('#overlay-reopen-count')).toHaveText('1')
    })

    test('modal content teleports to body portal', async ({ page }) => {
        await page.goto(fixturePath)

        await page.locator('#modal-open').click()

        await expect(page.locator('body > #modal-backdrop')).toBeVisible()
        await expect(page.locator('body > #modal-panel')).toBeVisible()

        await page.locator('#modal-close').click()

        await expect(page.locator('body > #modal-panel')).toBeHidden()
    })
})

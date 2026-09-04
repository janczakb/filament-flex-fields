import { test, expect } from '@playwright/test'
import { gotoPlaygroundPage } from './global-setup.mjs'
import { waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'

test.describe('SelectField dependsOn cascade', () => {
    test('Region stays clickable while Country live() is in flight', async ({ page }) => {
        test.setTimeout(90_000)

        await gotoPlaygroundPage(page, 'select-field')
        await waitForSelectCoordinatorAttached(page)

        const country = page.locator('[id$="select__cascade_country"].fi-select-input-btn').first()
        const region = page.locator('[id$="select__cascade_region"].fi-select-input-btn').first()
        await region.scrollIntoViewIfNeeded()

        // Warm Region once so Alpine/Livewire mixin is attached.
        await region.click()
        await page.keyboard.press('Escape')
        await page.waitForTimeout(200)

        let blockedSamples = 0
        let firstOpenAt = null

        await country.click()
        await page
            .locator('#form\\.select__cascade_country-fff-headless-menu.is-open .fi-select-input-option')
            .filter({ hasText: /Poland/i })
            .first()
            .click()

        const t0 = Date.now()

        while (Date.now() - t0 < 2500) {
            const elapsed = Date.now() - t0
            let clickOk = false

            try {
                await region.click({ timeout: 80, trial: true })
                clickOk = true
            } catch {
                clickOk = false
                blockedSamples += 1
            }

            if (clickOk && firstOpenAt == null) {
                try {
                    await region.click({ timeout: 200 })
                    const open = await page.locator('#form\\.select__cascade_region-fff-headless-menu.is-open').count()

                    if (open > 0) {
                        firstOpenAt = elapsed
                        break
                    }
                } catch {
                    // keep polling
                }
            }

            await page.waitForTimeout(50)
        }

        // Full-page morph used to freeze Region for ~2s. With skipRender + no
        // background getOptionsForJs, clicks must succeed immediately.
        expect(blockedSamples, `Region trial-clicks failed ${blockedSamples} times`).toBeLessThan(3)
        expect(firstOpenAt, 'Region menu never opened').not.toBeNull()
        expect(firstOpenAt).toBeLessThan(800)
    })
})

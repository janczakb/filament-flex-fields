import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'
import { ensurePlaygroundAuthenticated, playgroundUrl } from './helpers/playground-auth.mjs'

/** Keep in sync with ShowreelMode::hubOrder() in PHP. */
const v3Hubs = [
    'schema-conditions',
    'field-intelligence',
    'composition-recipes',
    'select-field',
    'phone-field',
    'country-field',
    'date-time-fields',
    'schedule-field',
    'barcode-scanner-field',
    'choice-cards',
    'segment-tabs',
    'form-layouts',
    'admin-columns',
    'hold-confirm',
    'user-column',
]

const hubExpectations = {
    'schema-conditions': '.fff-playground-toolbar, [data-fff-related-hubs]',
    'composition-recipes': '.fff-playground-toolbar',
    'select-field': '.fff-select-field, .fff-headless-select',
    'admin-columns': '.fff-progress-column, .fff-rating-column',
    'hold-confirm': '[data-fff-hold-confirm]',
    'user-column': '.fff-user-column',
}

test.describe('Flex Fields v3 playground hubs', () => {

    for (const slug of v3Hubs) {
        test(`${slug} loads without JS errors`, async ({ page }) => {
            const { assertClean } = trackConsoleErrors(page)

            await page.goto(playgroundUrl(slug))
            await ensurePlaygroundAuthenticated(page)
            await page.goto(playgroundUrl(slug))

            await expect(page.locator('body')).toBeVisible()
            await expect(page.locator('.fi-main, .fi-body, main').first()).toBeVisible({ timeout: 30_000 })

            const selector = hubExpectations[slug]

            if (selector) {
                await expect(page.locator(selector).first()).toBeVisible({ timeout: 15_000 })
            }

            assertClean()
        })
    }
})

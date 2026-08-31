import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'
import { ensurePlaygroundAuthenticated, playgroundUrl } from './helpers/playground-auth.mjs'

const v3Hubs = [
    'field-intelligence',
]

test.describe('Flex Fields v3 playground hubs', () => {

    for (const slug of v3Hubs) {
        test(`${slug} loads without JS errors`, async ({ page }) => {
            const { assertClean } = trackConsoleErrors(page)

            await page.goto(playgroundUrl(slug))
            await ensurePlaygroundAuthenticated(page)
            await page.goto(playgroundUrl(slug))

            await expect(page.locator('body')).toBeVisible()
            await expect(page.locator('.fi-main, .fi-body, main').first()).toBeVisible({ timeout: 30_000 })

            assertClean()
        })
    }
})

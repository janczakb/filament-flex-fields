import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'
import { ensurePlaygroundAuthenticated, playgroundUrl } from './helpers/playground-auth.mjs'

const playgroundSlug = 'file-upload'

/**
 * @param {import('@playwright/test').Page} page
 * @param {{ buttonLabel: RegExp | string, headingPattern: RegExp | string, expectSlideOver: boolean }} options
 */
async function assertSkeletonInjectorInOverlay(page, { buttonLabel, headingPattern, expectSlideOver }) {
    await page.evaluate(() => window.FffSkeletonDemo?.enable())

    await Promise.all([
        page.waitForFunction(() => document.querySelector('.fi-modal.fff-flex-fields-assets-pending') !== null, null, {
            timeout: 10_000,
        }),
        page.getByRole('button', { name: buttonLabel }).click(),
    ])

    const modal = page.locator('[role="dialog"].fi-modal').filter({
        has: page.getByRole('heading', { name: headingPattern }),
    })

    await expect(modal).toHaveClass(/fff-flex-fields-assets-pending/)

    if (expectSlideOver) {
        await expect(modal).toHaveClass(/fi-modal-slide-over/)
    } else {
        await expect(modal).not.toHaveClass(/fi-modal-slide-over/)
    }

    await expect(modal.locator('.fff-schedule-field').first()).toBeVisible({ timeout: 15_000 })
    await expect(modal.locator('.fff-barcode-scanner').first()).toBeVisible({ timeout: 15_000 })

    await expect(modal).toHaveClass(/fff-flex-fields-assets-ready/, { timeout: 15_000 })
    await expect(modal).not.toHaveClass(/fff-flex-fields-assets-pending/)

    await page.keyboard.press('Escape')
    await expect(modal).not.toHaveClass(/fff-flex-fields-assets-pending/, { timeout: 10_000 })

    await expect(page.locator('.fff-flex-fields-assets-pending:not(.fi-modal)')).toHaveCount(0)
}

test.describe('Flex Fields playground skeleton asset injector', () => {
    test.skip(! process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Set FLEX_FIELDS_PLAYGROUND_URL to run playground E2E')

    test.beforeEach(async ({ page }) => {
        await page.goto(playgroundUrl(playgroundSlug), { waitUntil: 'domcontentloaded' })
        await ensurePlaygroundAuthenticated(page)
        await expect(page.getByRole('button', { name: /1\. Enable slow CSS demo/i })).toBeVisible()
    })

    test('modal and slide-over show pending skeleton, styled lazy fields, and cleanup', async ({ page }) => {
        const { errors } = trackConsoleErrors(page)

        await assertSkeletonInjectorInOverlay(page, {
            buttonLabel: /3\. Open skeleton demo slide-over/i,
            headingPattern: /Skeleton loading demo \(slide-over\)/i,
            expectSlideOver: true,
        })

        await assertSkeletonInjectorInOverlay(page, {
            buttonLabel: /2\. Open skeleton demo modal/i,
            headingPattern: /^Skeleton loading demo$/i,
            expectSlideOver: false,
        })

        const relevantErrors = errors.filter((message) => ! message.includes('Livewire Entangle Error'))

        if (relevantErrors.length > 0) {
            throw new Error(`Unexpected console errors:\n${relevantErrors.join('\n')}`)
        }
    })
})

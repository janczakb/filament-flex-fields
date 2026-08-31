import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'
import { gotoPlaygroundPage } from './helpers/playground-auth.mjs'

test.describe('Media capture playgrounds', () => {
    test('file-upload hub loads flex file upload shell', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, 'file-upload')

        await expect(page.locator('.fff-flex-file-upload').first()).toBeVisible({ timeout: 15_000 })

        const sourceTabs = page.locator('.fff-flex-file-upload__source-tabs')

        if (await sourceTabs.count()) {
            await sourceTabs.first().scrollIntoViewIfNeeded()
            await expect(sourceTabs.first()).toBeVisible()
        }

        assertClean()
    })

    test('file-upload URL tab rejects unsafe localhost import when source tabs are enabled', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, 'file-upload')

        const sourceTabs = page.locator('.fff-flex-file-upload__source-tabs')

        if (await sourceTabs.count() === 0) {
            test.skip(true, 'Upload source tabs are not enabled on this playground build')

            return
        }

        const sourcesShell = sourceTabs.first().locator('xpath=ancestor::div[contains(@class, "fff-flex-file-upload")]')

        const urlTab = sourceTabs.first().locator('[role="tab"]').filter({ hasText: /url/i }).first()
        await urlTab.click()

        const urlInput = sourcesShell.locator('.fff-flex-file-upload__url-input input, .fff-flex-file-upload__url-input').first()
        await urlInput.fill('http://127.0.0.1/private.jpg')

        const importButton = sourcesShell.locator('.fff-flex-file-upload__url-import, button').filter({ hasText: /import|fetch|load/i }).first()

        if (await importButton.count()) {
            await importButton.click()
            await expect(sourcesShell.locator('.fi-fo-field-wrp-error-message, .fff-flex-file-upload__url-error, [data-validation-error]').first()).toBeVisible({ timeout: 10_000 })
        }

        assertClean()
    })

    test('voice-note-recorder-field hub loads recorder UI', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, 'voice-note-recorder-field')

        const recorder = page.locator('.fff-voice-recorder').first()
        await expect(recorder).toBeVisible({ timeout: 15_000 })
        await expect(recorder.locator('button, [role="button"]').first()).toBeVisible()

        assertClean()
    })

    test('signature-field hub loads pad without server error', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, 'signature-field')

        await expect(page.locator('.fff-signature-field').first()).toBeVisible()
        await expect(page.locator('canvas, .fff-signature__pad, [x-data*="signatureField"]').first()).toBeVisible()

        assertClean()
    })

    test('signature pdf preview toolbar uses icon button without visible label text', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, 'signature-field')

        const pdfPreviewButton = page.getByRole('button', { name: /preview document/i }).first()

        if (await pdfPreviewButton.count() === 0) {
            test.skip(true, 'No pdfPreview() demo on this playground build')

            return
        }

        await pdfPreviewButton.scrollIntoViewIfNeeded()
        await expect(pdfPreviewButton.locator('.fff-signature__action-icon svg, .fff-signature__action-icon .fi-icon').first()).toBeVisible()
        await expect(pdfPreviewButton.locator('.fff-signature__action-label')).toHaveCount(0)

        assertClean()
    })

    test('credit-card playground loads payment inputs', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, 'credit-card')

        const card = page.locator('.fff-credit-card-field, .fff-credit-card').first()
        await expect(card).toBeVisible({ timeout: 15_000 })
        await expect(card.locator('.fff-credit-card__input').first()).toBeVisible()
        await expect(card.locator('.fff-credit-card__field-label').filter({ hasText: /number|card/i }).first()).toBeVisible()

        assertClean()
    })
})

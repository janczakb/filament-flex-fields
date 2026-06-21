import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'

test.describe('Flex Fields playground flex rich editor', () => {
    test.skip(! process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Set FLEX_FIELDS_PLAYGROUND_URL to run playground E2E')

    test('flex-rich-editor loads toolbar, editor shell, and footer without JS errors', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/flex-rich-editor')

        const editor = page.locator('.fff-rich-editor').first()

        await expect(editor).toBeVisible()
        await expect(editor.locator('.fff-rich-editor__toolbar[role="toolbar"]')).toBeVisible()
        await expect(editor.locator('.fff-rich-editor__content[role="textbox"]')).toBeVisible()
        await expect(editor.locator('.fff-rich-editor__footer-stats[aria-live="polite"]')).toBeVisible()

        await expect(editor.locator('.ProseMirror')).toBeVisible({ timeout: 15_000 })

        assertClean()
    })

    test('flex-rich-editor toolbar supports keyboard roving focus', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/flex-rich-editor')

        const toolbar = page.locator('.fff-rich-editor').first().locator('.fff-rich-editor__toolbar')
        const firstTool = toolbar.locator('.fi-fo-rich-editor-tool').first()

        await expect(firstTool).toBeVisible({ timeout: 15_000 })
        await firstTool.focus()

        await page.keyboard.press('ArrowRight')

        const focusedTag = await page.evaluate(() => document.activeElement?.className ?? '')

        expect(focusedTag).toContain('fi-fo-rich-editor-tool')

        assertClean()
    })

    test('flex-rich-editor updates live word stats while typing', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/flex-rich-editor')

        const proseMirror = page.locator('.fff-rich-editor .ProseMirror').first()
        const stats = page.locator('.fff-rich-editor__footer-stats').first()

        await expect(proseMirror).toBeVisible({ timeout: 15_000 })
        await proseMirror.click()
        await page.keyboard.type('Top tier editor benchmark sentence.')

        await expect(stats).toContainText(/characters/i, { timeout: 5_000 })
        await expect(stats).toContainText(/words/i)

        assertClean()
    })

    test('flex-rich-editor exposes youtube toolbar control when enabled', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await page.goto('/flex-rich-editor')

        const youtubeButton = page.locator('.fff-rich-editor .fi-fo-rich-editor-tool[aria-label*="YouTube"], .fff-rich-editor .fi-fo-rich-editor-tool[aria-label*="youtube"]').first()

        await expect(youtubeButton).toBeVisible({ timeout: 15_000 })

        assertClean()
    })
})

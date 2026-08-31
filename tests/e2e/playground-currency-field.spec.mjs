import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'
import { playgroundUrl } from './helpers/playground-auth.mjs'

async function openCurrencyPlayground(page) {
    await page.goto(playgroundUrl('currency-field'), { waitUntil: 'networkidle' })

    if (page.url().includes('/login')) {
        await page.getByLabel(/email/i).fill(process.env.FLEX_FIELDS_PLAYGROUND_EMAIL ?? 'admin@wyachts.com')
        await page.locator('#password').fill(process.env.FLEX_FIELDS_PLAYGROUND_PASSWORD ?? 'password')
        await page.getByRole('button', { name: /sign in|log in/i }).click()
        await page.waitForURL(/flex-fields-playground\/currency-field/, { timeout: 20_000 })
    }

    await expect(page.locator('.fff-currency-field').first()).toBeVisible({ timeout: 30_000 })
    await expect(page.locator('.fff-currency-field__live-display.is-ready').first()).toBeVisible({ timeout: 15_000 })
    await page.waitForFunction(() => window.Livewire && window.Alpine, null, { timeout: 15_000 })
}

function fieldByLabel(page, label) {
    return page.locator('.fi-fo-field').filter({ has: page.locator('label', { hasText: label }) }).locator('.fff-currency-field').first()
}

async function caretHostWidths(field) {
    return field.locator('.fff-currency-field__caret').evaluateAll((nodes) => {
        return nodes.map((node) => {
            const style = window.getComputedStyle(node)

            return {
                width: node.getBoundingClientRect().width,
                cssWidth: style.width,
            }
        })
    })
}

test.describe('Flex Fields playground currency field', () => {

    test('empty field focuses on click, types decimals, caret stays layout-neutral', async ({ page }) => {
        const tracker = trackConsoleErrors(page)

        await openCurrencyPlayground(page)

        const field = fieldByLabel(page, /^Empty$/)
        await expect(field).toBeVisible()

        const control = field.locator('.fff-currency-field__control')
        const hidden = field.locator('.fff-currency-field__hidden-input')
        const live = field.locator('.fff-currency-field__live-display')
        const digits = field.locator('.fff-currency-field__digits')

        await control.click()
        await expect(hidden).toBeFocused()
        await expect(field).toHaveClass(/is-focused/)

        const widthBefore = await digits.evaluate((el) => el.getBoundingClientRect().width)

        await page.keyboard.type('1234')
        await expect(live).toContainText(/1/)

        const boxesWhileTyping = await caretHostWidths(field)
        expect(boxesWhileTyping.some((box) => box.cssWidth === '0px' || box.width === 0)).toBeTruthy()

        await page.keyboard.type(',')
        await page.keyboard.type('56')
        await expect(live).toContainText(/5/)

        const widthAfter = await digits.evaluate((el) => el.getBoundingClientRect().width)
        expect(widthAfter).toBeGreaterThan(widthBefore)

        const liveBox = await live.boundingBox()
        expect(liveBox).not.toBeNull()

        await page.mouse.click(liveBox.x + Math.min(12, liveBox.width / 4), liveBox.y + liveBox.height / 2)
        await expect(hidden).toBeFocused()

        await page.keyboard.press('ArrowRight')
        await page.keyboard.press('ArrowLeft')

        const caretAfterNav = await caretHostWidths(field)
        expect(caretAfterNav.every((box) => box.width === 0 || box.cssWidth === '0px')).toBeTruthy()

        const meaningful = tracker.errors.filter((message) => ! /\$wire is not defined/i.test(message))
        expect(meaningful, meaningful.join('\n')).toEqual([])
    })

    test('pln amount field accepts click and keyboard without console errors', async ({ page }) => {
        const tracker = trackConsoleErrors(page)

        await openCurrencyPlayground(page)

        const pln = fieldByLabel(page, /Amount \(PLN\)/)
        await expect(pln).toBeVisible()

        await pln.locator('.fff-currency-field__control').click()
        await expect(pln.locator('.fff-currency-field__hidden-input')).toBeFocused()

        await page.keyboard.press('End')
        await page.keyboard.press('Backspace')
        await page.keyboard.type('5')

        await expect(pln.locator('.fff-currency-field__live-display.is-ready')).toBeVisible()

        const meaningful = tracker.errors.filter((message) => ! /\$wire is not defined/i.test(message))
        expect(meaningful, meaningful.join('\n')).toEqual([])
    })
})

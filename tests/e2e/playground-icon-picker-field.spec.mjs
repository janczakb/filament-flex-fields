import { test, expect } from '@playwright/test'

import { trackConsoleErrors } from './helpers/console-errors.mjs'
import { playgroundUrl } from './helpers/playground-auth.mjs'

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} slug
 */
async function gotoPlaygroundPage(page, slug) {
    const url = playgroundUrl(slug)

    await page.goto(url)

    if (page.url().includes('/login')) {
        await page.getByLabel(/email/i).fill(process.env.FLEX_FIELDS_PLAYGROUND_EMAIL ?? 'admin@wyachts.com')
        await page.locator('#password').fill(process.env.FLEX_FIELDS_PLAYGROUND_PASSWORD ?? 'password')
        await page.getByRole('button', { name: /sign in|log in/i }).click()
        await page.waitForURL(/\/admin/, { timeout: 15_000 })
    }

    await page.goto(url)
    await expect(page.locator('.fi-main, .fi-body, main').first()).toBeVisible({ timeout: 30_000 })
}

const ICON_PICKER_PATH = '/icon-picker-field'

/**
 * @param {import('@playwright/test').Page} page
 */
async function openIconPickerPanel(page, triggerSelector) {
    const trigger = page.locator(triggerSelector).first()

    await expect(trigger).toBeVisible()
    await trigger.click()

    const panel = page.locator('body > .fff-icon-picker__panel.is-positioned').first()

    await expect(panel).toBeVisible({ timeout: 15_000 })
    await expect(panel).toHaveClass(/is-positioned/)

    return panel
}

/**
 * @param {import('@playwright/test').Locator} panel
 */
async function resultsScroller(panel) {
    return panel.locator('.fff-icon-picker__results').first()
}

test.describe('Icon picker field playground', () => {

    test('first open shows initial skeleton before icons', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, ICON_PICKER_PATH)

        const panel = await openIconPickerPanel(page, '[id$="icon_picker__empty"] .fi-select-input-btn')

        const skeleton = panel.locator('.fff-icon-picker__initial-skeleton')
        const track = panel.locator('.fff-icon-picker__track')

        await expect(skeleton).toBeVisible({ timeout: 5_000 })

        const skeletonBox = await skeleton.locator('.fff-icon-picker__skeleton--icon').first().boundingBox()

        expect(skeletonBox).not.toBeNull()
        expect(skeletonBox.height).toBeGreaterThan(16)
        expect(skeletonBox.width).toBeGreaterThan(16)

        await expect(track).toBeHidden()

        await expect(panel.locator('.fff-icon-picker__track .fff-icon-picker__option').first()).toBeVisible({
            timeout: 15_000,
        })

        await expect(skeleton).toBeHidden({ timeout: 5_000 })

        assertClean()
    })

    test('icon grid cells stay stable on first open (no vertical jump)', async ({ page }) => {
        await gotoPlaygroundPage(page, ICON_PICKER_PATH)

        const panel = await openIconPickerPanel(page, '[id$="icon_picker__grid"] .fi-select-input-btn')
        const scroller = await resultsScroller(panel)

        await expect(panel.locator('.fff-icon-picker__track .fff-icon-picker__option').first()).toBeVisible({
            timeout: 15_000,
        })

        const samplePositions = async () => page.evaluate(() => {
            const options = [...document.querySelectorAll('body > .fff-icon-picker__panel.is-positioned .fff-icon-picker__track .fff-icon-picker__option')].slice(0, 8)

            return options.map((option) => {
                const rect = option.getBoundingClientRect()

                return {
                    top: Math.round(rect.top * 10) / 10,
                    left: Math.round(rect.left * 10) / 10,
                    width: Math.round(rect.width * 10) / 10,
                    height: Math.round(rect.height * 10) / 10,
                }
            })
        })

        const positions = []
        const deadline = Date.now() + 2_500

        while (Date.now() < deadline) {
            positions.push(await samplePositions())
            await page.waitForTimeout(120)
        }

        expect(positions.length).toBeGreaterThan(3)

        const first = positions[0]
        const last = positions.at(-1)

        for (let index = 0; index < Math.min(first.length, last.length); index += 1) {
            expect(Math.abs(first[index].top - last[index].top)).toBeLessThanOrEqual(1)
            expect(Math.abs(first[index].left - last[index].left)).toBeLessThanOrEqual(1)
            expect(Math.abs(first[index].width - last[index].width)).toBeLessThanOrEqual(1)
            expect(Math.abs(first[index].height - last[index].height)).toBeLessThanOrEqual(1)
        }

        const firstOption = panel.locator('.fff-icon-picker__track .fff-icon-picker__option').first()
        const iconArea = firstOption.locator('.fff-icon-picker__option-icon').first()
        const perIconSkeleton = iconArea.locator('.fff-icon-picker__option-icon-skeleton')

        await expect(perIconSkeleton).toHaveCount(1)

        const skeletonBox = await perIconSkeleton.boundingBox()
        const iconBox = await iconArea.boundingBox()

        expect(skeletonBox).not.toBeNull()
        expect(iconBox).not.toBeNull()

        if (skeletonBox && iconBox) {
            expect(Math.abs(skeletonBox.top - iconBox.top)).toBeLessThanOrEqual(1)
            expect(Math.abs(skeletonBox.left - iconBox.left)).toBeLessThanOrEqual(1)
            expect(Math.abs(skeletonBox.width - iconBox.width)).toBeLessThanOrEqual(1)
            expect(Math.abs(skeletonBox.height - iconBox.height)).toBeLessThanOrEqual(1)
        }

        await expect(scroller).toBeVisible()
    })

    test('virtual scroll stays stable while scrolling down', async ({ page }) => {
        await gotoPlaygroundPage(page, ICON_PICKER_PATH)

        const panel = await openIconPickerPanel(page, '[id$="icon_picker__grid"] .fi-select-input-btn')
        const scroller = await resultsScroller(panel)

        await expect(panel.locator('.fff-icon-picker__track .fff-icon-picker__option').first()).toBeVisible({
            timeout: 15_000,
        })

        const scrollSamples = []

        for (let step = 0; step < 8; step += 1) {
            await scroller.evaluate((element) => {
                element.scrollTop += 72
            })

            await page.waitForTimeout(80)

            const sample = await page.evaluate(() => {
                const element = document.querySelector('body > .fff-icon-picker__panel.is-positioned .fff-icon-picker__results')

                if (! element) {
                    return null
                }

                const option = element.querySelector('.fff-icon-picker__track .fff-icon-picker__option')

                if (! option) {
                    return null
                }

                const rect = option.getBoundingClientRect()
                const scrollerRect = element.getBoundingClientRect()

                return {
                    scrollTop: element.scrollTop,
                    optionTop: Math.round(rect.top * 10) / 10,
                    scrollerTop: Math.round(scrollerRect.top * 10) / 10,
                }
            })

            scrollSamples.push(sample)
        }

        expect(scrollSamples.filter(Boolean).length).toBeGreaterThan(4)

        for (let index = 1; index < scrollSamples.length; index += 1) {
            const previous = scrollSamples[index - 1]
            const current = scrollSamples[index]

            if (! previous || ! current) {
                continue
            }

            const scrollDelta = current.scrollTop - previous.scrollTop
            const visualDelta = current.optionTop - previous.optionTop
            const expectedVisualDelta = -scrollDelta

            expect(Math.abs(visualDelta - expectedVisualDelta)).toBeLessThanOrEqual(2)
        }
    })
})

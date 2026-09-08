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

const ICON_PICKER_PATH = 'icon-picker-field'

/**
 * @param {string} statePath
 */
function iconPickerTrigger(statePath) {
    return `[x-data*="${statePath}"] .fi-select-input-btn`
}

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

/**
 * @param {import('@playwright/test').Locator} panel
 */
function initialSkeleton(panel) {
    return panel.locator('.fff-icon-picker__initial-skeleton')
}

/**
 * @param {import('@playwright/test').Page} page
 */
async function sampleOpenCoverage(page) {
    return page.evaluate(() => {
        const panel = document.querySelector('body > .fff-icon-picker__panel.is-positioned')
        const results = panel?.querySelector('.fff-icon-picker__results')
        const skeleton = results?.querySelector('.fff-icon-picker__initial-skeleton')
        const cell = skeleton?.querySelector('.fff-icon-picker__skeleton--cell')
        const option = results?.querySelector('.fff-icon-picker__track .fff-icon-picker__option')
        const skRect = cell?.getBoundingClientRect()
        const optRect = option?.getBoundingClientRect()
        const skVisible = Boolean(
            skeleton?.classList.contains('is-visible')
            && skRect
            && skRect.height > 20
            && skRect.width > 20
            && getComputedStyle(skeleton).display !== 'none'
            && Number(getComputedStyle(skeleton).opacity) > 0.05,
        )
        const iconsVisible = Boolean(
            option
            && optRect
            && optRect.height > 16
            && getComputedStyle(option).display !== 'none'
            && (! skeleton?.classList.contains('is-visible')
                || Number(getComputedStyle(skeleton).opacity) < 0.95),
        )

        return {
            skVisible,
            iconsVisible,
            blank: ! skVisible && ! iconsVisible,
            skClass: skeleton?.className ?? null,
            cellH: skRect ? Math.round(skRect.height) : 0,
        }
    })
}

test.describe('Icon picker field playground', () => {

    test('first open shows skeleton bones then one reveal to icons', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, ICON_PICKER_PATH)

        const panel = await openIconPickerPanel(page, iconPickerTrigger('icon_picker__empty'))
        const skeleton = initialSkeleton(panel)

        await expect(skeleton).toHaveClass(/is-visible/, { timeout: 5_000 })
        await expect(skeleton).toBeVisible()

        const bone = skeleton.locator('.fff-icon-picker__skeleton--cell').first()
        const skeletonBox = await bone.boundingBox()

        expect(skeletonBox).not.toBeNull()
        expect(skeletonBox.height).toBeGreaterThan(28)
        expect(skeletonBox.width).toBeGreaterThan(28)

        const boneBg = await bone.evaluate((el) => getComputedStyle(el).backgroundColor)

        expect(boneBg).not.toBe('rgba(0, 0, 0, 0)')
        expect(boneBg).not.toBe('transparent')

        const skeletonOptionSize = await page.evaluate(() => {
            const option = document.querySelector('body > .fff-icon-picker__panel.is-positioned .fff-icon-picker__initial-skeleton .fff-icon-picker__option--loading')
            const rect = option?.getBoundingClientRect()

            return rect ? { w: Math.round(rect.width * 10) / 10, h: Math.round(rect.height * 10) / 10 } : null
        })

        expect(skeletonOptionSize).not.toBeNull()

        const samples = []
        const heightSamples = []

        for (let i = 0; i < 40; i += 1) {
            samples.push(await sampleOpenCoverage(page))
            heightSamples.push(await page.evaluate(() => {
                const results = document.querySelector('body > .fff-icon-picker__panel.is-positioned .fff-icon-picker__results')

                return Math.round(results?.getBoundingClientRect().height ?? 0)
            }))
            await page.waitForTimeout(50)
        }

        expect(samples.some((sample) => sample.skVisible)).toBeTruthy()
        expect(samples.every((sample) => ! sample.blank)).toBeTruthy()

        const positiveHeights = heightSamples.filter((h) => h > 0)
        const minH = Math.min(...positiveHeights)
        const maxH = Math.max(...positiveHeights)

        expect(minH).toBeGreaterThanOrEqual(220)
        // Sub-pixel / padding rounding while the sheet settles — not the old ~65px jump.
        expect(maxH - minH).toBeLessThanOrEqual(4)

        await expect(panel.locator('.fff-icon-picker__track .fff-icon-picker__option').first()).toBeVisible({
            timeout: 15_000,
        })
        await expect(skeleton).not.toHaveClass(/is-visible/, { timeout: 10_000 })

        const realOptionSize = await page.evaluate(() => {
            const option = document.querySelector('body > .fff-icon-picker__panel.is-positioned .fff-icon-picker__track .fff-icon-picker__option')
            const rect = option?.getBoundingClientRect()

            return rect ? { w: Math.round(rect.width * 10) / 10, h: Math.round(rect.height * 10) / 10 } : null
        })

        expect(realOptionSize).not.toBeNull()
        expect(Math.abs(skeletonOptionSize.w - realOptionSize.w)).toBeLessThanOrEqual(2)
        expect(Math.abs(skeletonOptionSize.h - realOptionSize.h)).toBeLessThanOrEqual(2)

        assertClean()
    })

    test('icon grid cells stay stable on first open (no vertical jump)', async ({ page }) => {
        await gotoPlaygroundPage(page, ICON_PICKER_PATH)

        const panel = await openIconPickerPanel(page, iconPickerTrigger('icon_picker__grid'))
        const scroller = await resultsScroller(panel)

        await expect(panel.locator('.fff-icon-picker__track .fff-icon-picker__option').first()).toBeVisible({
            timeout: 15_000,
        })
        await expect(initialSkeleton(panel)).not.toHaveClass(/is-visible/, { timeout: 15_000 })
        await expect(panel.locator('.fff-icon-picker__track--virtual, .fff-icon-picker__track')).toBeVisible()
        await page.waitForTimeout(200)

        const samplePositions = async () => page.evaluate(() => {
            const options = [...document.querySelectorAll('body > .fff-icon-picker__panel.is-positioned .fff-icon-picker__track .fff-icon-picker__option')].slice(0, 8)

            return options.map((option) => {
                const rect = option.getBoundingClientRect()

                return {
                    top: Math.round(rect.top * 10) / 10,
                    height: Math.round(rect.height * 10) / 10,
                }
            })
        })

        const before = await samplePositions()

        expect(before.length).toBeGreaterThan(3)

        await scroller.evaluate((element) => {
            element.scrollTop += 1
        })
        await page.waitForTimeout(50)
        await scroller.evaluate((element) => {
            element.scrollTop -= 1
        })
        await page.waitForTimeout(50)

        const after = await samplePositions()

        expect(after.length).toBe(before.length)

        for (let index = 0; index < before.length; index += 1) {
            expect(Math.abs(after[index].top - before[index].top)).toBeLessThanOrEqual(2)
            expect(Math.abs(after[index].height - before[index].height)).toBeLessThanOrEqual(2)
        }

        await expect(scroller).toBeVisible()
    })

    test('virtual scroll stays stable while scrolling down', async ({ page }) => {
        await gotoPlaygroundPage(page, ICON_PICKER_PATH)

        const panel = await openIconPickerPanel(page, iconPickerTrigger('icon_picker__grid'))
        const scroller = await resultsScroller(panel)

        await expect(panel.locator('.fff-icon-picker__track .fff-icon-picker__option').first()).toBeVisible({
            timeout: 15_000,
        })
        await expect(initialSkeleton(panel)).not.toHaveClass(/is-visible/, { timeout: 15_000 })
        await expect(panel.locator('.fff-icon-picker__track--virtual, .fff-icon-picker__track')).toBeVisible()
        await page.waitForTimeout(400)

        let previousScrollTop = await scroller.evaluate((element) => element.scrollTop)

        for (let step = 0; step < 8; step += 1) {
            await scroller.evaluate((element) => {
                element.scrollTop += 72
            })

            await page.waitForTimeout(80)

            const sample = await page.evaluate(() => {
                const element = document.querySelector('body > .fff-icon-picker__panel.is-positioned .fff-icon-picker__results')
                const option = element?.querySelector('.fff-icon-picker__track .fff-icon-picker__option')

                return {
                    scrollTop: element?.scrollTop ?? 0,
                    hasOption: Boolean(option),
                    optionHeight: option ? option.getBoundingClientRect().height : 0,
                }
            })

            expect(sample.hasOption).toBeTruthy()
            expect(sample.optionHeight).toBeGreaterThan(20)
            expect(sample.scrollTop).toBeGreaterThan(previousScrollTop)
            previousScrollTop = sample.scrollTop
        }
    })

    test('mobile sheet skeleton columns and bone size match real icons 1:1', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 })
        await gotoPlaygroundPage(page, ICON_PICKER_PATH)

        const panel = await openIconPickerPanel(page, iconPickerTrigger('icon_picker__empty'))

        await expect(panel).toHaveClass(/fff-teleported-menu--sheet|fff-overlay-sheet/)

        const skeleton = initialSkeleton(panel)

        await expect(skeleton).toHaveClass(/is-visible/, { timeout: 5_000 })

        const during = await page.evaluate(() => {
            const panelEl = document.querySelector('body > .fff-icon-picker__panel.is-positioned')
            const skGrid = panelEl?.querySelector('.fff-icon-picker__initial-skeleton .fff-icon-picker__grid')
            const skOpt = panelEl?.querySelector('.fff-icon-picker__initial-skeleton .fff-icon-picker__option--loading')
            const skRect = skOpt?.getBoundingClientRect()

            return {
                cols: skGrid
                    ? getComputedStyle(skGrid).gridTemplateColumns.split(' ').filter(Boolean).length
                    : 0,
                size: skRect
                    ? { w: Math.round(skRect.width * 10) / 10, h: Math.round(skRect.height * 10) / 10 }
                    : null,
            }
        })

        expect(during.cols).toBe(8)
        expect(during.size).not.toBeNull()
        expect(Math.abs(during.size.w - during.size.h)).toBeLessThanOrEqual(1)

        await expect(panel.locator('.fff-icon-picker__track .fff-icon-picker__option').first()).toBeVisible({
            timeout: 15_000,
        })
        await expect(skeleton).not.toHaveClass(/is-visible/, { timeout: 10_000 })

        const after = await page.evaluate(() => {
            const panelEl = document.querySelector('body > .fff-icon-picker__panel.is-positioned')
            const grid = panelEl?.querySelector('.fff-icon-picker__track .fff-icon-picker__grid')
            const opt = panelEl?.querySelector('.fff-icon-picker__track .fff-icon-picker__option')
            const rect = opt?.getBoundingClientRect()

            return {
                cols: grid
                    ? getComputedStyle(grid).gridTemplateColumns.split(' ').filter(Boolean).length
                    : 0,
                size: rect
                    ? { w: Math.round(rect.width * 10) / 10, h: Math.round(rect.height * 10) / 10 }
                    : null,
            }
        })

        expect(after.cols).toBe(during.cols)
        expect(after.size).not.toBeNull()
        expect(Math.abs(during.size.w - after.size.w)).toBeLessThanOrEqual(2)
        expect(Math.abs(during.size.h - after.size.h)).toBeLessThanOrEqual(2)
    })
})

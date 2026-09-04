import { expect, test } from '@playwright/test'
import { gotoPlaygroundPage } from './global-setup.mjs'

/**
 * Country dial dropdown must not flash wrong size/placement on first open.
 * Regression: menu became is-positioned before Alpine list paint + min width,
 * jumping top (~600→307) and width (~274→288) while contents reflowed.
 */
test('phone country menu opens without size or placement jump', async ({ page }) => {
    test.setTimeout(60_000)

    await gotoPlaygroundPage(page, 'phone-field')
    await page.waitForSelector('.fff-phone-field__country-trigger', { timeout: 20_000 })

    await page.evaluate(() => {
        window.__phoneMenuFrames = []
        window.__phoneMenuPoll = setInterval(() => {
            const menu = [...document.querySelectorAll('.fff-phone-field__country-menu')].find((el) => {
                const style = getComputedStyle(el)

                return style.display !== 'none' && style.visibility !== 'hidden'
            })

            if (! menu) {
                return
            }

            const rect = menu.getBoundingClientRect()

            window.__phoneMenuFrames.push({
                t: performance.now(),
                w: Math.round(rect.width),
                h: Math.round(rect.height),
                top: Math.round(rect.top),
                left: Math.round(rect.left),
            })
        }, 8)
    })

    await page.locator('.fff-phone-field__country-trigger').first().click()

    await page.waitForFunction(() => {
        const menu = [...document.querySelectorAll('.fff-phone-field__country-menu')].find((el) => {
            const style = getComputedStyle(el)

            return style.display !== 'none' && style.visibility !== 'hidden'
        })

        return Boolean(menu?.classList.contains('is-positioned'))
    }, null, { timeout: 10_000 })

    await page.waitForTimeout(400)

    const frames = await page.evaluate(() => {
        clearInterval(window.__phoneMenuPoll)

        const raw = window.__phoneMenuFrames || []
        const t0 = raw[0]?.t ?? 0
        const normalized = raw.map((frame) => ({
            ...frame,
            t: Math.round(frame.t - t0),
        }))

        const unique = []
        let prev = ''

        for (const frame of normalized) {
            const key = `${frame.w}|${frame.h}|${frame.top}|${frame.left}`

            if (key !== prev) {
                unique.push(frame)
                prev = key
            }
        }

        return unique
    })

    expect(frames.length).toBeGreaterThan(0)

    const first = frames[0]
    const last = frames[frames.length - 1]

    // First visible frame must already match the settled geometry (±2px).
    expect(Math.abs(first.w - last.w)).toBeLessThanOrEqual(2)
    expect(Math.abs(first.h - last.h)).toBeLessThanOrEqual(2)
    expect(Math.abs(first.top - last.top)).toBeLessThanOrEqual(2)
    expect(Math.abs(first.left - last.left)).toBeLessThanOrEqual(2)
    expect(last.w).toBeGreaterThanOrEqual(280)
    expect(last.h).toBeGreaterThanOrEqual(200)
})

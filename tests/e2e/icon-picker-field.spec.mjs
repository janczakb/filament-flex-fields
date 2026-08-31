import { test, expect } from '@playwright/test'

const fixturePath = '/tests/e2e/fixtures/icon-picker-field.html'

async function openFixturePanel(page) {
    await page.goto(fixturePath)

    const trigger = page.locator('#icon-picker-trigger')

    await expect(trigger).toBeVisible()
    await trigger.click()

    const panel = page.locator('body > .fff-icon-picker__panel.is-positioned').first()

    await expect(panel).toBeVisible({ timeout: 15_000 })
    await expect(panel).toHaveClass(/is-positioned/)

    return panel
}

async function waitForLoadedTrack(panel) {
    const track = panel.locator('.fff-icon-picker__track')

    await expect(track.locator('.fff-icon-picker__option').first()).toBeVisible({ timeout: 15_000 })
    await expect(panel.locator('.fff-icon-picker__initial-skeleton')).toBeHidden({ timeout: 5_000 })

    return track
}

test.describe('Icon picker field fixture (layout precision)', () => {
    test('first open shows initial skeleton before icons render', async ({ page }) => {
        await page.goto(fixturePath)

        await page.locator('#icon-picker-trigger').click()

        const panel = page.locator('body > .fff-icon-picker__panel.is-positioned').first()
        const skeleton = panel.locator('.fff-icon-picker__initial-skeleton')
        const track = panel.locator('.fff-icon-picker__track')

        await expect(panel).toBeVisible({ timeout: 15_000 })
        await expect(skeleton).toBeVisible({ timeout: 5_000 })
        await expect(track).toBeHidden()

        const skeletonCell = skeleton.locator('.fff-icon-picker__option--loading').first()
        const skeletonBox = await skeletonCell.boundingBox()

        expect(skeletonBox).not.toBeNull()
        expect(skeletonBox.height).toBeGreaterThan(16)
        expect(skeletonBox.width).toBeGreaterThan(16)

        await waitForLoadedTrack(panel)
    })

    test('icon cells do not jump more than 1px after the track is visible', async ({ page }) => {
        const panel = await openFixturePanel(page)

        await waitForLoadedTrack(panel)

        const samplePositions = () => page.evaluate(() => {
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
            await page.waitForTimeout(100)
        }

        expect(positions.length).toBeGreaterThan(4)

        const first = positions[0]
        const last = positions.at(-1)

        for (let index = 0; index < Math.min(first.length, last.length); index += 1) {
            expect(Math.abs(first[index].top - last[index].top)).toBeLessThanOrEqual(1)
            expect(Math.abs(first[index].left - last[index].left)).toBeLessThanOrEqual(1)
            expect(Math.abs(first[index].width - last[index].width)).toBeLessThanOrEqual(1)
            expect(Math.abs(first[index].height - last[index].height)).toBeLessThanOrEqual(1)
        }
    })

    test('per-icon skeleton is absolutely positioned over the icon slot', async ({ page }) => {
        const panel = await openFixturePanel(page)

        await expect(panel.locator('.fff-icon-picker__track .fff-icon-picker__option').first()).toBeVisible({
            timeout: 15_000,
        })

        const styles = await page.evaluate(() => {
            const skeleton = document.querySelector('body > .fff-icon-picker__panel .fff-icon-picker__option-icon-skeleton')

            if (! skeleton) {
                return null
            }

            const computed = window.getComputedStyle(skeleton)

            return {
                position: computed.position,
                top: computed.top,
                right: computed.right,
                bottom: computed.bottom,
                left: computed.left,
            }
        })

        expect(styles).not.toBeNull()
        expect(styles.position).toBe('absolute')
        expect(styles.top).toBe('0px')
        expect(styles.right).toBe('0px')
        expect(styles.bottom).toBe('0px')
        expect(styles.left).toBe('0px')
    })

    test('virtual scroll advances scrollTop smoothly without backward jumps', async ({ page }) => {
        const panel = await openFixturePanel(page)
        const scroller = panel.locator('.fff-icon-picker__results')

        await waitForLoadedTrack(panel)

        const scrollTops = []

        for (let step = 0; step < 10; step += 1) {
            await scroller.evaluate((element) => {
                element.scrollTop += 42
            })

            await page.waitForTimeout(60)

            const scrollTop = await scroller.evaluate((element) => element.scrollTop)

            scrollTops.push(scrollTop)
        }

        expect(scrollTops.length).toBe(10)

        for (let index = 1; index < scrollTops.length; index += 1) {
            const delta = scrollTops[index] - scrollTops[index - 1]

            expect(delta).toBeGreaterThanOrEqual(0)
            expect(delta).toBeGreaterThan(30)
            expect(delta).toBeLessThan(50)
        }
    })

    test('trigger shell keeps stable height while alpine hydrates', async ({ page }) => {
        await page.setContent(`
            <!DOCTYPE html>
            <html>
                <head>
                    <link rel="stylesheet" href="/resources/dist/css/icon-picker-field.css" />
                    <link rel="stylesheet" href="/resources/dist/css/select-field.css" />
                </head>
                <body>
                    <div class="fi-input-wrp fff-select-field fff-icon-picker-field">
                        <div class="fi-select-input fff-icon-picker-shell">
                            <div class="fff-select-trigger-ssr fff-icon-picker-trigger-ssr fi-select-input-ctn fi-select-input-ctn-clearable fff-select-trigger-ssr--clearable" aria-hidden="true">
                                <span class="fff-select-trigger-ssr__btn">
                                    <span class="fff-select-trigger-ssr__value-ctn fi-select-input-value-ctn">
                                        <span class="fi-select-input-value-label">
                                            <span class="fff-icon-picker__preview"><svg viewBox="0 0 24 24" width="24" height="24"><rect width="24" height="24"></rect></svg></span>
                                            <span class="fff-icon-picker__name">heroicon-o-star</span>
                                        </span>
                                    </span>
                                </span>
                            </div>
                            <div class="fff-icon-picker">
                                <div class="fi-select-input-ctn fi-select-input-ctn-clearable">
                                    <button type="button" class="fi-select-input-btn">
                                        <span class="fi-select-input-value-ctn">
                                            <span class="fi-select-input-value-label">
                                                <span class="fff-icon-picker__preview"></span>
                                                <span class="fff-icon-picker__name">heroicon-o-star</span>
                                            </span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </body>
            </html>
        `, { waitUntil: 'load' })

        const shell = page.locator('.fff-icon-picker-shell')
        const before = await shell.boundingBox()

        expect(before).not.toBeNull()

        await page.locator('.fff-icon-picker-trigger-ssr').evaluate((element) => {
            element.classList.add('is-replaced')
        })

        const after = await shell.boundingBox()

        expect(after).not.toBeNull()

        if (before && after) {
            expect(Math.abs(before.height - after.height)).toBeLessThanOrEqual(1)
            expect(Math.abs(before.width - after.width)).toBeLessThanOrEqual(1)
        }
    })
})

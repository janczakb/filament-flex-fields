import assert from 'node:assert/strict'
import { test } from '@playwright/test'

test('year scroll surface scrolls with wheel without growing the viewport', async ({ page }) => {
    await page.goto('/tests/e2e/fixtures/year-scroll.html')

    const surface = page.getByTestId('year-scroll')
    const metrics = await surface.evaluate((element) => {
        const before = element.scrollTop

        element.scrollTop = before + 120
        const afterManual = element.scrollTop

        element.scrollTop = before
        element.dispatchEvent(new WheelEvent('wheel', { deltaY: 120, bubbles: true, cancelable: true }))

        return {
            clientHeight: element.clientHeight,
            scrollHeight: element.scrollHeight,
            manualScrollWorks: afterManual > before,
            scrollTopAfterWheel: element.scrollTop,
        }
    })

    assert.ok(metrics.scrollHeight > metrics.clientHeight + 1)
    assert.ok(metrics.manualScrollWorks)
    assert.ok(metrics.scrollTopAfterWheel > 0, 'wheel handler should scroll the year grid')
})

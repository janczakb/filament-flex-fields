import { test } from '@playwright/test'

import { gotoPlaygroundPage } from './global-setup.mjs'

test('audio settings menu does not jump vertically while closing', async ({ page }) => {
    await gotoPlaygroundPage(page, 'audio-field')

    const field = page.locator('.fff-audio-field.has-transcription').first()
    await field.waitFor({ state: 'visible', timeout: 30_000 })

    const settingsBtn = field.locator('.fff-video-field__glass-btn').first()
    await settingsBtn.waitFor({ state: 'visible', timeout: 15_000 })

    await settingsBtn.click()

    const menu = field.locator('.fff-audio-field__settings-menu')
    await menu.waitFor({ state: 'visible', timeout: 10_000 })

    await page.waitForTimeout(400)

    const samples = []

    const sample = async (label) => {
        const box = await menu.boundingBox()
        const style = await menu.evaluate((el) => {
            const computed = getComputedStyle(el)

            return {
                top: computed.top,
                right: computed.right,
                transform: computed.transform,
                opacity: computed.opacity,
                position: computed.position,
                height: computed.height,
                ending: el.hasAttribute('data-ending-style'),
                starting: el.hasAttribute('data-starting-style'),
                hidden: el.hasAttribute('hidden'),
                inlineTop: el.style.top,
                inlineTransform: el.style.transform,
                inlinePosition: el.style.position,
            }
        })

        samples.push({ label, box, style })
    }

    await sample('open-stable')

    await settingsBtn.click()

    for (let i = 0; i < 12; i++) {
        await page.waitForTimeout(30)
        await sample(`close-${i}`)
    }

    console.log(JSON.stringify(samples, null, 2))

    const openY = samples[0]?.box?.y
    const closeFrames = samples.slice(1).filter((s) => s.box)
    const maxDelta = closeFrames.reduce((max, frame) => {
        const delta = Math.abs((frame.box?.y ?? 0) - (openY ?? 0))

        return Math.max(max, delta)
    }, 0)

    console.log('maxYDelta', maxDelta)

    if (maxDelta > 2) {
        throw new Error(`Settings menu jumped vertically by ${maxDelta}px during close`)
    }
})

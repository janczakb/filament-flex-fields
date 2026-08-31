import { test, expect } from '@playwright/test'

import { gotoPlaygroundPage } from './global-setup.mjs'

test('audio whisper transcription completes or surfaces runtime error', async ({ page }) => {
    const consoleErrors = []
    const failedRequests = []

    page.on('console', (message) => {
        if (message.type() === 'error') {
            consoleErrors.push(message.text())
        }
    })

    page.on('requestfailed', (request) => {
        failedRequests.push(`${request.method()} ${request.url()} :: ${request.failure()?.errorText ?? 'failed'}`)
    })

    await gotoPlaygroundPage(page, 'audio-field')

    const field = page.locator('.fff-audio-field.has-transcription').first()
    await field.waitFor({ state: 'visible', timeout: 30_000 })

    const transcribeButton = field.locator('.fff-audio-field__stt-button').first()
    await transcribeButton.waitFor({ state: 'visible', timeout: 15_000 })
    await transcribeButton.click()

    const status = field.locator('.fff-audio-field__stt-status')
    await status.waitFor({ state: 'visible', timeout: 10_000 })

    await expect
        .poll(async () => {
            const text = (await status.textContent())?.trim() ?? ''
            const transcript = (await field.locator('.fff-audio-field__transcript-text').textContent().catch(() => '')) ?? ''
            const errorVisible = await field.locator('.fff-audio-field__stt-status.is-error').isVisible().catch(() => false)

            return { text, transcript: transcript.trim(), errorVisible }
        }, {
            timeout: 180_000,
            message: 'Whisper transcription did not finish within 3 minutes',
        })
        .toMatchObject({
            transcript: expect.not.stringMatching(/^$/),
        })

    console.log('consoleErrors', consoleErrors)
    console.log('failedRequests', failedRequests.slice(0, 20))
})

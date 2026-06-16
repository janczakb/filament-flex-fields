import { test, expect } from '@playwright/test'

import { trackConsoleErrors, waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'

const fixturePath = '/tests/e2e/fixtures/select-field-coordinator.html'

test.describe('SelectField coordinator fixture', () => {
    test('attaches immediately when inner select is ready', async ({ page }) => {
        const { errors } = trackConsoleErrors(page)

        await page.goto(fixturePath)

        await waitForSelectCoordinatorAttached(page, '#immediate-coordinator')

        await expect(page.locator('#immediate-coordinator')).toHaveAttribute('data-fff-select-attached', 'true')
        expect(errors).toEqual([])
    })

    test('attaches after delayed inner select initialization', async ({ page }) => {
        const { errors } = trackConsoleErrors(page)

        await page.goto(fixturePath)

        await waitForSelectCoordinatorAttached(page, '#delayed-coordinator')

        await expect(page.locator('#delayed-coordinator')).toHaveAttribute('data-fff-select-attached', 'true')
        expect(errors).toEqual([])
    })

    test('does not emit attach-failed events on the happy path', async ({ page }) => {
        const failures = []

        page.on('console', (message) => {
            if (message.type() === 'error') {
                failures.push(message.text())
            }
        })

        await page.goto(fixturePath)

        await waitForSelectCoordinatorAttached(page)

        expect(failures.filter((message) => message.includes('failed to attach patches'))).toEqual([])
    })
})

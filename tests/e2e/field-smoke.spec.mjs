import { test, expect } from '@playwright/test'

import { trackConsoleErrors, waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'

const fixturePath = '/tests/e2e/fixtures/field-smoke.html'

test.describe('Field smoke fixture', () => {
    test('select coordinator attaches immediately when inner select is ready', async ({ page }) => {
        const { errors } = trackConsoleErrors(page)

        await page.goto(fixturePath)

        await waitForSelectCoordinatorAttached(page, '#immediate-coordinator')

        await expect(page.locator('#immediate-coordinator')).toHaveAttribute('data-fff-select-attached', 'true')
        expect(errors).toEqual([])
    })

    test('select coordinator attaches after delayed inner select initialization', async ({ page }) => {
        const { errors } = trackConsoleErrors(page)

        await page.goto(fixturePath)

        await waitForSelectCoordinatorAttached(page, '#delayed-coordinator')

        await expect(page.locator('#delayed-coordinator')).toHaveAttribute('data-fff-select-attached', 'true')
        expect(errors).toEqual([])
    })

    test('flex-file-upload module initializes summary without console errors', async ({ page }) => {
        const { errors } = trackConsoleErrors(page)

        await page.goto(fixturePath)

        await expect(page.locator('#file-upload-smoke [data-fff-file-upload-summary]')).toBeVisible()
        await expect(page.locator('#file-upload-smoke [data-fff-file-upload-summary]')).not.toHaveText('')

        expect(errors).toEqual([])
    })

    test('schedule-field module mounts with configured days', async ({ page }) => {
        const { errors } = trackConsoleErrors(page)

        await page.goto(fixturePath)

        await expect(page.locator('#schedule-smoke [data-fff-schedule-ready]')).toHaveText('2')

        expect(errors).toEqual([])
    })
})

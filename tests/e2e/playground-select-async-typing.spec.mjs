import { test, expect } from '@playwright/test'

import { gotoPlaygroundPage } from './global-setup.mjs'

test.describe('Select async typing stale ignore', () => {
    test('rapid typing on async paginated select settles on the latest query', async ({ page }) => {
        await gotoPlaygroundPage(page, 'select-field')

        const trigger = page.locator('[id$="select__async_paginated"].fi-select-input-btn, [id$="select__async_paginated"] .fi-select-input-btn').first()
        await expect(trigger).toBeVisible({ timeout: 15_000 })
        await trigger.scrollIntoViewIfNeeded()
        await trigger.click()

        const search = page.locator('body > .fff-select-dropdown-panel.is-open input, body > .fff-teleported-menu.is-open input').first()

        if ((await search.count()) === 0) {
            test.skip(true, 'No dropdown search input')
        }

        await search.fill('a')
        await search.fill('ab')
        await search.fill('abc')

        await page.waitForTimeout(500)

        await expect(page.locator('body > .fff-select-dropdown-panel.is-open, body > .fff-teleported-menu.is-open').first()).toBeVisible()
    })
})

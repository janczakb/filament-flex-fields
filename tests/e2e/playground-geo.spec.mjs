import { test, expect } from '@playwright/test'

test.describe('Flex Fields playground geo fields', () => {
    test.skip(! process.env.FLEX_FIELDS_PLAYGROUND_URL, 'Set FLEX_FIELDS_PLAYGROUND_URL to run playground E2E')

    test('map picker playground renders search input', async ({ page }) => {
        await page.goto('/map-picker')

        await expect(page.getByRole('group', { name: /map/i })).toBeVisible()
        await expect(page.locator('.fff-map-picker__search-input')).toBeVisible()
    })

    test('address autocomplete playground renders combobox', async ({ page }) => {
        await page.goto('/address-autocomplete')

        await expect(page.getByRole('group')).toBeVisible()
        await expect(page.locator('.fff-address-autocomplete input[role="combobox"]')).toBeVisible()
    })
})

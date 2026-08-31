import { test, expect } from '@playwright/test'

test.describe('Flex Fields playground geo fields', () => {

    test('map picker playground renders search input', async ({ page }) => {
        await page.goto('/map-picker')

        await expect(page.getByRole('group', { name: /map/i })).toBeVisible()
        await expect(page.locator('.fff-map-picker__search .fff-flex-text-input__input')).toBeVisible()
    })

    test('address autocomplete playground renders combobox', async ({ page }) => {
        await page.goto('/address-autocomplete')

        await expect(page.getByRole('group')).toBeVisible()
        await expect(page.locator('.fff-address-autocomplete input[role="combobox"]')).toBeVisible()
    })
})

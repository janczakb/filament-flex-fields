import { test, expect } from '@playwright/test'
import { gotoPlaygroundPage } from './global-setup.mjs'
import { waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'

test('Email recipients chips update before check exit finishes', async ({ page }) => {
  await gotoPlaygroundPage(page, 'select-field')
  await waitForSelectCoordinatorAttached(page)

  const trigger = page.locator('[id$="select__email_recipients"].fi-select-input-btn').first()
  await trigger.scrollIntoViewIfNeeded()
  await trigger.click()

  const menu = page.locator('#form\\.select__email_recipients-fff-headless-menu.is-open')
  await expect(menu).toBeVisible()

  // jane is preselected — deselect her
  const jane = menu.locator('.fi-select-input-option[data-value="jane"]')
  await expect(jane).toHaveAttribute('aria-selected', 'true')
  await jane.click()

  // Chip must disappear immediately (not after ~200ms check stroke)
  await expect.poll(async () => {
    return trigger.locator('.fi-badge').filter({ hasText: /jane\.cooper/i }).count()
  }, { timeout: 150 }).toBe(0)

  // aria-selected flips immediately; check exit may still be painting
  await expect(jane).toHaveAttribute('aria-selected', 'false')
})

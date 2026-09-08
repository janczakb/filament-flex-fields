import { test, expect } from '@playwright/test'

import { trackConsoleErrors, waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'
import { gotoPlaygroundPage } from './global-setup.mjs'
import { closeSelectMenu, openSelect } from './helpers/select-playground.mjs'

const playgroundSlugs = ['select-field', 'user-select']

const selectFieldSmokeTargets = [
    { label: 'basic status', selector: '[id$="select__basic"].fi-select-input-btn, [id$="select__basic"] .fi-select-input-btn' },
    { label: 'searchable', selector: '[id$="select__searchable"].fi-select-input-btn, [id$="select__searchable"] .fi-select-input-btn' },
    { label: 'multiple', selector: '[id$="select__multiple"].fi-select-input-btn, [id$="select__multiple"] .fi-select-input-btn' },
    { label: 'multiple checklist', selector: '[id$="select__multiple_checklist"].fi-select-input-btn, [id$="select__multiple_checklist"] .fi-select-input-btn' },
    { label: 'grid theme', selector: '[id$="select__grid"].fi-select-input-btn, [id$="select__grid"] .fi-select-input-btn' },
    { label: 'rich options', selector: '[id$="select__rich"].fi-select-input-btn, [id$="select__rich"] .fi-select-input-btn' },
]

const userSelectSmokeTargets = [
    { label: 'single assignee', selector: '[id$="user_select__single"].fi-select-input-btn, [id$="user_select__single"] .fi-select-input-btn' },
    { label: 'multiple team', selector: '[id$="user_select__multiple"].fi-select-input-btn, [id$="user_select__multiple"] .fi-select-input-btn' },
]

test.describe('Flex Fields playground select fields', () => {

    for (const slug of playgroundSlugs) {
        test(`/${slug} loads without JS errors and attaches coordinators`, async ({ page }) => {
            const { assertClean } = trackConsoleErrors(page)

            await gotoPlaygroundPage(page, slug)

            await expect(page.locator('.fi-select-input-btn').first()).toBeVisible()
            await waitForSelectCoordinatorAttached(page)

            const attachedCount = await page.locator('.fff-select-field__shell[data-fff-select-attached="true"]').count()
            const shellCount = await page.locator('.fff-select-field__shell').count()

            expect(attachedCount).toBeGreaterThan(0)
            expect(attachedCount).toBe(shellCount)
            assertClean()
        })
    }

    test('select-field playground opens key variants without JS errors', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, 'select-field')
        await waitForSelectCoordinatorAttached(page)

        for (const target of selectFieldSmokeTargets) {
            const trigger = page.locator(target.selector).first()

            await expect(trigger, `Missing trigger for ${target.label}`).toBeVisible()
            await trigger.click()
            await expect(page.locator('body > .fff-select-dropdown-panel.is-open, body > .fi-dropdown-panel.fff-select-dropdown-panel.is-open').first()).toBeVisible()
            await page.keyboard.press('Escape')
            await expect(page.locator('body > .fff-select-dropdown-panel.is-open')).toHaveCount(0)
        }

        assertClean()
    })

    test('select-field close then rapid reopen keeps the live menu', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, 'select-field')
        await waitForSelectCoordinatorAttached(page)

        await openSelect(page, 'select__basic')
        const panel = page.locator('#form\\.select__basic-fff-headless-menu.is-open')
        await expect(panel).toBeVisible()
        await closeSelectMenu(page)
        await expect(page.locator('#form\\.select__basic-fff-headless-menu.is-open')).toHaveCount(0)
        await openSelect(page, 'select__basic')
        await expect(page.locator('#form\\.select__basic-fff-headless-menu.is-open')).toBeVisible()
        await expect(page.locator('#form\\.select__basic-fff-headless-menu.is-open')).not.toHaveCSS('display', 'none')

        assertClean()
    })

    test('rich options list icons stay compact in the dropdown', async ({ page }) => {
        await gotoPlaygroundPage(page, 'select-field')
        await waitForSelectCoordinatorAttached(page)

        const trigger = page.locator('[id$="select__rich"].fi-select-input-btn, [id$="select__rich"] .fi-select-input-btn').first()

        await trigger.click()

        const icon = page.locator('body > .fff-select-dropdown-panel.is-open .fff-select-option__icon').first()

        await expect(icon).toBeVisible()

        const box = await icon.boundingBox()

        expect(box).not.toBeNull()
        expect(box.height).toBeLessThanOrEqual(22)
        expect(box.width).toBeLessThanOrEqual(22)
    })

    test('user-select playground opens single and multiple fields without JS errors', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, 'user-select')
        await waitForSelectCoordinatorAttached(page)

        for (const target of userSelectSmokeTargets) {
            const trigger = page.locator(target.selector).first()

            await expect(trigger, `Missing trigger for ${target.label}`).toBeVisible()
            await trigger.click()
            await expect(page.locator('body > .fff-select-dropdown-panel.is-open, body > .fi-dropdown-panel.fff-select-dropdown-panel.is-open').first()).toBeVisible()
            await page.keyboard.press('Escape')
            await expect(page.locator('body > .fff-select-dropdown-panel.is-open')).toHaveCount(0)
        }

        assertClean()
    })

    test('multiple checklist toggles options inside the teleported panel', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, 'select-field')
        await waitForSelectCoordinatorAttached(page)

        const trigger = page.locator('[id$="select__multiple_checklist"].fi-select-input-btn, [id$="select__multiple_checklist"] .fi-select-input-btn').first()

        await trigger.click()

        const panel = page.locator('body > .fff-select-dropdown-panel.is-open, body > .fi-dropdown-panel.fff-select-dropdown-panel.is-open').first()
        const firstOption = panel.locator('.fi-select-input-option').first()

        await expect(firstOption).toBeVisible()
        await firstOption.click()
        await expect(firstOption).toHaveAttribute('aria-selected', 'true')
        await page.keyboard.press('Escape')

        assertClean()
    })

    test('multiple chips field opens teleported panel', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await gotoPlaygroundPage(page, 'select-field')
        await waitForSelectCoordinatorAttached(page)

        const trigger = page.locator('[id$="select__multiple"].fi-select-input-btn, [id$="select__multiple"] .fi-select-input-btn').first()

        await trigger.click()
        await expect(page.locator('body > .fff-select-dropdown-panel.is-open .fi-select-input-option').first()).toBeVisible()
        await page.keyboard.press('Escape')

        assertClean()
    })

    test('teleported select panel renders in body portal', async ({ page }) => {
        await gotoPlaygroundPage(page, 'select-field')
        await waitForSelectCoordinatorAttached(page)

        const trigger = page.locator('[id$="select__searchable"].fi-select-input-btn, [id$="select__searchable"] .fi-select-input-btn').first()

        await trigger.click()

        const panel = page.locator('body > .fff-select-dropdown-panel.is-open, body > .fi-dropdown-panel.fff-select-dropdown-panel.is-open').first()

        await expect(panel).toBeVisible()
        await expect(panel).toHaveClass(/fff-teleported-menu|fff-select-dropdown-panel/)
    })
})

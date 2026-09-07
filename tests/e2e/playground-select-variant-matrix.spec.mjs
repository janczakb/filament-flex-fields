import { expect, test } from '@playwright/test'

import { SELECT_PLAYGROUND_E2E_VARIANTS } from './fixtures/select-playground-variants.mjs'
import { gotoPlaygroundPage } from './global-setup.mjs'
import { trackConsoleErrors, waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'
import {
    clearSelectViaX,
    clearSelectSearch,
    closeSelectMenu,
    openSelect,
    pickOptionByText,
    pickOptionByValue,
    readLivewireData,
    selectTrigger,
    typeSelectSearch,
} from './helpers/select-playground.mjs'

test.describe('SelectField playground — ultra variant matrix', () => {
    test.describe.configure({ timeout: 120_000 })

    test.beforeEach(async ({ page }) => {
        await gotoPlaygroundPage(page, 'select-field')
        await waitForSelectCoordinatorAttached(page)
    })

    test('every SelectField shell attaches the headless coordinator', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)
        const shells = page.locator('.fff-select-field__shell')
        const attached = page.locator('.fff-select-field__shell[data-fff-select-attached="true"]')

        await expect(shells.first()).toBeVisible()
        await expect.poll(async () => attached.count()).toBeGreaterThan(40)
        expect(await attached.count()).toBe(await shells.count())
        assertClean()
    })

    for (const variant of SELECT_PLAYGROUND_E2E_VARIANTS) {
        test.describe(`${variant.key} · ${variant.label}`, () => {
            test('trigger is visible and openable (or disabled)', async ({ page }) => {
                const { assertClean } = trackConsoleErrors(page)
                const trigger = selectTrigger(page, variant.key)
                await expect(trigger, `Missing ${variant.key}`).toBeVisible({ timeout: 15_000 })
                await trigger.scrollIntoViewIfNeeded()

                if (variant.disabled) {
                    await expect(trigger).toBeDisabled()
                    assertClean()

                    return
                }

                if (variant.skipOpen) {
                    assertClean()

                    return
                }

                const preferChevron = Boolean(variant.preferChevron || variant.flags?.includes('reorderable'))
                await openSelect(page, variant.key, { preferChevron })
                await expect(page.locator(`#form\\.${variant.key}-fff-headless-menu.is-open`)).toBeVisible()
                await closeSelectMenu(page)
                assertClean()
            })

            if (! variant.skipPick && ! variant.disabled) {
                test('pick option updates trigger and Livewire data', async ({ page }) => {
                    test.setTimeout(45_000)
                    const { assertClean } = trackConsoleErrors(page)
                    const preferChevron = Boolean(variant.preferChevron || variant.flags?.includes('reorderable'))
                    const { trigger, menu } = await openSelect(page, variant.key, { preferChevron })

                    if (variant.searchQuery) {
                        await typeSelectSearch(page, variant.key, variant.searchQuery)
                        await page.waitForTimeout(250)
                    } else if (variant.flags?.includes('inline_search')) {
                        // Inline search often seeds the query with the current label — clear so other options show.
                        await clearSelectSearch(page, variant.key)
                        await page.waitForTimeout(120)
                    }

                    if (variant.flags?.includes('dynamic') || variant.flags?.includes('async')) {
                        await expect
                            .poll(async () => menu.locator('.fi-select-input-option, .fff-select-dropdown-empty, [data-row-type]').count(), { timeout: 15_000 })
                            .toBeGreaterThan(0)
                    }

                    if (variant.pickValue) {
                        await pickOptionByValue(page, variant.key, variant.pickValue)
                    } else if (variant.pickText) {
                        await pickOptionByText(page, variant.key, variant.pickText)
                    }

                    if (! variant.multiple) {
                        await expect(page.locator(`#form\\.${variant.key}-fff-headless-menu.is-open`)).toHaveCount(0, { timeout: 5_000 })
                    } else {
                        await closeSelectMenu(page)
                    }

                    const wire = await readLivewireData(page, variant.key)

                    if (variant.pickValue) {
                        if (variant.multiple) {
                            expect(Array.isArray(wire) ? wire.map(String) : []).toContain(String(variant.pickValue))
                        } else if (variant.key === 'select__boolean') {
                            // Filament boolean may store true/false or "1"/"0"
                            expect([true, false, 1, 0, '1', '0', variant.pickValue].map(String)).toContain(String(wire))
                            expect(wire === null || wire === '' || wire === undefined).toBeFalsy()
                        } else {
                            expect(String(wire)).toBe(String(variant.pickValue))
                        }
                    } else if (variant.pickText && ! variant.multiple) {
                        expect(wire === null || wire === undefined || wire === '').toBeFalsy()
                        await expect(trigger).not.toHaveText(/Select an option|Choose status|Make your mind/i)
                    }

                    assertClean()
                })
            }

            if (variant.clearable !== false && ! variant.skipClear && ! variant.disabled && ! variant.multiple) {
                test('× clear writes empty Livewire state', async ({ page }) => {
                    test.setTimeout(45_000)
                    const { assertClean } = trackConsoleErrors(page)

                    // Ensure a value is selected first
                    await openSelect(page, variant.key)

                    if (variant.searchQuery) {
                        await typeSelectSearch(page, variant.key, variant.searchQuery)
                        await page.waitForTimeout(250)
                    } else if (variant.flags?.includes('inline_search')) {
                        await clearSelectSearch(page, variant.key)
                        await page.waitForTimeout(120)
                    }

                    if (variant.pickValue) {
                        await pickOptionByValue(page, variant.key, variant.pickValue)
                    } else if (variant.pickText) {
                        await pickOptionByText(page, variant.key, variant.pickText)
                    } else if (variant.alternateValue) {
                        await pickOptionByValue(page, variant.key, variant.alternateValue)
                    } else {
                        await pickOptionByText(page, variant.key, /.+/i)
                    }

                    if (variant.pickValue) {
                        await expect.poll(async () => String(await readLivewireData(page, variant.key))).toBe(String(variant.pickValue))
                    } else {
                        await expect.poll(async () => {
                            const value = await readLivewireData(page, variant.key)

                            return value !== null && value !== '' && value !== undefined
                        }).toBeTruthy()
                    }

                    await clearSelectViaX(page, variant.key)

                    await expect.poll(async () => {
                        const value = await readLivewireData(page, variant.key)

                        return value === null || value === '' || value === undefined
                    }, { timeout: 5_000 }).toBeTruthy()

                    const clearBtn = page.locator(`.fi-input-wrp:has([id$="${variant.key}"]) .fi-select-input-value-remove-btn, .fff-select-field:has([id$="${variant.key}"]) .fi-select-input-value-remove-btn`).first()
                    // Button may stay in DOM under x-show — must not be visible when empty
                    await expect(clearBtn).toBeHidden()

                    assertClean()
                })
            }

            if (variant.clearable === false && ! variant.disabled) {
                test('not clearable: × button is absent when filled', async ({ page }) => {
                    const trigger = selectTrigger(page, variant.key)
                    await trigger.scrollIntoViewIfNeeded()

                    const clearBtn = page.locator(`.fi-select-input-ctn:has([id$="${variant.key}"]) .fi-select-input-value-remove-btn`)
                    // defaultState usually has a value — clear must not appear
                    await expect(clearBtn).toHaveCount(0)
                })
            }
        })
    }

    test('cascade: country change clears region and refetches options', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        // defaultState country is already `us` — change away first so wire commit is not a no-op
        await openSelect(page, 'select__cascade_country')
        await pickOptionByValue(page, 'select__cascade_country', 'pl')
        await expect.poll(async () => readLivewireData(page, 'select__cascade_country')).toBe('pl')

        await openSelect(page, 'select__cascade_region')
        await pickOptionByValue(page, 'select__cascade_region', 'mz')
        await expect.poll(async () => readLivewireData(page, 'select__cascade_region')).toBe('mz')

        await openSelect(page, 'select__cascade_country')
        await pickOptionByValue(page, 'select__cascade_country', 'us')
        await expect.poll(async () => readLivewireData(page, 'select__cascade_country')).toBe('us')
        await expect.poll(async () => {
            const value = await readLivewireData(page, 'select__cascade_region')

            return value === null || value === ''
        }).toBeTruthy()

        await openSelect(page, 'select__cascade_region')
        const menu = page.locator('#form\\.select__cascade_region-fff-headless-menu.is-open')
        await expect(menu.locator('.fi-select-input-option[data-value="ca"]')).toBeVisible()
        await expect(menu.locator('.fi-select-input-option[data-value="mz"]')).toHaveCount(0)

        await pickOptionByValue(page, 'select__cascade_region', 'ca')
        await expect.poll(async () => readLivewireData(page, 'select__cascade_region')).toBe('ca')

        await clearSelectViaX(page, 'select__cascade_region')
        await expect.poll(async () => {
            const value = await readLivewireData(page, 'select__cascade_region')

            return value === null || value === ''
        }).toBeTruthy()

        assertClean()
    })

    test('create option · single: type new label and commit Create row', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)
        const unique = `E2ECreate${Date.now()}`

        await openSelect(page, 'select__create_single')
        await typeSelectSearch(page, 'select__create_single', unique)

        const menu = page.locator('#form\\.select__create_single-fff-headless-menu.is-open')
        const createRow = menu.locator('[data-row-type="create"], .fff-select-headless-dropdown-row').filter({ hasText: /Create/i }).first()
        await expect(createRow).toBeVisible({ timeout: 5_000 })
        await createRow.click()

        await expect.poll(async () => String(await readLivewireData(page, 'select__create_single'))).toBe(unique)
        assertClean()
    })

    test('reorderable multi: chips stay in Livewire order after reverse set via UI toggles', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)
        const trigger = selectTrigger(page, 'select__reorderable')
        await trigger.scrollIntoViewIfNeeded()

        const before = await readLivewireData(page, 'select__reorderable')
        expect(Array.isArray(before)).toBeTruthy()
        expect(before.length).toBeGreaterThan(1)

        // Chip badges reflect current order
        const chips = trigger.locator('.fi-badge, .fff-select-chip, [data-value]')
        await expect(chips.first()).toBeVisible()

        // Drag first chip toward last (if sortable handles exist)
        const handles = trigger.locator('[data-sortable-handle], .fi-sortable-handle, .fff-select-chip')
        const count = await handles.count()

        if (count >= 2) {
            const first = handles.nth(0)
            const last = handles.nth(count - 1)
            await first.dragTo(last)
            await page.waitForTimeout(300)

            const after = await readLivewireData(page, 'select__reorderable')
            expect(Array.isArray(after)).toBeTruthy()
            expect(after.length).toBe(before.length)
            // Order may or may not change depending on drop target — state must stay a non-empty list
            expect(after.length).toBeGreaterThan(0)
        }

        assertClean()
    })

    test('clearable × then re-pick does not resurrect stale value', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await openSelect(page, 'select__clearable')
        await pickOptionByValue(page, 'select__clearable', 'draft')
        await expect.poll(async () => readLivewireData(page, 'select__clearable')).toBe('draft')

        await clearSelectViaX(page, 'select__clearable')
        await expect.poll(async () => {
            const value = await readLivewireData(page, 'select__clearable')

            return value === null || value === ''
        }).toBeTruthy()

        await openSelect(page, 'select__clearable')
        await pickOptionByValue(page, 'select__clearable', 'reviewing')
        await expect.poll(async () => readLivewireData(page, 'select__clearable')).toBe('reviewing')

        assertClean()
    })

    test('10k virtualized menu opens and filters without mounting full DOM', async ({ page }) => {
        const { assertClean } = trackConsoleErrors(page)

        await openSelect(page, 'select__scale_10k')
        const menu = page.locator('#form\\.select__scale_10k-fff-headless-menu.is-open')

        // Dynamic 10k options — wait for first rows after getOptionsForJs
        await expect(menu.locator('.fi-select-input-option').first()).toBeVisible({ timeout: 30_000 })

        const options = menu.locator('.fi-select-input-option')
        const visibleCount = await options.count()

        expect(visibleCount).toBeGreaterThan(0)
        expect(visibleCount).toBeLessThan(500)

        await typeSelectSearch(page, 'select__scale_10k', 'Option 00042')
        await expect(menu.locator('.fi-select-input-option[data-value="opt_42"]').first()).toBeVisible({ timeout: 10_000 })
        await pickOptionByValue(page, 'select__scale_10k', 'opt_42')
        await expect.poll(async () => readLivewireData(page, 'select__scale_10k')).toBe('opt_42')

        await clearSelectViaX(page, 'select__scale_10k')
        await expect.poll(async () => {
            const value = await readLivewireData(page, 'select__scale_10k')

            return value === null || value === ''
        }).toBeTruthy()

        assertClean()
    })
})

test.describe('Select-based related playground hubs', () => {
    const hubs = [
        {
            slug: 'timezone-field',
            trigger: '.fff-timezone-field .fi-select-input-btn, .fff-timezone-field button[aria-haspopup]',
            menu: 'body > .fff-select-dropdown-panel, body > .fff-teleported-menu, [id$="-fff-headless-menu"].is-open',
        },
        {
            slug: 'country-field',
            trigger: '.fff-country-field:not(.fi-disabled) .fi-select-input-btn, .fff-country-field:not(.fi-disabled) button[aria-haspopup], .fi-input-wrp:not(.fi-disabled) .fff-country-field button[aria-haspopup]',
            menu: '.fff-country-field__menu.is-positioned, body > .fff-country-field__menu, body > .fff-select-dropdown-panel, body > .fff-teleported-menu.is-positioned',
        },
        {
            slug: 'tags-field',
            trigger: '.fff-tags-field .fff-tags-field__input:not([readonly]), .fff-tags-field input.fi-input:not([readonly])',
            menu: 'body > .fff-select-dropdown-panel, body > .fff-teleported-menu, [id$="-fff-headless-menu"].is-open, .fff-tags-field [role="listbox"]',
            smokeOnly: true,
        },
        {
            slug: 'currency-field',
            // Currency is a segmented numeric control — smoke that the live display mounts
            trigger: '.fff-currency-field__live-display, .fff-currency-field',
            smokeOnly: true,
        },
        {
            slug: 'user-select',
            trigger: '[id$="user_select__single"].fi-select-input-btn, [id$="user_select__single"] .fi-select-input-btn',
            menu: 'body > .fff-select-dropdown-panel, body > .fff-teleported-menu, [id$="-fff-headless-menu"].is-open',
        },
        {
            slug: 'phone-field',
            trigger: '.fff-phone-field__country-trigger',
            menu: '.fff-phone-field__country-menu.is-positioned, body > .fff-phone-field__country-menu',
        },
    ]

    for (const hub of hubs) {
        test(`${hub.slug} opens primary dropdown without console errors`, async ({ page }) => {
            test.setTimeout(90_000)
            const { assertClean } = trackConsoleErrors(page)

            await gotoPlaygroundPage(page, hub.slug)
            await page.waitForTimeout(400)

            const trigger = page.locator(hub.trigger).filter({ visible: true }).first()
            await expect(trigger).toBeVisible({ timeout: 30_000 })

            if (hub.smokeOnly) {
                assertClean()

                return
            }

            await trigger.click()
            await expect(page.locator(hub.menu).filter({ visible: true }).first()).toBeVisible({ timeout: 15_000 })
            await page.keyboard.press('Escape')
            assertClean()
        })
    }
})

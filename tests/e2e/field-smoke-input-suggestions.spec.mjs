import { test, expect } from '@playwright/test'
import { build } from 'esbuild'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'

const root = fileURLToPath(new URL('../..', import.meta.url))
const bundle = await build({
    stdin: { resolveDir: root, contents: `
        import { createInputSuggestions } from './resources/js/components/text-input/suggestions.js';
        window.mountSuggestions = (options = {}) => {
            const input = document.querySelector('input');
            window.owner = { $refs: { input }, $el: input.parentElement,
                options: { editable: true, suggestionsEnabled: true, suggestionMinLength: 0,
                    suggestionDebounce: 0, suggestionLimit: 20, suggestionPlacement: 'auto',
                    messages: { suggestions_empty: 'No suggestions', suggestions_empty_hint: 'Enter a value',
                        suggestions_error: 'Error', suggestions_loading: 'Loading', retry: 'Retry' },
                    suggestions: Array.from({ length: 12 }, (_, index) => ({ value: 'value-' + index, label: 'Option ' + index })),
                    ...options,
                }, commitToolValue(value) { input.value = value; },
            };
            owner.$wire = { callSchemaComponentMethod: async () => owner.options.suggestions };
            window.suggestions = createInputSuggestions(owner);
            input.addEventListener('input', () => suggestions.schedule());
            input.focus(); suggestions.schedule();
        };
    ` },
    bundle: true, write: false, format: 'iife', logLevel: 'silent',
})
const css = ['core.css', 'overlay-runtime.css', 'select-field.css', 'flex-text-input.css']
    .map(name => readFileSync(new URL(`../../resources/dist/css/${name}`, import.meta.url), 'utf8')).join('\n')

for (const position of ['below', 'trailing']) {
    for (const direction of ['ltr', 'rtl']) {
        for (const mode of ['light', 'dark']) {
            for (const asyncSuggestions of [false, true]) {
                test(`${position} ${direction} ${mode} ${asyncSuggestions ? 'async' : 'local'} suggestion descriptions preserve layout and value`, async ({ page }) => {
                    await page.setViewportSize({ width: 900, height: 700 })
                    await page.setContent(`<html dir="${direction}" class="${mode === 'dark' ? 'dark' : ''}"><body><div class="fff-flex-text-input__shell" style="margin:80px 24px;width:300px"><input aria-label="Order" id="order" style="height:40px;width:100%"></div></body></html>`)
                    await page.addStyleTag({ content: css })
                    await page.addScriptTag({ content: bundle.outputFiles[0].text })
                    await page.evaluate(({ position, asyncSuggestions }) => window.mountSuggestions({
                        suggestionDescriptionPosition: position,
                        asyncSuggestions,
                        suggestions: [{ value: '001', label: '001', description: 'A long customer description '.repeat(7) }],
                    }), { position, asyncSuggestions })

                    const option = page.getByRole('option').first()
                    await expect(option).toBeVisible()
                    const insets = await option.locator(':scope > span').evaluate(el => {
                        const style = getComputedStyle(el)
                        return [parseFloat(style.paddingInlineStart), parseFloat(style.paddingInlineEnd)]
                    })
                    expect(insets[0]).toBeGreaterThan(0)
                    expect(insets[1]).toBe(insets[0])
                    const label = option.locator('.fff-select-headless-option-label')
                    const description = option.locator('.fff-input-suggestions__description')
                    const labelBox = await label.boundingBox()
                    const descriptionBox = await description.boundingBox()
                    if (position === 'trailing') {
                        expect(Math.abs(labelBox.y - descriptionBox.y)).toBeLessThan(8)
                        if (direction === 'ltr') {
                            expect(descriptionBox.x).toBeGreaterThanOrEqual(labelBox.x + labelBox.width)
                        } else {
                            expect(descriptionBox.x + descriptionBox.width).toBeLessThanOrEqual(labelBox.x)
                        }
                        await expect(description).toHaveCSS('text-overflow', 'ellipsis')
                        expect(await label.evaluate(el => el.scrollWidth <= el.clientWidth)).toBe(true)
                        expect(await description.evaluate(el => el.scrollWidth > el.clientWidth)).toBe(true)
                    } else {
                        expect(descriptionBox.y).toBeGreaterThanOrEqual(labelBox.y + labelBox.height)
                    }
                    await option.click()
                    await expect(page.getByLabel('Order')).toHaveValue('001')
                    await page.evaluate(() => suggestions.dispose())
                })
            }
        }
    }
}

for (const width of [900, 390]) {
    for (const direction of ['ltr', 'rtl']) {
        test(`trailing long labels and descriptions fit at ${width}px in ${direction}`, async ({ page }) => {
            await page.setViewportSize({ width, height: 844 })
            await page.setContent(`<html dir="${direction}"><body><div class="fff-flex-text-input__shell" style="margin:80px 24px;width:280px"><input aria-label="Campaign" id="campaign" style="height:40px;width:100%"></div></body></html>`)
            await page.addStyleTag({ content: css })
            await page.addScriptTag({ content: bundle.outputFiles[0].text })
            await page.evaluate(() => window.mountSuggestions({
                suggestionDescriptionPosition: 'trailing',
                suggestions: [
                    { value: 'long', label: 'A very long campaign title '.repeat(4), description: 'A very long channel description '.repeat(5) },
                    { value: 'plain', label: 'A suggestion without metadata' },
                ],
            }))
            const panel = page.locator('.fff-input-suggestions')
            const option = panel.getByRole('option').first()
            await expect(option).toBeVisible()
            const metrics = await option.evaluate(el => {
                const content = el.querySelector('.fff-input-suggestions__content')
                const label = content.querySelector('.fff-select-headless-option-label')
                const description = content.querySelector('.fff-input-suggestions__description')
                return {
                    width: content.clientWidth, scrollWidth: content.scrollWidth,
                    labelWidth: label.clientWidth, descriptionWidth: description.clientWidth,
                    labelTruncated: label.scrollWidth > label.clientWidth,
                    descriptionTruncated: description.scrollWidth > description.clientWidth,
                }
            })
            expect(metrics.scrollWidth).toBeLessThanOrEqual(metrics.width + 1)
            expect(metrics.labelWidth).toBeGreaterThan(24)
            expect(metrics.descriptionWidth).toBeGreaterThan(24)
            expect(metrics.labelTruncated).toBe(true)
            expect(metrics.descriptionTruncated).toBe(true)
            const plain = panel.getByRole('option').nth(1)
            expect(await plain.locator('.fff-input-suggestions__description').count()).toBe(0)
            await plain.click()
            await expect(page.getByLabel('Campaign')).toHaveValue('plain')
            await page.evaluate(() => suggestions.dispose())
        })
    }
}

test('native mobile handle drag preserves input focus, then option selection and blur still close suggestions', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 })
    await page.setContent('<div class="fff-flex-text-input__shell" style="margin:80px 24px"><input aria-label="Subject" id="subject" style="height:40px;width:100%"></div><button id="outside" style="position:fixed;top:5px">Outside</button>')
    await page.addStyleTag({ content: css })
    await page.addScriptTag({ content: bundle.outputFiles[0].text })
    await page.evaluate(() => window.mountSuggestions())
    const panel = page.locator('.fff-input-suggestions')
    const handle = panel.locator('[data-fff-overlay-handle]')
    await expect(handle).toBeVisible()
    await expect(panel).toHaveClass(/is-positioned/)
    await expect(panel).not.toHaveAttribute('data-fff-sheet-entering', 'true')
    const insets = await panel.getByRole('option').first().locator(':scope > span').evaluate(el => {
        const style = getComputedStyle(el)
        return [parseFloat(style.paddingInlineStart), parseFloat(style.paddingInlineEnd)]
    })
    expect(insets[0]).toBeGreaterThan(0)
    expect(insets[1]).toBe(insets[0])
    const bounds = await handle.boundingBox()
    const x = bounds.x + bounds.width / 2
    const y = bounds.y + bounds.height / 2
    await page.mouse.move(x, y)
    await page.mouse.down()
    await page.mouse.move(x, y + 35, { steps: 8 })
    await expect(page.getByLabel('Subject')).toBeFocused()
    await expect(panel).toHaveAttribute('data-fff-sheet-dragging', 'true')
    expect(await page.evaluate(() => owner.suggestionsOpen)).toBe(true)
    await page.mouse.move(x, y, { steps: 8 })
    await page.mouse.up()
    await expect(page.getByLabel('Subject')).toBeFocused()
    await panel.getByRole('option', { name: 'Option 0', exact: true }).click()
    await expect(page.getByLabel('Subject')).toHaveValue('value-0')
    expect(await page.evaluate(() => owner.suggestionsOpen)).toBe(false)

    await page.getByLabel('Subject').fill('Option')
    await expect(panel.getByRole('option').first()).toBeVisible()
    await page.getByRole('button', { name: 'Outside' }).click()
    expect(await page.evaluate(() => owner.suggestionsOpen)).toBe(false)
    await page.evaluate(() => suggestions.dispose())
    await expect(panel).toHaveCount(0)
})

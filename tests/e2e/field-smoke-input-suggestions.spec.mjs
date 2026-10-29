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

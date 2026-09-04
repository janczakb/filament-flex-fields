import { expect, test } from '@playwright/test'
import { gotoPlaygroundPage } from './global-setup.mjs'

test('browser timezone trigger paints catalog label immediately and never swaps names', async ({ page }) => {
  test.setTimeout(60_000)
  await gotoPlaygroundPage(page, 'timezone-field')
  await page.reload({ waitUntil: 'domcontentloaded' })

  const samples = []
  const t0 = Date.now()

  while (Date.now() - t0 < 1800) {
    const sample = await page.evaluate(() => {
      const lab = [...document.querySelectorAll('label')]
        .find((node) => /Browser timezone/i.test(node.textContent || ''))
      const wrap = lab?.closest('.fi-fo-field-wrp, .fi-sc-component')
      const root = wrap?.querySelector('.fff-timezone-field')

      if (! root) {
        return { missing: true }
      }

      const ssr = root.querySelector('.fff-timezone-field__ssr-label')
      const labelCtn = root.querySelector('.fff-timezone-field__label')
      const visibleBits = []

      for (const el of labelCtn?.querySelectorAll('span') || []) {
        const style = getComputedStyle(el)

        if (style.visibility === 'hidden' || style.display === 'none' || Number(style.opacity) < 0.05) {
          continue
        }

        const text = (el.textContent || '').replace(/\s+/g, ' ').trim()

        if (text) {
          visibleBits.push(text)
        }
      }

      return {
        visibleBits,
        ssr: ssr?.textContent?.trim() || '',
        booted: root.dataset.fffTzBooted === '1',
        displayReady: window.Alpine?.$data?.(root)?.displayReady ?? null,
      }
    }).catch(() => null)

    if (sample) {
      const key = JSON.stringify(sample)

      if (samples.at(-1) !== key) {
        samples.push(key)
      }

      if (! sample.missing && sample.visibleBits.length > 0) {
        const joined = sample.visibleBits.join(' ')
        expect(joined.trim().length).toBeGreaterThan(0)
        expect(joined).not.toMatch(/Central European/i)
        expect(joined).not.toMatch(/Poland Time/i)
        expect(joined).not.toMatch(/^select timezone$/i)
      }
    }

    await page.waitForTimeout(40)
  }

  expect(samples.length).toBeGreaterThan(0)

  const parsed = samples.map((row) => JSON.parse(row)).filter((row) => ! row.missing && row.visibleBits.length)
  const labels = [...new Set(parsed.map((row) => row.visibleBits.join(' ').trim()))]

  expect(labels.length).toBe(1)
  expect(labels[0]).toMatch(/Warsaw, Poland|Warszawa, Polska/i)
  expect(labels[0]).not.toMatch(/Central European|Poland Time/i)
  expect(parsed.at(-1).displayReady).not.toBe(true)
})

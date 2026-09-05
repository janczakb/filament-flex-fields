import { expect, test } from '@playwright/test'

import { gotoPlaygroundPage } from './global-setup.mjs'
import { waitForSelectCoordinatorAttached } from './helpers/console-errors.mjs'

/**
 * Browser probe: Project members mobile sheet enter must slide smoothly.
 * Samples transform + long tasks while opening — fails on frozen/skipped enter.
 *
 * Livewire option fetch must not run during the enter window (main-thread long
 * tasks starve rAF and leave the menu without sheet/is-open).
 */
test('Project members mobile sheet enter is smooth', async ({ page }) => {
    test.setTimeout(90_000)

    await page.setViewportSize({ width: 390, height: 844 })
    await page.emulateMedia({ colorScheme: 'light' })

    // Force sheet mode even if Playwright reports fine pointer.
    await page.addInitScript(() => {
        const original = window.matchMedia.bind(window)
        window.matchMedia = (query) => {
            const q = String(query)
            if (q.includes('pointer: coarse')) {
                return {
                    matches: true,
                    media: q,
                    onchange: null,
                    addListener() {},
                    removeListener() {},
                    addEventListener() {},
                    removeEventListener() {},
                    dispatchEvent() { return false },
                }
            }
            if (q.includes('pointer: fine')) {
                return {
                    matches: false,
                    media: q,
                    onchange: null,
                    addListener() {},
                    removeListener() {},
                    addEventListener() {},
                    removeEventListener() {},
                    dispatchEvent() { return false },
                }
            }
            return original(query)
        }
    })

    await gotoPlaygroundPage(page, 'user-select')
    await waitForSelectCoordinatorAttached(page)

    const trigger = page.locator('#form\\.user_select__members')
    await expect(trigger).toBeVisible({ timeout: 20_000 })
    await trigger.scrollIntoViewIfNeeded()
    // Let Livewire/Alpine settle so open cost is the sheet path, not cold boot.
    await page.waitForTimeout(500)

    const report = await page.evaluate(async () => {
        const btn = document.getElementById('form.user_select__members')
        if (! btn) {
            return { error: 'missing trigger' }
        }

        const longTasks = []
        let observer = null
        try {
            observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    longTasks.push({
                        duration: entry.duration,
                        startTime: entry.startTime,
                        name: entry.name,
                    })
                }
            })
            observer.observe({ entryTypes: ['longtask'] })
        } catch {
            // Safari / some Chromium builds may lack longtask.
        }

        const samples = []
        let rafId = 0
        const clickAt = performance.now()

        const sampleLoop = () => {
            const menu = document.querySelector(
                'body > .fff-select-dropdown-panel.fff-teleported-menu--sheet.is-open, body > .fff-select-dropdown-panel.fff-overlay-sheet.is-open, body > .fi-dropdown-panel.fff-teleported-menu--sheet.is-open',
            ) ?? document.querySelector(
                'body > .fff-select-dropdown-panel.fff-teleported-menu--sheet, body > .fff-select-dropdown-panel.fff-overlay-sheet',
            )
            const cs = menu ? getComputedStyle(menu) : null
            const matrix = cs?.transform || null
            let ty = null
            if (matrix && matrix !== 'none') {
                const m = matrix.match(/matrix\(([^)]+)\)/)
                    || matrix.match(/matrix3d\(([^)]+)\)/)
                if (m) {
                    const parts = m[1].split(',').map((v) => Number(v.trim()))
                    ty = matrix.startsWith('matrix3d') ? parts[13] : parts[5]
                }
            }

            samples.push({
                t: performance.now() - clickAt,
                hasMenu: Boolean(menu),
                sheet: Boolean(
                    menu?.classList.contains('fff-teleported-menu--sheet')
                    || menu?.classList.contains('fff-overlay-sheet'),
                ),
                isOpen: menu?.classList.contains('is-open') ?? false,
                entering: menu?.dataset?.fffSheetEntering === 'true',
                hidden: menu?.hidden ?? null,
                display: cs?.display || null,
                transform: matrix,
                ty,
                transitionProperty: cs?.transitionProperty || null,
                height: menu ? Math.round(menu.getBoundingClientRect().height) : null,
            })

            if (performance.now() - clickAt < 900) {
                rafId = requestAnimationFrame(sampleLoop)
            }
        }

        rafId = requestAnimationFrame(sampleLoop)
        btn.click()

        await new Promise((resolve) => setTimeout(resolve, 1000))
        cancelAnimationFrame(rafId)
        observer?.disconnect?.()

        const withTy = samples.filter((s) => typeof s.ty === 'number')
        const openSample = samples.find((s) => s.isOpen)
        const sheetSample = samples.find((s) => s.sheet)
        const firstMenu = samples.find((s) => s.hasMenu)

        const tySeries = withTy.map((s) => Math.round(s.ty))
        const uniqueTy = [...new Set(tySeries)]

        let maxFrameGap = 0
        for (let i = 1; i < samples.length; i++) {
            // Only score gaps during the enter window (first 400ms).
            if (samples[i].t > 400) {
                break
            }
            maxFrameGap = Math.max(maxFrameGap, samples[i].t - samples[i - 1].t)
        }

        const enterLongTasks = longTasks
            .filter((t) => t.startTime >= clickAt && t.startTime <= clickAt + 400)
            .map((t) => ({ duration: Math.round(t.duration), at: Math.round(t.startTime - clickAt) }))

        return {
            clickToMenuMs: firstMenu ? firstMenu.t : null,
            clickToSheetMs: sheetSample ? sheetSample.t : null,
            clickToOpenMs: openSample ? openSample.t : null,
            sampleCount: samples.length,
            uniqueTyCount: uniqueTy.length,
            tySeries: tySeries.slice(0, 40),
            uniqueTy: uniqueTy.slice(0, 20),
            maxFrameGapEnter: maxFrameGap,
            enterLongTasks,
            firstOpen: openSample || null,
            mid: samples[Math.floor(samples.length / 3)] || null,
            last: samples[samples.length - 1] || null,
        }
    })

    console.log('SHEET_PERF_REPORT', JSON.stringify(report, null, 2))

    expect(report.error, JSON.stringify(report)).toBeUndefined()
    expect(report.clickToSheetMs, `never became sheet: ${JSON.stringify(report)}`).not.toBeNull()
    expect(report.clickToOpenMs, JSON.stringify(report)).not.toBeNull()

    // Enter should start within ~400ms of click (nextTick+rAF budget).
    expect(report.clickToOpenMs, `slow open: ${JSON.stringify(report)}`).toBeLessThan(400)
    expect(report.clickToSheetMs, `slow sheet class: ${JSON.stringify(report)}`).toBeLessThan(100)

    const prefersReduced = await page.evaluate(() => window.matchMedia('(prefers-reduced-motion: reduce)').matches)
    if (! prefersReduced) {
        expect(report.uniqueTyCount, `no intermediate frames: ${JSON.stringify(report)}`).toBeGreaterThanOrEqual(3)
    }

    // Enter window must stay interactive — no multi-frame freezes / Livewire longtasks.
    expect(report.maxFrameGapEnter, `enter freeze: ${JSON.stringify(report)}`).toBeLessThan(80)
    expect(
        report.enterLongTasks.filter((t) => t.duration >= 80).length,
        `long tasks during enter: ${JSON.stringify(report)}`,
    ).toBe(0)

    const menu = page.locator('body > .fff-select-dropdown-panel.fff-teleported-menu--sheet, body > .fi-dropdown-panel.fff-overlay-sheet').first()
    await expect(menu).toHaveClass(/is-open/)
})

import assert from 'node:assert/strict'
import fs from 'node:fs'
import path from 'node:path'
import { describe, it } from 'node:test'
import { fileURLToPath } from 'node:url'

const distCss = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../resources/dist/css')

describe('overlay sheet iOS safe-area flush', () => {
    it('keeps opaque sheet fill with safe-area as padding-bottom only (Apple path)', () => {
        const teleported = fs.readFileSync(path.join(distCss, 'teleported-menu.css'), 'utf8')
        const select = fs.readFileSync(path.join(distCss, 'select-field.css'), 'utf8')
        const overlay = fs.readFileSync(path.join(distCss, 'overlay-runtime.css'), 'utf8')

        const sheetRule = teleported.match(
            /\.fff-teleported-menu\.fff-teleported-menu--sheet,\.fff-teleported-menu\.fff-overlay-sheet\{[^}]+\}/,
        )?.[0] ?? ''

        assert.ok(sheetRule.length > 0, 'canonical teleported sheet rule must exist')
        assert.match(
            sheetRule,
            /--fff-overlay-sheet-pad-bottom:\s*env\(safe-area-inset-bottom/,
            'safe-area token must use env(safe-area-inset-bottom) only',
        )
        assert.match(
            sheetRule,
            /padding-bottom:\s*var\(--fff-overlay-sheet-pad-bottom\)/,
            'safe-area must be applied as padding-bottom',
        )
        assert.match(
            sheetRule,
            /(?:inset:auto 0 0|bottom:0)/,
            'sheet must pin to the viewport bottom',
        )
        assert.equal(
            /(?:^|[^-])bottom\s*:\s*var\(--fff-overlay-sheet-pad-bottom\)/.test(sheetRule)
            || /(?:^|[^-])bottom\s*:\s*env\(safe-area/.test(sheetRule)
            || /(?:^|[^-])bottom\s*:\s*max\([^)]*safe-area/.test(sheetRule),
            false,
            'safe-area must never lift the sheet via bottom:',
        )
        assert.match(
            sheetRule,
            /background:\s*#fff(?:fff)?/,
            'mobile sheets use opaque white',
        )
        assert.match(
            sheetRule,
            /backdrop-filter:\s*none/,
            'mobile sheets disable backdrop-filter',
        )
        assert.equal(
            /chrome-bleed/.test(sheetRule),
            false,
            'must not paint fake chrome-bleed under Safari toolbar',
        )
        assert.equal(
            /box-shadow:\s*0\s+var\(--fff-overlay-sheet-chrome-bleed/.test(teleported),
            false,
            'must not use chrome-bleed box-shadow hacks',
        )
        assert.equal(
            /html\.fff-overlay-sheet-open\{[^}]*background-color/.test(overlay),
            false,
            'must not paint html/body behind the sheet for Safari chrome',
        )
        assert.match(
            select,
            /background:\s*#fff(?:fff)?/,
            'select sheets keep opaque white fill',
        )
        assert.match(
            select,
            /padding-bottom:\s*var\(--fff-overlay-sheet-pad-bottom/,
            'select sheets must keep safe-area padding-bottom',
        )
        assert.match(
            select,
            /fff-teleported-menu--sheet[^}]*fi-select-input-search-ctn[^}]*fi-input,?[^}]*font-size:16px/,
            'sheet search inputs must be ≥16px to prevent iOS focus zoom',
        )
        assert.match(
            teleported,
            /fff-teleported-menu--sheet\s+\.fff-teleported-menu__search[\s\S]*?font-size:16px!important/,
            'teleported sheet search must be ≥16px to prevent iOS focus zoom',
        )
    })
})

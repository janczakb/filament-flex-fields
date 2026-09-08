import assert from 'node:assert/strict'
import fs from 'node:fs'
import path from 'node:path'
import { describe, it } from 'node:test'
import { fileURLToPath } from 'node:url'

const distCss = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../resources/dist/css')

function assertPhoneMenuKeepsGlass(css, label) {
    assert.equal(
        css.includes('fff-phone-field__country-menu.fff-teleported-menu'),
        false,
        `${label}: must not re-scope teleported glass selectors onto the phone menu`,
    )
    assert.equal(
        /fff-phone-field__country-menu[^\{]*\{[^}]*transform:\s*none/.test(css),
        false,
        `${label}: phone country menu must keep teleported-menu scale glass (no transform:none override)`,
    )
    assert.equal(
        /fff-phone-field__country-menu[^\{]*\{[^}]*transition:\s*opacity\s*\.14s/.test(css),
        false,
        `${label}: phone country menu must not use opacity-only enter/exit`,
    )
}

describe('phone country menu glass CSS (panel + playground)', () => {
    it('keeps glass on the production phone-field stylesheet', () => {
        const css = fs.readFileSync(path.join(distCss, 'phone-field.css'), 'utf8')

        assertPhoneMenuKeepsGlass(css, 'phone-field.css')
        assert.match(css, /\.fff-phone-field__country-menu\.is-positioned\{[^}]*visibility:\s*visible/)
    })

    it('keeps glass on the playground phone stylesheet', () => {
        const css = fs.readFileSync(path.join(distCss, 'playground-phone-field.css'), 'utf8')

        assertPhoneMenuKeepsGlass(css, 'playground-phone-field.css')
        // Playground concatenates teleported-menu — glass scale must be present.
        assert.ok(
            css.includes('scale(.96)') || css.includes('scale(0.96)'),
            'playground-phone-field.css must include teleported glass scale',
        )
    })

    it('overlay-runtime does not apply fade transform to teleported menus', () => {
        const css = fs.readFileSync(path.join(distCss, 'overlay-runtime.css'), 'utf8')

        assert.ok(
            css.includes('fff-overlay-panel:not(.fff-teleported-menu)'),
            'generic overlay-panel fade must exclude .fff-teleported-menu so phone/country/select glass wins everywhere',
        )
        assert.equal(
            /\.fff-overlay-panel\{[^}]*transform:\s*translateY\(0\.375rem\)/.test(css.replace(/\s+/g, '')),
            false,
            'unscoped .fff-overlay-panel translateY fade must not ship — it flattened phone glass outside playground',
        )
    })
})

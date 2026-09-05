import test from 'node:test'
import assert from 'node:assert/strict'
import imageChoiceCardsFormComponent from '../../resources/js/components/image-choice-cards.js'

test('radio select replaces previous value (exclusive)', () => {
    const component = imageChoiceCardsFormComponent({
        state: 'a',
        multiple: false,
        disabledOptions: {},
        disabled: false,
        rippleEnabled: false,
        maxSelections: null,
    })

    component.select('b')

    assert.equal(component.state, 'b')
    assert.equal(component.isSelected('a'), false)
    assert.equal(component.isSelected('b'), true)
})

test('checkbox toggle respects maxSelections', () => {
    const component = imageChoiceCardsFormComponent({
        state: ['a'],
        multiple: true,
        disabledOptions: {},
        disabled: false,
        rippleEnabled: false,
        maxSelections: 2,
    })

    component.toggle('b')
    assert.deepEqual(component.state, ['a', 'b'])

    component.toggle('c')
    assert.deepEqual(component.state, ['a', 'b'])
})

test('clearDisabledSelection drops locked keys', () => {
    const single = imageChoiceCardsFormComponent({
        state: 'locked',
        multiple: false,
        disabledOptions: { locked: true },
        disabled: false,
        rippleEnabled: false,
        maxSelections: null,
    })

    single.clearDisabledSelection()
    assert.equal(single.state, null)

    const multi = imageChoiceCardsFormComponent({
        state: ['ok', 'locked'],
        multiple: true,
        disabledOptions: { locked: true },
        disabled: false,
        rippleEnabled: false,
        maxSelections: null,
    })

    multi.clearDisabledSelection()
    assert.deepEqual(multi.state, ['ok'])
})

test('syncFromDomIfNeeded adopts a pre-hydrate radio tap', () => {
    const component = imageChoiceCardsFormComponent({
        state: 'athletic',
        multiple: false,
        disabledOptions: {},
        disabled: false,
        rippleEnabled: false,
        maxSelections: null,
    })

    component.$root = {
        querySelectorAll: () => [{ value: 'slim', checked: true }],
    }

    component.syncFromDomIfNeeded()

    assert.equal(component.state, 'slim')
})

test('ripple skips touch / coarse pointers', () => {
    const component = imageChoiceCardsFormComponent({
        state: null,
        multiple: false,
        disabledOptions: {},
        disabled: false,
        rippleEnabled: true,
        maxSelections: null,
    })

    const card = { clientWidth: 100, clientHeight: 100, appendChild() {}, getBoundingClientRect() {
        return { left: 0, top: 0, width: 100, height: 100 }
    } }

    const originalMatchMedia = globalThis.window?.matchMedia
    globalThis.window = {
        ...(globalThis.window ?? {}),
        matchMedia: () => ({ matches: true }),
        setTimeout: globalThis.setTimeout,
    }

    component.ripple({
        pointerType: 'touch',
        currentTarget: card,
        clientX: 10,
        clientY: 10,
    })

    // No throw / no DOM append for touch.
    assert.equal(component.rippleEnabled, true)

    if (originalMatchMedia) {
        globalThis.window.matchMedia = originalMatchMedia
    }
})

test('bindImageLoading marks already-complete images', () => {
    const component = imageChoiceCardsFormComponent({
        state: null,
        multiple: false,
        disabledOptions: {},
        disabled: false,
        rippleEnabled: false,
        maxSelections: null,
    })

    const img = {
        complete: true,
        naturalWidth: 120,
        classList: { added: [], add(name) { this.added.push(name) } },
        addEventListener() {},
    }

    component.$root = {
        querySelectorAll: (selector) => selector.includes('image') ? [img] : [],
    }

    component.bindImageLoading()

    assert.deepEqual(img.classList.added, ['is-loaded'])
})

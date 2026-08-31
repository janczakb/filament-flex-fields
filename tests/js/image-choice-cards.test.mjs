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

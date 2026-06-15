import assert from 'node:assert/strict'
import test from 'node:test'

import {
    buildCategoryIconStyles,
    getCategoryIconGroupIds,
    getCategoryIconSvg,
    prepareGravitySvg,
} from '../../resources/js/core/emoji-picker-category-icons.js'

test('getCategoryIconGroupIds returns every emoji-picker-element nav group', () => {
    assert.deepEqual(getCategoryIconGroupIds(), [
        '-1',
        '0',
        '1',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
    ])
})

test('getCategoryIconSvg returns gravity svg markup for known groups', () => {
    const svg = getCategoryIconSvg(0)

    assert.match(svg, /<svg[^>]*viewBox="0 0 16 16"/)
    assert.match(svg, /fill="currentColor"/)
})

test('prepareGravitySvg adds gravity icon class and removes fixed dimensions', () => {
    const prepared = prepareGravitySvg('<svg width="16" height="16" viewBox="0 0 16 16"><path /></svg>')

    assert.match(prepared, /class="fff-gravity-icon"/)
    assert.doesNotMatch(prepared, /width="16"/)
    assert.doesNotMatch(prepared, /height="16"/)
})

test('buildCategoryIconStyles reorders nav above search and styles inline svg icons', () => {
    const styles = buildCategoryIconStyles(false, 'rgb(59 130 246)')

    assert.match(styles, /\.nav\s*\{\s*order:\s*1/)
    assert.match(styles, /\.search-row\s*\{\s*order:\s*3/)
    assert.match(styles, /\.nav-emoji \.fff-gravity-icon/)
    assert.match(styles, /\.fff-search-icon/)
    assert.doesNotMatch(styles, /mask-image:/)
    assert.match(styles, /\.nav-emoji\.emoji:active/)
    assert.match(styles, /color: rgb\(59 130 246\)/)
})

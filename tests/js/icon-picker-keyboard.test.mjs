import test from 'node:test'
import assert from 'node:assert/strict'
import { highlightIconLabel } from '../../resources/js/core/icon-picker-keyboard.js'

test('highlightIconLabel wraps matching query text', () => {
    const html = highlightIconLabel('O Star', 'star')

    assert.match(html, /<mark class="fff-icon-picker__highlight">Star<\/mark>/)
})

test('highlightIconLabel returns original label when query is empty', () => {
    assert.equal(highlightIconLabel('O Star', ''), 'O Star')
})

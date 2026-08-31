import assert from 'node:assert/strict'
import test from 'node:test'

import { parseEntityMentionQuery } from '../../resources/js/core/entity-mention.js'

test('parseEntityMentionQuery detects active mention term', () => {
    const parsed = parseEntityMentionQuery('assign @jan', '@')

    assert.equal(parsed.active, true)
    assert.equal(parsed.term, 'jan')
    assert.equal(parsed.search, 'jan')
})

test('parseEntityMentionQuery ignores mention after whitespace break', () => {
    const parsed = parseEntityMentionQuery('hello @jan doe', '@')

    assert.equal(parsed.active, false)
    assert.equal(parsed.search, 'hello @jan doe')
})

test('parseEntityMentionQuery supports inlineSearch query prefix', () => {
    const parsed = parseEntityMentionQuery('@jan', '@')

    assert.equal(parsed.active, true)
    assert.equal(parsed.term, 'jan')
    assert.equal(parsed.search, 'jan')
})

test('parseEntityMentionQuery supports partial inline typing', () => {
    const parsed = parseEntityMentionQuery('@', '@')

    assert.equal(parsed.active, true)
    assert.equal(parsed.term, '')
    assert.equal(parsed.search, '')
})

test('parseEntityMentionQuery returns plain search when trigger absent', () => {
    const parsed = parseEntityMentionQuery('plain search', '@')

    assert.equal(parsed.active, false)
    assert.equal(parsed.search, 'plain search')
})

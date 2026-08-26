import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    bumpSelectCloseToken,
    shouldCommitDeferredClose,
} from '../../resources/js/components/select-field/select-field-close-guard.js'

describe('select-field close guard', () => {
    it('bumps a monotonic close token', () => {
        const select = {}

        assert.equal(bumpSelectCloseToken(select), 1)
        assert.equal(bumpSelectCloseToken(select), 2)
        assert.equal(select.__fffCloseToken, 2)
    })

    it('commits deferred close only for the latest token while closed', () => {
        const select = { isOpen: false, __fffCloseToken: 3 }

        assert.equal(shouldCommitDeferredClose(select, 3), true)
        assert.equal(shouldCommitDeferredClose(select, 2), false)

        select.isOpen = true

        assert.equal(shouldCommitDeferredClose(select, 3), false)
    })

    it('invalidates a pending close after reopen bumps the token', () => {
        const select = { isOpen: false }
        const closingToken = bumpSelectCloseToken(select)

        select.isOpen = true
        bumpSelectCloseToken(select)
        select.isOpen = true

        assert.equal(shouldCommitDeferredClose(select, closingToken), false)
    })
})

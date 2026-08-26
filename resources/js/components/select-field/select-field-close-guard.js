/**
 * Guards deferred close animations against reopen races.
 * Close sets isOpen=false immediately, then restores the portaled panel after transition.
 * A reopen in that window must invalidate the pending restore/originalClose.
 */

export function bumpSelectCloseToken(select) {
    if (! select) {
        return 0
    }

    select.__fffCloseToken = (select.__fffCloseToken ?? 0) + 1

    return select.__fffCloseToken
}

export function shouldCommitDeferredClose(select, token) {
    if (! select) {
        return false
    }

    return token === select.__fffCloseToken && ! select.isOpen
}

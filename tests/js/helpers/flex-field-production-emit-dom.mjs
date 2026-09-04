/**
 * Production-fidelity DOM fixtures for FFART emit-assets (batch-only).
 */

import { css } from './flex-field-asset-injector-dom.mjs'

export function createProductionEmitBatch({
    document,
    stylesheets = [css('select-field')],
    chunks = [`/js/janczakb/filament-flex-fields/components/flex-fields-select-field.js`],
    consumerComponent = 'select-field',
    livewireKey = 'lw1.authorId',
    parent = null,
} = {}) {
    const batch = document.createElement('span')
    batch.hidden = true
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': JSON.stringify(stylesheets),
        'data-fff-chunks': JSON.stringify(chunks),
    }
    batch.dataset.fffAssetBatch = ''
    batch.dataset.fffStylesheets = JSON.stringify(stylesheets)
    batch.dataset.fffChunks = JSON.stringify(chunks)

    if (consumerComponent && livewireKey) {
        batch.attributes['data-fff-asset-consumer'] = consumerComponent
        batch.attributes['data-fff-asset-consumer-id'] = livewireKey
        batch.dataset.fffAssetConsumer = consumerComponent
        batch.dataset.fffAssetConsumerId = livewireKey
    }

    ;(parent ?? document.body).appendChild(batch)

    return batch
}

export function mountProductionPageSelect({ document, livewireKey = 'lw1.authorId' } = {}) {
    return createProductionEmitBatch({ document, livewireKey })
}

export function mountProductionModalSelect({ document, modal, livewireKey = 'lw1.authorId' } = {}) {
    return createProductionEmitBatch({ document, livewireKey, parent: modal })
}

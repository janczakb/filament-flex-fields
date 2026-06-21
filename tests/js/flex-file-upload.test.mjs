import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import flexFileUploadFormComponent, {
    createDropzoneBehavior,
    createPondBridgeBehavior,
    createReadyWatchBehavior,
    createSummaryBehavior,
    syncFilePondCompactLayout,
} from '../../resources/js/components/flex-file-upload.js'

describe('flexFileUploadFormComponent', () => {
    it('exports focused behavior factories', () => {
        assert.equal(typeof createDropzoneBehavior, 'function')
        assert.equal(typeof createPondBridgeBehavior, 'function')
        assert.equal(typeof createReadyWatchBehavior, 'function')
        assert.equal(typeof createSummaryBehavior, 'function')
    })

    it('composes lifecycle methods on the Alpine factory', () => {
        const component = flexFileUploadFormComponent({
            showUploadSummary: true,
            statePath: 'data.attachment',
        })

        assert.equal(typeof component.init, 'function')
        assert.equal(typeof component.destroy, 'function')
        assert.equal(typeof component.watchReady, 'function')
        assert.equal(typeof component.bindFileUpload, 'function')
        assert.equal(typeof component.refreshSummary, 'function')
        assert.equal(component.statePath, 'data.attachment')
        assert.equal(component.showUploadSummary, true)
    })

    it('refreshSummary formats template placeholders for processed uploads', () => {
        const component = flexFileUploadFormComponent({
            showUploadSummary: true,
            summaryTemplate: ':count files · :size KB',
        })

        component.getFileUploadComponent = () => ({
            pond: {
                getFiles: () => [
                    { origin: 2, fileSize: 2048, status: 5 },
                    { origin: 1, fileSize: 9999, status: 5, serverId: 'abc' },
                    { origin: 1, fileSize: 512, status: 3 },
                ],
            },
        })

        component.refreshSummary()

        assert.equal(component.summaryLabel, '2 files · 11.8 KB')
    })

    it('refreshSummary hides label until at least one processed upload exists', () => {
        const component = flexFileUploadFormComponent({
            showUploadSummary: true,
            summaryTemplate: ':count files · :size KB',
        })

        component.getFileUploadComponent = () => ({
            pond: {
                getFiles: () => [
                    { origin: 1, fileSize: 9999, status: 3 },
                ],
            },
        })

        component.refreshSummary()

        assert.equal(component.summaryLabel, '')
    })

    it('refreshRemainingSlots updates remaining slots label from pond file count', () => {
        const component = flexFileUploadFormComponent({
            remainingSlotsTemplate: ':remaining of :max slots remaining',
            maxFiles: 5,
        })

        component.getFileUploadComponent = () => ({
            pond: {
                getFiles: () => [
                    { origin: 1, fileSize: 1024, status: 5, serverId: 'a' },
                    { origin: 1, fileSize: 1024, status: 5, serverId: 'b' },
                    { origin: 1, fileSize: 1024, status: 5, serverId: 'c' },
                ],
            },
        })

        component.refreshSummary()

        assert.equal(component.remainingSlotsLabel, '2 of 5 slots remaining')
    })

    it('syncFilePondCompactLayout is a no-op stub', () => {
        assert.equal(syncFilePondCompactLayout(), false)
    })
})

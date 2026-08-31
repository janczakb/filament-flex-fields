import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'

const root = join(dirname(fileURLToPath(import.meta.url)), '../..')
const source = readFileSync(join(root, 'resources/js/components/icon-picker-field.js'), 'utf8')
const virtualScroll = readFileSync(join(root, 'resources/js/core/icon-picker-virtual-scroll.js'), 'utf8')

test('icon picker waits for panelReady with a pending flag instead of $watch cleanup', () => {
    assert.match(source, /pendingResultsRefresh/)
    assert.match(source, /whenPanelResultsReady/)
    assert.match(source, /markIconPickerTriggerReady/)
    assert.match(virtualScroll, /ensureResultsGeometryReady/)
    assert.doesNotMatch(source, /schedulePanelResultsRefresh/)
    assert.doesNotMatch(source, /unwatch\(\)/)
})

test('icon picker anchors menu width and trigger before teleported positioning', () => {
    assert.match(source, /resolveMenuTriggerElement\(\)/)
    assert.match(source, /applyIconPickerPanelWidth\(\)/)
    assert.match(source, /teleportedMenu\.updateMenuPosition\.call\(this/)
    assert.match(source, /closest\('\.fi-select-input-ctn'\)/)
})

test('virtual scroll mixin merges getters before alpine init', () => {
    assert.match(virtualScroll, /mergeAlpineComponentDescriptors/)
    assert.match(source, /return mergeAlpineComponentDescriptors\(component, createIconPickerVirtualScrollMixin\(\)\)/)
    assert.doesNotMatch(source, /mergeAlpineComponentDescriptors\(this, createIconPickerVirtualScrollMixin\(\)\)/)
})

test('virtual scroll computes live window from virtualScrollTop', () => {
    assert.match(virtualScroll, /void this\.virtualScrollTop/)
    assert.match(virtualScroll, /resolveLiveResultsWindow/)
    assert.match(virtualScroll, /initialSkeletonSlots/)
    assert.match(virtualScroll, /virtualTopSpacerStyle/)
})

test('initial skeleton waits for positioned panel before showing', () => {
    assert.match(virtualScroll, /panelReady && this\.iconLoadingPhase === 'initial'/)
    assert.match(virtualScroll, /resultsGeometryReady/)
})

test('virtual scroll only mounts a bounded window for large lists', () => {
    assert.match(virtualScroll, /shouldUseIconPickerVirtualTrack/)
    assert.match(virtualScroll, /gridGeometryLocked/)
    assert.match(virtualScroll, /syncVirtualGridGeometry\(\{ force/)
})

import assert from 'node:assert/strict'
import { test } from 'node:test'

import {
    bootTimezoneBrowserSsrElement,
    bootTimezoneBrowserSsrDefaults,
} from '../../resources/js/core/timezone-browser-ssr-boot.js'
import timezoneFieldFormComponent from '../../resources/js/components/timezone-field.js'

function createClassList(initial = []) {
    const classes = new Set(initial)

    return {
        classes,
        add(name) {
            classes.add(name)
        },
        remove(name) {
            classes.delete(name)
        },
        contains(name) {
            return classes.has(name)
        },
    }
}

function mountBrowserTimezoneFieldDom({
    catalog = {
        'Europe/Warsaw': ['Warsaw, Poland', 'UTC+02:00'],
        UTC: ['UTC', 'UTC+00:00'],
    },
    ssrText = 'Select timezone',
    placeholder = true,
} = {}) {
    const ssrLabel = {
        textContent: ssrText,
        classList: createClassList(placeholder ? ['is-placeholder'] : []),
        attrs: {},
        setAttribute(name, value) {
            this.attrs[name] = String(value)
        },
        getAttribute(name) {
            return this.attrs[name] ?? null
        },
        removeAttribute(name) {
            delete this.attrs[name]
        },
    }

    const ssrMeta = {
        textContent: '',
    }

    const boot = {
        attrs: {
            'data-fff-timezone-boot': '1',
            'data-fff-timezone-catalog': JSON.stringify(catalog),
        },
        getAttribute(name) {
            return this.attrs[name] ?? null
        },
        setAttribute(name, value) {
            this.attrs[name] = String(value)
        },
        closest(selector) {
            return selector === '.fff-timezone-field' ? root : null
        },
    }

    const root = {
        dataset: {},
        querySelector(selector) {
            if (selector === '.fff-timezone-field__ssr-label') {
                return ssrLabel
            }

            if (selector === '.fff-timezone-field__ssr-meta') {
                return ssrMeta
            }

            if (selector === '[data-fff-timezone-boot]') {
                return boot
            }

            return null
        },
    }

    return { root, boot, ssrLabel, ssrMeta }
}

function stubResolvedTimezone(timeZone) {
    const originalResolved = Intl.DateTimeFormat.prototype.resolvedOptions
    Intl.DateTimeFormat.prototype.resolvedOptions = function resolvedOptions() {
        return { ...originalResolved.call(this), timeZone }
    }

    return () => {
        Intl.DateTimeFormat.prototype.resolvedOptions = originalResolved
    }
}

test('blocking SSR boot paints official catalog label, not Intl generic name', () => {
    const { root, boot, ssrLabel, ssrMeta } = mountBrowserTimezoneFieldDom()
    const restore = stubResolvedTimezone('Europe/Warsaw')

    try {
        assert.equal(bootTimezoneBrowserSsrElement(boot), true)
        assert.equal(root.dataset.fffDetectedTimezone, 'Europe/Warsaw')
        assert.equal(root.dataset.fffTzBooted, '1')
        assert.equal(ssrLabel.textContent, 'Warsaw, Poland')
        assert.equal(ssrLabel.classList.contains('is-placeholder'), false)
        assert.equal(ssrMeta.textContent, 'UTC+02:00')
        assert.doesNotMatch(ssrLabel.textContent, /Central European/i)
        assert.equal(bootTimezoneBrowserSsrElement(boot), false)
    } finally {
        restore()
    }
})

test('blocking SSR boot skips timezones missing from the catalog', () => {
    const { boot, ssrLabel } = mountBrowserTimezoneFieldDom({
        catalog: { UTC: ['UTC', 'UTC+00:00'] },
    })
    const restore = stubResolvedTimezone('Europe/Warsaw')

    try {
        assert.equal(bootTimezoneBrowserSsrElement(boot), false)
        assert.equal(ssrLabel.textContent, 'Select timezone')
        assert.equal(ssrLabel.classList.contains('is-placeholder'), true)
    } finally {
        restore()
    }
})

test('bootTimezoneBrowserSsrDefaults boots every marker in scope', () => {
    const first = mountBrowserTimezoneFieldDom()
    const second = mountBrowserTimezoneFieldDom({
        catalog: { 'America/New_York': ['New York Time (UTC-04:00)', 'UTC-04:00'] },
    })
    const scope = {
        querySelectorAll() {
            return [first.boot, second.boot]
        },
    }
    const restore = stubResolvedTimezone('Europe/Warsaw')

    try {
        bootTimezoneBrowserSsrDefaults(scope)
        assert.equal(first.root.dataset.fffTzBooted, '1')
        assert.equal(first.ssrLabel.textContent, 'Warsaw, Poland')
        assert.equal(second.boot.getAttribute('data-fff-timezone-boot-done'), '1')
        assert.notEqual(second.root.dataset.fffTzBooted, '1')
    } finally {
        restore()
    }
})

test('finalizeTimezoneTriggerHandoff keeps catalog SSR and never flips displayReady', () => {
    const ticks = []
    const ssrLabel = {
        textContent: 'Warsaw, Poland',
        classList: createClassList(),
    }
    const component = timezoneFieldFormComponent({
        state: null,
        statePath: 'timezone__browser',
        timezones: [{
            id: 'Europe/Warsaw',
            label: 'Warsaw, Poland',
            offset: 'UTC+02:00',
            offset_seconds: 7200,
            region: 'Europe',
        }],
        defaultTimezone: null,
        disabled: false,
        readOnly: false,
        searchable: true,
        showOffset: true,
        searchPlaceholder: 'Search',
        placeholder: 'Select timezone',
        browserTimezoneDefault: true,
        allowedTimezoneIdentifiers: ['Europe/Warsaw'],
        initialState: null,
    })

    component.$el = {
        dataset: { fffDetectedTimezone: 'Europe/Warsaw', fffTzBooted: '1' },
        querySelector(selector) {
            return selector === '.fff-timezone-field__ssr-label' ? ssrLabel : null
        },
    }
    component.$nextTick = (cb) => {
        ticks.push(cb)
    }
    component.displayReady = false

    component.finalizeTimezoneTriggerHandoff(0)
    assert.equal(component.state, 'Europe/Warsaw')
    assert.equal(component.displayReady, false)
    assert.equal(ssrLabel.textContent, 'Warsaw, Poland')
    assert.equal(ticks.length, 0)
})

test('finalizeTimezoneTriggerHandoff does not rewrite Intl SSR into catalog at Alpine time', () => {
    const ssrLabel = {
        textContent: 'Central European Time (UTC+02:00)',
        classList: createClassList(),
        removeAttribute() {},
    }
    const component = timezoneFieldFormComponent({
        state: 'Europe/Warsaw',
        statePath: 'timezone__browser',
        timezones: [{
            id: 'Europe/Warsaw',
            label: 'Warsaw, Poland',
            offset: 'UTC+02:00',
            offset_seconds: 7200,
            region: 'Europe',
        }],
        defaultTimezone: null,
        disabled: false,
        readOnly: false,
        searchable: true,
        showOffset: true,
        searchPlaceholder: 'Search',
        placeholder: 'Select timezone',
        browserTimezoneDefault: true,
        allowedTimezoneIdentifiers: ['Europe/Warsaw'],
        initialState: null,
    })

    component.$el = {
        dataset: { fffDetectedTimezone: 'Europe/Warsaw', fffTzBooted: '1' },
        querySelector(selector) {
            return selector === '.fff-timezone-field__ssr-label' ? ssrLabel : null
        },
    }
    component.$nextTick = () => {}
    component.displayReady = false

    component.finalizeTimezoneTriggerHandoff(0)
    assert.equal(component.displayReady, false)
    assert.equal(ssrLabel.textContent, 'Central European Time (UTC+02:00)')
})

test('applyBrowserTimezoneDefault prefers data-fff-detected-timezone from boot', () => {
    const component = timezoneFieldFormComponent({
        state: null,
        statePath: 'timezone__browser',
        timezones: [{
            id: 'Europe/Warsaw',
            label: 'Warsaw, Poland',
            offset: 'UTC+02:00',
            offset_seconds: 7200,
            region: 'Europe',
        }],
        defaultTimezone: null,
        disabled: false,
        readOnly: false,
        searchable: true,
        showOffset: true,
        searchPlaceholder: 'Search',
        placeholder: 'Select timezone',
        browserTimezoneDefault: true,
        allowedTimezoneIdentifiers: ['Europe/Warsaw'],
        initialState: null,
    })

    component.$el = {
        dataset: { fffDetectedTimezone: 'Europe/Warsaw' },
        querySelector() {
            return null
        },
    }

    let detectCalled = false
    component.detectBrowserTimezone = () => {
        detectCalled = true

        return 'America/New_York'
    }

    assert.equal(component.applyBrowserTimezoneDefault(), true)
    assert.equal(component.state, 'Europe/Warsaw')
    assert.equal(detectCalled, false)
})

test('applyBrowserTimezoneDefault is a no-op when state already set', () => {
    const component = timezoneFieldFormComponent({
        state: 'UTC',
        statePath: 'timezone',
        timezones: [{ id: 'UTC', label: 'UTC', offset: 'UTC+00:00', offset_seconds: 0, region: 'UTC' }],
        defaultTimezone: null,
        disabled: false,
        readOnly: false,
        searchable: true,
        showOffset: true,
        searchPlaceholder: 'Search',
        placeholder: 'Select timezone',
        browserTimezoneDefault: true,
        allowedTimezoneIdentifiers: ['UTC'],
        initialState: 'UTC',
    })

    let detected = false
    component.detectBrowserTimezone = () => {
        detected = true

        return 'Europe/Warsaw'
    }

    assert.equal(component.applyBrowserTimezoneDefault(), false)
    assert.equal(detected, false)
    assert.equal(component.state, 'UTC')
})

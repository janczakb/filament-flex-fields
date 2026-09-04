import assert from 'node:assert/strict'
import { test } from 'node:test'

import { createComboboxEngine } from '../../resources/js/core/combobox-engine.js'
import { normalizeSearchQuery } from '../../resources/js/core/search-normalize.js'
import headlessComboboxAlpine from '../../resources/js/components/select-field/headless-combobox-alpine.js'

test('syncClearablePresentation toggles wrapper and ctn clearable classes', () => {
    const createClassList = () => {
        const classes = new Set()

        return {
            contains(name) {
                return classes.has(name)
            },
            toggle(name, force) {
                if (force) {
                    classes.add(name)

                    return
                }

                classes.delete(name)
            },
        }
    }

    const wrapper = { classList: createClassList() }
    const ctn = { classList: createClassList() }

    const config = headlessComboboxAlpine({
        state: null,
        initialState: null,
        placeholder: 'Make your mind up...',
        clearable: true,
        options: [],
    })

    config.$el = {
        closest() {
            return wrapper
        },
    }
    config.$refs = { headlessTriggerCtn: ctn }
    config.comboboxSelectedValues = []

    config.syncClearablePresentation()
    assert.equal(wrapper.classList.contains('fff-select-field--clearable-has-value'), false)
    assert.equal(ctn.classList.contains('fi-select-input-ctn-clearable'), false)

    config.comboboxSelectedValues = ['1']
    config.syncClearablePresentation()
    assert.equal(wrapper.classList.contains('fff-select-field--clearable-has-value'), true)
    assert.equal(ctn.classList.contains('fi-select-input-ctn-clearable'), true)
})

test('markHeadlessDisplayReady waits for engine before replacing SSR', () => {
    const config = headlessComboboxAlpine({
        state: 'enterprise_agreement',
        initialState: 'enterprise_agreement',
        placeholder: 'Choose…',
        initialOptionLabel: 'Enterprise agreement (very long label)',
        options: [],
    })

    config.comboboxSelectedValues = ['enterprise_agreement']
    config.markHeadlessDisplayReady()
    assert.equal(config.displayReady, false)

    config._engine = {}
    config.markHeadlessDisplayReady()
    assert.equal(config.displayReady, true)
})

test('ensureHeadlessSsrHandoff re-applies is-replaced after remorph', () => {
    const ssr = {
        classList: {
            classes: new Set(),
            add(name) {
                this.classes.add(name)
            },
            contains(name) {
                return this.classes.has(name)
            },
        },
    }

    const shell = {
        dataset: {},
        querySelectorAll() {
            return [ssr]
        },
    }

    const config = headlessComboboxAlpine({
        state: null,
        initialState: null,
        options: [{ value: 'a', label: 'A' }],
    })

    config._engine = {}
    config.$el = {
        closest() {
            return shell
        },
    }

    config.markHeadlessDisplayReady()
    assert.equal(shell.dataset.fffSelectAttached, 'true')
    assert.equal(ssr.classList.contains('is-replaced'), true)

    ssr.classList.classes.delete('is-replaced')
    delete shell.dataset.fffSelectAttached

    config.ensureHeadlessSsrHandoff()
    assert.equal(shell.dataset.fffSelectAttached, 'true')
    assert.equal(ssr.classList.contains('is-replaced'), true)
})

test('markHeadlessDisplayReady waits for label metadata when options are deferred', () => {
    const config = headlessComboboxAlpine({
        state: 'enterprise_agreement',
        initialState: 'enterprise_agreement',
        placeholder: 'Choose…',
        options: [],
    })

    config._engine = {}
    config.comboboxSelectedValues = ['enterprise_agreement']
    config.markHeadlessDisplayReady()
    assert.equal(config.displayReady, false)

    config.storeLabelEntry('enterprise_agreement', {
        value: 'enterprise_agreement',
        label: 'Enterprise agreement (very long label)',
        triggerLabel: 'Enterprise agreement (very long label)',
    })
    config.markHeadlessDisplayReady()
    assert.equal(config.displayReady, true)
})

test('markHeadlessDisplayReady waits for UserSelect trigger HTML before SSR handoff', async () => {
    const { createHeadlessUserSelectMixin } = await import(
        '../../resources/js/components/select-field/headless-user-select.js'
    )

    const config = headlessComboboxAlpine({
        state: 'jane',
        initialState: 'jane',
        placeholder: 'Select a user',
        isUserSelectField: true,
        initialSelectedUserEntries: [{
            value: 'jane',
            user: {
                name: 'Jane Cooper',
                email: 'jane.cooper@example.com',
                avatarUrl: null,
                verified: true,
                initials: 'JC',
            },
        }],
        options: [],
    })

    config.$el = { closest() { return null } }
    config.$watch = () => {}
    config._engine = {}
    config.comboboxSelectedValues = ['jane']
    config.markHeadlessDisplayReady()
    assert.equal(config.displayReady, false, 'mixins not loaded yet')

    Object.assign(config, createHeadlessUserSelectMixin({ isUserSelectField: true }))
    config._optionalMixinsLoaded = true
    config.initUserSelectIntegration()

    assert.match(String(config.userSelectTriggerHtml()), /Jane Cooper/)
    config.markHeadlessDisplayReady()
    assert.equal(config.displayReady, true)
})

test('userSelectTriggerHtml falls back to option label while user repo catches up', async () => {
    const { createHeadlessUserSelectMixin } = await import(
        '../../resources/js/components/select-field/headless-user-select.js'
    )

    const config = headlessComboboxAlpine({
        state: 'jane',
        initialState: 'jane',
        placeholder: 'Select a user',
        isUserSelectField: true,
        options: [],
    })

    Object.assign(config, createHeadlessUserSelectMixin({ isUserSelectField: true }))
    config._optionalMixinsLoaded = true
    config._engine = {}
    config.comboboxSelectedValues = ['jane']
    config.storeLabelEntry('jane', {
        value: 'jane',
        label: '<span class="fff-user-select-option">Jane Cooper</span>',
    })

    assert.match(String(config.userSelectTriggerHtml()), /Jane Cooper/)
    assert.equal(config.canShowHydratedTrigger(), true)
})

test('bumpTriggerLabelEpoch increments reactive trigger dependency', () => {
    const config = headlessComboboxAlpine({
        state: null,
        initialState: null,
        options: [],
    })

    assert.equal(config.triggerLabelEpoch, 0)
    config.bumpTriggerLabelEpoch()
    assert.equal(config.triggerLabelEpoch, 1)
})

test('comboboxOpenMenu does not force SSR replacement before init completes', () => {
    const config = headlessComboboxAlpine({
        state: 'draft',
        initialState: 'draft',
        placeholder: 'Choose…',
        options: [{ value: 'draft', label: 'Draft' }],
    })

    let replaced = false

    config.markHeadlessDisplayReady = () => {
        replaced = true
    }
    config.scheduleMenuPosition = () => {}

    config.comboboxOpenMenu()

    assert.equal(replaced, false)
    assert.equal(config.comboboxOpen, true)
})

test('labelEntry and optionRecord do not recurse when livewire mixin is merged', async () => {
    const { createHeadlessComboboxLivewireMixin } = await import(
        '../../resources/js/components/select-field/headless-combobox-livewire.js'
    )

    const config = headlessComboboxAlpine({
        state: 'draft',
        initialState: 'draft',
        placeholder: 'Choose…',
        options: [{ value: 'draft', label: 'Draft' }],
    })

    Object.assign(config, createHeadlessComboboxLivewireMixin({}))

    assert.doesNotThrow(() => config.labelEntry('missing'))
    assert.doesNotThrow(() => config.optionRecord('missing'))
    assert.equal(config.labelEntry('draft')?.label, 'Draft')
    assert.equal(config.optionRecord('draft')?.label, 'Draft')
})

test('teardownHeadlessMenuPosition keeps menuReady after first position', () => {
    const config = headlessComboboxAlpine({
        state: 'draft',
        initialState: 'draft',
        placeholder: 'Choose…',
        options: [{ value: 'draft', label: 'Draft' }],
    })

    const menu = {
        __fffHasBeenPositioned: true,
        classList: {
            classes: new Set(['is-open', 'is-closing']),
            remove(...names) {
                names.forEach((name) => this.classes.delete(name))
            },
            add(...names) {
                names.forEach((name) => this.classes.add(name))
            },
            contains(name) {
                return this.classes.has(name)
            },
        },
    }

    config.$refs = { headlessMenu: menu }
    config.comboboxOpen = false
    config.menuReady = true

    config.teardownHeadlessMenuPosition()

    assert.equal(config.menuReady, true)
})

test('static client-side searchable selects show Filament empty-state messages', () => {
    const config = headlessComboboxAlpine({
        state: null,
        initialState: null,
        componentKey: 'playground-select',
        searchable: true,
        hasClientSideOptionList: true,
        hasDynamicOptions: false,
        hasDynamicSearchResults: false,
        noSearchResultsMessage: 'No authors found.',
        noOptionsMessage: 'No authors available.',
        options: [{ value: 'a', label: 'Alpha' }],
    })

    config.getEngineOptions = () => []
    config.comboboxQuery = 'zzzz'
    config.comboboxOpen = true

    assert.equal(config.headlessSelectDropdownState(), 'search')
    assert.equal(config.shouldShowHeadlessSelectEmptyState(), true)
    assert.equal(config.headlessSelectEmptyTitle(), 'No authors found.')

    config.comboboxQuery = ''
    config.options = []
    config.getEngineOptions = () => []

    assert.equal(config.headlessSelectDropdownState(), 'options')
    assert.equal(config.headlessSelectEmptyTitle(), 'No authors available.')
})

test('optionsLimit caps client-side filtered option trees', async () => {
    const { limitHeadlessOptionTree } = await import('../../resources/js/components/select-field/headless-select-options.js')

    const limited = limitHeadlessOptionTree([
        { value: '1', label: 'One' },
        { value: '2', label: 'Two' },
        { value: '3', label: 'Three' },
    ], 2)

    assert.equal(limited.length, 2)
    assert.equal(limited[0].value, '1')
    assert.equal(limited[1].value, '2')
})

test('static client-side selects keep livewire stubs without loading the mixin', () => {
    const config = headlessComboboxAlpine({
        state: null,
        initialState: null,
        componentKey: 'playground-select',
        hasClientSideOptionList: true,
        hasDynamicOptions: false,
        hasDynamicSearchResults: false,
        options: [{ value: 'a', label: 'Alpha' }],
    })

    assert.equal(config.shouldShowHeadlessSelectSkeleton(), false)
    assert.equal(typeof config.onHeadlessMenuOpenedForLivewire, 'function')
})

test('allowCreateOption shows create row for client-side searchable selects', () => {
    const config = headlessComboboxAlpine({
        state: null,
        initialState: null,
        searchable: true,
        smartSuggestEnabled: true,
        allowCreateOption: true,
        createOptionLabel: 'Create',
        options: [
            { value: 'laravel', label: 'Laravel' },
            { value: 'tailwind', label: 'Tailwind CSS' },
        ],
    })

    config._engine = createComboboxEngine({
        options: [
            { value: 'laravel', label: 'Laravel' },
            { value: 'tailwind', label: 'Tailwind CSS' },
        ],
        searchable: true,
        allowCreate: true,
        createOptionLabel: (query) => `Create "${query}"`,
        filterFn: (option, normalizedQuery, getOptionLabel) => {
            if (normalizedQuery === '') {
                return true
            }

            return normalizeSearchQuery(getOptionLabel(option)).includes(normalizedQuery)
        },
    })

    config.$nextTick = (callback) => {
        callback()

        return Promise.resolve()
    }
    config.$refs = { headlessMenu: null }
    config.scheduleMenuPositionAfterLayout = () => {}
    config.markKnownOptionChecksVisible = () => {}

    config.comboboxSetQuery('Svelte')

    const rows = config.buildHeadlessDropdownRowsForDisplay()

    assert.equal(rows.some((row) => row.type === 'create'), true)
    assert.match(rows.find((row) => row.type === 'create')?.label ?? '', /Svelte/)
})

test('applyComboboxQueryToEngine keeps smart suggest query for client-side search', () => {
    const config = headlessComboboxAlpine({
        state: null,
        initialState: null,
        searchable: true,
        smartSuggestEnabled: true,
        allowCreateOption: true,
        options: [
            { value: 'laravel', label: 'Laravel' },
            { value: 'tailwind', label: 'Tailwind CSS' },
        ],
    })

    config._engine = createComboboxEngine({
        options: [
            { value: 'laravel', label: 'Laravel' },
            { value: 'tailwind', label: 'Tailwind CSS' },
        ],
        searchable: true,
        allowCreate: true,
        filterFn: (option, normalizedQuery, getOptionLabel) => {
            if (normalizedQuery === '') {
                return true
            }

            return normalizeSearchQuery(getOptionLabel(option)).includes(normalizedQuery)
        },
    })

    config.comboboxQuery = 'wind'
    config.applyComboboxQueryToEngine()

    assert.equal(config.getEngineOptions().length, 1)
    assert.equal(config.getEngineOptions()[0]?.value, 'tailwind')
})

test('client-side grouped search filters nested country options', () => {
    const config = headlessComboboxAlpine({
        state: null,
        initialState: null,
        searchable: true,
        smartSuggestEnabled: false,
        optionGroupSeparators: true,
        options: [
            {
                label: 'North America',
                options: [
                    { value: 'usa', label: 'United States' },
                    { value: 'canada', label: 'Canada' },
                ],
            },
            {
                label: 'Europe',
                options: [
                    { value: 'uk', label: 'United Kingdom' },
                    { value: 'fr', label: 'France' },
                    { value: 'de', label: 'Germany' },
                ],
            },
        ],
    })

    config._engine = createComboboxEngine({
        options: [
            { value: 'usa', label: 'United States' },
            { value: 'canada', label: 'Canada' },
            { value: 'uk', label: 'United Kingdom' },
            { value: 'fr', label: 'France' },
            { value: 'de', label: 'Germany' },
        ],
        searchable: true,
    })
    config.$nextTick = (callback) => {
        callback()

        return Promise.resolve()
    }
    config.$refs = { headlessMenu: null }
    config.scheduleMenuPositionAfterLayout = () => {}
    config.markKnownOptionChecksVisible = () => {}

    config.comboboxSetQuery('fr')

    const displayRows = config.buildHeadlessDropdownRowsForDisplay()
    const groups = displayRows.filter((row) => row.type === 'group')

    assert.equal(groups.length, 1)
    assert.equal(groups[0]?.label, 'Europe')
    assert.equal(groups[0]?.options.length, 1)
    assert.equal(groups[0]?.options[0]?.value, 'fr')
    assert.match(groups[0]?.key ?? '', /:fr$/)

    const rendered = config.comboboxFilteredDropdownRows()
    assert.deepEqual(
        rendered.map((row) => row.type),
        ['group-header', 'option'],
    )
    assert.equal(rendered[0]?.label, 'Europe')
    assert.equal(rendered[1]?.option?.value, 'fr')
    assert.equal(rendered.every((row) => row.type !== 'group'), true)
})

test('client-side search windows large lists instead of mounting every row', () => {
    const options = Array.from({ length: 150 }, (_, index) => ({
        value: `opt-${index}`,
        label: `Alpha ${index}`,
    }))

    const config = headlessComboboxAlpine({
        state: null,
        initialState: null,
        searchable: true,
        smartSuggestEnabled: false,
        virtualizeThreshold: 100,
        virtualWindowSize: 20,
        optionsLimit: 0,
        options,
    })

    config._engine = createComboboxEngine({
        options,
        searchable: true,
    })
    config.$nextTick = (callback) => {
        callback()

        return Promise.resolve()
    }
    config.$refs = { headlessMenu: null }
    config.scheduleMenuPositionAfterLayout = () => {}
    config.markKnownOptionChecksVisible = () => {}

    assert.equal(config.shouldVirtualizeDropdown(), true)

    config.comboboxSetQuery('Alpha 12')

    const visible = config.comboboxFilteredDropdownRows()
    const values = visible
        .filter((row) => row.type === 'option')
        .map((row) => row.option?.value)

    assert.equal(config.shouldVirtualizeDropdown(), false)
    assert.equal(values.includes('opt-12'), true)
    assert.equal(values.includes('opt-1'), false)
})

test('optionsLimit caps static client-side lists like Filament', () => {
    const options = Array.from({ length: 80 }, (_, index) => ({
        value: `opt-${index}`,
        label: `Item ${index}`,
    }))

    const config = headlessComboboxAlpine({
        state: null,
        initialState: null,
        searchable: true,
        optionsLimit: 20,
        options,
    })

    const limited = config.comboboxFilteredOptionTree()

    assert.equal(limited.length, 20)
    assert.equal(limited[0].value, 'opt-0')
    assert.equal(limited[19].value, 'opt-19')
})

test('maxItems blocks extra multi-select picks and shows the Filament message', () => {
    const config = headlessComboboxAlpine({
        state: [],
        initialState: [],
        multiple: true,
        searchable: true,
        maxItems: 2,
        maxItemsMessage: 'Maximum number of items selected',
        options: [
            { value: 'a', label: 'A' },
            { value: 'b', label: 'B' },
            { value: 'c', label: 'C' },
        ],
    })

    config.comboboxSelectedValues = ['a', 'b']
    config._engine = { selectValue: () => {} }
    config._syncFromEngine = () => {}
    config.rememberSelectedOption = () => {}
    config.queueOptionCheckEnter = () => {}
    config.comboboxCloseMenu = () => {}
    config.syncInlineSearchInputAfterSelection = () => {}

    assert.equal(config.hasReachedMaxItems(), true)

    config.comboboxSelectValue('c')

    assert.equal(config.maxItemsMessageVisible, true)
    assert.equal(config.shouldShowMaxItemsMessage(), true)
})

test('dynamic options refetch marker resets when menu opens for dependsOn', async () => {
    const { createHeadlessComboboxLivewireMixin } = await import('../../resources/js/components/select-field/headless-combobox-livewire.js')

    const mixin = createHeadlessComboboxLivewireMixin({
        hasDynamicOptions: true,
        hasDynamicSearchResults: false,
    })

    const host = {
        ...mixin,
        hasDynamicOptions: true,
        hasDynamicSearchResults: false,
        dynamicOptionsLoaded: true,
        comboboxQuery: '',
        flatOptions: [],
        hasPaginatedSearchResults: false,
        searchPending: false,
        minSearchLength: 0,
        fetchDynamicOptions: async () => {
            host.fetched = true
            host.dynamicOptionsLoaded = true

            return true
        },
        hasMissingSelectedLabels: () => false,
        resolveSelectedLabelsFromServer: async () => {},
    }

    await host.onHeadlessMenuOpenedForLivewire()

    assert.equal(host.fetched, true)
})

test('fetchDynamicOptions queues a second request while loading', async () => {
    const { createHeadlessComboboxLivewireMixin } = await import('../../resources/js/components/select-field/headless-combobox-livewire.js')

    const mixin = createHeadlessComboboxLivewireMixin({
        hasDynamicOptions: true,
        componentKey: 'form.region',
    })

    let calls = 0
    const host = {
        ...mixin,
        hasDynamicOptions: true,
        optionsLoading: false,
        dynamicOptionsLoaded: false,
        flatOptions: [],
        options: [],
        comboboxSelectedValues: [],
        applyRemoteOptions(next) {
            this.options = next
            this.flatOptions = next
        },
        async callSchemaMethod() {
            calls += 1

            if (calls === 1) {
                await new Promise((resolve) => setTimeout(resolve, 10))

                return [{ value: 'ca', label: 'California' }]
            }

            return [{ value: 'tx', label: 'Texas' }]
        },
        $nextTick(cb) {
            cb?.()
        },
        _syncFromEngine() {},
    }

    const first = host.fetchDynamicOptions()
    const second = host.fetchDynamicOptions()

    assert.equal(await second, false)
    assert.equal(await first, true)
    assert.equal(calls, 2)
    assert.equal(host.flatOptions[0]?.value, 'tx')
})

test('dynamic options commit invalidation does not re-enter while fetching', async () => {
    const { createHeadlessComboboxLivewireMixin, DYNAMIC_OPTIONS_FETCH_GUARD_MS } = await import('../../resources/js/components/select-field/headless-combobox-livewire.js')

    const commitListeners = []

    globalThis.Livewire = {
        hook(name, callback) {
            assert.equal(name, 'commit')
            commitListeners.push(callback)

            return () => {}
        },
    }

    const mixin = createHeadlessComboboxLivewireMixin({
        hasDynamicOptions: true,
        livewireId: 'lw-1',
        componentKey: 'form.region',
    })

    let fetchCalls = 0
    const host = {
        ...mixin,
        hasDynamicOptions: true,
        comboboxOpen: true,
        optionsLoading: false,
        _suppressDynamicOptionsInvalidation: false,
        _skipDynamicOptionsInvalidations: 0,
        dynamicOptionsLoaded: true,
        async fetchDynamicOptions() {
            fetchCalls += 1

            return true
        },
    }

    host.bindDynamicOptionsInvalidation()

    const fireCommit = () => {
        for (const listener of commitListeners) {
            listener({
                component: { id: 'lw-1' },
                succeed(handler) {
                    handler()
                },
            })
        }
    }

    host.beginDynamicOptionsFetchGuard()
    host.optionsLoading = true
    fireCommit()
    assert.equal(fetchCalls, 0)

    host.optionsLoading = false
    host.endDynamicOptionsFetchGuard()

    // Own-fetch succeed callbacks must not re-enter while the guard / skip budget holds.
    fireCommit()
    fireCommit()
    assert.equal(fetchCalls, 0)

    await new Promise((resolve) => setTimeout(resolve, DYNAMIC_OPTIONS_FETCH_GUARD_MS + 20))

    // Skip budget still covers late succeed callbacks from our own fetch.
    fireCommit()
    fireCommit()
    assert.equal(fetchCalls, 0)

    // A later parent remorph (after own-fetch settles) may refresh once.
    fireCommit()
    await new Promise((resolve) => setTimeout(resolve, 100))

    assert.equal(fetchCalls, 1)

    delete globalThis.Livewire
})

test('dynamic options commit storm does not loop fetch after a successful load', async () => {
    const {
        createHeadlessComboboxLivewireMixin,
        DYNAMIC_OPTIONS_FETCH_GUARD_MS,
        DYNAMIC_OPTIONS_INVALIDATION_DEBOUNCE_MS,
    } = await import('../../resources/js/components/select-field/headless-combobox-livewire.js')

    const commitListeners = []

    globalThis.Livewire = {
        hook(name, callback) {
            commitListeners.push(callback)

            return () => {}
        },
    }

    const mixin = createHeadlessComboboxLivewireMixin({
        hasDynamicOptions: true,
        livewireId: 'lw-region',
        componentKey: 'form.region',
    })

    let calls = 0
    const host = {
        ...mixin,
        hasDynamicOptions: true,
        comboboxOpen: true,
        flatOptions: [],
        options: [],
        comboboxSelectedValues: [],
        applyRemoteOptions(next) {
            this.options = next
            this.flatOptions = next
        },
        async callSchemaMethod() {
            calls += 1

            return [
                { value: 'ca', label: 'California' },
                { value: 'tx', label: 'Texas' },
            ]
        },
        $nextTick(cb) {
            cb?.()
        },
        _syncFromEngine() {},
    }

    host.bindDynamicOptionsInvalidation()

    await host.fetchDynamicOptions()
    assert.equal(calls, 1)
    assert.equal(host.dynamicOptionsLoaded, true)
    assert.equal(host.optionsLoading, false)

    const fireCommit = () => {
        for (const listener of commitListeners) {
            listener({
                component: { id: 'lw-region' },
                succeed(handler) {
                    handler()
                },
            })
        }
    }

    // Simulate Livewire succeed from getOptionsForJs + a few remorphs right after.
    for (let index = 0; index < 12; index += 1) {
        fireCommit()
    }

    await new Promise((resolve) => setTimeout(
        resolve,
        DYNAMIC_OPTIONS_FETCH_GUARD_MS + DYNAMIC_OPTIONS_INVALIDATION_DEBOUNCE_MS + 40,
    ))

    assert.equal(calls, 1)
    assert.equal(host.flatOptions.length, 2)
    assert.equal(host.optionsLoading, false)

    delete globalThis.Livewire
})

test('closed dependsOn select invalidates without background fetch on label refresh', async () => {
    const { createHeadlessComboboxLivewireMixin } = await import('../../resources/js/components/select-field/headless-combobox-livewire.js')

    const previousWindow = globalThis.window
    const listeners = new Map()

    globalThis.window = {
        addEventListener(name, handler) {
            listeners.set(name, handler)
        },
        removeEventListener(name, handler) {
            if (listeners.get(name) === handler) {
                listeners.delete(name)
            }
        },
    }

    const mixin = createHeadlessComboboxLivewireMixin({
        hasDynamicOptions: true,
        livewireId: 'lw-1',
        statePath: 'select__cascade_region',
        componentKey: 'data.select__cascade_region',
    })

    let fetchCalls = 0
    let resolveCalls = 0
    const host = {
        ...mixin,
        hasDynamicOptions: true,
        comboboxOpen: false,
        dynamicOptionsLoaded: true,
        displayReady: true,
        flatOptions: [
            { value: 'ca', label: 'California' },
        ],
        options: [
            { value: 'ca', label: 'California' },
        ],
        comboboxSelectedValues: ['ca'],
        applyRemoteOptions(next) {
            this.options = next
            this.flatOptions = next
        },
        async resolveSelectedLabelsFromServer() {
            resolveCalls += 1
        },
        async fetchDynamicOptions() {
            fetchCalls += 1

            return true
        },
        ensureHeadlessSsrHandoff() {
            this.handoffCalls = (this.handoffCalls ?? 0) + 1
        },
        $nextTick(cb) {
            cb?.()
        },
        _syncFromEngine() {},
    }

    host.bindSelectedOptionLabelRefreshListener()

    await host._refreshOptionLabelListener({
        detail: {
            livewireId: 'lw-1',
            statePath: 'select__cascade_region',
        },
    })

    assert.equal(fetchCalls, 0)
    assert.equal(resolveCalls, 0)
    assert.equal(host.handoffCalls, 1)
    assert.equal(host._pendingSelectedLabelRefresh, true)
    assert.equal(host.dynamicOptionsLoaded, false)
    assert.equal(host.flatOptions.length, 1)
    assert.equal(host.optionsLoading, false)

    host.teardownSelectedOptionLabelRefreshListener()
    globalThis.window = previousWindow
})

test('closed dependsOn select clears stale options on commit without fetching', async () => {
    const { createHeadlessComboboxLivewireMixin } = await import('../../resources/js/components/select-field/headless-combobox-livewire.js')

    const commitListeners = []

    globalThis.Livewire = {
        hook(name, callback) {
            commitListeners.push(callback)

            return () => {}
        },
    }

    const mixin = createHeadlessComboboxLivewireMixin({
        hasDynamicOptions: true,
        livewireId: 'lw-1',
        componentKey: 'form.region',
    })

    let fetchCalls = 0
    const host = {
        ...mixin,
        hasDynamicOptions: true,
        comboboxOpen: false,
        dynamicOptionsLoaded: true,
        flatOptions: [{ value: 'ca', label: 'California' }],
        options: [{ value: 'ca', label: 'California' }],
        applyRemoteOptions(next) {
            this.options = next
            this.flatOptions = next
        },
        async fetchDynamicOptions() {
            fetchCalls += 1

            return true
        },
        $nextTick(cb) {
            cb?.()
        },
        _syncFromEngine() {},
    }

    host.bindDynamicOptionsInvalidation()

    for (const listener of commitListeners) {
        listener({
            component: { id: 'lw-1' },
            succeed(handler) {
                handler()
            },
        })
    }

    // Keep stale options painted; never background-fetch while closed.
    assert.equal(fetchCalls, 0)
    assert.equal(host.dynamicOptionsLoaded, false)
    assert.equal(host.flatOptions.length, 1)

    await new Promise((resolve) => setTimeout(resolve, 150))

    assert.equal(fetchCalls, 0)
    assert.equal(host.flatOptions.length, 1)

    delete globalThis.Livewire
})

test('dynamic search results are not client-filtered again', () => {
    const config = headlessComboboxAlpine({
        state: null,
        initialState: null,
        searchable: true,
        hasDynamicSearchResults: true,
        options: [
            { value: '1', label: 'Rick Sanchez' },
        ],
    })

    config.comboboxQuery = 'morty'

    assert.equal(config.comboboxFilteredOptionTree().length, 1)
    assert.equal(config.comboboxFilteredOptionTree()[0]?.value, '1')
})

test('multi deselect updates chips immediately without waiting for check exit', () => {
    const previousWindow = globalThis.window
    const previousMatchMedia = globalThis.matchMedia

    globalThis.window = {
        matchMedia: () => ({ matches: false }),
        setTimeout() {
            return 1
        },
        clearTimeout() {},
    }
    globalThis.matchMedia = globalThis.window.matchMedia

    const config = headlessComboboxAlpine({
        state: ['jane', 'john'],
        initialState: ['jane', 'john'],
        multiple: true,
        searchable: true,
        keepSelectedOptionsInDropdown: true,
        options: [
            { value: 'jane', label: 'Jane' },
            { value: 'john', label: 'John' },
            { value: 'alex', label: 'Alex' },
        ],
    })

    let selected = ['jane', 'john']

    config.comboboxOpen = true
    config.comboboxSelectedValues = selected.slice()
    config.multiple = true
    config.isGridLayout = false
    config._engine = {
        deselectValue(value) {
            selected = selected.filter((entry) => entry !== String(value))
        },
        getSnapshot() {
            return {
                query: '',
                highlightedIndex: -1,
                selectedValues: selected,
            }
        },
    }
    config.scheduleMenuPositionAfterLayout = () => {}
    config.findOptionCheckElement = () => ({
        getAttribute(name) {
            return name === 'data-visible' ? 'true' : null
        },
        setAttribute() {},
        removeAttribute() {},
        querySelector() {
            return {
                style: { removeProperty() {} },
                offsetWidth: 1,
                addEventListener() {},
                removeEventListener() {},
            }
        },
    })

    config.comboboxDeselectValue('jane')

    assert.deepEqual(config.comboboxSelectedValues, ['john'])
    assert.equal(config.isOptionSelected('jane'), false)
    assert.equal(config.isOptionCheckExiting('jane'), true)
    assert.equal(config.isOptionSelectedInDropdown('jane'), true)
    assert.equal(config.selectedChips().map((chip) => chip.value).join(','), 'john')

    globalThis.window = previousWindow
    globalThis.matchMedia = previousMatchMedia
})

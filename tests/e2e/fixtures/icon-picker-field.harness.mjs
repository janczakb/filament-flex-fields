import Alpine from 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/module.esm.js'

import iconPickerFieldFormComponent from '../../../resources/dist/components/icon-picker-field.js'

const mockCatalog = Array.from({ length: 240 }, (_, index) => ({
    name: `heroicon-o-icon-${index}`,
    label: `Icon ${index}`,
}))

const perPage = 64

window.Livewire = {
    find() {
        return {
            async callSchemaComponentMethod(_componentKey, method, params = {}) {
                if (method === 'getIconPickerSearchResults') {
                    await new Promise((resolve) => setTimeout(resolve, 280))

                    const page = Math.max(1, Number(params.page) || 1)
                    const start = (page - 1) * perPage
                    const icons = mockCatalog.slice(start, start + perPage)

                    return {
                        icons,
                        hasMore: start + perPage < mockCatalog.length,
                        sets: [],
                    }
                }

                if (method === 'getIconPickerSvgPreviews') {
                    return (params.icons ?? []).map((name) => ({
                        name,
                        html: '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="4"/></svg>',
                    }))
                }

                return null
            },
        }
    },
}

Alpine.data('iconPickerFixture', () => iconPickerFieldFormComponent({
    state: null,
    componentKey: 'fixture-icon-picker',
    availableSets: [],
    layout: 'icons',
    closeOnSelect: false,
    gridColumns: 8,
    preload: false,
    perPage,
    readOnly: false,
    clearable: true,
    placeholder: 'Choose icon',
    labels: {
        search: 'Search icons',
        clear: 'Clear',
        clearSearch: 'Clear search',
        noResults: 'No icons found',
        loadMore: 'Load more',
        allSets: 'All sets',
    },
    initialSelectedHtml: '',
    initialSelectedName: null,
}))

window.Alpine = Alpine
Alpine.start()

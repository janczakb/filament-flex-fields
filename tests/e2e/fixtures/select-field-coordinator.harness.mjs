import Alpine from 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/module.esm.js'

globalThis.__fffSelectFieldPatchApplicator = () => () => {}

import fffSelectFieldCoordinator from '../../../resources/dist/components/select-field.js'

function createFixtureSelect() {
    const style = {
        setProperty() {},
    }

    const classList = {
        add() {},
        remove() {},
        contains: () => false,
        toggle() {},
    }

    const select = {
        dropdown: {
            classList,
            isActive: false,
            closest: () => null,
            style,
        },
        selectButton: {
            classList,
            querySelector: () => null,
            insertBefore() {},
            closest: () => null,
            style,
        },
        labelRepository: {},
        options: [],
        openDropdown() {},
        closeDropdown() {},
        positionDropdown() {},
        async updateSelectedDisplay() {},
    }

    return select
}

Alpine.data('fffSelectFieldCoordinator', fffSelectFieldCoordinator)

Alpine.data('immediateSelectInner', () => ({
    select: createFixtureSelect(),
}))

Alpine.data('delayedSelectInner', () => ({
    select: null,

    init() {
        requestAnimationFrame(() => {
            this.select = createFixtureSelect()
        })
    },
}))

window.Alpine = Alpine
Alpine.start()

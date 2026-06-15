import { createFlexDropdownRegistry } from './flex-dropdown-registry.js'

let dropdownOwnerSequence = 0

/** @type {ReturnType<typeof createFlexDropdownRegistry>} */
const registry = createFlexDropdownRegistry()

/** @type {WeakMap<Element, () => void>} */
const elementBindings = new WeakMap()

/** @type {WeakMap<Element, string>} */
const dropdownOwnerIds = new WeakMap()

let alpineStoreInitialized = false

export function createDropdownOwnerId(prefix = 'fff-dropdown') {
    dropdownOwnerSequence += 1

    return `${prefix}-${dropdownOwnerSequence}`
}

function resolveOwnerKey(target) {
    if (target instanceof Element) {
        return target
    }

    if (target?.$el instanceof Element) {
        return target.$el
    }

    return null
}

export function resolveAlpineComponent(target) {
    if (target instanceof Element) {
        return window.Alpine?.$data(target) ?? null
    }

    if (target?.$el instanceof Element) {
        return window.Alpine?.$data(target.$el) ?? target
    }

    return target ?? null
}

export function resolveDropdownOwnerId(target, ownerIdPrefix = 'fff-dropdown') {
    const ownerKey = resolveOwnerKey(target)

    if (! ownerKey) {
        return null
    }

    let ownerId = dropdownOwnerIds.get(ownerKey)

    if (! ownerId) {
        ownerId = createDropdownOwnerId(ownerIdPrefix)
        dropdownOwnerIds.set(ownerKey, ownerId)
    }

    return ownerId
}

/**
 * @param {object} component
 * @param {{ openKey?: string, closeMethod?: string|null }} options
 * @returns {import('./flex-dropdown-registry.js').FlexDropdownController}
 */
export function createFlexDropdownController(component, {
    openKey = 'menuOpen',
    closeMethod = null,
} = {}) {
    return {
        isOpen: () => Boolean(component?.[openKey]),
        close: () => {
            if (! component) {
                return
            }

            if (closeMethod && typeof component[closeMethod] === 'function') {
                component[closeMethod]()

                return
            }

            component[openKey] = false
        },
    }
}

export function openExclusiveFlexDropdown(ownerId) {
    registry.openExclusive(ownerId)
}

export function registerFlexDropdown(ownerId, controller) {
    return registry.register(ownerId, () => controller)
}

export function unregisterFlexDropdown(ownerId) {
    registry.unregister(ownerId)
}

export function bindFlexDropdown(target, {
    getController,
    ownerIdPrefix = 'fff-dropdown',
} = {}) {
    const ownerKey = resolveOwnerKey(target)

    if (! (ownerKey instanceof Element) || typeof getController !== 'function') {
        return () => {}
    }

    elementBindings.get(ownerKey)?.()

    const ownerId = resolveDropdownOwnerId(ownerKey, ownerIdPrefix)

    if (! ownerId) {
        return () => {}
    }

    const unregister = registry.register(ownerId, getController)

    const unbind = () => {
        unregister()
        elementBindings.delete(ownerKey)
    }

    elementBindings.set(ownerKey, unbind)

    return unbind
}

export function wireExclusiveFlexDropdown(component, {
    openKey = 'menuOpen',
    closeMethod = null,
    ownerIdPrefix = 'fff-dropdown',
} = {}) {
    const ownerKey = component.$el

    if (! (ownerKey instanceof Element)) {
        return
    }

    bindFlexDropdown(ownerKey, {
        ownerIdPrefix,
        getController: () => createFlexDropdownController(component, {
            openKey,
            closeMethod,
        }),
    })

    component.$watch(openKey, (open) => {
        if (! open) {
            return
        }

        const ownerId = resolveDropdownOwnerId(ownerKey, ownerIdPrefix)

        if (ownerId) {
            openExclusiveFlexDropdown(ownerId)
        }
    })
}

export function createExclusiveDropdownMixin({
    openKey = 'menuOpen',
    closeMethod = null,
    ownerIdPrefix = 'fff-dropdown',
} = {}) {
    return {
        wireExclusiveFlexDropdown() {
            wireExclusiveFlexDropdown(this, {
                openKey,
                closeMethod,
                ownerIdPrefix,
            })
        },
    }
}

function initAlpineOverlayStore() {
    if (alpineStoreInitialized || typeof window === 'undefined' || ! window.Alpine?.store) {
        return
    }

    alpineStoreInitialized = true

    window.Alpine.store('fffOverlays', {
        resolveOwnerId: resolveDropdownOwnerId,
        register(ownerId, controller) {
            return registry.register(ownerId, () => controller)
        },
        unregister(ownerId) {
            registry.unregister(ownerId)
        },
        open(ownerId) {
            registry.openExclusive(ownerId)
        },
    })
}

if (typeof window !== 'undefined') {
    document.addEventListener('alpine:init', initAlpineOverlayStore)

    if (window.Alpine?.store) {
        initAlpineOverlayStore()
    }
}

export { createFlexDropdownRegistry, registry as flexDropdownRegistry }

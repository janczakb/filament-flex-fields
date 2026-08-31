function isUsableLivewireWire(wire) {
    return wire != null
        && (
            typeof wire.callSchemaComponentMethod === 'function'
            || (
                typeof wire.call === 'function'
                && typeof wire.get === 'function'
            )
        )
}

export function resolveIconPickerLivewire(component) {
    const host = component.$el?.closest('[wire\\:id], [wire-id]')

    if (host && typeof Livewire !== 'undefined' && typeof Livewire.find === 'function') {
        const wireId = host.getAttribute('wire:id') ?? host.getAttribute('wire-id')
        const wire = wireId ? Livewire.find(wireId) : null

        if (isUsableLivewireWire(wire)) {
            return wire
        }
    }

    if (isUsableLivewireWire(component.$wire)) {
        return component.$wire
    }

    return null
}

export async function callIconPickerSchemaMethod(component, method, params = {}) {
    if (! component?.componentKey) {
        return null
    }

    const wire = resolveIconPickerLivewire(component)

    if (! wire) {
        return null
    }

    const { componentKey } = component
    const args = [componentKey, method, params]

    try {
        if (typeof wire.callSchemaComponentMethod === 'function') {
            const result = await wire.callSchemaComponentMethod(componentKey, method, params)

            if (result !== undefined) {
                return result
            }
        }

        if (typeof wire.call === 'function') {
            const result = await wire.call('callSchemaComponentMethod', componentKey, method, params)

            if (result !== undefined) {
                return result
            }
        }
    } catch {
        return null
    }

    const instance = wire.__instance ?? wire

    if (typeof Livewire !== 'undefined' && typeof Livewire.fireAction === 'function' && instance) {
        try {
            return await Livewire.fireAction(
                instance,
                'callSchemaComponentMethod',
                args,
                { async: true },
            )
        } catch {
            return null
        }
    }

    return null
}

export function canCallIconPickerSchemaMethod(component) {
    return resolveIconPickerLivewire(component) !== null
}

export function isIconPickerSearchPayload(payload) {
    return payload != null
        && typeof payload === 'object'
        && Array.isArray(payload.icons)
}

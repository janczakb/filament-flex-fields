/**
 * Merge Alpine component data objects while preserving accessor descriptors.
 *
 * Object spread evaluates getters immediately against the source object, not the
 * final component instance, which breaks reactive getters on both base data and mixins.
 */
export function mergeAlpineComponentData(base, ...mixins) {
    const result = {}

    for (const key of Reflect.ownKeys(base)) {
        const descriptor = Object.getOwnPropertyDescriptor(base, key)

        if (descriptor) {
            Object.defineProperty(result, key, descriptor)
        }
    }

    for (const mixin of mixins) {
        for (const key of Reflect.ownKeys(mixin)) {
            const descriptor = Object.getOwnPropertyDescriptor(mixin, key)

            if (descriptor) {
                Object.defineProperty(result, key, descriptor)
            }
        }
    }

    return result
}

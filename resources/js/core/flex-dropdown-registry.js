/**
 * @typedef {object} FlexDropdownController
 * @property {() => boolean} isOpen
 * @property {() => void} close
 */

/**
 * @typedef {() => FlexDropdownController} FlexDropdownControllerFactory
 */

/**
 * @returns {{
 *   register: (ownerId: string, factory: FlexDropdownControllerFactory) => () => void,
 *   unregister: (ownerId: string) => void,
 *   openExclusive: (ownerId: string) => void,
 *   size: () => number,
 * }}
 */
export function createFlexDropdownRegistry() {
    /** @type {Map<string, FlexDropdownControllerFactory>} */
    const controllers = new Map()

    return {
        register(ownerId, factory) {
            if (! ownerId) {
                return () => {}
            }

            controllers.set(ownerId, factory)

            return () => {
                controllers.delete(ownerId)
            }
        },

        unregister(ownerId) {
            if (! ownerId) {
                return
            }

            controllers.delete(ownerId)
        },

        openExclusive(ownerId) {
            if (! ownerId) {
                return
            }

            for (const [registeredOwnerId, factory] of controllers.entries()) {
                if (registeredOwnerId === ownerId) {
                    continue
                }

                const controller = factory()

                if (! controller.isOpen()) {
                    continue
                }

                controller.close()
            }
        },

        size() {
            return controllers.size
        },
    }
}

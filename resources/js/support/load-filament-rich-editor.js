const runtimePromises = new Map()

/**
 * Load Filament's rich editor Alpine factory once per page (shared across fields).
 *
 * @param {string} src Absolute URL to filament/forms rich-editor.js
 * @returns {Promise<(...args: unknown[]) => object>}
 */
export function loadFilamentRichEditorFormComponent(src) {
    if (! src) {
        throw new Error('Filament rich editor runtime source URL is required.')
    }

    if (! runtimePromises.has(src)) {
        runtimePromises.set(
            src,
            import(/* @vite-ignore */ src).then((module) => {
                const factory = module?.default

                if (typeof factory !== 'function') {
                    throw new Error('Filament rich editor module did not export a component factory.')
                }

                return factory
            }),
        )
    }

    return runtimePromises.get(src)
}

export function prefetchFilamentRichEditorFormComponent(src) {
    if (! src || runtimePromises.has(src)) {
        return
    }

    loadFilamentRichEditorFormComponent(src).catch(() => {
        runtimePromises.delete(src)
    })
}

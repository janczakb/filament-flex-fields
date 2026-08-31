/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

/**
 * Dev-time inspector for Flex Fields lazy CSS / Alpine asset URLs.
 *
 * @param {{ getLoadedUrls?: () => string[] }} options
 */
export function createAssetInspector({ getLoadedUrls = () => [] } = {}) {
    if (typeof getLoadedUrls !== 'function') {
        throw new TypeError('createAssetInspector requires getLoadedUrls to be a function.')
    }

    return {
        /** @returns {string[]} */
        listUrls() {
            return getLoadedUrls()
                .filter((url) => typeof url === 'string' && url !== '')
        },

        /** @returns {string[]} */
        duplicateHrefs() {
            const seen = new Map()
            const duplicates = []

            for (const url of this.listUrls()) {
                if (seen.has(url)) {
                    if (!duplicates.includes(url)) {
                        duplicates.push(url)
                    }

                    continue
                }

                seen.set(url, true)
            }

            return duplicates
        },

        /** @returns {{ urls: string[], duplicates: string[] }} */
        inspect() {
            const urls = this.listUrls()

            return {
                urls,
                duplicates: this.duplicateHrefs(),
            }
        },
    }
}

if (typeof window !== 'undefined') {
    window.createAssetInspector = createAssetInspector
    window.FffAssetInspector = {
        create: createAssetInspector,
    }
}

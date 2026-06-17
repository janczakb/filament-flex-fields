const DEFAULT_BATCH_DELAY_MS = 16

export function createIconPickerSvgLoader({
    getSvgCache = () => ({}),
    patchSvgCache = () => {},
    fetchSvgs = async () => [],
    batchDelayMs = DEFAULT_BATCH_DELAY_MS,
    batchSize = 48,
} = {}) {
    let pendingIcons = new Set()
    let batchTimer = null
    let fetchToken = 0

    const scheduleBatch = () => {
        if (batchTimer !== null) {
            clearTimeout(batchTimer)
        }

        batchTimer = setTimeout(() => {
            void flushBatch()
        }, batchDelayMs)
    }

    const flushBatch = async () => {
        batchTimer = null

        if (pendingIcons.size === 0) {
            return
        }

        const icons = [...pendingIcons].slice(0, batchSize)
        const remainder = [...pendingIcons].slice(batchSize)

        pendingIcons = new Set(remainder)
        const token = ++fetchToken

        try {
            const rendered = await fetchSvgs(icons)

            if (token !== fetchToken) {
                return
            }

            if (! Array.isArray(rendered)) {
                return
            }

            const updates = {}

            for (const item of rendered) {
                if (! item?.name || ! item?.html) {
                    continue
                }

                updates[item.name] = item.html
            }

            if (Object.keys(updates).length > 0) {
                patchSvgCache(updates)
            }
        } catch {
            //
        }

        if (pendingIcons.size > 0) {
            scheduleBatch()
        }
    }

    const queueIcons = (icons) => {
        const cache = getSvgCache()
        let queued = false

        for (const icon of icons) {
            if (! icon || cache[icon]) {
                continue
            }

            pendingIcons.add(icon)
            queued = true
        }

        if (! queued) {
            return
        }

        scheduleBatch()
    }

    const disconnect = () => {
        if (batchTimer !== null) {
            clearTimeout(batchTimer)
            batchTimer = null
        }

        pendingIcons = new Set()
        fetchToken++
    }

    return {
        queueIcons,
        disconnect,
    }
}

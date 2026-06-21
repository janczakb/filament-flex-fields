/**
 * @deprecated Layout is handled in flex-file-upload.css for compact + files state.
 */
export function syncFilePondCompactLayout() {
    return false
}

export function bindFilePondCompactLayoutSync() {
    return {
        sync() {},
        disconnect() {},
    }
}

export function createPondLayoutSyncBehavior() {
    return {
        bindPondLayoutSync() {},
        unbindPondLayoutSync() {},
    }
}

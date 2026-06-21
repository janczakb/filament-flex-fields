import { createPondComponentResolverBehavior } from './pond-component-resolver.js'
import { createPondFileStatusBehavior } from './pond-file-status.js'
import { createPondLayoutSyncBehavior } from './pond-layout-sync.js'
import { createPondServerSyncBehavior } from './pond-server-sync.js'
import { createPondUploadStateBehavior } from './pond-upload-state.js'

export { createPondComponentResolverBehavior } from './pond-component-resolver.js'
export { createPondFileStatusBehavior } from './pond-file-status.js'
export { createPondLayoutSyncBehavior, syncFilePondCompactLayout } from './pond-layout-sync.js'
export { createPondServerSyncBehavior } from './pond-server-sync.js'
export { createPondUploadStateBehavior } from './pond-upload-state.js'

/**
 * Bridges flex-file-upload to Filament's FilePond Alpine component.
 */
export function createPondBridgeBehavior() {
    return {
        ...createPondComponentResolverBehavior(),
        ...createPondUploadStateBehavior(),
        ...createPondServerSyncBehavior(),
        ...createPondFileStatusBehavior(),
        ...createPondLayoutSyncBehavior(),
    }
}

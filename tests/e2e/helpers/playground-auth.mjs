/**
 * Re-export playground auth helpers for Playwright specs.
 */
export {
    DEFAULT_PLAYGROUND_BASE_URL,
    PLAYGROUND_AUTH_FILE,
    ensurePlaygroundAuthenticated,
    gotoPlaygroundPage,
    loginToPlaygroundIfNeeded,
    playgroundUrl,
    resolvePlaygroundBaseUrl,
} from '../global-setup.mjs'

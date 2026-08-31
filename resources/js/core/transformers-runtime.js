/** @type {Promise<{ env: import('@xenova/transformers').env, pipeline: import('@xenova/transformers').pipeline }> | null} */
let runtimePromise = null

/**
 * @param {string} moduleUrl
 * @param {string} wasmBaseUrl
 */
export function loadTransformersRuntime(moduleUrl, wasmBaseUrl) {
    if (! moduleUrl || ! wasmBaseUrl) {
        return Promise.reject(new Error('transcription_runtime_unconfigured'))
    }

    if (! runtimePromise) {
        runtimePromise = import(/* webpackIgnore: true */ moduleUrl).then(({ env, pipeline }) => {
            env.allowLocalModels = false
            env.allowRemoteModels = true
            env.useBrowserCache = true

            if (env.backends?.onnx?.wasm) {
                env.backends.onnx.wasm.proxy = false
                env.backends.onnx.wasm.wasmPaths = wasmBaseUrl
            }

            return { env, pipeline }
        }).catch((error) => {
            runtimePromise = null

            throw error
        })
    }

    return runtimePromise
}

export function resetTransformersRuntimeForTests() {
    runtimePromise = null
}

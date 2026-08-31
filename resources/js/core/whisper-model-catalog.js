/**
 * @typedef {{
 *   id: string,
 *   multilingual?: boolean,
 *   distil?: boolean,
 *   sizes: number[],
 * }} WhisperModelEntry
 */

/**
 * @param {WhisperModelEntry} model
 */
export function isDistilWhisperModel(model) {
    return model.distil === true || model.id.startsWith('distil-whisper/')
}

/**
 * Mirrors whisper-web AudioManager model filtering.
 *
 * @param {WhisperModelEntry[]} models
 * @param {boolean} multilingual
 * @param {boolean} quantized
 */
export function filterWhisperModels(models, multilingual, quantized) {
    return models.filter((model) => {
        if (! quantized && model.sizes.length !== 2) {
            return false
        }

        if (multilingual && isDistilWhisperModel(model)) {
            return false
        }

        return true
    })
}

/**
 * @param {WhisperModelEntry} model
 * @param {boolean} multilingual
 */
export function displayWhisperModelId(model, multilingual) {
    if (multilingual || isDistilWhisperModel(model)) {
        return model.id
    }

    if (model.id.endsWith('.en')) {
        return model.id
    }

    return `${model.id}.en`
}

/**
 * @param {WhisperModelEntry} model
 * @param {boolean} quantized
 */
export function displayWhisperModelSizeMb(model, quantized) {
    if (quantized) {
        return model.sizes[0] ?? 0
    }

    if (model.sizes.length === 2) {
        return model.sizes[1] ?? model.sizes[0] ?? 0
    }

    return model.sizes[0] ?? 0
}

/**
 * @param {WhisperModelEntry} model
 * @param {boolean} multilingual
 * @param {boolean} quantized
 */
export function formatWhisperModelLabel(model, multilingual, quantized) {
    const id = displayWhisperModelId(model, multilingual)
    const size = displayWhisperModelSizeMb(model, quantized)

    return `${id} (${size} MB)`
}

/**
 * @param {string} modelId
 */
export function normalizeWhisperModelId(modelId) {
    if (modelId.startsWith('distil-whisper/')) {
        return modelId
    }

    if (modelId.endsWith('.en')) {
        return modelId.replace(/\.en$/, '')
    }

    return modelId
}

/**
 * @param {WhisperModelEntry[]} models
 * @param {string} modelId
 * @param {boolean} multilingual
 * @param {boolean} quantized
 */
export function pickWhisperModelSelection(models, modelId, multilingual, quantized) {
    const normalizedId = normalizeWhisperModelId(modelId)
    const visible = filterWhisperModels(models, multilingual, quantized)

    if (visible.some((model) => model.id === normalizedId)) {
        return normalizedId
    }

    return visible[0]?.id ?? normalizedId
}

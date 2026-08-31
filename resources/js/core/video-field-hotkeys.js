export const VIDEO_FIELD_HOTKEYS = {
    togglePaused: [' ', 'k', 'K'],
    toggleMuted: ['m', 'M'],
    toggleFullscreen: ['f', 'F'],
    togglePictureInPicture: ['i', 'I'],
    seekBackShort: ['ArrowLeft'],
    seekForwardShort: ['ArrowRight'],
    volumeUp: ['ArrowUp'],
    volumeDown: ['ArrowDown'],
    escape: ['Escape'],
}

export function isEditableTarget(target) {
    if (! target || ! (target instanceof Element)) {
        return false
    }

    const tag = target.tagName

    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
        return true
    }

    return target.isContentEditable
}

export function resolveVideoHotkeyAction(event) {
    if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey) {
        return null
    }

    if (isEditableTarget(event.target)) {
        return null
    }

    const key = event.key

    for (const [action, keys] of Object.entries(VIDEO_FIELD_HOTKEYS)) {
        if (keys.includes(key)) {
            return action
        }
    }

    return null
}

export function formatAudioTime(seconds) {
    if (! Number.isFinite(seconds) || seconds < 0) {
        return '0:00'
    }

    const total = Math.floor(seconds)
    const minutes = Math.floor(total / 60)
    const secs = total % 60

    return `${minutes}:${String(secs).padStart(2, '0')}`
}

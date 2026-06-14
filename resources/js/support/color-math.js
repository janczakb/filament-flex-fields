import defaultColorGrid from './default-color-grid.json'

export function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value))
}

export function hexToRgb(hex) {
    if (! hex) {
        return { r: 0, g: 0, b: 0, a: 1 }
    }

    let normalized = String(hex).trim().replace('#', '')

    if (normalized.length === 3) {
        normalized = normalized.split('').map((char) => char + char).join('')
    }

    const alphaHex = normalized.length === 8 ? normalized.slice(6, 8) : null
    const base = normalized.length >= 6 ? normalized.slice(0, 6) : normalized.padEnd(6, '0')

    return {
        r: parseInt(base.slice(0, 2), 16),
        g: parseInt(base.slice(2, 4), 16),
        b: parseInt(base.slice(4, 6), 16),
        a: alphaHex ? parseInt(alphaHex, 16) / 255 : 1,
    }
}

export function rgbToHex(r, g, b, alpha = 1) {
    const toHex = (value) => clamp(Math.round(value), 0, 255).toString(16).padStart(2, '0')
    const base = `#${toHex(r)}${toHex(g)}${toHex(b)}`.toUpperCase()

    if (alpha >= 0.999) {
        return base
    }

    return `${base}${toHex(alpha * 255)}`.toUpperCase()
}

export function hsvToRgb(h, s, v) {
    const hue = ((h % 360) + 360) % 360
    const saturation = clamp(s, 0, 100) / 100
    const value = clamp(v, 0, 100) / 100
    const chroma = value * saturation
    const x = chroma * (1 - Math.abs(((hue / 60) % 2) - 1))
    const match = value - chroma

    let r = 0
    let g = 0
    let b = 0

    if (hue < 60) {
        r = chroma; g = x
    } else if (hue < 120) {
        r = x; g = chroma
    } else if (hue < 180) {
        g = chroma; b = x
    } else if (hue < 240) {
        g = x; b = chroma
    } else if (hue < 300) {
        r = x; b = chroma
    } else {
        r = chroma; b = x
    }

    return {
        r: Math.round((r + match) * 255),
        g: Math.round((g + match) * 255),
        b: Math.round((b + match) * 255),
    }
}

export function rgbToHsv(r, g, b) {
    const red = clamp(r, 0, 255) / 255
    const green = clamp(g, 0, 255) / 255
    const blue = clamp(b, 0, 255) / 255
    const max = Math.max(red, green, blue)
    const min = Math.min(red, green, blue)
    const delta = max - min

    let h = 0

    if (delta !== 0) {
        if (max === red) {
            h = 60 * (((green - blue) / delta) % 6)
        } else if (max === green) {
            h = 60 * (((blue - red) / delta) + 2)
        } else {
            h = 60 * (((red - green) / delta) + 4)
        }
    }

    if (h < 0) {
        h += 360
    }

    const s = max === 0 ? 0 : (delta / max) * 100
    const v = max * 100

    return { h, s, v }
}

export function hsvToHex(h, s, v, alpha = 1) {
    const { r, g, b } = hsvToRgb(h, s, v)

    return rgbToHex(r, g, b, alpha)
}

export function parseColor(input) {
    const value = String(input ?? '').trim()

    if (value === '') {
        return { h: 260, s: 65, v: 85, a: 1 }
    }

    if (value.startsWith('#')) {
        const { r, g, b, a } = hexToRgb(value)
        const { h, s, v } = rgbToHsv(r, g, b)

        return { h, s, v, a }
    }

    const rgba = value.match(/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*([\d.]+))?\s*\)$/i)

    if (rgba) {
        const { h, s, v } = rgbToHsv(Number(rgba[1]), Number(rgba[2]), Number(rgba[3]))

        return {
            h,
            s,
            v,
            a: rgba[4] === undefined ? 1 : clamp(Number(rgba[4]), 0, 1),
        }
    }

    const hsla = value.match(/^hsla?\(\s*([\d.]+)\s*,\s*([\d.]+)%\s*,\s*([\d.]+)%(?:\s*,\s*([\d.]+))?\s*\)$/i)

    if (hsla) {
        const { r, g, b } = hsvToRgb(Number(hsla[1]), Number(hsla[2]), Number(hsla[3]))
        const { h, s, v } = rgbToHsv(r, g, b)

        return {
            h,
            s,
            v,
            a: hsla[4] === undefined ? 1 : clamp(Number(hsla[4]), 0, 1),
        }
    }

    if (CSS.supports('color', value)) {
        const { r, g, b, a } = hexToRgb(cssColorToHex(value))

        return { ...rgbToHsv(r, g, b), a }
    }

    return { h: 260, s: 65, v: 85, a: 1 }
}

function cssColorToHex(color) {
    const element = document.createElement('div')
    element.style.color = color
    document.body.appendChild(element)
    const computed = getComputedStyle(element).color
    document.body.removeChild(element)

    const match = computed.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/)

    if (! match) {
        return '#000000'
    }

    const alpha = match[4] === undefined ? 1 : Number(match[4])

    return rgbToHex(Number(match[1]), Number(match[2]), Number(match[3]), alpha)
}

export function formatColor(h, s, v, alpha, format) {
    const { r, g, b } = hsvToRgb(h, s, v)
    const a = clamp(alpha, 0, 1)

    if (format === 'rgb') {
        return `rgb(${r}, ${g}, ${b})`
    }

    if (format === 'rgba') {
        return `rgba(${r}, ${g}, ${b}, ${roundAlpha(a)})`
    }

    if (format === 'hsl') {
        return hslStringFromHsv(h, s, v)
    }

    if (format === 'hsla') {
        const hsl = hslStringFromRgb(r, g, b)

        return hsl.replace('hsl(', 'hsla(').replace(')', `, ${roundAlpha(a)})`)
    }

    return rgbToHex(r, g, b, a)
}

function hslStringFromRgb(r, g, b) {
    const red = r / 255
    const green = g / 255
    const blue = b / 255
    const max = Math.max(red, green, blue)
    const min = Math.min(red, green, blue)
    const lightness = (max + min) / 2
    const delta = max - min
    let hue = 0
    let saturation = 0

    if (delta !== 0) {
        saturation = lightness > 0.5 ? delta / (2 - max - min) : delta / (max + min)

        if (max === red) {
            hue = ((green - blue) / delta) % 6
        } else if (max === green) {
            hue = ((blue - red) / delta) + 2
        } else {
            hue = ((red - green) / delta) + 4
        }
    }

    hue = Math.round(hue * 60)

    if (hue < 0) {
        hue += 360
    }

    return `hsl(${hue}, ${Math.round(saturation * 100)}%, ${Math.round(lightness * 100)}%)`
}

function hslStringFromHsv(h, s, v) {
    const { r, g, b } = hsvToRgb(h, s, v)

    return hslStringFromRgb(r, g, b)
}

function roundAlpha(alpha) {
    return Math.round(alpha * 100) / 100
}

export const DEFAULT_COLOR_GRID_COLUMNS = 17

export const DEFAULT_COLOR_GRID_ROWS = 11

export function getDefaultColorGrid() {
    return defaultColorGrid
}

export function generateColorGrid(columns = DEFAULT_COLOR_GRID_COLUMNS, rows = DEFAULT_COLOR_GRID_ROWS) {
    if (columns === DEFAULT_COLOR_GRID_COLUMNS && rows === DEFAULT_COLOR_GRID_ROWS) {
        return defaultColorGrid
    }

    const colors = []

    for (let row = 0; row < rows; row++) {
        const rowProgress = row / Math.max(rows - 1, 1)
        const value = 97 - rowProgress * 70
        const saturation = 22 + rowProgress * 66

        for (let col = 0; col < columns; col++) {
            const hue = (col / columns) * 360
            colors.push(hsvToHex(hue, saturation, value))
        }
    }

    return colors
}

export function opacityPercent(alpha) {
    return `${Math.round(clamp(alpha, 0, 1) * 100)}%`
}

export function isValidColorString(value, format) {
    const input = String(value ?? '').trim()

    if (input === '') {
        return true
    }

    return CSS.supports('color', input) || input.startsWith('#')
}

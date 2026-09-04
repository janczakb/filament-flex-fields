/**
 * Radial circle ↔ scallop morph (lerp radius per angle — no spin) + stroke timing.
 */

export const MORPH_SELECT_MS = 360
export const MORPH_DESELECT_MS = 280
export const STROKE_WIDTH_MAX = 0.04
export const MORPH_ANGLE_COUNT = 160
export const CIRCLE_RADIUS = 0.495

/** GSAP-like power2.out */
export function easePower2Out(t) {
    const p = Math.min(1, Math.max(0, t))

    return 1 - (1 - p) * (1 - p)
}

/** GSAP-like power2.in — snappy deselect. */
export function easePower2In(t) {
    const p = Math.min(1, Math.max(0, t))

    return p * p
}

export function easeForMorph(t, selecting) {
    return selecting ? easePower2Out(t) : easePower2In(t)
}

/**
 * Stroke opacity/width factor: appears with morph, collapses faster on the way down
 * so the border never hangs over the arena after the clip shrinks.
 */
export function strokeWidthFactor(progress, selecting) {
    const p = Math.min(1, Math.max(0, progress))

    if (p <= 0) {
        return 0
    }

    if (p >= 1) {
        return 1
    }

    if (selecting) {
        return easePower2Out(p)
    }

    // Collapse almost immediately on deselect so the ring never flashes over the arena.
    return Math.pow(p, 3.4)
}

export function sampleSvgGeometry(el, count) {
    if (! el || typeof el.getTotalLength !== 'function' || typeof el.getPointAtLength !== 'function') {
        return null
    }

    const length = el.getTotalLength()

    if (! Number.isFinite(length) || length <= 0) {
        return null
    }

    const points = []

    for (let i = 0; i < count; i += 1) {
        const point = el.getPointAtLength((i / count) * length)
        points.push({ x: point.x, y: point.y })
    }

    return points
}

function normalizeAngle(angle) {
    let a = angle

    while (a <= -Math.PI) {
        a += Math.PI * 2
    }

    while (a > Math.PI) {
        a -= Math.PI * 2
    }

    return a
}

/**
 * Build a dense radius table indexed by uniform angle (starts at -PI/2 = top).
 * Uses nearest-neighbor blend in polar space — no loop rotation / spin.
 */
export function radiiFromPathPoints(points, angleCount = MORPH_ANGLE_COUNT) {
    if (! points?.length) {
        return Array.from({ length: angleCount }, () => CIRCLE_RADIUS)
    }

    const polar = points.map((point) => {
        const dx = point.x - 0.5
        const dy = point.y - 0.5

        return {
            angle: Math.atan2(dy, dx),
            radius: Math.hypot(dx, dy) || CIRCLE_RADIUS,
        }
    }).sort((a, b) => a.angle - b.angle)

    // Close the loop for interpolation
    const loop = [
        ...polar,
        { angle: polar[0].angle + Math.PI * 2, radius: polar[0].radius },
        { angle: polar[polar.length - 1].angle - Math.PI * 2, radius: polar[polar.length - 1].radius },
    ].sort((a, b) => a.angle - b.angle)

    const radii = []

    for (let i = 0; i < angleCount; i += 1) {
        const angle = normalizeAngle(-Math.PI / 2 + (i / angleCount) * Math.PI * 2)
        let lo = loop[0]
        let hi = loop[loop.length - 1]

        for (let j = 0; j < loop.length - 1; j += 1) {
            if (angle >= loop[j].angle && angle <= loop[j + 1].angle) {
                lo = loop[j]
                hi = loop[j + 1]
                break
            }
        }

        const span = hi.angle - lo.angle || 1
        const t = (angle - lo.angle) / span
        radii.push(lo.radius + (hi.radius - lo.radius) * t)
    }

    return radii
}

export function circleRadii(angleCount = MORPH_ANGLE_COUNT, radius = CIRCLE_RADIUS) {
    return Array.from({ length: angleCount }, () => radius)
}

export function lerpRadii(from, to, t) {
    const p = Math.min(1, Math.max(0, t))

    return from.map((radius, index) => radius + (to[index] - radius) * p)
}

export function radiiToPoints(radii) {
    const count = radii.length

    return radii.map((radius, index) => {
        const angle = -Math.PI / 2 + (index / count) * Math.PI * 2

        return {
            x: 0.5 + radius * Math.cos(angle),
            y: 0.5 + radius * Math.sin(angle),
        }
    })
}

export function pointsToPolygon(points) {
    return `polygon(${points.map((point) => `${(point.x * 100).toFixed(4)}% ${(point.y * 100).toFixed(4)}%`).join(',')})`
}

export function pointsToPathD(points) {
    if (! points.length) {
        return ''
    }

    const [first, ...rest] = points
    let d = `M${first.x.toFixed(5)} ${first.y.toFixed(5)}`

    for (const point of rest) {
        d += `L${point.x.toFixed(5)} ${point.y.toFixed(5)}`
    }

    return `${d}Z`
}

/** Static circle stroke path — safe before scallop radii are sampled. */
export function circleStrokePathD(angleCount = MORPH_ANGLE_COUNT, radius = CIRCLE_RADIUS) {
    return pointsToPathD(radiiToPoints(circleRadii(angleCount, radius)))
}

export function resolveMorphRadii(documentRef = typeof document !== 'undefined' ? document : null) {
    const circle = circleRadii()
    let scallop = circle

    const scallopEl = documentRef?.getElementById?.('fff-bubble-choice-geom-scallop')
    const sampled = sampleSvgGeometry(scallopEl, Math.max(MORPH_ANGLE_COUNT * 2, 240))

    if (sampled) {
        scallop = radiiFromPathPoints(sampled, MORPH_ANGLE_COUNT)
    }

    return { circle, scallop }
}

export function morphPointsAt(progress, morphRadii) {
    const radii = lerpRadii(morphRadii.circle, morphRadii.scallop, progress)

    return radiiToPoints(radii)
}

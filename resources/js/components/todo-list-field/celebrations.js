/**
 * TodoList celebration registry + five built-in canvas FX.
 * No GSAP — rAF particle systems with hard duration stop.
 */

/** @type {Map<string, { durationMs?: number, start: Function, stop?: Function }>} */
const celebrations = new Map()

export function registerTodoListCelebration(key, definition) {
    if (! key || typeof definition?.start !== 'function') {
        return
    }

    celebrations.set(String(key), definition)

    if (typeof window !== 'undefined') {
        window.FilamentFlexFieldsTodoList = window.FilamentFlexFieldsTodoList || {}
        window.FilamentFlexFieldsTodoList.registerCelebration = registerTodoListCelebration
        window.FilamentFlexFieldsTodoList.celebrations = celebrations
    }
}

export function getTodoListCelebration(key) {
    if (! key) {
        return null
    }

    return celebrations.get(String(key)) ?? null
}

export function listTodoListCelebrations() {
    return [...celebrations.keys()]
}

function randomBetween(min, max) {
    return min + Math.random() * (max - min)
}

function createRunner({
    canvas,
    durationMs,
    playSound,
    startSound,
    burstSound,
    reducedMotion,
    paint,
    playStartSound = true,
    fullscreen = false,
    startSoundFadeOutAfterMs = null,
    startSoundFadeMs = 400,
    trailFade = null,
    /** When true, celebration stop clears the canvas but never fades/pauses audio. */
    letSoundsFinish = false,
}) {
    const ctx = canvas.getContext('2d')
    let raf = 0
    let stopped = false
    const startedAt = performance.now()
    let last = startedAt
    /** @type {Array<{ stop?: Function, fadeOut?: Function }>} */
    const soundHandles = []

    const trackSound = (handle) => {
        if (handle) {
            soundHandles.push(handle)
        }

        return handle
    }

    if (! reducedMotion && playStartSound && startSound) {
        trackSound(playSound?.(startSound, {
            fadeOutAfterMs: startSoundFadeOutAfterMs ?? undefined,
            fadeMs: startSoundFadeMs,
        }))
    }

    const measure = () => {
        if (fullscreen) {
            return {
                width: Math.max(1, window.innerWidth || canvas.clientWidth || 1),
                height: Math.max(1, window.innerHeight || canvas.clientHeight || 1),
            }
        }

        return {
            width: Math.max(1, canvas.clientWidth || 1),
            height: Math.max(1, canvas.clientHeight || 1),
        }
    }

    const frame = (now) => {
        if (stopped) {
            return
        }

        const elapsed = now - startedAt
        const dt = Math.min(0.05, (now - last) / 1000)
        last = now

        const { width, height } = measure()
        const dpr = window.devicePixelRatio || 1

        if (canvas.width !== Math.floor(width * dpr) || canvas.height !== Math.floor(height * dpr)) {
            canvas.width = Math.floor(width * dpr)
            canvas.height = Math.floor(height * dpr)
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0)
        }

        if (typeof trailFade === 'number' && trailFade > 0) {
            ctx.globalCompositeOperation = 'destination-out'
            ctx.fillStyle = `rgba(0, 0, 0, ${Math.min(1, trailFade)})`
            ctx.fillRect(0, 0, width, height)
            ctx.globalCompositeOperation = 'source-over'
        } else {
            ctx.clearRect(0, 0, width, height)
        }

        paint({
            ctx,
            width,
            height,
            dt,
            elapsed,
            playStart: (playOptions = {}) => {
                if (startSound) {
                    trackSound(playSound?.(startSound, playOptions))
                }
            },
            playBurst: (playOptions = {}) => {
                if (burstSound) {
                    trackSound(playSound?.(burstSound, playOptions))
                }
            },
        })

        if (elapsed >= durationMs) {
            stop()

            return
        }

        raf = requestAnimationFrame(frame)
    }

    const stop = () => {
        if (stopped) {
            return
        }

        stopped = true

        if (typeof cancelAnimationFrame === 'function') {
            cancelAnimationFrame(raf)
        }

        if (! letSoundsFinish) {
            for (const handle of soundHandles) {
                handle.fadeOut?.(320)
            }
        }

        soundHandles.length = 0

        const { width, height } = measure()
        ctx.clearRect(0, 0, width, height)
    }

    if (reducedMotion) {
        return { stop }
    }

    if (typeof requestAnimationFrame === 'function') {
        raf = requestAnimationFrame(frame)
    }

    return { stop }
}

registerTodoListCelebration('fireworks', {
    // In-box ≈2–3s; fullscreen overridden to ≈4–5s in runTodoListCelebration.
    durationMs: 2500,
    start(api) {
        const createParticle = (x, y, maxVelocity) => {
            const radian = Math.PI * 2 * Math.random()
            const velocity = (1 - (Math.random() ** 6)) * maxVelocity
            const rate = Math.random()

            return {
                x,
                y,
                vx: velocity * Math.cos(radian) * rate,
                vy: velocity * Math.sin(radian) * rate,
                radius: 1.5,
                gravity: 0.03,
                friction: 0.98,
            }
        }

        const createFirework = (width, height) => {
            const area = Math.max(1, width * height)
            const shortSide = Math.max(1, Math.min(width, height))
            const fullscreen = Boolean(api.fullscreen)
            const inRow = ! fullscreen && height < 200

            const particleCount = fullscreen
                ? Math.max(100, Math.min(220, Math.round(Math.sqrt(area) / 3.8)))
                : Math.max(40, Math.min(inRow ? 72 : 110, Math.round(area / (inRow ? 280 : 420))))

            const maxVelocity = fullscreen
                ? 4.4
                : Math.max(2.0, Math.min(2.8, shortSide / (inRow ? 20 : 90)))

            const hue = (256 * Math.random()) | 0
            const x = randomBetween(width / 8, (width * 7) / 8)
            // Fullscreen: burst a bit higher so the climb is shorter + snappier.
            const y = fullscreen
                ? randomBetween(height * 0.16, height * 0.42)
                : randomBetween(height / 4, height / 2)
            const radius = 2
            const particles = []

            for (let i = 0; i < particleCount; i += 1) {
                particles.push(createParticle(x, y, maxVelocity))
            }

            return {
                x,
                y,
                x0: x,
                y0: height + radius,
                color: `hsl(${hue}, 84%, 69%)`,
                status: 0,
                launched: false,
                burstPlayed: false,
                theta: 0,
                waitCount: fullscreen
                    ? (randomBetween(4, 12) | 0)
                    : (randomBetween(14, 26) | 0),
                opacity: 1,
                // Fullscreen needs a much faster climb across a tall viewport.
                velocity: fullscreen ? -6.2 : (inRow ? -2.6 : -3),
                gravity: fullscreen ? 0.0012 : 0.002,
                radius,
                threshold: fullscreen ? 56 : Math.max(28, Math.min(50, height * 0.45)),
                deltaOpacity: fullscreen ? 0.016 : 0.012,
                deltaTheta: Math.PI / 10,
                particles,
            }
        }

        const fireworks = []
        let seeded = false
        let fireworkInterval = 0
        let maxFireworkInterval = 0

        const nextInterval = (width, height) => {
            if (api.fullscreen) {
                // Dense, snappy barrage for the 4–5s fullscreen window.
                return (randomBetween(8, 22) | 0) || 8
            }

            const inRow = height < 200

            // Per-item: leave clear gaps so shots do not pile up in the row.
            return (randomBetween(inRow ? 36 : 30, inRow ? 58 : 48) | 0) || 36
        }

        const maxRisingAtOnce = () => (api.fullscreen ? 4 : 1)

        const tickFirework = (fw, context, playStart, playBurst) => {
            switch (fw.status) {
                case 0: {
                    context.save()
                    context.fillStyle = fw.color
                    context.globalCompositeOperation = 'lighter'
                    context.globalAlpha = fw.y0 - fw.y <= fw.threshold
                        ? (fw.y0 - fw.y) / fw.threshold
                        : 1
                    context.translate(fw.x0 + Math.sin(fw.theta) / 2, fw.y0)
                    context.scale(0.8, 2.4)
                    context.beginPath()
                    context.arc(0, 0, fw.radius, 0, Math.PI * 2, false)
                    context.fill()
                    context.restore()

                    fw.y0 += fw.velocity

                    if (fw.y0 <= fw.y) {
                        fw.status = 1
                    }

                    if (! fw.launched) {
                        fw.launched = true
                        playStart?.()
                    }

                    fw.theta += fw.deltaTheta
                    fw.theta %= Math.PI * 2
                    fw.velocity += fw.gravity

                    return true
                }
                case 1: {
                    fw.waitCount -= 1

                    if (fw.waitCount <= 0) {
                        fw.status = 2
                    }

                    return true
                }
                case 2: {
                    if (! fw.burstPlayed) {
                        fw.burstPlayed = true
                        playBurst?.()
                    }

                    context.save()
                    context.globalCompositeOperation = 'lighter'
                    context.globalAlpha = fw.opacity
                    context.fillStyle = fw.color

                    for (const particle of fw.particles) {
                        context.beginPath()
                        context.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2, false)
                        context.fill()

                        particle.x += particle.vx
                        particle.y += particle.vy
                        particle.vy += particle.gravity
                        particle.vx *= particle.friction
                        particle.vy *= particle.friction
                    }

                    context.restore()
                    fw.opacity -= fw.deltaOpacity

                    return fw.opacity > 0
                }
                default:
                    return false
            }
        }

        return createRunner({
            ...api,
            playStartSound: false,
            letSoundsFinish: true,
            paint({ ctx, width, height, elapsed, playStart, playBurst }) {
                if (! seeded) {
                    fireworks.length = 0
                    fireworks.push(createFirework(width, height))
                    maxFireworkInterval = nextInterval(width, height)
                    fireworkInterval = maxFireworkInterval
                    seeded = true
                }

                for (let i = fireworks.length - 1; i >= 0; i -= 1) {
                    if (! tickFirework(fireworks[i], ctx, playStart, playBurst)) {
                        fireworks.splice(i, 1)
                    }
                }

                const spawnUntil = api.durationMs - (api.fullscreen ? 900 : 850)

                if (elapsed >= spawnUntil) {
                    return
                }

                fireworkInterval -= 1

                const rising = fireworks.filter((fw) => fw.status < 2).length

                if (fireworkInterval <= 0 && rising < maxRisingAtOnce()) {
                    fireworks.push(createFirework(width, height))
                    maxFireworkInterval = nextInterval(width, height)
                    fireworkInterval = maxFireworkInterval
                } else if (fireworkInterval <= 0) {
                    // Wait for a slot — retry soon instead of dumping another rocket.
                    fireworkInterval = api.fullscreen ? 4 : 10
                }
            },
        })
    },
})

registerTodoListCelebration('confetti', {
    // Audio (todo-confetti.mp3) is ~1.78s — keep FX in sync, no long recycle rain.
    durationMs: 1900,
    start(api) {
        const colors = ['#6366f1', '#22c55e', '#f59e0b', '#ec4899', '#06b6d4', '#a855f7', '#f97316']
        const pieces = []
        const measuredWidth = Math.max(
            1,
            api.canvas?.clientWidth || api.canvas?.width || 320,
        )
        const pieceCount = Math.max(22, Math.min(90, Math.round(measuredWidth / 12)))
        const spawnWindowSec = 0.42

        const spawnPiece = (index) => {
            const kind = Math.random()
            const size = randomBetween(3.5, 8.5)

            return {
                x: randomBetween(-0.02, 1.02),
                y: randomBetween(-0.45, -0.02) - (index % 16) * 0.015,
                w: kind > 0.55 ? size : size * randomBetween(0.35, 0.7),
                h: kind > 0.55 ? size * randomBetween(0.4, 0.85) : size,
                vx: randomBetween(-1.2, 1.2),
                vy: randomBetween(0.9, 2.6),
                rot: randomBetween(0, Math.PI * 2),
                vr: randomBetween(-0.35, 0.35),
                wobble: randomBetween(0.4, 1.6),
                wobblePhase: Math.random() * Math.PI * 2,
                gravity: randomBetween(4.5, 8.5),
                color: colors[Math.floor(Math.random() * colors.length)],
                shape: kind > 0.7 ? 'rect' : kind > 0.4 ? 'tri' : 'bar',
                delay: index * randomBetween(0.004, 0.018),
            }
        }

        for (let i = 0; i < pieceCount; i += 1) {
            pieces.push(spawnPiece(i))
        }

        let elapsedLocal = 0

        return createRunner({
            ...api,
            letSoundsFinish: true,
            paint({ ctx, width, height, dt }) {
                elapsedLocal += dt

                for (let i = pieces.length - 1; i >= 0; i -= 1) {
                    const piece = pieces[i]

                    if (elapsedLocal < piece.delay) {
                        continue
                    }

                    piece.wobblePhase += dt * piece.wobble
                    piece.vy += piece.gravity * dt
                    piece.vx += Math.sin(piece.wobblePhase) * 0.3 * dt
                    piece.x += (piece.vx * 48 * dt) / width
                    piece.y += (piece.vy * 48 * dt) / height
                    piece.rot += piece.vr + Math.sin(piece.wobblePhase) * 0.04

                    // No recycle — let pieces fall away so the end isn't a hard cut.
                    if (piece.y > 1.25) {
                        pieces.splice(i, 1)

                        continue
                    }

                    // After spawn window, accelerate exit so canvas empties with the sound.
                    if (elapsedLocal > spawnWindowSec) {
                        piece.vy += 6 * dt
                    }

                    const fade = piece.y > 0.78
                        ? Math.max(0, 1 - ((piece.y - 0.78) / 0.47))
                        : 1

                    const x = piece.x * width
                    const y = piece.y * height
                    ctx.save()
                    ctx.translate(x, y)
                    ctx.rotate(piece.rot)
                    ctx.globalAlpha = 0.92 * fade
                    ctx.fillStyle = piece.color

                    if (piece.shape === 'tri') {
                        ctx.beginPath()
                        ctx.moveTo(0, -piece.h / 2)
                        ctx.lineTo(piece.w / 2, piece.h / 2)
                        ctx.lineTo(-piece.w / 2, piece.h / 2)
                        ctx.closePath()
                        ctx.fill()
                    } else {
                        ctx.fillRect(-piece.w / 2, -piece.h / 2, piece.w, piece.h)
                    }

                    ctx.restore()
                }
            },
        })
    },
})

registerTodoListCelebration('sparkles', {
    durationMs: 2200,
    start(api) {
        const sparks = []

        const spawn = (width, height) => {
            sparks.push({
                x: randomBetween(width * 0.1, width * 0.9),
                y: randomBetween(height * 0.1, height * 0.9),
                r: 0,
                max: randomBetween(8, 18),
                life: 1,
                hue: randomBetween(38, 55),
            })
        }

        let tick = 0

        return createRunner({
            ...api,
            // Keep chime through the FX, then fade with the finish (~2.2s total).
            startSoundFadeOutAfterMs: 1700,
            startSoundFadeMs: 500,
            paint({ ctx, width, height, dt, elapsed }) {
                tick += dt

                // Stop spawning near the end so sparks can settle out cleanly.
                if (tick > 0.08 && elapsed < api.durationMs - 450) {
                    tick = 0
                    spawn(width, height)
                }

                for (let i = sparks.length - 1; i >= 0; i -= 1) {
                    const spark = sparks[i]
                    spark.r += dt * 28
                    spark.life -= dt * 1.4

                    if (spark.life <= 0) {
                        sparks.splice(i, 1)

                        continue
                    }

                    ctx.save()
                    ctx.globalCompositeOperation = 'lighter'
                    ctx.globalAlpha = Math.max(0, spark.life)
                    ctx.strokeStyle = `hsl(${spark.hue} 90% 65%)`
                    ctx.lineWidth = 1.4
                    ctx.beginPath()

                    for (let a = 0; a < 8; a += 1) {
                        const ang = (a / 8) * Math.PI * 2
                        ctx.moveTo(spark.x, spark.y)
                        ctx.lineTo(spark.x + Math.cos(ang) * spark.r, spark.y + Math.sin(ang) * spark.r)
                    }

                    ctx.stroke()
                    ctx.restore()
                }
            },
        })
    },
})

registerTodoListCelebration('streamers', {
    durationMs: 2200,
    start(api) {
        // Meteors — diagonal glowing streaks (replaces weak side-cannon ribbons)
        const meteors = []
        const colors = ['#a5b4fc', '#f9a8d4', '#67e8f9', '#fde68a', '#c4b5fd']

        const spawn = (width, height) => {
            meteors.push({
                x: randomBetween(-width * 0.1, width * 0.7),
                y: randomBetween(-height * 0.15, height * 0.35),
                len: randomBetween(40, 90),
                speed: randomBetween(320, 520),
                angle: Math.PI * 0.22 + randomBetween(-0.08, 0.08),
                life: 1,
                color: colors[Math.floor(Math.random() * colors.length)],
                width: randomBetween(1.5, 2.8),
            })
        }

        let cool = 0

        return createRunner({
            ...api,
            paint({ ctx, width, height, dt, elapsed, playBurst }) {
                cool -= dt

                if (cool <= 0 && elapsed < api.durationMs - 450) {
                    cool = randomBetween(0.08, 0.18)
                    spawn(width, height)

                    if (Math.random() > 0.65) {
                        playBurst()
                    }
                }

                for (let i = meteors.length - 1; i >= 0; i -= 1) {
                    const m = meteors[i]
                    const dx = Math.cos(m.angle) * m.speed * dt
                    const dy = Math.sin(m.angle) * m.speed * dt
                    m.x += dx
                    m.y += dy
                    m.life -= dt * 0.55

                    if (m.life <= 0 || m.x > width + 40 || m.y > height + 40) {
                        meteors.splice(i, 1)

                        continue
                    }

                    const tx = m.x - Math.cos(m.angle) * m.len
                    const ty = m.y - Math.sin(m.angle) * m.len
                    const grad = ctx.createLinearGradient(tx, ty, m.x, m.y)
                    grad.addColorStop(0, 'transparent')
                    grad.addColorStop(1, m.color)

                    ctx.save()
                    ctx.globalCompositeOperation = 'lighter'
                    ctx.globalAlpha = Math.max(0, m.life)
                    ctx.strokeStyle = grad
                    ctx.lineWidth = m.width
                    ctx.lineCap = 'round'
                    ctx.beginPath()
                    ctx.moveTo(tx, ty)
                    ctx.lineTo(m.x, m.y)
                    ctx.stroke()
                    ctx.fillStyle = m.color
                    ctx.beginPath()
                    ctx.arc(m.x, m.y, m.width * 1.2, 0, Math.PI * 2)
                    ctx.fill()
                    ctx.restore()
                }
            },
        })
    },
})

registerTodoListCelebration('bloom', {
    durationMs: 4200,
    start(api) {
        // Checks — cascading checklist ticks that feel like finishing a todo list
        const ticks = []
        const accent = ['#22c55e', '#16a34a', '#4ade80', '#86efac', '#a3e635']

        const spawnTick = (width, height, index) => {
            ticks.push({
                x: randomBetween(width * 0.12, width * 0.88),
                y: randomBetween(height * 0.18, height * 0.82),
                scale: 0,
                maxScale: randomBetween(0.85, 1.35),
                life: 1,
                rot: randomBetween(-0.35, 0.35),
                color: accent[Math.floor(Math.random() * accent.length)],
                delay: index * randomBetween(0.08, 0.14),
                born: false,
            })
        }

        for (let i = 0; i < 8; i += 1) {
            spawnTick(320, 200, i)
        }

        let elapsedLocal = 0

        return createRunner({
            ...api,
            paint({ ctx, width, height, dt }) {
                elapsedLocal += dt

                for (const tick of ticks) {
                    if (elapsedLocal < tick.delay) {
                        continue
                    }

                    if (! tick.born) {
                        tick.born = true
                        tick.x = randomBetween(width * 0.1, width * 0.9)
                        tick.y = randomBetween(height * 0.15, height * 0.85)
                    }

                    tick.scale = Math.min(tick.maxScale, tick.scale + dt * 4.2)
                    tick.life -= dt * 0.42
                    tick.y -= dt * 8

                    if (tick.life <= 0) {
                        continue
                    }

                    const size = 14 * tick.scale
                    ctx.save()
                    ctx.translate(tick.x, tick.y)
                    ctx.rotate(tick.rot)
                    ctx.globalAlpha = Math.max(0, Math.min(1, tick.life)) * 0.92
                    ctx.strokeStyle = tick.color
                    ctx.lineWidth = Math.max(2, 2.6 * tick.scale)
                    ctx.lineCap = 'round'
                    ctx.lineJoin = 'round'
                    ctx.beginPath()
                    ctx.moveTo(-size * 0.42, size * 0.05)
                    ctx.lineTo(-size * 0.08, size * 0.38)
                    ctx.lineTo(size * 0.48, -size * 0.36)
                    ctx.stroke()
                    ctx.restore()
                }
            },
        })
    },
})

export function runTodoListCelebration(key, api) {
    const def = getTodoListCelebration(key)

    if (! def) {
        return null
    }

    // Built-in FX duration wins over the field's celebrationDurationMs default (5500).
    let durationMs = def.durationMs ?? api.durationMs ?? 5500

    // In-box ≈2–3s; fullscreen ≈4–5s. Shot/particle counts scale inside start().
    if (key === 'fireworks') {
        durationMs = api.fullscreen ? 4500 : (def.durationMs ?? 2500)
    } else if (api.fullscreen && def.durationMs == null) {
        durationMs = Math.max(durationMs, 5500)
    }

    const canvas = api.canvas

    if (api.fullscreen && canvas) {
        canvas.classList.add('fff-todo-list-field__celebration--fullscreen')
        // Ensure fullscreen canvas measures the viewport, not the field shell.
        canvas.style.width = '100vw'
        canvas.style.height = '100vh'
    }

    const handle = def.start({
        ...api,
        durationMs,
    })

    if (! handle) {
        return null
    }

    const originalStop = handle.stop?.bind(handle)

    return {
        stop() {
            originalStop?.()

            if (canvas && api.fullscreen) {
                canvas.classList.remove('fff-todo-list-field__celebration--fullscreen')
                canvas.style.width = ''
                canvas.style.height = ''
            }

            api.onStop?.()
        },
    }
}

if (typeof window !== 'undefined') {
    window.FilamentFlexFieldsTodoList = window.FilamentFlexFieldsTodoList || {}
    window.FilamentFlexFieldsTodoList.registerCelebration = registerTodoListCelebration
}

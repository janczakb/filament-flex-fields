{{-- Demo custom celebration: flowing silk aurora ribbons (fullscreen). --}}
@once('fff-todo-list-playground-custom-celebration')
    <script>
        (() => {
            const KEY = 'playground-burst'

            const register = () => {
                const api = window.FilamentFlexFieldsTodoList

                if (! api?.registerCelebration) {
                    return false
                }

                api.registerCelebration(KEY, {
                    durationMs: 3600,
                    start({ canvas, durationMs, fullscreen, reducedMotion, playSound }) {
                        if (reducedMotion || ! canvas) {
                            return { stop() {} }
                        }

                        playSound?.('https://www.myinstants.com/media/sounds/victory_sJDDywi.mp3', {
                            fadeOutAfterMs: Math.max(0, durationMs - 500),
                            fadeMs: 450,
                        })

                        const ctx = canvas.getContext('2d')
                        let raf = 0
                        let stopped = false
                        const startedAt = performance.now()
                        const rand = (a, b) => a + Math.random() * (b - a)

                        const ribbons = Array.from({ length: fullscreen ? 7 : 5 }, (_, i) => ({
                            phase: rand(0, Math.PI * 2),
                            speed: rand(0.7, 1.4),
                            amp: rand(0.08, 0.18),
                            thickness: rand(18, 42),
                            hue: [200, 280, 320, 35, 160, 190, 250][i % 7],
                            yBase: 0.15 + i * 0.12,
                            drift: rand(-0.04, 0.04),
                        }))

                        const motes = Array.from({ length: fullscreen ? 55 : 28 }, () => ({
                            x: Math.random(),
                            y: Math.random(),
                            r: rand(1.1, 2.6),
                            vx: rand(-0.03, 0.03),
                            vy: rand(-0.08, -0.02),
                            hue: rand(180, 320),
                            phase: rand(0, Math.PI * 2),
                        }))

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

                        const drawRibbon = (ribbon, width, height, elapsed, alphaScale) => {
                            const steps = Math.max(40, Math.floor(width / 14))
                            const t = elapsed * 0.001 * ribbon.speed

                            ctx.save()
                            ctx.globalCompositeOperation = 'lighter'
                            ctx.lineCap = 'round'
                            ctx.lineJoin = 'round'

                            // Soft outer glow pass
                            ctx.lineWidth = ribbon.thickness * 1.8
                            ctx.strokeStyle = `hsla(${ribbon.hue} 90% 65% / ${0.12 * alphaScale})`
                            ctx.beginPath()

                            for (let i = 0; i <= steps; i += 1) {
                                const u = i / steps
                                const x = u * width
                                const y = height * (
                                    ribbon.yBase
                                    + ribbon.drift * Math.sin(t * 0.6 + u * 3)
                                    + ribbon.amp * Math.sin(u * Math.PI * 2.2 + t + ribbon.phase)
                                    + ribbon.amp * 0.35 * Math.sin(u * Math.PI * 5.5 - t * 1.3)
                                )

                                if (i === 0) {
                                    ctx.moveTo(x, y)
                                } else {
                                    ctx.lineTo(x, y)
                                }
                            }

                            ctx.stroke()

                            // Bright core
                            ctx.lineWidth = ribbon.thickness * 0.35
                            ctx.strokeStyle = `hsla(${ribbon.hue + 20} 95% 78% / ${0.55 * alphaScale})`
                            ctx.beginPath()

                            for (let i = 0; i <= steps; i += 1) {
                                const u = i / steps
                                const x = u * width
                                const y = height * (
                                    ribbon.yBase
                                    + ribbon.drift * Math.sin(t * 0.6 + u * 3)
                                    + ribbon.amp * Math.sin(u * Math.PI * 2.2 + t + ribbon.phase)
                                    + ribbon.amp * 0.35 * Math.sin(u * Math.PI * 5.5 - t * 1.3)
                                )

                                if (i === 0) {
                                    ctx.moveTo(x, y)
                                } else {
                                    ctx.lineTo(x, y)
                                }
                            }

                            ctx.stroke()
                            ctx.restore()
                        }

                        const frame = (now) => {
                            if (stopped) {
                                return
                            }

                            const elapsed = now - startedAt
                            const dt = Math.min(0.05, 1 / 60)
                            const { width, height } = measure()
                            const dpr = window.devicePixelRatio || 1

                            if (canvas.width !== Math.floor(width * dpr) || canvas.height !== Math.floor(height * dpr)) {
                                canvas.width = Math.floor(width * dpr)
                                canvas.height = Math.floor(height * dpr)
                                ctx.setTransform(dpr, 0, 0, dpr, 0, 0)
                            }

                            ctx.clearRect(0, 0, width, height)

                            // Fade in / out envelope so it doesn't hard-cut
                            const fadeIn = Math.min(1, elapsed / 280)
                            const fadeOut = elapsed > durationMs - 600
                                ? Math.max(0, (durationMs - elapsed) / 600)
                                : 1
                            const alpha = fadeIn * fadeOut

                            for (const ribbon of ribbons) {
                                drawRibbon(ribbon, width, height, elapsed, alpha)
                                // Slow vertical wander
                                ribbon.yBase += Math.sin(elapsed * 0.0007 + ribbon.phase) * 0.00015
                            }

                            for (const mote of motes) {
                                mote.phase += dt * 2.5
                                mote.x = (mote.x + mote.vx * dt + 1) % 1
                                mote.y = (mote.y + mote.vy * dt + 1) % 1

                                const mx = mote.x * width + Math.sin(mote.phase) * 10
                                const my = mote.y * height
                                ctx.save()
                                ctx.globalCompositeOperation = 'lighter'
                                ctx.globalAlpha = alpha * (0.25 + 0.45 * (0.5 + 0.5 * Math.sin(mote.phase)))
                                ctx.fillStyle = `hsl(${mote.hue} 90% 72%)`
                                ctx.beginPath()
                                ctx.arc(mx, my, mote.r, 0, Math.PI * 2)
                                ctx.fill()
                                ctx.restore()
                            }

                            if (elapsed >= durationMs) {
                                stop()

                                return
                            }

                            raf = requestAnimationFrame(frame)
                        }

                        const stop = () => {
                            stopped = true

                            if (typeof cancelAnimationFrame === 'function') {
                                cancelAnimationFrame(raf)
                            }

                            const { width, height } = measure()
                            ctx.clearRect(0, 0, width, height)
                        }

                        raf = requestAnimationFrame(frame)

                        return { stop }
                    },
                })

                return true
            }

            if (register()) {
                return
            }

            let tries = 0
            const timer = window.setInterval(() => {
                tries += 1

                if (register() || tries > 80) {
                    window.clearInterval(timer)
                }
            }, 50)
        })()
    </script>
@endonce

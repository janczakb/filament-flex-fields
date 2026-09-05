export default function imageChoiceCardsFormComponent({
    state,
    multiple,
    disabledOptions,
    disabled,
    rippleEnabled,
    maxSelections,
}) {
    return {
        state,
        multiple,
        disabledOptions,
        disabled,
        rippleEnabled,
        maxSelections,

        init() {
            this.syncFromDomIfNeeded()
            this.clearDisabledSelection()
            this.$watch('disabledOptions', () => this.clearDisabledSelection())
            this.bindImageLoading()
            this.warmNearbyImages()

            this.$nextTick(() => {
                this.$root.classList.add('is-hydrated')
            })
        },

        destroy() {
            this._imageWarmObserver?.disconnect()
            this._imageWarmObserver = null
        },

        /**
         * Soft-reveal remote images once decoded (avoids hard pop over the gray media bg).
         */
        bindImageLoading() {
            const images = this.$root.querySelectorAll('.fff-image-choice-cards__image')

            images.forEach((img) => {
                const markLoaded = () => img.classList.add('is-loaded')

                if (img.complete && img.naturalWidth > 0) {
                    markLoaded()

                    return
                }

                img.addEventListener('load', markLoaded, { once: true })
                img.addEventListener('error', markLoaded, { once: true })
            })
        },

        /**
         * Safari lazy-loads late; promote nearby images to eager ~1 viewport early
         * so remote URLs start fetching before the gray card is fully on screen.
         */
        warmNearbyImages() {
            if (typeof IntersectionObserver === 'undefined') {
                return
            }

            const images = [...this.$root.querySelectorAll('.fff-image-choice-cards__image[loading="lazy"]')]

            if (images.length === 0) {
                return
            }

            this._imageWarmObserver = new IntersectionObserver(
                (entries) => {
                    for (const entry of entries) {
                        if (! entry.isIntersecting) {
                            continue
                        }

                        const img = entry.target

                        if (img.getAttribute('loading') === 'lazy') {
                            img.setAttribute('loading', 'eager')
                            // Re-assign src so Safari starts the fetch immediately.
                            const src = img.getAttribute('src')

                            if (src) {
                                img.src = src
                            }
                        }

                        this._imageWarmObserver?.unobserve(img)
                    }
                },
                { root: null, rootMargin: '1200px 0px', threshold: 0.01 },
            )

            images.forEach((img) => this._imageWarmObserver.observe(img))
        },

        /**
         * Honor a native radio/checkbox change that happened before Alpine
         * finished loading (common first-tap lag on mobile + x-load).
         */
        syncFromDomIfNeeded() {
            const checked = [...this.$root.querySelectorAll('.fff-image-choice-cards__input:checked')]
                .map((input) => this.normalize(input.value))
                .filter((value) => value !== null && value !== '')

            if (checked.length === 0) {
                return
            }

            if (this.multiple) {
                const current = this.selectedValues()
                const same = current.length === checked.length
                    && checked.every((value) => current.includes(value))

                if (! same) {
                    this.state = checked
                }

                return
            }

            const next = checked[0]

            if (this.normalize(this.state) !== next) {
                this.state = next
            }
        },

        normalize(value) {
            return value === null || value === undefined ? null : String(value)
        },

        selectedValues() {
            if (! this.multiple) {
                return []
            }

            if (! Array.isArray(this.state)) {
                return []
            }

            return this.state.map((value) => this.normalize(value))
        },

        isSelected(value) {
            const key = this.normalize(value)

            if (this.multiple) {
                return this.selectedValues().includes(key)
            }

            return this.normalize(this.state) === key
        },

        isOptionDisabled(value) {
            return this.disabledOptions[this.normalize(value)] ?? false
        },

        isMaxReached() {
            return this.multiple
                && this.maxSelections !== null
                && this.selectedValues().length >= this.maxSelections
        },

        canSelect(value) {
            return ! this.disabled && ! this.isOptionDisabled(value)
        },

        canToggle(value) {
            if (! this.canSelect(value)) {
                return false
            }

            if (this.isSelected(value)) {
                return true
            }

            return ! this.isMaxReached()
        },

        select(value) {
            if (! this.canSelect(value)) {
                return
            }

            this.state = value
        },

        toggle(value) {
            if (! this.multiple || ! this.canToggle(value)) {
                return
            }

            const key = this.normalize(value)
            const current = this.selectedValues()

            if (current.includes(key)) {
                this.state = current.filter((item) => item !== key)

                return
            }

            this.state = [...current, key]
        },

        clearDisabledSelection() {
            if (this.multiple) {
                const next = this.selectedValues().filter((key) => ! this.isOptionDisabled(key))

                if (next.length !== this.selectedValues().length) {
                    this.state = next
                }

                return
            }

            if (this.state !== null && this.state !== undefined && this.state !== '' && this.isOptionDisabled(this.state)) {
                this.state = null
            }
        },

        ripple(event) {
            if (! this.rippleEnabled) {
                return
            }

            // Touch devices already give press feedback; skip DOM work on scroll/tap.
            if (event.pointerType === 'touch' || window.matchMedia('(pointer: coarse)').matches) {
                return
            }

            const card = event.currentTarget
            const circle = document.createElement('span')
            const diameter = Math.max(card.clientWidth, card.clientHeight)
            const rect = card.getBoundingClientRect()
            const x = (event.clientX ?? (rect.left + rect.width / 2)) - rect.left
            const y = (event.clientY ?? (rect.top + rect.height / 2)) - rect.top

            circle.className = 'fff-image-choice-cards__ripple'
            circle.style.width = `${diameter}px`
            circle.style.height = `${diameter}px`
            circle.style.left = `${x - (diameter / 2)}px`
            circle.style.top = `${y - (diameter / 2)}px`

            card.appendChild(circle)

            window.setTimeout(() => circle.remove(), 650)
        },
    }
}

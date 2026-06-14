export default function trafficSplitFormComponent({
    state,
    segmentCount,
    minWeight,
    valueThreshold,
    disabled,
    labels,
    lockedSegments,
    initialWeights,
    isLinked = false,
}) {
    return {
        state,
        segmentCount,
        minWeight,
        valueThreshold,
        disabled,
        labels,
        lockedSegments,
        initialWeights,
        isLinked,

        get weights() {
            if (Array.isArray(this.state) && this.state.length >= 2) {
                return this.state.map((weight) => parseInt(weight, 10) || this.minWeight)
            }

            if (Array.isArray(this.initialWeights) && this.initialWeights.length === this.segmentCount) {
                return this.initialWeights.map((weight) => parseInt(weight, 10) || this.minWeight)
            }

            return this.equalSplit()
        },

        init() {
            if (this.isLinked) {
                return
            }

            if (Array.isArray(this.state) && this.state.length === this.segmentCount) {
                this.ensureState()
            }
        },

        normalizedWeights(values) {
            if (! Array.isArray(values)) {
                return []
            }

            return values.map((weight) => parseInt(weight, 10) || this.minWeight)
        },

        weightsEqual(left, right) {
            const normalizedLeft = this.normalizedWeights(left)
            const normalizedRight = this.normalizedWeights(right)

            if (normalizedLeft.length !== normalizedRight.length) {
                return false
            }

            return normalizedLeft.every((weight, index) => weight === normalizedRight[index])
        },

        setStateIfChanged(nextState) {
            if (this.weightsEqual(this.state, nextState)) {
                return
            }

            this.state = nextState
        },

        equalSplit() {
            const count = this.segmentCount
            const base = Math.floor(100 / count)
            const remainder = 100 % count

            return Array.from({ length: count }, (_, index) => base + (index < remainder ? 1 : 0))
        },

        redistributeUnlocked(weights) {
            const locked = Array.isArray(this.lockedSegments) ? this.lockedSegments : []
            const unlocked = Array.from({ length: this.weights.length }, (_, index) => index)
                .filter((index) => ! locked.includes(index))

            if (unlocked.length === 0) {
                return Array.isArray(weights) ? [...weights] : this.equalSplit()
            }

            const lockedSum = locked.reduce((total, index) => total + weights[index], 0)
            const remaining = 100 - lockedSum

            if (remaining < this.minWeight * unlocked.length) {
                return this.equalSplit()
            }

            const base = Math.floor(remaining / unlocked.length)
            const remainder = remaining % unlocked.length
            const result = [...weights]

            unlocked.forEach((index, position) => {
                result[index] = base + (position < remainder ? 1 : 0)
            })

            return result
        },

        ensureState() {
            if (this.disabled || this.isLinked) {
                return
            }

            if (! Array.isArray(this.state) || this.state.length !== this.segmentCount) {
                this.setStateIfChanged(this.equalSplit())

                return
            }

            const locked = Array.isArray(this.lockedSegments) ? this.lockedSegments : []

            if (locked.length === 0) {
                const normalized = this.weights.map((weight) => Math.max(this.minWeight, weight))
                const sum = normalized.reduce((total, weight) => total + weight, 0)

                if (sum !== 100) {
                    this.setStateIfChanged(this.equalSplit())

                    return
                }

                if (normalized.some((weight, index) => weight !== this.state[index])) {
                    this.setStateIfChanged(normalized)
                }

                return
            }

            const weights = this.weights.map((weight, index) => (
                locked.includes(index)
                    ? Math.max(this.minWeight, weight)
                    : Math.max(this.minWeight, weight)
            ))

            const sum = weights.reduce((total, weight) => total + weight, 0)

            if (sum !== 100) {
                this.setStateIfChanged(this.redistributeUnlocked(weights))

                return
            }

            if (weights.some((weight, index) => weight !== this.state[index])) {
                this.setStateIfChanged(weights)
            }
        },

        handlePosition(index) {
            let position = 0

            for (let offset = 0; offset <= index; offset++) {
                position += this.weights[offset]
            }

            return position
        },

        isSegmentLocked(index) {
            return Array.isArray(this.lockedSegments) && this.lockedSegments.includes(index)
        },

        isHandleLocked(index) {
            return this.isSegmentLocked(index) || this.isSegmentLocked(index + 1)
        },

        segmentLabel(index) {
            if (Array.isArray(this.labels) && this.labels[index] !== undefined) {
                return this.labels[index]
            }

            return String(index + 1)
        },

        shouldShowValue(index) {
            return this.weights[index] >= this.valueThreshold
        },

        updateWeight(index, value) {
            if (! Array.isArray(this.state) || this.disabled || this.isSegmentLocked(index)) {
                return
            }

            this.state[index] = Math.max(this.minWeight, value)
        },

        startDrag(index, event) {
            if (this.disabled || this.isHandleLocked(index)) {
                return
            }

            event.preventDefault()

            const isTouch = event.type.startsWith('touch')

            const rect = this.$refs.container.getBoundingClientRect()
            const containerWidth = rect.width
            const containerLeft = rect.left

            if (! isTouch) {
                document.body.style.cursor = 'col-resize'
            }

            let sumBefore = 0

            for (let offset = 0; offset < index; offset++) {
                sumBefore += this.weights[offset]
            }

            const segmentSum = this.weights[index] + this.weights[index + 1]

            const onMove = (moveEvent) => {
                const currentX = moveEvent.type.startsWith('touch') ? moveEvent.touches[0].clientX : moveEvent.clientX
                const offsetPercent = ((currentX - containerLeft) / containerWidth) * 100

                const minPercent = sumBefore + this.minWeight
                const maxPercent = sumBefore + segmentSum - this.minWeight

                let targetPercent = Math.round(offsetPercent)
                targetPercent = Math.max(minPercent, Math.min(maxPercent, targetPercent))

                const newWeightCurrent = targetPercent - sumBefore
                const newWeightNext = segmentSum - newWeightCurrent

                this.updateWeight(index, newWeightCurrent)
                this.updateWeight(index + 1, newWeightNext)
            }

            const onEnd = () => {
                if (! isTouch) {
                    document.body.style.cursor = ''
                }

                if (isTouch) {
                    window.removeEventListener('touchmove', onMove)
                    window.removeEventListener('touchend', onEnd)
                } else {
                    window.removeEventListener('mousemove', onMove)
                    window.removeEventListener('mouseup', onEnd)
                }
            }

            if (isTouch) {
                window.addEventListener('touchmove', onMove, { passive: true })
                window.addEventListener('touchend', onEnd)
            } else {
                window.addEventListener('mousemove', onMove)
                window.addEventListener('mouseup', onEnd)
            }
        },
    }
}

import {
    finalizeSegmentValue,
    getSegmentMaxLength,
    processSegmentInputValue,
    resolveAdjacentSegmentIndex,
} from './segmented-input.js'

export function createTimePanelBehavior() {
    return {
        onTimeSegmentFocus(target, part, event) {
            this.activeRangeTarget = target

            const input = event?.target

            if (input) {
                this.$nextTick(() => this.placeSegmentCaret(input))
            }
        },

        onTimeSegmentBlur(target, part, event) {
            this.finalizeTimeSegment(target, part, () => {
                this.commitRangeTime(target)
            })
        },

        onTimeSegmentInput(target, part, event) {
            if (this.isLocked) {
                return
            }

            const previousValue = this.timeSegments[target][part] ?? ''
            const value = processSegmentInputValue(part, previousValue, event.target.value, this.config.hourCycle, 'time')

            this.timeSegments[target][part] = value
            this.commitRangeTime(target)

            if (value.length >= getSegmentMaxLength(part)) {
                const index = this.timeSegmentParts.indexOf(part)
                const nextIndex = resolveAdjacentSegmentIndex(this.timeSegmentParts, index)

                this.$nextTick(() => {
                    if (nextIndex === null) {
                        this.finishSegmentEditing(event.target)
                    } else {
                        this.focusTimeSegment(target, nextIndex)
                    }
                })
            }
        },

        onTimeSegmentKeydown(target, part, event) {
            const index = this.timeSegmentParts.indexOf(part)

            this.handleSegmentKeydown(event, this.timeSegmentParts, index, (nextIndex) => {
                this.focusTimeSegment(target, nextIndex)
            }, (input) => {
                this.finishSegmentEditing(input)
            })
        },

        finalizeTimeSegment(target, part, commit) {
            if (this.isLocked) {
                return
            }

            const current = this.timeSegments[target][part] ?? ''

            if (current === '') {
                return
            }

            const finalized = finalizeSegmentValue(part, current, this.config.hourCycle, 'time')

            if (finalized !== current) {
                this.timeSegments[target][part] = finalized
                commit()
            }
        },

        focusTimeSegment(target, index) {
            const part = this.timeSegmentParts[index]

            this.$nextTick(() => {
                const input = this.$root.querySelector(`[data-time-target="${target}"] [data-segment-part="${part}"]`)

                input?.focus()
                this.placeSegmentCaret(input)
            })
        },
    }
}

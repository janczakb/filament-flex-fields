/**
 * Entity / @mention query parsing for headless SelectField.
 *
 * @param {string} query
 * @param {string} trigger
 * @returns {{ active: boolean, trigger: string, term: string, search: string }}
 */
export function parseEntityMentionQuery(query, trigger = '@') {
    const text = String(query ?? '')
    const normalizedTrigger = String(trigger ?? '@')

    if (normalizedTrigger === '' || ! text.includes(normalizedTrigger)) {
        return {
            active: false,
            trigger: normalizedTrigger,
            term: '',
            search: text.trim(),
        }
    }

    const atIndex = text.lastIndexOf(normalizedTrigger)

    if (atIndex < 0) {
        return {
            active: false,
            trigger: normalizedTrigger,
            term: '',
            search: text.trim(),
        }
    }

    const afterTrigger = text.slice(atIndex + normalizedTrigger.length)
    const whitespaceBreak = /\s/.test(afterTrigger)

    if (whitespaceBreak && afterTrigger.trim() !== afterTrigger.replace(/\s+.*/, '').trim()) {
        return {
            active: false,
            trigger: normalizedTrigger,
            term: '',
            search: text.trim(),
        }
    }

    const term = afterTrigger.trim()

    return {
        active: true,
        trigger: normalizedTrigger,
        term,
        search: term,
    }
}

/**
 * @param {{
 *   enabledKey?: string,
 *   triggerKey?: string,
 *   queryKey?: string,
 *   sectionLabel?: string,
 * }} config
 */
export function createEntityMentionMixin({
    enabledKey = 'entityMentionsEnabled',
    triggerKey = 'mentionTrigger',
    queryKey = 'comboboxQuery',
    sectionLabel = 'Mentions',
} = {}) {
    return {
        entityMentionSectionLabel: sectionLabel,

        comboboxEntityMentionState() {
            if (! this[enabledKey]) {
                return parseEntityMentionQuery('', '')
            }

            return parseEntityMentionQuery(this[queryKey], this[triggerKey])
        },

        comboboxEntityMentionActive() {
            return this.comboboxEntityMentionState().active
        },

        comboboxEntityMentionSearchTerm() {
            const state = this.comboboxEntityMentionState()

            return state.active ? state.search : String(this[queryKey] ?? '').trim()
        },

        comboboxEntityMentionHighlightLabel(label) {
            const state = this.comboboxEntityMentionState()

            if (! state.active || state.term === '') {
                return label
            }

            const text = String(label ?? '')
            const lowerText = text.toLowerCase()
            const lowerTerm = state.term.toLowerCase()
            const index = lowerText.indexOf(lowerTerm)

            if (index === -1) {
                return text
            }

            const before = text.slice(0, index)
            const match = text.slice(index, index + state.term.length)
            const after = text.slice(index + state.term.length)

            return `${before}<mark class="fff-select-entity-mention__highlight">${match}</mark>${after}`
        },

        onHeadlessTriggerMentionKeydown(event) {
            if (! this[enabledKey] || this.disabled) {
                return
            }

            const trigger = String(this[triggerKey] ?? '@')
            const isAtKey = event.key === trigger
                || (trigger === '@' && event.key === '2' && event.shiftKey)

            if (! isAtKey) {
                return
            }

            event.preventDefault()
            this.comboboxOpenMenu()
            this.comboboxSetQuery(trigger)

            this.$nextTick(() => {
                this.focusHeadlessSearchInput()
            })
        },

        focusHeadlessSearchInput() {
            const sheet = typeof this.isSheetPresentation === 'function'
                ? this.isSheetPresentation()
                : false
            const input = (! this.inlineSearch || sheet)
                ? this.$refs.headlessSearchInput
                : this.$refs.headlessInlineSearchInput

            input?.focus({ preventScroll: true })
        },

        isEntityMentionValue(value) {
            return Boolean(this.labelEntry?.(value)?.entityMention)
        },

        entityMentionChipLabel(value) {
            const label = this.optionLabel(value)
            const entry = this.labelEntry?.(value)

            if (! entry?.entityMention) {
                return label
            }

            return `${this[triggerKey] ?? '@'}${label}`
        },
    }
}

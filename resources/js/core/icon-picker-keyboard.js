function resolveIconItems(component, itemsKey) {
    const value = component[itemsKey]

    if (typeof value === 'function') {
        return value.call(component)
    }

    return value ?? []
}

export function createIconPickerKeyboardMixin({
    openKey = 'panelOpen',
    itemsKey = 'visibleIconItems',
    resultsRef = 'iconResults',
    searchRef = 'iconSearch',
    selectMethod = 'selectIcon',
    columnsKey = 'gridColumns',
    layoutKey = 'layout',
} = {}) {
    return {
        activeIconIndex: -1,
        iconPickerPreviouslyFocused: null,

        initIconPickerKeyboard() {
            this.$watch(openKey, (open) => {
                if (open) {
                    this.syncActiveIconIndex()
                    this.iconPickerPreviouslyFocused = document.activeElement

                    this.$nextTick(() => {
                        this.$refs[searchRef]?.focus({ preventScroll: true })
                    })

                    return
                }

                this.activeIconIndex = -1

                if (typeof this.iconPickerPreviouslyFocused?.focus === 'function') {
                    this.iconPickerPreviouslyFocused.focus({ preventScroll: true })
                }

                this.iconPickerPreviouslyFocused = null
            })

            this.$watch(itemsKey, () => {
                if (this[openKey]) {
                    this.syncActiveIconIndex()
                }
            })
        },

        resolveIconColumns() {
            if (this[layoutKey] === 'list') {
                return 1
            }

            return Math.max(1, Number(this[columnsKey] ?? 6))
        },

        syncActiveIconIndex() {
            const items = resolveIconItems(this, itemsKey)

            if (! items.length) {
                this.activeIconIndex = -1

                return
            }

            if (this.state) {
                const selectedIndex = items.findIndex((item) => item.name === this.state)

                if (selectedIndex >= 0) {
                    this.activeIconIndex = selectedIndex

                    return
                }
            }

            this.activeIconIndex = 0
        },

        scrollActiveIconIntoView() {
            if (this.activeIconIndex < 0) {
                return
            }

            const element = this.$refs[resultsRef]

            if (! element) {
                return
            }

            const scrollToActiveOption = () => {
                const option = element.querySelector(`[data-icon-index="${this.activeIconIndex}"]`)

                if (! option) {
                    return
                }

                const containerRect = element.getBoundingClientRect()
                const optionRect = option.getBoundingClientRect()

                if (optionRect.top < containerRect.top) {
                    element.scrollTop -= containerRect.top - optionRect.top
                } else if (optionRect.bottom > containerRect.bottom) {
                    element.scrollTop += optionRect.bottom - containerRect.bottom
                }
            }

            if (! element.querySelector(`[data-icon-index="${this.activeIconIndex}"]`)) {
                if (typeof this.ensureIconIndexVisible === 'function') {
                    this.ensureIconIndexVisible(this.activeIconIndex)
                    this.$nextTick(() => scrollToActiveOption())

                    return
                }
            }

            scrollToActiveOption()
        },

        onIconResultsKeydown(event) {
            if (! this[openKey]) {
                return
            }

            const items = resolveIconItems(this, itemsKey)

            if (! items.length) {
                return
            }

            const columns = this.resolveIconColumns()
            const maxIndex = items.length - 1

            if (event.key === 'Escape') {
                event.preventDefault()
                this.closePanel()

                return
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault()
                const next = Math.min(this.activeIconIndex + columns, maxIndex)
                this.activeIconIndex = next < 0 ? 0 : next
                this.scrollActiveIconIntoView()

                return
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault()
                this.activeIconIndex = Math.max(this.activeIconIndex - columns, 0)
                this.scrollActiveIconIntoView()

                return
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault()
                this.activeIconIndex = Math.min(this.activeIconIndex + 1, maxIndex)
                this.scrollActiveIconIntoView()

                return
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault()
                this.activeIconIndex = Math.max(this.activeIconIndex - 1, 0)
                this.scrollActiveIconIntoView()

                return
            }

            if (event.key === 'Home') {
                event.preventDefault()
                this.activeIconIndex = 0
                this.scrollActiveIconIntoView()

                return
            }

            if (event.key === 'End') {
                event.preventDefault()
                this.activeIconIndex = maxIndex
                this.scrollActiveIconIntoView()

                return
            }

            if (event.key === 'Enter' && this.activeIconIndex >= 0) {
                event.preventDefault()
                const item = items[this.activeIconIndex]

                if (item?.name && typeof this[selectMethod] === 'function') {
                    this[selectMethod](item.name)
                }
            }
        },

        onIconSearchKeydown(event) {
            if (['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight', 'Enter', 'Home', 'End', 'Escape'].includes(event.key)) {
                this.onIconResultsKeydown(event)
            }
        },

        iconOptionClasses(index) {
            return {
                'is-selected': this.state === resolveIconItems(this, itemsKey)[index]?.name,
                'is-active': this.activeIconIndex === index,
            }
        },
    }
}

export function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return ''
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;")
}

export function highlightIconLabel(label, query) {
    const text = String(label ?? '')
    const term = String(query ?? '').trim()

    if (term === '') {
        return escapeHtml(text)
    }

    const lowerText = text.toLowerCase()
    const lowerTerm = term.toLowerCase()
    const matchIndex = lowerText.indexOf(lowerTerm)

    if (matchIndex === -1) {
        return escapeHtml(text)
    }

    const before = text.slice(0, matchIndex)
    const match = text.slice(matchIndex, matchIndex + term.length)
    const after = text.slice(matchIndex + term.length)

    return `${escapeHtml(before)}<mark class="fff-icon-picker__highlight">${escapeHtml(match)}</mark>${escapeHtml(after)}`
}

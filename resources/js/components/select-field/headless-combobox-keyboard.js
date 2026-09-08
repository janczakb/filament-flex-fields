export function createHeadlessComboboxKeyboardMixin() {
    return {
        comboboxMoveHighlight(delta) {
            this._engine?.moveHighlight(delta)
            this.virtualScrollTick = (this.virtualScrollTick ?? 0) + 1
            this._syncFromEngine()

            this.$nextTick(() => {
                const list = this.$refs.headlessOptionsList

                if (! list || ! this.shouldVirtualizeDropdown() || ! this._engine) {
                    return
                }

                const { meta } = this._engine.filteredOptions()
                const rowHeight = this.virtualRowHeight ?? 36
                const highlight = this.comboboxHighlightedIndex

                if (highlight < 0) {
                    return
                }

                const targetTop = highlight * rowHeight
                const targetBottom = targetTop + rowHeight

                if (targetTop < list.scrollTop) {
                    list.scrollTop = targetTop
                } else if (targetBottom > list.scrollTop + list.clientHeight) {
                    list.scrollTop = targetBottom - list.clientHeight
                }
            })
        },

        comboboxSelectHighlighted() {
            const selected = this._engine?.selectHighlighted() ?? false
            const previous = this.comboboxSelectedValues.slice()

            this._syncFromEngine()

            const highlightedKey = this.comboboxSelectedValues.find(
                (value) => ! previous.map(String).includes(String(value)),
            ) ?? this.comboboxSelectedValues.at(-1) ?? null

            if (selected && highlightedKey) {
                this.rememberSelectedOption(highlightedKey)
            }

            if (selected && highlightedKey && this.multiple) {
                this.queueOptionCheckEnter(highlightedKey)
            }

            if (selected && ! this.multiple) {
                this.comboboxCloseMenu({ immediate: true })

                if (highlightedKey) {
                    this.syncInlineSearchInputAfterSelection(highlightedKey)
                }
            }

            return selected
        },

        onHeadlessMenuKeydown(event) {
            if (event.key === 'Escape') {
                event.stopPropagation()
                this.comboboxCloseMenu()
            }
        },
    }
}

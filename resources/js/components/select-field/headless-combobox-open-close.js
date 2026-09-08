import { resolveOverlayMode } from '../../core/overlay-mode.js'
import { syncDropdownScrollbarInset } from './headless-combobox-scroll-virt.js'
import {
    scheduleInlineSearchCaretAtEnd,
    resolveInlineSearchInputAfterClose,
    resolveInlineSearchInputPlaceholder,
    resolveInlineSearchInputValue,
    resolveSelectSearchFocusTarget,
    shouldInlineSearchInputBeEditable,
    shouldShowSelectMenuSearch,
    stripHtmlToPlainText,
} from './headless-inline-search.js'

export function createHeadlessComboboxOpenCloseMixin() {
    return {
        comboboxOpenMenu() {
            if (this.disabled) {
                return
            }

            this.ensureInlineSearchInputValueBeforeOpen()

            this.syncEngineOptions()
            this._engine?.open()
            this._syncFromEngine()
            this.comboboxOpen = true

            // Optimistic glass reveal for desktop panels — do not wait for Livewire
            // option fetch / measure. Sheets must NOT get is-open here: playSheetEnter
            // owns the first slide-up (optimistic open paints at translateY(0) and
            // the first height measure then looks like a missing enter animation).
            const menu = typeof this.resolveMenuElement === 'function'
                ? this.resolveMenuElement()
                : this.$refs?.headlessMenu

            if (menu) {
                menu.classList.remove('is-closing')
                menu.hidden = false
                menu.setAttribute('aria-hidden', 'false')

                const sheetMode = resolveOverlayMode(window) === 'sheet'
                    || menu.classList?.contains?.('fff-teleported-menu--sheet')
                    || menu.classList?.contains?.('fff-overlay-sheet')

                if (sheetMode) {
                    // Apply sheet classes synchronously so CSS bottom-sheet rules
                    // win before the async anchor pass — avoids a panel-scale flash
                    // and keeps enter work off the Livewire critical path.
                    menu.classList.add('fff-teleported-menu--sheet')
                    menu.classList.remove(
                        'fff-teleported-menu--panel',
                        'fff-select-dropdown-panel--below',
                        'fff-select-dropdown-panel--above',
                        'fff-teleported-menu--above',
                        'fff-teleported-menu--below',
                    )

                    if (menu.dataset) {
                        menu.dataset.fffOverlayPresentation = 'sheet'
                    }
                } else {
                    menu.classList.add('is-open')
                }
            }

            if (typeof this.scheduleMenuPosition === 'function') {
                this.scheduleMenuPosition()
            }
        },

        ensureInlineSearchInputValueBeforeOpen() {
            if (! this.usesInlineSearchTriggerInput() || ! this.isTriggerLabelSelected()) {
                return
            }

            const label = this.plainOptionLabel(this.comboboxSelectedValues[0])

            if (String(this.comboboxQuery ?? '').trim() === '') {
                this.comboboxQuery = label
                this.comboboxSetQuery(label)

                return
            }

            if (this.comboboxQuery === label) {
                this.comboboxSetQuery(label)
            }
        },

        comboboxCloseMenu({ immediate = false } = {}) {
            if (! this.comboboxOpen) {
                return
            }

            this._engine?.close()
            this.comboboxHighlightedIndex = -1
            this.inlineSearchFocused = false

            if (immediate && typeof this.closeTeleportedMenuImmediate === 'function') {
                this.closeTeleportedMenuImmediate()

                return
            }

            if (typeof this.closeTeleportedMenu === 'function') {
                this.closeTeleportedMenu()

                return
            }

            this.comboboxOpen = false
        },

        syncInlineSearchInputAfterSelection(value) {
            if (! this.usesInlineSearchTriggerInput()) {
                return
            }

            this.comboboxQuery = this.plainOptionLabel(value)
            this._engine?.setQuery('')
        },

        syncInlineSearchInputAfterClose() {
            if (this.usesInlineSearchTriggerInput()) {
                this.comboboxQuery = resolveInlineSearchInputAfterClose(
                    this.isTriggerLabelSelected(),
                    this.isTriggerLabelSelected()
                        ? this.plainOptionLabel(this.comboboxSelectedValues[0])
                        : '',
                )
            } else {
                this.comboboxQuery = ''
            }

            this._engine?.setQuery('')
        },

        focusHeadlessSearchInput() {
            const target = resolveSelectSearchFocusTarget({
                inlineSearch: this.inlineSearch && this.searchable,
                sheetPresentation: this.isSheetPresentation(),
            })
            const input = target === 'inline'
                ? this.$refs.headlessInlineSearchInput
                : this.$refs.headlessSearchInput

            input?.focus({ preventScroll: true })

            // Pin caret only when opening onto an existing value (selected label).
            // Re-forcing on every focus fights mid-word edits and bidi typing.
            if (input && String(input.value ?? '').length > 0) {
                this.$nextTick(() => {
                    scheduleInlineSearchCaretAtEnd(input)
                })
            }
        },

        shouldShowMenuSearch() {
            return shouldShowSelectMenuSearch({
                searchable: this.searchable,
                inlineSearch: this.inlineSearch && ! this.multiple,
                sheetPresentation: this.isSheetPresentation(),
            })
        },

        onHeadlessTriggerClick(event) {
            if (this.disabled) {
                return
            }

            if (this.inlineSearch && this.searchable) {
                if (this.comboboxOpen) {
                    event?.preventDefault?.()
                    this.focusHeadlessSearchInput()

                    return
                }

                this.comboboxOpenMenu()
                this.$nextTick(() => this.focusHeadlessSearchInput())

                return
            }

            this.comboboxToggle()
        },

        usesInlineSearchTriggerInput() {
            return this.inlineSearch && this.searchable && ! this.multiple
        },

        plainOptionLabel(value) {
            if (this.isUserSelectField) {
                const entry = this.labelEntry?.(value) ?? this.optionRecord(value)

                if (entry?.userName) {
                    return String(entry.userName)
                }
            }

            return stripHtmlToPlainText(this.optionLabel(value))
        },

        inlineSearchInputReadonly() {
            if (! this.usesInlineSearchTriggerInput()) {
                return false
            }

            // Drawer search owns keyboard input — keep the trigger non-editable
            // so focus cannot land on the field buried under the sheet.
            if (this.isSheetPresentation()) {
                return true
            }

            return ! shouldInlineSearchInputBeEditable(this.comboboxOpen, this.inlineSearchFocused)
        },

        inlineSearchInputValue() {
            if (! this.usesInlineSearchTriggerInput()) {
                return this.comboboxQuery ?? ''
            }

            return resolveInlineSearchInputValue({
                comboboxQuery: this.comboboxQuery,
            })
        },

        inlineSearchInputPlaceholder() {
            if (! this.usesInlineSearchTriggerInput()) {
                return this.searchPrompt ?? ''
            }

            return resolveInlineSearchInputPlaceholder({
                comboboxQuery: this.comboboxQuery,
                searchPrompt: this.searchPrompt,
            })
        },

        onInlineSearchFocus() {
            if (! this.usesInlineSearchTriggerInput() || this.disabled) {
                return
            }

            this.inlineSearchFocused = true

            if (! this.comboboxOpen) {
                this.comboboxOpenMenu()
            }

            this.$nextTick(() => {
                if (this.isSheetPresentation()) {
                    this.focusHeadlessSearchInput()

                    return
                }

                const input = this.$refs.headlessInlineSearchInput

                if (input && String(input.value ?? '').length > 0) {
                    scheduleInlineSearchCaretAtEnd(input)
                }
            })
        },

        onInlineSearchBlur() {
            if (! this.usesInlineSearchTriggerInput()) {
                return
            }

            window.setTimeout(() => {
                const active = document.activeElement
                const menu = this.resolveMenuElement()
                const trigger = this.$refs.headlessTrigger

                if (menu?.contains(active) || trigger?.contains(active)) {
                    return
                }

                this.inlineSearchFocused = false
            }, 0)
        },

        onInlineSearchClearedIfEmpty(event) {
            if (! this.usesInlineSearchTriggerInput() || this.multiple) {
                return
            }

            const value = event?.target?.value ?? ''

            if (value === '' && this.isTriggerLabelSelected()) {
                this.clearSelection()
            }
        },

        onInlineSearchInput(event) {
            const value = event?.target?.value ?? ''
            const hadSelection = this.isTriggerLabelSelected()

            this.comboboxSetQuery(value)

            if (this.usesInlineSearchTriggerInput() && ! this.multiple && value === '' && hadSelection) {
                this.clearSelection()
            }
        },

        comboboxToggle() {
            if (this.disabled) {
                return
            }

            if (this.comboboxOpen) {
                this.comboboxCloseMenu()

                return
            }

            this.comboboxOpenMenu()
        },

        comboboxSetQuery(value) {
            this.comboboxQuery = value
            this.virtualRowWindowStart = 0
            this._virtualFlatRows = []
            this._virtualPrefixSums = []
            this.virtualScrollTick = (this.virtualScrollTick ?? 0) + 1

            this.applyComboboxQueryToEngine()

            // Alpine $watch('comboboxQuery') also runs these in the browser; keep
            // them here for programmatic callers / unit tests without a watcher.
            if (! this.comboboxOpen || this.__fffSheetClosing) {
                return
            }

            this.$nextTick(() => {
                syncDropdownScrollbarInset(this.resolveMenuElement())
                this.markKnownOptionChecksVisible()
                this.scheduleMenuPositionAfterLayout()
            })
        },

        comboboxClearSearch() {
            this.comboboxSetQuery('')

            this.$refs.headlessSearchInput?.focus?.()
        }
    }
}

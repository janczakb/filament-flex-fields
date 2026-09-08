import {
    findHeadlessOptionRecord,
    headlessOptionLabelHtml,
    headlessOptionValue,
} from './headless-select-options.js'
import {
    cancelAllOptionCheckAnimations,
    runAfterSelectedCheckExit,
    scheduleCheckEnter,
    setCheckVisibleInstant,
} from './headless-select-selection-ux.js'

export function createHeadlessComboboxSelectionMixin() {
    return {
        labelEntry(value) {
            return this._knownLabelEntries.get(String(value)) ?? null
        },

        storeLabelEntry(value, entry) {
            if (! entry) {
                return
            }

            this._knownLabelEntries.set(String(value), entry)
        },

        selectCreateOption(value) {
            if (this.multiple && this.hasReachedMaxItems()) {
                this.showMaxItemsMessage()

                return
            }

            if (! this._engine?.createInlineOption?.(value)) {
                return
            }

            this.hideMaxItemsMessage()
            this._syncFromEngine()
        },

        hasReachedMaxItems() {
            const limit = this.maxItems

            if (limit == null || limit === '' || Number(limit) <= 0) {
                return false
            }

            return this.comboboxSelectedValues.length >= Number(limit)
        },

        showMaxItemsMessage() {
            if (! this.maxItemsMessage) {
                return
            }

            this.maxItemsMessageVisible = true
        },

        hideMaxItemsMessage() {
            this.maxItemsMessageVisible = false
        },

        shouldShowMaxItemsMessage() {
            return Boolean(this.multiple && this.maxItemsMessageVisible && this.maxItemsMessage)
        },

        smartCreateRowLabel() {
            const query = String(this.comboboxQuery ?? '').trim()

            if (query === '') {
                return this.createOptionLabel
            }

            return `${this.createOptionLabel} "${query}"`
        },

        smartCreateRowHtml() {
            const query = String(this.comboboxQuery ?? '').trim()

            if (query === '') {
                return this.escapeHtml(String(this.createOptionLabel ?? ''))
            }

            const label = this.escapeHtml(String(this.createOptionLabel ?? ''))
            const term = this.escapeHtml(query)

            return `${label} <em class="fff-select-smart-create__term">"${term}"</em>`
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
        },

        reorderSelectedChips(event) {
            if (! this.isReorderable || ! this.multiple || this.disabled) {
                return
            }

            if (
                event
                && Number.isInteger(event.oldIndex)
                && Number.isInteger(event.newIndex)
                && event.oldIndex !== event.newIndex
            ) {
                const next = this.comboboxSelectedValues.slice()
                const [moved] = next.splice(event.oldIndex, 1)

                if (moved === undefined) {
                    return
                }

                next.splice(event.newIndex, 0, moved)
                this._engine?.setSelectedValues(next)
                this._syncFromEngine()

                return
            }

            const container = this.$refs.headlessBadgesCtn

            if (! container) {
                return
            }

            const nextValues = Array.from(container.querySelectorAll('[data-value]'))
                .map((element) => element.getAttribute('data-value'))
                .filter((value) => value !== null && value !== '')

            if (nextValues.length === 0) {
                return
            }

            this._engine?.setSelectedValues(nextValues)
            this._syncFromEngine()
        },
        rememberSelectedOption(value) {
            const record = this.optionRecord(value) ?? this.labelEntry(value)

            if (! record) {
                return
            }

            const entry = {
                ...record,
                entityMention: this.comboboxEntityMentionActive?.() ? true : (record.entityMention ?? false),
            }

            this.storeLabelEntry(value, entry)

            if (record.user) {
                this.storeUserInRepository(value, record.user)
            }
        },

        comboboxSelectValue(value) {
            const key = String(value)

            if (this.multiple && this.hasReachedMaxItems()) {
                this.showMaxItemsMessage()

                return
            }

            this.hideMaxItemsMessage()
            this.rememberSelectedOption(value)
            this._engine?.selectValue(value)
            this._syncFromEngine()

            if (! this.multiple) {
                this.comboboxCloseMenu({ immediate: true })
                this.syncInlineSearchInputAfterSelection(value)

                return
            }

            this.queueOptionCheckEnter(key)
        },

        comboboxDeselectValue(value) {
            const key = String(value)
            const shouldAnimateExit = this.multiple
                && ! this.isGridLayout
                && this.comboboxOpen

            const check = shouldAnimateExit ? this.findOptionCheckElement(key) : null
            const animateExit = Boolean(check && check.getAttribute('data-visible') === 'true')

            // Mark exiting BEFORE syncing selection so Alpine keeps the selected-row
            // chrome (and the check node) while chips update immediately.
            if (animateExit) {
                this.markOptionCheckExiting(key)
            }

            this.knownSelectedChecks.delete(key)
            this._engine?.deselectValue(key)
            this._syncFromEngine()

            if (this.comboboxOpen) {
                this.scheduleMenuPositionAfterLayout()
            }

            if (! animateExit) {
                setCheckVisibleInstant(check, false)

                return
            }

            this.checkExitCancel?.()
            this.checkExitCancel = runAfterSelectedCheckExit(check, () => {
                this.checkExitCancel = null
                this.clearOptionCheckExiting(key)
            })
        },

        finishDeselectValue(key) {
            this.clearOptionCheckExiting(key)
            this.knownSelectedChecks.delete(key)
            this._engine?.deselectValue(key)
            this._syncFromEngine()

            const check = this.findOptionCheckElement(key)
            setCheckVisibleInstant(check, false)

            if (this.comboboxOpen) {
                this.scheduleMenuPositionAfterLayout()
            }
        },

        optionRecord(value) {
            const normalized = String(value)

            const fromOptions = findHeadlessOptionRecord(this.options, normalized)
                ?? findHeadlessOptionRecord(this.flatOptions, normalized)

            if (fromOptions) {
                return fromOptions
            }

            const fromKnown = this._knownLabelEntries?.get(normalized)

            if (fromKnown) {
                return fromKnown
            }

            const fromRepository = this.labelRepository?.[normalized]

            return fromRepository ?? null
        },

        optionLabel(value) {
            if (this.isUserSelectField) {
                const userHtml = this.userOptionLabelHtml(value, 'trigger')

                if (userHtml !== String(value)) {
                    return userHtml
                }
            }

            const stored = this.labelEntry(value)

            if (stored) {
                return headlessOptionLabelHtml(stored, 'trigger')
            }

            const match = this.optionRecord(value)

            return match ? headlessOptionLabelHtml(match, 'trigger') : String(value)
        },

        optionDropdownLabel(option) {
            if (option?.fffClientRender && option?.user) {
                if (typeof this.renderUserOptionHtml === 'function') {
                    return this.renderUserOptionHtml(option.user, 'list')
                }

                return String(option.user?.name ?? option.label ?? option.user?.email ?? '')
            }

            return headlessOptionLabelHtml(option, 'dropdown')
        },

        headlessOptionValue(option) {
            return headlessOptionValue(option)
        },

        selectedChips() {
            return this.comboboxSelectedValues.map((value) => ({
                value,
                label: this.entityMentionChipLabel?.(value) ?? this.optionLabel(value),
                isEntityMention: this.isEntityMentionValue?.(value) ?? false,
            }))
        },

        triggerLabelHtml() {
            // Depend on epoch so Alpine re-runs `x-html` after user/label seeding.
            void this.triggerLabelEpoch

            if (this.isUserSelectField) {
                return this.userSelectTriggerHtml()
            }

            if (this.multiple) {
                return this.placeholder
            }

            if (this.comboboxSelectedValues.length === 0) {
                return this.placeholder
            }

            return this.optionLabel(this.comboboxSelectedValues[0])
        },

        isTriggerLabelSelected() {
            return this.comboboxSelectedValues.length > 0
        },

        isOptionSelected(value) {
            return this.comboboxSelectedValues.includes(String(value))
        },

        isOptionCheckExiting(value) {
            void this.checkExitTick

            return Boolean(this.checkExitKeys[String(value)])
        },

        /**
         * Dropdown may keep the selected-row chrome while the check strokes out.
         * Trigger chips always follow isOptionSelected() only.
         */
        isOptionSelectedInDropdown(value) {
            return this.isOptionSelected(value) || this.isOptionCheckExiting(value)
        },

        markOptionCheckExiting(value) {
            const key = String(value)

            if (this.checkExitKeys[key]) {
                return
            }

            this.checkExitKeys = { ...this.checkExitKeys, [key]: true }
            this.checkExitTick = (this.checkExitTick ?? 0) + 1
        },

        clearOptionCheckExiting(value) {
            const key = String(value)

            if (! this.checkExitKeys[key]) {
                return
            }

            const next = { ...this.checkExitKeys }
            delete next[key]
            this.checkExitKeys = next
            this.checkExitTick = (this.checkExitTick ?? 0) + 1
        },

        clearAllOptionCheckExiting() {
            if (Object.keys(this.checkExitKeys).length === 0) {
                return
            }

            this.checkExitKeys = {}
            this.checkExitTick = (this.checkExitTick ?? 0) + 1
        },

        /**
         * Teleported menus live outside the Alpine root; click.outside must not
         * treat option/search clicks as dismiss.
         */
        isEventInsideHeadlessMenu(event) {
            const target = event?.target

            if (! target || typeof target.closest !== 'function') {
                return false
            }

            const menu = typeof this.resolveMenuElement === 'function'
                ? this.resolveMenuElement()
                : this.$refs?.headlessMenu

            if (menu?.contains?.(target)) {
                return true
            }

            const owner = this.componentKey ?? this.statePath ?? null

            if (owner == null || String(owner) === '') {
                return Boolean(target.closest('.fff-select-headless-menu'))
            }

            const escaped = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
                ? CSS.escape(String(owner))
                : String(owner).replace(/\\/g, '\\\\').replace(/"/g, '\\"')

            return Boolean(target.closest(`.fff-select-headless-menu[data-fff-select-menu-owner="${escaped}"]`))
        },

        toggleOption(value) {
            if (this.disabled) {
                return
            }

            const record = this.optionRecord(value)

            if (record && this.isHeadlessOptionDisabled(record)) {
                return
            }

            if (this.isOptionSelected(value)) {
                if (this.multiple) {
                    this.hideMaxItemsMessage()
                    this.comboboxDeselectValue(value)

                    return
                }

                if (this.clearable) {
                    this.clearSelection()
                }

                // Always close — re-clicking the current value used to leave the
                // glass panel open and cover sibling cascade fields (Region).
                this.comboboxCloseMenu({ immediate: true })

                return
            }

            this.comboboxSelectValue(value)

            // Keep @-mention mode ready for the next pick (multi) or clear the
            // trigger character after a single select so search does not stick.
            if (this.comboboxEntityMentionActive?.()) {
                if (this.multiple) {
                    this.comboboxSetQuery(String(this.mentionTrigger ?? '@'))
                } else {
                    this.comboboxSetQuery('')
                }
            }
        },

        clearSelection() {
            if (this.disabled || ! this.clearable) {
                return
            }

            this._engine?.setSelectedValues([])
            this._syncFromEngine()
            this.syncClearablePresentation()

            if (this.usesInlineSearchTriggerInput() && ! this.comboboxOpen) {
                this.syncInlineSearchInputAfterClose()
            }
        },

        markKnownOptionChecksVisible() {
            if (this.isGridLayout) {
                return
            }

            this.$refs.headlessOptionsList?.querySelectorAll('.fi-select-input-option.fi-selected .fff-select-option-selected-check').forEach((check) => {
                setCheckVisibleInstant(check, true)
            })
        },

        freezeOptionChecksForMenuClose() {
            this.checkExitCancel?.()
            this.checkExitCancel = null
            this.clearAllOptionCheckExiting()

            if (this.isGridLayout) {
                return
            }

            cancelAllOptionCheckAnimations(this.$refs.headlessOptionsList)

            this.$refs.headlessOptionsList?.querySelectorAll('.fi-select-input-option').forEach((option) => {
                const value = String(option.getAttribute('data-value') ?? '')
                const check = option.querySelector('.fff-select-option-selected-check')

                if (! check) {
                    return
                }

                setCheckVisibleInstant(check, this.isOptionSelected(value))
            })
        },

        queueOptionCheckEnter(value) {
            if (! this.comboboxOpen) {
                return
            }

            const key = String(value)

            if (this.isGridLayout || this.knownSelectedChecks.has(key)) {
                const check = this.findOptionCheckElement(key)
                setCheckVisibleInstant(check, true)
                this.knownSelectedChecks.add(key)

                return
            }

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    const check = this.findOptionCheckElement(key)

                    if (! check) {
                        return
                    }

                    scheduleCheckEnter(check)
                    this.knownSelectedChecks.add(key)
                })
            })
        },

        findOptionCheckElement(value) {
            const escaped = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
                ? CSS.escape(value)
                : String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"')

            const button = this.$refs.headlessOptionsList?.querySelector(
                `.fi-select-input-option[data-value="${escaped}"]`,
            )

            return button?.querySelector('.fff-select-option-selected-check') ?? null
        }
    }
}

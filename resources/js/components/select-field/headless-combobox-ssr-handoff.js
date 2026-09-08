import { findHeadlessOptionRecord } from './headless-select-options.js'
import { resolveHeadlessBoundState } from './headless-select-state.js'

export function createHeadlessComboboxSsrHandoffMixin() {
    return {
        syncClearablePresentation() {
            if (this.multiple || ! this.clearable) {
                return
            }

            const hasValue = this.isTriggerLabelSelected()
            const wrapper = this.$el?.closest?.('.fff-select-field')
            const ctn = this.$refs.headlessTriggerCtn

            wrapper?.classList.toggle('fff-select-field--clearable-has-value', hasValue)
            ctn?.classList.toggle('fi-select-input-ctn-clearable', hasValue)
        },

        seedInitialTriggerLabels() {
            if (this.initialOptionLabel != null && this.initialOptionLabel !== '' && ! this.multiple) {
                const value = this.comboboxSelectedValues[0]

                if (value != null && value !== '') {
                    this.storeLabelEntry(value, {
                        value,
                        label: String(this.initialOptionLabel),
                        triggerLabel: String(this.initialOptionLabel),
                    })
                }
            }

            if (this.multiple && Array.isArray(this.initialOptionLabels)) {
                for (const entry of this.initialOptionLabels) {
                    if (entry?.value != null) {
                        this.storeLabelEntry(entry.value, entry)
                    }
                }
            }

            if (this.isUserSelectField && Array.isArray(this.initialSelectedUserEntries)) {
                for (const entry of this.initialSelectedUserEntries) {
                    if (entry?.value === undefined || entry?.value === null) {
                        continue
                    }

                    this.storeLabelEntry(entry.value, {
                        value: entry.value,
                        label: entry.user?.name ?? String(entry.value),
                        triggerLabel: entry.user?.name ?? String(entry.value),
                        user: entry.user,
                        fffClientRender: Boolean(entry.user),
                    })

                    if (entry.user && typeof this.storeUserInRepository === 'function') {
                        this.storeUserInRepository(entry.value, entry.user)
                    }
                }
            }
        },

        canShowHydratedTrigger() {
            if (! this._engine) {
                return false
            }

            if (! this.isTriggerLabelSelected()) {
                return true
            }

            // UserSelect paints via client `userSelectTriggerHtml()`. Do not replace SSR
            // until that HTML is a real selection — otherwise Alpine can snapshot the
            // placeholder and never refresh (Assignee FOUC on reload).
            if (this.isUserSelectField) {
                if (! this._optionalMixinsLoaded) {
                    return false
                }

                if (typeof this.userSelectTriggerHtml !== 'function') {
                    return false
                }

                const html = this.userSelectTriggerHtml()

                return html != null
                    && String(html) !== ''
                    && String(html) !== String(this.placeholder ?? '')
            }

            const value = String(this.comboboxSelectedValues[0] ?? '')

            if (this.labelEntry(value)) {
                return true
            }

            if (findHeadlessOptionRecord(this.options, value) || findHeadlessOptionRecord(this.flatOptions, value)) {
                return true
            }

            if (
                this.initialOptionLabel != null
                && this.initialOptionLabel !== ''
                && value === String(resolveHeadlessBoundState(this.state, this.initialState, this.multiple) ?? '')
            ) {
                return true
            }

            return false
        },

        bumpTriggerLabelEpoch() {
            this.triggerLabelEpoch = (Number(this.triggerLabelEpoch) || 0) + 1
        },

        markHeadlessDisplayReady() {
            if (this.displayReady || ! this._engine || ! this.canShowHydratedTrigger()) {
                return
            }

            this.displayReady = true
            this.ensureHeadlessSsrHandoff()
        },

        /**
         * Re-apply SSR → hydrated handoff after Livewire remorphs. Parent
         * dependsOn ->live() can drop `.is-replaced` while Alpine stays alive,
         * leaving the trigger under pointer-events: none for 1–2s.
         */
        ensureHeadlessSsrHandoff() {
            if (! this.displayReady) {
                return
            }

            const shell = this.$el?.closest?.('.fff-select-field__shell--headless')
                ?? this.$el?.closest?.('.fff-select-field__shell')

            if (! shell) {
                return
            }

            shell.dataset.fffSelectAttached = 'true'

            shell.querySelectorAll('.fff-select-trigger-ssr, .fff-select-item-card-ssr').forEach((element) => {
                element.classList.add('is-replaced')
            })
        },
    }
}

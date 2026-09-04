function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
}

export function createHeadlessUserSelectMixin({
    isUserSelectField = false,
    verifiedIconHtml = '',
    tagRemoveIconHtml = '',
    userSelectNoOptionsIconHtml = '',
    userSelectNoResultsIconHtml = '',
    userSelectEmptyStateHints = {},
} = {}) {
    return {
        isUserSelectField,
        verifiedIconHtml,
        tagRemoveIconHtml,
        userSelectNoOptionsIconHtml,
        userSelectNoResultsIconHtml,
        userSelectEmptyStateHints,
        userRepository: {},

        initUserSelectIntegration() {
            if (! this.isUserSelectField) {
                return
            }

            this.seedUserRepository()
            this.bindUserSelectTagRemoveHandlers()
            this.syncUserSelectTags()
            this.bumpTriggerLabelEpoch?.()

            this.$watch('comboboxSelectedValues', () => {
                this.syncUserSelectTags()
                this.bumpTriggerLabelEpoch?.()
            })
        },

        resolveUserSelectTagsContainer() {
            return this.$el?.closest?.('.fff-select-field-wrapper')
                ?.querySelector('[data-fff-user-select-tags]')
                ?? null
        },

        bindUserSelectTagRemoveHandlers() {
            const container = this.resolveUserSelectTagsContainer()

            if (! container || container.__fffHeadlessUserSelectTagsBound) {
                return
            }

            container.__fffHeadlessUserSelectTagsBound = true

            container.addEventListener('click', (event) => {
                const button = event.target.closest('.fff-user-select__selected-tag-remove')

                if (! button) {
                    return
                }

                const tag = button.closest('.fff-user-select__selected-tag[data-value]')
                const value = tag?.dataset.value

                if (value === undefined) {
                    return
                }

                event.preventDefault()
                event.stopPropagation()
                this.removeUserSelectTag(value)
            })
        },

        syncUserSelectTags() {
            if (! this.isUserSelectField || ! this.multiple) {
                return
            }

            const container = this.resolveUserSelectTagsContainer()

            if (! container) {
                return
            }

            const entries = this.selectedUserEntries()

            if (entries.length < 2) {
                container.hidden = true
                container.replaceChildren()

                return
            }

            container.hidden = false
            container.replaceChildren()

            for (const entry of entries) {
                const tag = document.createElement('span')
                tag.className = 'fff-user-select__selected-tag fff-tags-field__tag'
                tag.dataset.value = String(entry.value)

                const content = document.createElement('span')
                content.className = 'fff-user-select__selected-tag-content'
                content.innerHTML = this.renderUserOptionHtml(entry.user, 'tag')

                const removeButton = document.createElement('button')
                removeButton.type = 'button'
                removeButton.className = 'fff-tags-field__tag-remove fff-user-select__selected-tag-remove'
                removeButton.setAttribute('aria-label', `Remove ${entry.user?.name ?? entry.value}`)
                removeButton.tabIndex = -1
                removeButton.innerHTML = this.tagRemoveIconHtml

                if (this.disabled) {
                    removeButton.disabled = true
                }

                tag.append(content, removeButton)
                container.append(tag)
            }
        },

        seedUserRepository() {
            if (Array.isArray(this.initialSelectedUserEntries)) {
                for (const entry of this.initialSelectedUserEntries) {
                    this.storeUserInRepository(entry?.value, entry?.user)
                }
            }

            this.seedUsersFromOptions(this.options)
        },

        seedUsersFromOptions(options) {
            if (! Array.isArray(options)) {
                return
            }

            for (const option of options) {
                if (option?.options && Array.isArray(option.options)) {
                    this.seedUsersFromOptions(option.options)

                    continue
                }

                if (option?.user && option?.value !== undefined && option?.value !== null) {
                    this.storeUserInRepository(option.value, option.user)
                }
            }
        },

        storeUserInRepository(value, user) {
            if (value === undefined || value === null || ! user) {
                return
            }

            const key = String(value)

            this.userRepository[key] = user
        },

        resolveUserForValue(value) {
            const key = String(value)

            if (this.userRepository[key]) {
                return this.userRepository[key]
            }

            const entry = this.labelEntry?.(value) ?? this.optionRecord(value)

            if (entry?.user) {
                this.storeUserInRepository(value, entry.user)

                return entry.user
            }

            return null
        },

        renderUserAvatarHtml(user, layout = 'list') {
            if (! user) {
                return ''
            }

            const sizeClass = {
                trigger: 'fff-user-select__avatar--trigger',
                tag: 'fff-user-select__avatar--tag',
                list: 'fff-user-select__avatar--list',
            }[layout] ?? 'fff-user-select__avatar--list'

            const avatarUrl = user.avatarUrl ?? user.image ?? null
            const initials = user.initials ?? ''
            const verified = Boolean(user.verified)

            let html = `<span class="fff-user-select__avatar ${sizeClass}" aria-hidden="true">`
            html += '<span class="fff-user-select__avatar-surface">'

            if (avatarUrl) {
                html += `<img src="${escapeHtml(avatarUrl)}" alt="" class="fff-user-select__avatar-image" loading="lazy" />`
            } else {
                html += `<span class="fff-user-select__avatar-initials">${escapeHtml(initials)}</span>`
            }

            html += '</span>'

            if (verified && this.verifiedIconHtml) {
                html += `<span class="fff-user-select__verified-badge" title="Verified" aria-hidden="true">${this.verifiedIconHtml}</span>`
            }

            html += '</span>'

            return html
        },

        renderUserOptionHtml(user, layout = 'list') {
            if (! user) {
                return ''
            }

            const name = user.name ?? ''
            const email = user.email ?? user.description ?? null
            const layoutClass = {
                trigger: 'fff-user-select-option--trigger',
                tag: 'fff-user-select-option--tag',
                list: 'fff-user-select-option--list',
            }[layout] ?? 'fff-user-select-option--list'

            let html = `<span class="fff-user-select-option ${layoutClass}">`
            html += this.renderUserAvatarHtml(user, layout)

            if (layout === 'tag') {
                html += `<span class="fff-user-select-option__name">${escapeHtml(name)}</span>`
            } else {
                html += '<span class="fff-user-select-option__content">'
                html += `<span class="fff-user-select-option__name">${escapeHtml(name)}</span>`

                if (email && layout !== 'tag') {
                    html += `<span class="fff-user-select-option__email">${escapeHtml(email)}</span>`
                }

                html += '</span>'
            }

            html += '</span>'

            return html
        },

        userOptionLabelHtml(value, context = 'dropdown') {
            const user = this.resolveUserForValue(value)

            if (! user) {
                return String(value)
            }

            return this.renderUserOptionHtml(user, context === 'trigger' ? 'trigger' : 'list')
        },

        selectedUserEntries() {
            return this.comboboxSelectedValues
                .map((value) => ({
                    value,
                    user: this.resolveUserForValue(value),
                }))
                .filter((entry) => entry.user)
        },

        shouldShowUserSelectTags() {
            return this.isUserSelectField
                && this.multiple
                && this.selectedUserEntries().length >= 2
        },

        userSelectTriggerHtml() {
            void this.triggerLabelEpoch

            const entries = this.selectedUserEntries()

            if (entries.length === 0) {
                if (! Array.isArray(this.comboboxSelectedValues) || this.comboboxSelectedValues.length === 0) {
                    return this.placeholder
                }

                // Selection exists but the user repo has not resolved yet — prefer
                // stored option/label HTML over flashing the empty placeholder.
                if (this.comboboxSelectedValues.length === 1) {
                    const value = this.comboboxSelectedValues[0]
                    const option = this.optionRecord?.(value) ?? this.labelEntry?.(value)

                    if (option?.user) {
                        this.storeUserInRepository(value, option.user)

                        return this.renderUserOptionHtml(option.user, 'trigger')
                    }

                    if (option?.label) {
                        return option.label
                    }
                }

                return this.placeholder
            }

            if (entries.length === 1) {
                return this.renderUserOptionHtml(entries[0].user, 'trigger')
            }

            const names = entries
                .map((entry) => escapeHtml(entry.user?.name ?? entry.value))
                .join(', ')

            return `<span class="fff-user-select__trigger-names">${names}</span>`
        },

        removeUserSelectTag(value) {
            if (this.disabled) {
                return
            }

            this.comboboxDeselectValue(value)
        },

        headlessUserSelectDropdownState() {
            if (! this.isUserSelectField) {
                return null
            }

            if (this.optionsLoading) {
                return 'loading'
            }

            if (this.searchPending) {
                return 'searching'
            }

            const query = String(this.comboboxQuery ?? '').trim()
            const total = this.comboboxFilteredOptions().meta.total

            if (this.hasDynamicSearchResults && query.length < this.minSearchLength) {
                return 'prompt'
            }

            if (total > 0) {
                return null
            }

            if (this.hasDynamicSearchResults && query.length >= this.minSearchLength) {
                return 'search'
            }

            return 'options'
        },

        shouldShowHeadlessUserSelectSkeleton() {
            const state = this.headlessUserSelectDropdownState()

            return state === 'loading' || state === 'searching'
        },

        headlessUserSelectSkeletonAriaLabel() {
            const state = this.headlessUserSelectDropdownState()

            if (state === 'searching') {
                return this.searchingMessage ?? 'Searching users'
            }

            return this.loadingMessage ?? 'Loading users'
        },

        headlessUserSelectSkeletonRows() {
            return [0, 1, 2]
        },

        shouldShowHeadlessUserSelectEmptyState() {
            const state = this.headlessUserSelectDropdownState()

            return state === 'prompt' || state === 'search' || state === 'options'
        },

        headlessUserSelectEmptyIconHtml() {
            const state = this.headlessUserSelectDropdownState()

            if (state === 'search') {
                return this.userSelectNoResultsIconHtml
            }

            return this.userSelectNoOptionsIconHtml
        },

        headlessUserSelectEmptyTitle() {
            const state = this.headlessUserSelectDropdownState()

            if (state === 'prompt') {
                return this.searchPrompt
            }

            if (state === 'search') {
                return this.noSearchResultsMessage
            }

            return this.noOptionsMessage
        },

        headlessUserSelectEmptyHint() {
            const hints = this.userSelectEmptyStateHints ?? {}
            const state = this.headlessUserSelectDropdownState()

            if (state === 'search') {
                return hints.tryDifferentSearch ?? ''
            }

            if (this.searchable && Number(this.minSearchLength ?? 0) > 0) {
                return hints.minSearchLength ?? ''
            }

            return hints.noUsersAvailable ?? ''
        },
    }
}

import { createExclusiveDropdownMixin } from '../core/flex-dropdown-coordinator.js'
import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'
import {
    availablePlatforms,
    collectSocialLinkRowErrors,
    dehydrateSocialLinksState,
    firstSocialLinkValidationError,
    formatSocialLinkUrl,
    hasSocialLinkValidationErrors,
    normalizeSocialLinksState,
} from '../support/social-link-validation.js'

const exclusiveDropdown = createExclusiveDropdownMixin({
    openKey: 'platformMenuOpen',
    closeMethod: 'closePlatformMenu',
    ownerIdPrefix: 'fff-social-links',
})

const platformMenu = createSearchableSelectMenuMixin({
    openKey: 'platformMenuOpen',
    readyKey: 'platformMenuReady',
    triggerRef: 'addShell',
    menuRef: 'platformMenu',
    closeMethod: 'closePlatformMenu',
    ownerIdPrefix: 'fff-social-links-platform',
    matchTriggerWidth: true,
    minMenuWidth: 240,
})

export default function socialLinksFieldFormComponent({
    state,
    statePath = 'social_links',
    initialLinks = [],
    initialSelectedPlatform = null,
    readOnly = false,
    initialShowValidationErrors = false,
    platforms = [],
    brandIcons = {},
    maxLinks = null,
    reorderable = false,
    autoFormatUrls = true,
    labels = {},
}) {
    return {
        ...exclusiveDropdown,
        ...platformMenu,
        state,
        statePath,
        readOnly,
        platforms,
        brandIcons,
        maxLinks,
        reorderable,
        autoFormatUrls,
        labels,
        links: normalizeSocialLinksState(initialLinks),
        rowErrors: {},
        showValidationErrors: initialShowValidationErrors,
        selectedPlatform: initialSelectedPlatform,
        platformMenuOpen: false,
        platformMenuReady: false,
        platformMenuHighlightIndex: -1,
        _submitGuardBound: false,

        init() {
            this.wireExclusiveFlexDropdown()
            this.bindSelectMenuLifecycle({ wireExclusive: false })
            this.links = normalizeSocialLinksState(this.state?.length ? this.state : initialLinks)

            if (! this.selectedPlatform && this.availablePlatforms.length > 0) {
                this.selectedPlatform = this.availablePlatforms[0].value
            }

            this.syncState()
            this.bindFormSubmitGuard()

            this.$watch('links', () => {
                this.syncState()
            })

            this.$watch('platformMenuOpen', (isOpen) => {
                if (isOpen) {
                    this.resetPlatformMenuHighlight()
                }
            })

            this.markHydrated()
        },

        markHydrated() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        this.$root.classList.add('is-hydrated')
                    })
                })
            })
        },

        get canAddMore() {
            if (this.readOnly) {
                return false
            }

            if (this.maxLinks !== null && this.links.length >= this.maxLinks) {
                return false
            }

            return this.availablePlatforms.length > 0
        },

        get availablePlatforms() {
            return availablePlatforms(this.platforms, this.links, this.maxLinks)
        },

        get selectedPlatformLabel() {
            if (! this.selectedPlatform) {
                return null
            }

            return this.platformLabel(this.selectedPlatform)
        },

        bindFormSubmitGuard() {
            const form = this.$el.closest('form')

            if (! form || this._submitGuardBound) {
                return
            }

            this._submitGuardBound = true

            form.addEventListener('submit', (event) => {
                this.showValidationErrors = true
                this.validateAllRows()

                if (! this.hasClientValidationErrors()) {
                    return
                }

                event.preventDefault()
                event.stopImmediatePropagation()
                this.syncClientValidationErrorsToLivewire()
            }, true)
        },

        hasClientValidationErrors() {
            return hasSocialLinkValidationErrors(this.rowErrors)
        },

        firstClientValidationErrorMessage() {
            return firstSocialLinkValidationError(this.rowErrors)
        },

        syncClientValidationErrorsToLivewire() {
            const message = this.firstClientValidationErrorMessage()

            if (! message || ! this.$wire || ! this.statePath) {
                return
            }

            this.$wire.addError(this.statePath, message)
        },

        syncState() {
            this.state = this.links.map((link) => ({
                platform: link.platform,
                url: String(link.url ?? '').trim(),
            }))

            if (this.showValidationErrors) {
                this.validateAllRows()
            }
        },

        validateAllRows() {
            this.rowErrors = collectSocialLinkRowErrors(
                this.links,
                this.platforms,
                (code, platform) => this.validationMessage(code, platform),
            )
        },

        validationMessage(code, platform) {
            const platformLabel = this.platformLabel(platform)

            return ({
                required: this.labels.required ?? 'URL is required.',
                unknown_platform: this.labels.unknownPlatform ?? 'Unknown platform.',
                platform_not_allowed: this.labels.platformNotAllowed ?? 'This platform is not allowed.',
                invalid_url: this.labels.invalidUrl ?? 'Enter a valid URL.',
                platform_mismatch: (this.labels.platformMismatch ?? 'URL must match :platform.').replace(':platform', platformLabel),
            })[code] ?? code
        },

        rowError(index) {
            if (! this.showValidationErrors) {
                return null
            }

            return this.rowErrors[index] ?? null
        },

        rowHasError(index) {
            return this.showValidationErrors && Boolean(this.rowErrors[index])
        },

        platformLabel(platform) {
            return this.platforms.find((entry) => entry.value === platform)?.label ?? platform
        },

        platformPlaceholder(platform) {
            return this.platforms.find((entry) => entry.value === platform)?.placeholder ?? 'https://'
        },

        platformIconMarkup(platform) {
            return this.brandIcons[platform] ?? this.brandIcons.website ?? ''
        },

        platformOptionId(platform) {
            return `${this.statePath}__platform-option-${platform}`
        },

        resetPlatformMenuHighlight() {
            const index = this.availablePlatforms.findIndex((platform) => platform.value === this.selectedPlatform)

            this.platformMenuHighlightIndex = index >= 0 ? index : (this.availablePlatforms.length > 0 ? 0 : -1)
            this.scrollHighlightedPlatformIntoView()
        },

        highlightedPlatformValue() {
            if (this.platformMenuHighlightIndex < 0) {
                return null
            }

            return this.availablePlatforms[this.platformMenuHighlightIndex]?.value ?? null
        },

        platformMenuActiveDescendant() {
            const value = this.highlightedPlatformValue()

            return value ? this.platformOptionId(value) : null
        },

        isPlatformHighlighted(platform) {
            return this.highlightedPlatformValue() === platform
        },

        scrollHighlightedPlatformIntoView() {
            this.$nextTick(() => {
                const value = this.highlightedPlatformValue()

                if (! value) {
                    return
                }

                const option = this.$refs.platformMenu?.querySelector?.(`#${this.platformOptionId(value)}`)

                option?.scrollIntoView?.({ block: 'nearest' })
            })
        },

        movePlatformMenuHighlight(delta) {
            if (this.availablePlatforms.length === 0) {
                this.platformMenuHighlightIndex = -1

                return
            }

            const nextIndex = this.platformMenuHighlightIndex < 0
                ? (delta > 0 ? 0 : this.availablePlatforms.length - 1)
                : this.platformMenuHighlightIndex + delta

            this.platformMenuHighlightIndex = Math.max(0, Math.min(this.availablePlatforms.length - 1, nextIndex))
            this.selectedPlatform = this.availablePlatforms[this.platformMenuHighlightIndex].value
            this.scrollHighlightedPlatformIntoView()
        },

        onPlatformMenuKeydown(event) {
            if (! this.platformMenuOpen || this.readOnly) {
                return
            }

            switch (event.key) {
            case 'ArrowDown':
                event.preventDefault()
                this.movePlatformMenuHighlight(1)
                break
            case 'ArrowUp':
                event.preventDefault()
                this.movePlatformMenuHighlight(-1)
                break
            case 'Home':
                event.preventDefault()
                this.platformMenuHighlightIndex = 0
                this.selectedPlatform = this.availablePlatforms[0]?.value ?? null
                this.scrollHighlightedPlatformIntoView()
                break
            case 'End':
                event.preventDefault()
                this.platformMenuHighlightIndex = this.availablePlatforms.length - 1
                this.selectedPlatform = this.availablePlatforms[this.platformMenuHighlightIndex]?.value ?? null
                this.scrollHighlightedPlatformIntoView()
                break
            case 'Enter':
                event.preventDefault()

                if (this.selectedPlatform) {
                    this.selectPlatform(this.selectedPlatform)
                }
                break
            case 'Escape':
                event.preventDefault()
                this.closePlatformMenu()
                break
            default:
                break
            }
        },

        togglePlatformMenu() {
            if (this.readOnly || ! this.canAddMore) {
                return
            }

            this.platformMenuOpen = ! this.platformMenuOpen

            if (! this.platformMenuOpen) {
                return
            }

            if (! this.selectedPlatform && this.availablePlatforms.length > 0) {
                this.selectedPlatform = this.availablePlatforms[0].value
            }

            this.resetPlatformMenuHighlight()
        },

        closePlatformMenu() {
            this.closeTeleportedMenu()
        },

        selectPlatform(platform) {
            this.selectedPlatform = platform
            this.closePlatformMenu()
        },

        onAddTriggerKeydown(event) {
            if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                event.preventDefault()
                this.platformMenuOpen = true
                this.resetPlatformMenuHighlight()
            }

            if (event.key === 'Escape') {
                this.closePlatformMenu()
            }
        },

        confirmAddPlatform() {
            if (this.readOnly || ! this.selectedPlatform || ! this.canAddMore) {
                return
            }

            if (this.links.some((link) => link.platform === this.selectedPlatform)) {
                return
            }

            this.links.push({
                platform: this.selectedPlatform,
                url: '',
            })

            const addedPlatform = this.selectedPlatform
            this.selectedPlatform = this.availablePlatforms[0]?.value ?? null
            this.closePlatformMenu()

            this.$nextTick(() => {
                const input = this.$root.querySelector(`#${this.statePath}-${addedPlatform}-url`)

                input?.focus?.()
            })
        },

        formatUrlOnBlur(index) {
            if (this.readOnly || ! this.autoFormatUrls) {
                return
            }

            const link = this.links[index]

            if (! link) {
                return
            }

            link.url = formatSocialLinkUrl(link.url)

            if (this.showValidationErrors) {
                this.validateAllRows()
            }
        },

        canMoveLinkUp(index) {
            return this.reorderable && ! this.readOnly && index > 0
        },

        canMoveLinkDown(index) {
            return this.reorderable && ! this.readOnly && index < this.links.length - 1
        },

        moveLinkUp(index) {
            if (! this.canMoveLinkUp(index)) {
                return
            }

            const link = this.links[index]
            this.links.splice(index, 1)
            this.links.splice(index - 1, 0, link)
            this.reindexRowErrorsAfterMove(index, index - 1)
        },

        moveLinkDown(index) {
            if (! this.canMoveLinkDown(index)) {
                return
            }

            const link = this.links[index]
            this.links.splice(index, 1)
            this.links.splice(index + 1, 0, link)
            this.reindexRowErrorsAfterMove(index, index + 1)
        },

        reindexRowErrorsAfterMove(fromIndex, toIndex) {
            if (! this.showValidationErrors) {
                return
            }

            this.validateAllRows()
        },

        removeLink(index) {
            if (this.readOnly) {
                return
            }

            delete this.rowErrors[index]

            this.links.splice(index, 1)

            if (this.showValidationErrors) {
                this.validateAllRows()
            }
        },
    }
}

export {
    collectSocialLinkRowErrors,
    dehydrateSocialLinksState,
    firstSocialLinkValidationError,
    formatSocialLinkUrl,
    hasSocialLinkValidationErrors,
    normalizeSocialLinksState,
}

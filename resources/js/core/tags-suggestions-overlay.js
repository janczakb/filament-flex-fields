import { createSearchableSelectMenuMixin } from './searchable-select-menu.js'
import { createOverlayMenuKeyboardMixin } from './overlay-menu-keyboard.js'

const teleportedSuggestions = createSearchableSelectMenuMixin({
    openKey: 'suggestionsMenuOpen',
    readyKey: 'suggestionsMenuReady',
    triggerRef: 'tagsInputMount',
    menuRef: 'suggestionsMenu',
    closeMethod: 'closeSuggestionsMenu',
    ownerIdPrefix: 'fff-tags-field',
    matchTriggerWidth: true,
})

const suggestionKeyboard = createOverlayMenuKeyboardMixin({
    openKey: 'suggestionsMenuOpen',
    resultsKey: 'filteredSuggestionItems',
    menuRef: 'suggestionsMenu',
    searchRef: 'tagsInput',
    selectMethod: 'selectSuggestion',
    optionIdPrefix: 'fff-tags-suggestion',
    activeIndexKey: 'suggestionActiveIndex',
    getItemValue: (item) => item,
    onEscape: 'dismissSuggestions',
})

/**
 * M2 teleported suggestions menu for TagsField (sheet + overlay runtime on mobile).
 */
export function createTagsSuggestionsOverlayMixin() {
    return {
        ...teleportedSuggestions,
        ...suggestionKeyboard,

        suggestionsMenuOpen: false,
        suggestionsMenuReady: false,
        suggestionsSuppressed: false,
        suggestionActiveIndex: 0,

        resolveMenuTriggerElement() {
            return this.$refs.tagsInputMount ?? this.$refs.tagsInputShell ?? null
        },

        syncTagsSuggestionsMenuWidth() {
            const menu = this.$refs.suggestionsMenu
            const trigger = this.resolveMenuTriggerElement()

            if (! menu || ! trigger) {
                return
            }

            const width = Math.max(Math.round(trigger.getBoundingClientRect().width), 0)

            if (width === 0) {
                return
            }

            menu.style.width = `${width}px`
            menu.style.minWidth = `${width}px`
            menu.style.maxWidth = `${Math.min(width, window.innerWidth - 32)}px`
        },

        initTagsSuggestionsOverlay() {
            this.bindSelectMenuLifecycle()

            const baseUpdateMenuPosition = this.updateMenuPosition.bind(this)

            this.updateMenuPosition = (options = {}) => {
                this.syncTagsSuggestionsMenuWidth()
                baseUpdateMenuPosition(options)
            }

            this.$watch(
                () => this.shouldShowSuggestions() && ! this.suggestionsSuppressed,
                (open) => {
                    if (open) {
                        this.openSuggestionsMenu()

                        return
                    }

                    this.closeSuggestionsMenu()
                },
            )
        },

        filteredSuggestionItems() {
            return this.filteredSuggestions().slice(0, 8)
        },

        openSuggestionsMenu() {
            if (this.suggestionsMenuOpen) {
                return
            }

            this.suggestionsMenuOpen = true
            this.syncOverlayMenuActiveIndex()
        },

        closeSuggestionsMenu() {
            this.closeTeleportedMenu?.()
            this.suggestionsMenuOpen = false
            this.suggestionsMenuReady = false
        },

        dismissSuggestions() {
            this.suggestionsSuppressed = true
            this.closeSuggestionsMenu()
        },

        onSuggestionsClickOutside(event) {
            if (this.$refs.suggestionsMenu?.contains(event.target)) {
                return
            }

            this.dismissSuggestions()
        },
    }
}

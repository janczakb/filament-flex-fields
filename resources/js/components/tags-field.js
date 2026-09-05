import { normalizeSearchQuery } from '../core/search-normalize.js'
import { createTagsSuggestionsOverlayMixin } from '../core/tags-suggestions-overlay.js'

export default function tagsFieldFormComponent({
    state,
    splitKeys,
    maxTags,
    suggestions,
    suggestionsOnly,
    duplicateInsensitive,
    tagPrefix,
    tagSuffix,
    disabled,
    searchSuggestions = false,
    minSearchLength = 2,
    componentKey = null,
    suggestionLabels = {},
}) {
    const overlayMixin = createTagsSuggestionsOverlayMixin()

    return {
        ...overlayMixin,

        newTag: '',
        state,
        splitKeys,
        maxTags,
        suggestions,
        suggestionsOnly,
        duplicateInsensitive,
        tagPrefix: tagPrefix ?? '',
        tagSuffix: tagSuffix ?? '',
        disabled,
        searchSuggestions,
        minSearchLength,
        componentKey,
        suggestionLabels,
        searchResults: [],
        searchPending: false,
        searchDebounceTimer: null,
        suggestionsEngaged: false,

        init() {
            this.$refs.fieldShell?.classList.add('is-hydrated')
            this.$el.classList.add('is-hydrated')
            this.initTagsSuggestionsOverlay()

            this.$watch('newTag', (value) => {
                this.suggestionsSuppressed = false

                if (this.searchSuggestions) {
                    this.scheduleSuggestionSearch(value)
                }
            })
        },

        scheduleSuggestionSearch(value) {
            if (! this.searchSuggestions || ! this.componentKey || ! this.$wire?.callSchemaComponentMethod) {
                return;
            }

            clearTimeout(this.searchDebounceTimer);

            const query = String(value ?? '').trim();

            if (query === '' || query.length < this.minSearchLength) {
                this.searchResults = [];
                this.searchPending = false;

                return;
            }

            this.searchDebounceTimer = setTimeout(() => {
                this.fetchSuggestionSearch(query);
            }, 300);
        },

        async fetchSuggestionSearch(query) {
            if (! this.searchSuggestions || ! this.componentKey || ! this.$wire?.callSchemaComponentMethod) {
                return;
            }

            this.searchPending = true;

            try {
                const results = await this.$wire.callSchemaComponentMethod(
                    this.componentKey,
                    'getTagSearchResults',
                    { search: query },
                );

                this.searchResults = Array.isArray(results) ? results : [];
            } catch {
                this.searchResults = [];
            } finally {
                this.searchPending = false;
            }
        },

        normalizedTag(value) {
            return this.duplicateInsensitive
                ? String(value).toLowerCase()
                : String(value);
        },

        hasTag(tag) {
            const normalized = this.normalizedTag(tag);

            return (this.state ?? []).some(
                (existing) => this.normalizedTag(existing) === normalized,
            );
        },

        canAddMore() {
            return this.maxTags === null || (this.state?.length ?? 0) < this.maxTags;
        },

        displayLabel(tag) {
            return `${this.tagPrefix}${tag}${this.tagSuffix}`;
        },

        createTag() {
            if (this.disabled) {
                return;
            }

            const tag = this.newTag.trim();

            if (tag === '') {
                return;
            }

            if (! this.canAddMore()) {
                this.newTag = '';

                return;
            }

            if (
                this.suggestionsOnly
                && ! this.availableSuggestions().includes(tag)
            ) {
                this.newTag = '';

                return;
            }

            if (this.hasTag(tag)) {
                this.newTag = '';

                return;
            }

            if (! Array.isArray(this.state)) {
                this.state = [];
            }

            this.state.push(tag);
            this.newTag = '';
            this.searchResults = [];
            this.closeSuggestionsMenu();

            // Static suggestions stay "should show" while focused, so the open
            // watch does not re-fire after close — reopen for the next pick.
            this.$nextTick?.(() => {
                if (this.shouldShowSuggestions() && ! this.suggestionsSuppressed) {
                    this.openSuggestionsMenu()
                }
            })
        },

        deleteTag(tagToDelete) {
            if (this.disabled) {
                return;
            }

            this.state = (this.state ?? []).filter((tag) => tag !== tagToDelete);
        },

        selectSuggestion(suggestion) {
            if (this.disabled) {
                return;
            }

            this.newTag = suggestion;
            this.createTag();
        },

        reorderTags(event) {
            const reordered = this.state.splice(event.oldIndex, 1)[0];
            this.state.splice(event.newIndex, 0, reordered);
            this.state = [...this.state];
        },

        availableSuggestions() {
            return this.searchSuggestions ? this.searchResults : this.suggestions;
        },

        filteredSuggestions() {
            const query = normalizeSearchQuery(this.newTag);
            const source = this.availableSuggestions();

            return source.filter((suggestion) => {
                if (this.hasTag(suggestion)) {
                    return false;
                }

                if (query === '' || this.searchSuggestions) {
                    return true;
                }

                return normalizeSearchQuery(suggestion).includes(query);
            });
        },

        shouldShowSuggestions() {
            if (this.suggestionsSuppressed) {
                return false
            }

            if (this.searchSuggestions) {
                const query = this.newTag.trim()

                // Keep the teleported panel open for min-chars, loading, results, and empty states.
                return query !== ''
            }

            if (! this.suggestionsEngaged) {
                return false
            }

            return this.suggestions.length > 0
        },

        onTagsInputFocus() {
            this.suggestionsEngaged = true
            this.suggestionsSuppressed = false

            if (this.shouldShowSuggestions()) {
                this.openSuggestionsMenu()
            }
        },

        onTagsInputBlur() {
            window.setTimeout(() => {
                if (this.$refs.suggestionsMenu?.contains(document.activeElement)) {
                    return
                }

                this.suggestionsEngaged = false

                if (this.suggestionsMenuOpen) {
                    this.dismissSuggestions()
                }
            }, 150)
        },

        tagCountLabel() {
            const count = this.state?.length ?? 0;

            if (this.maxTags !== null) {
                return `${count}/${this.maxTags}`;
            }

            return String(count);
        },

        input: {
            ['x-on:focus']() {
                this.onTagsInputFocus()
            },
            ['x-on:blur']() {
                this.onTagsInputBlur()
                this.createTag()
            },
            ['x-model']: 'newTag',
            ['x-on:keydown'](event) {
                if (['ArrowDown', 'ArrowUp', 'Enter', 'Home', 'End', 'Escape'].includes(event.key)) {
                    this.onOverlayMenuSearchKeydown(event)

                    if (event.defaultPrevented) {
                        return
                    }
                }

                if (['Enter', ...this.splitKeys].includes(event.key)) {
                    event.preventDefault();
                    event.stopPropagation();

                    this.createTag();
                }
            },
            ['x-on:paste']() {
                this.$nextTick(() => {
                    if (this.splitKeys.length === 0) {
                        this.createTag();

                        return;
                    }

                    const pattern = this.splitKeys
                        .map((key) =>
                            key.replace(/[/\-\\^$*+?.()|[\]{}]/g, '\\$&'),
                        )
                        .join('|');

                    this.newTag
                        .split(new RegExp(pattern, 'g'))
                        .forEach((tag) => {
                            this.newTag = tag;

                            this.createTag();
                        });
                });
            },
        },
    };
}

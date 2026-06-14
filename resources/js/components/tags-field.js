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
}) {
    return {
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
        searchResults: [],
        searchPending: false,
        searchDebounceTimer: null,

        init() {
            if (! this.searchSuggestions) {
                return;
            }

            this.$watch('newTag', (value) => {
                this.scheduleSuggestionSearch(value);
            });
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
            const query = this.newTag.trim().toLowerCase();
            const source = this.availableSuggestions();

            return source.filter((suggestion) => {
                if (this.hasTag(suggestion)) {
                    return false;
                }

                if (query === '' || this.searchSuggestions) {
                    return true;
                }

                return suggestion.toLowerCase().includes(query);
            });
        },

        shouldShowSuggestions() {
            if (this.searchSuggestions) {
                const query = this.newTag.trim();

                return query.length >= this.minSearchLength
                    && (this.filteredSuggestions().length > 0 || this.searchPending);
            }

            return this.suggestions.length > 0;
        },

        tagCountLabel() {
            const count = this.state?.length ?? 0;

            if (this.maxTags !== null) {
                return `${count}/${this.maxTags}`;
            }

            return String(count);
        },

        input: {
            ['x-on:blur']: 'createTag()',
            ['x-model']: 'newTag',
            ['x-on:keydown'](event) {
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

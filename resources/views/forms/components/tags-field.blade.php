@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Facades\FilamentAsset;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReorderable = (! $isDisabled) && $isReorderable();
    $wrapperClasses = $getWrapperClasses();
    $hasError = filled($statePath) && $errors->has($statePath);
    $id = $getId();
    $placeholder = $getPlaceholder();
    $suggestions = $getSuggestionsForJs();
    $searchSuggestions = $shouldSearchSuggestions();
    $componentKey = $getKey();
    $livewireKey = $getLivewireKey();
    $initialTags = $field->getInitialTagsForSsr();
    $tagsAssetSrc = FilamentAsset::getAlpineComponentSrc('tags-field', FilamentFlexFieldsPlugin::PACKAGE_NAME);
    $noResultsIconHtml = \Filament\Support\generate_icon_html(GravityIcon::Magnifier, size: IconSize::Large)?->toHtml() ?? '';
    $minCharsIconHtml = \Filament\Support\generate_icon_html(GravityIcon::Magnifier, size: IconSize::Large)?->toHtml() ?? '';
    $suggestionLabels = [
        'searchMinChars' => __('filament-flex-fields::default.tags.suggestions.min_chars', [
            'min' => $field->getMinSearchLength(),
        ]),
        'searchLoading' => __('filament-flex-fields::default.tags.suggestions.loading'),
        'searchNoResults' => __('filament-flex-fields::default.tags.suggestions.no_results'),
    ];
    $ssrPartialData = [
        'field' => $field,
        'initialTags' => $initialTags,
        'isReorderable' => $isReorderable,
        'shouldShowTagCount' => $shouldShowTagCount(),
        'maxTags' => $getMaxTags(),
        'hasError' => $hasError,
        'placeholder' => $placeholder,
        'prefixActions' => $getPrefixActions(),
        'prefixIcon' => $getPrefixIcon(),
        'prefixIconColor' => $getPrefixIconColor(),
        'prefixLabel' => $getPrefixLabel(),
        'suffixActions' => $getSuffixActions(),
        'suffixIcon' => $getSuffixIcon(),
        'suffixIconColor' => $getSuffixIconColor(),
        'suffixLabel' => $getSuffixLabel(),
    ];
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'tags-field',
        'livewireKey' => $getLivewireKey(),
    ])

    @once
        @if (filled($tagsAssetSrc))
            <link
                rel="modulepreload"
                href="{{ $tagsAssetSrc }}"
                as="script"
                crossorigin
            />
        @endif
    @endonce

    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $getSize(), $getVariant()])), 0, 64) }}"
    >
        <x-filament-flex-fields::lazy-alpine-mount
            :eager="true"
            :wrap-slot="false"
        >
            <div
                x-load
                x-load-src="{{ $tagsAssetSrc }}"
                x-data="tagsFieldFormComponent({
                    state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                    splitKeys: @js($getSplitKeys()),
                    maxTags: @js($getMaxTags()),
                    suggestions: @js($suggestions),
                    suggestionsOnly: @js($isSuggestionsOnly()),
                    duplicateInsensitive: @js($isDuplicateInsensitive()),
                    tagPrefix: @js($getTagPrefix()),
                    tagSuffix: @js($getTagSuffix()),
                    disabled: @js($isDisabled),
                    searchSuggestions: @js($searchSuggestions),
                    minSearchLength: @js($getMinSearchLength()),
                    componentKey: @js($componentKey),
                    suggestionLabels: @js($suggestionLabels),
                })"
                x-init="init()"
                @class([
                    ...$wrapperClasses,
                    'is-disabled' => $isDisabled,
                    'has-focus-outline' => $shouldShowFocusOutline(),
                ])
                role="group"
                aria-label="{{ $getLabel() }}"
            >
                <div
                    class="fff-tags-field-input-mount"
                    x-ref="tagsInputMount"
                    x-on:click.outside="onSuggestionsClickOutside($event)"
                >
                    @include('filament-flex-fields::forms.components.partials.tags-field-input-ssr', $ssrPartialData)

                    <div
                        x-ref="fieldShell"
                        class="fff-tags-field__input-live"
                    >
                        <div
                            x-ref="tagsInputShell"
                            @class([
                                'fff-tags-field__shell fff-flex-text-input__shell',
                                'is-invalid' => $hasError,
                            ])
                        >
                            <div class="fff-tags-field__row fff-flex-text-input__row">
                                @if (count($prefixActions = $getPrefixActions()) || $prefixIcon = $getPrefixIcon() || filled($prefixLabel = $getPrefixLabel()))
                                    <div class="fff-flex-text-input__prefix-wrap">
                                        @if (filled($prefixLabel))
                                            <span class="fff-flex-text-input__prefix-label">{{ $prefixLabel }}</span>
                                        @endif

                                        @if ($prefixIcon)
                                            <span class="fff-flex-text-input__prefix-icon" aria-hidden="true">
                                                {{ \Filament\Support\generate_icon_html($prefixIcon, color: $getPrefixIconColor()) }}
                                            </span>
                                        @endif

                                        @foreach ($prefixActions as $prefixAction)
                                            {{ $prefixAction }}
                                        @endforeach
                                    </div>
                                @endif

                                <div class="fff-tags-field__control fff-flex-text-input__control">
                                    <input
                                        type="text"
                                        id="{{ $id }}"
                                        x-ref="tagsInput"
                                        class="fff-tags-field__input fff-flex-text-input__input fi-input"
                                        autocomplete="off"
                                        @disabled($isDisabled)
                                        @if ($isAutofocused()) autofocus @endif
                                        placeholder="{{ filled($placeholder) ? e($placeholder) : null }}"
                                        x-bind="input"
                                    />
                                </div>

                                @if (count($suffixActions = $getSuffixActions()) || $suffixIcon = $getSuffixIcon() || filled($suffixLabel = $getSuffixLabel()))
                                    <div class="fff-flex-text-input__suffix-wrap">
                                        @foreach ($suffixActions as $suffixAction)
                                            {{ $suffixAction }}
                                        @endforeach

                                        @if ($suffixIcon)
                                            <span class="fff-flex-text-input__suffix-icon" aria-hidden="true">
                                                {{ \Filament\Support\generate_icon_html($suffixIcon, color: $getSuffixIconColor()) }}
                                            </span>
                                        @endif

                                        @if (filled($suffixLabel))
                                            <span class="fff-flex-text-input__suffix-label">{{ $suffixLabel }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if ($searchSuggestions || count($suggestions) > 0)
                    <template x-teleport="body">
                        <div
                            class="fff-tags-field__suggestions fff-teleported-menu fff-select-dropdown-panel fi-dropdown-panel fff-select-dropdown-panel--below fff-select-dropdown-panel--dropdown-fixed fff-select-dropdown-panel--layout-plain fff-select-dropdown-panel--{{ $getSize() }}"
                            x-ref="suggestionsMenu"
                            role="listbox"
                            x-show="suggestionsMenuOpen"
                            x-cloak
                            x-bind:class="{ 'is-positioned': suggestionsMenuReady, 'is-open': suggestionsMenuOpen && suggestionsMenuReady }"
                            x-bind:aria-activedescendant="suggestionsMenuOpen && suggestionActiveIndex >= 0 ? overlayMenuOptionId(suggestionActiveIndex) : null"
                            x-on:click.stop
                        >
                            <div class="fi-select-input-options-ctn">
                                @if ($searchSuggestions)
                                    <div
                                        x-show="suggestionsMenuOpen && newTag.trim().length < minSearchLength && ! searchPending"
                                        x-cloak
                                        class="fff-select-dropdown-empty"
                                        role="status"
                                    >
                                        <span class="fff-select-dropdown-empty-icon" aria-hidden="true">{!! $minCharsIconHtml !!}</span>
                                        <span class="fff-select-dropdown-empty-title" x-text="suggestionLabels.searchMinChars"></span>
                                    </div>

                                    <div
                                        x-show="suggestionsMenuOpen && searchPending"
                                        x-cloak
                                        class="fff-select-dropdown-loading"
                                        role="status"
                                        aria-live="polite"
                                    >
                                        <x-filament::loading-indicator class="fff-select-dropdown-loading__spinner" />
                                        <span class="fff-select-dropdown-loading__label" x-text="suggestionLabels.searchLoading"></span>
                                    </div>

                                    <div
                                        x-show="suggestionsMenuOpen && newTag.trim().length >= minSearchLength && ! searchPending && filteredSuggestionItems().length === 0"
                                        x-cloak
                                        class="fff-select-dropdown-empty"
                                        role="status"
                                    >
                                        <span class="fff-select-dropdown-empty-icon" aria-hidden="true">{!! $noResultsIconHtml !!}</span>
                                        <span class="fff-select-dropdown-empty-title" x-text="suggestionLabels.searchNoResults"></span>
                                    </div>
                                @endif

                                <template x-for="(suggestion, index) in filteredSuggestionItems()" x-bind:key="suggestion">
                                    <button
                                        type="button"
                                        class="fff-tags-field__suggestion fi-select-input-option"
                                        role="option"
                                        x-bind:id="overlayMenuOptionId(index)"
                                        x-bind:class="{ 'fi-selected': suggestionActiveIndex === index }"
                                        x-bind:aria-selected="suggestionActiveIndex === index ? 'true' : 'false'"
                                        x-on:mousedown.prevent="selectSuggestion(suggestion)"
                                        x-on:mouseenter="suggestionActiveIndex = index"
                                    >
                                        <span x-text="suggestion"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                @endif

                @include('filament-flex-fields::forms.components.partials.tags-field-tags-ssr', $ssrPartialData)

                <div
                    class="fff-tags-field__tags"
                    x-show="(state?.length ?? 0) > 0"
                    @if ($isReorderable)
                        x-on:end.stop="reorderTags($event)"
                        x-sortable
                        data-sortable-animation-duration="{{ $getReorderAnimationDuration() }}"
                    @endif
                >
                    <template
                        x-for="(tag, index) in state"
                        x-bind:key="`${tag}-${index}`"
                    >
                        <span
                            @class([
                                'fff-tags-field__tag',
                                'is-reorderable' => $isReorderable,
                            ])
                            @if ($isReorderable)
                                x-bind:x-sortable-item="index"
                            @endif
                        >
                            <span class="fff-tags-field__tag-label" x-text="displayLabel(tag)"></span>
                            <button
                                type="button"
                                class="fff-tags-field__tag-remove"
                                x-on:click.stop="deleteTag(tag)"
                                x-bind:aria-label="'{{ __('filament-forms::components.tags_input.actions.delete.label') }}: ' + tag"
                                @disabled($isDisabled)
                            >
                                @include('filament-flex-fields::forms.components.partials.tag-pill-remove-icon')
                            </button>
                        </span>
                    </template>
                </div>

                @if ($shouldShowTagCount())
                    <div class="fff-tags-field__meta" x-text="tagCountLabel()"></div>
                @endif
            </div>
        </x-filament-flex-fields::lazy-alpine-mount>
    </div>
</x-dynamic-component>

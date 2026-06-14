@php
    use Illuminate\Support\Js;

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
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'tags-field'])
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $getSize(), $getVariant()])), 0, 64) }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('tags-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
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
        })"
        @class([
            ...$wrapperClasses,
            'is-disabled' => $isDisabled,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div @class([
            'fff-tags-field__shell fff-flex-text-input__shell',
            'is-invalid' => $hasError,
        ])>
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
                        class="fff-tags-field__input fff-flex-text-input__input fi-input"
                        autocomplete="off"
                        @disabled($isDisabled)
                        @if ($isAutofocused()) autofocus @endif
                        placeholder="{{ filled($placeholder) ? e($placeholder) : null }}"
                        @unless ($searchSuggestions)
                            list="{{ $id }}-suggestions"
                        @endunless
                        x-bind="input"
                    />

                    @unless ($searchSuggestions)
                        <datalist id="{{ $id }}-suggestions">
                            @foreach ($suggestions as $suggestion)
                                <option value="{{ $suggestion }}" />
                            @endforeach
                        </datalist>
                    @endunless
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

        @if ($searchSuggestions || count($suggestions) > 0)
            <div
                class="fff-tags-field__suggestions"
                x-show="shouldShowSuggestions() && filteredSuggestions().length > 0"
                x-cloak
            >
                <template x-for="suggestion in filteredSuggestions().slice(0, 8)" :key="suggestion">
                    <button
                        type="button"
                        class="fff-tags-field__suggestion"
                        x-on:click="selectSuggestion(suggestion)"
                        x-text="suggestion"
                    ></button>
                </template>
            </div>
        @endif

        <div
            class="fff-tags-field__tags"
            x-show="(state?.length ?? 0) > 0"
            x-cloak
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
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </span>
            </template>
        </div>

        @if ($shouldShowTagCount())
            <div class="fff-tags-field__meta" x-text="tagCountLabel()"></div>
        @endif
    </div>
</x-dynamic-component>

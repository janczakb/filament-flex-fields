<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Translations;
use Closure;
use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @mixin SelectField
 */
trait InteractsWithSelectHeadlessRuntime
{
    protected bool $allowDeferredOptionResolution = false;

    public function shouldUseHeadlessEngine(): bool
    {
        // Mirror select-field.blade.php: native only when not searchable/multiple/HTML.
        return (bool) ($this->isSearchable() || $this->isMultiple() || $this->isHtmlAllowed() || ! $this->isNative());
    }

    /**
     * Alpine bootstrap payload for the headless combobox (everything except Livewire `state`).
     *
     * @return array<string, mixed>
     */
    public function getHeadlessAlpineConfig(): array
    {
        $isUserSelectField = method_exists($this, 'renderUserOption');
        $state = $this->getRawState();
        $isMultiple = $this->isMultiple();
        $messages = $this->getSelectMessagesForJs();
        $smartSuggest = $this->getSmartSuggestConfigForJs();
        $needsAsyncMessages = $this->hasDynamicSearchResults()
            || $this->hasPaginatedSearchResults()
            || $this->hasDynamicOptions();
        $chipRemoveIconHtml = view('filament-flex-fields::forms.components.partials.tag-pill-remove-icon')->render();
        $clearIconHtml = \Filament\Support\generate_icon_html($this->getClearIcon(), size: IconSize::ExtraSmall)?->toHtml() ?? '';
        $selectedOptionCheckIconHtml = \Filament\Support\generate_icon_html($this->getSelectedOptionCheckIcon(), size: IconSize::Small)?->toHtml() ?? '';
        $id = $this->getId();

        $initialOptions = ($isUserSelectField && method_exists($this, 'getInitialOptionsForJs'))
            ? $this->getInitialOptionsForJs()
            : $this->getHeadlessInitialOptionsForJs();

        $skipInitialOptionLabels = $isUserSelectField
            && $isMultiple
            && method_exists($this, 'getInitialSelectedUserEntriesForJs')
            && filled($this->getInitialSelectedUserEntriesForJs());

        $initialOptionLabels = $skipInitialOptionLabels
            ? []
            : ($isUserSelectField
                ? ((filled($state) && $isMultiple) ? $this->getOptionLabelsForJs() : [])
                : ((filled($state) && $isMultiple)
                    ? $this->getHeadlessInitialOptionLabelsForJs()
                    : []));

        $minSearchLength = ($isUserSelectField && method_exists($this, 'getMinSearchLength'))
            ? (int) $this->getMinSearchLength()
            : 0;

        $iconHtml = static function (mixed $icon, IconSize $size): string {
            return \Filament\Support\generate_icon_html($icon, size: $size)?->toHtml() ?? '';
        };

        return [
            'initialState' => $state,
            'statePath' => $this->getStatePath(),
            'componentKey' => $this->getKey(),
            'menuDomId' => $id.'-fff-headless-menu',
            'multiple' => $isMultiple,
            'searchable' => $this->isSearchable(),
            'options' => $initialOptions,
            'placeholder' => $this->getPlaceholder(),
            'disabled' => $this->isDisabled(),
            'clearable' => $this->isClearableInUi(),
            'keepSelectedOptionsInDropdown' => $this->shouldKeepSelectedOptionsInDropdown(),
            'isHtmlAllowed' => $this->isHtmlAllowed() || $this->usesRichOptionHtml(),
            'isGridLayout' => $this->getOptionLayout() === 'grid',
            'useRichListDropdownLayout' => $this->shouldUseRichListDropdownLayout(),
            'selectedOptionCheckIconHtml' => $selectedOptionCheckIconHtml,
            'hasDynamicSearchResults' => $this->hasDynamicSearchResults(),
            'hasPaginatedSearchResults' => $this->hasPaginatedSearchResults(),
            'hasDynamicOptions' => $this->hasDynamicOptions(),
            'hasClientSideOptionList' => $this->hasClientSideOptionList(),
            'isPreloaded' => $this->isPreloaded(),
            'hasInitialNoOptionsMessage' => $this->hasInitialNoOptionsMessage(),
            'searchDebounce' => $this->getSearchDebounce(),
            'minSearchLength' => $minSearchLength,
            'optionsLimit' => $this->getOptionsLimit(),
            'searchableOptionFields' => $this->getSearchableOptionFields(),
            'livewireId' => $this->resolveHeadlessLivewireId(),
            'maxItems' => $this->getMaxItems(),
            'maxItemsMessage' => $this->getMaxItemsMessage(),
            'position' => $this->getPosition(),
            'loadingMessage' => $needsAsyncMessages ? $messages['loading'] : '',
            'searchingMessage' => $needsAsyncMessages ? $messages['searching'] : '',
            'loadingMoreMessage' => ($needsAsyncMessages && $this->hasPaginatedSearchResults())
                ? $messages['loadingMore']
                : '',
            'noOptionsMessage' => $messages['noOptions'],
            'noMoreOptionsMessage' => ($needsAsyncMessages && $this->hasPaginatedSearchResults())
                ? ($messages['noMoreOptions'] ?? '')
                : '',
            'noSearchResultsMessage' => $this->isSearchable() ? $messages['noSearchResults'] : '',
            'searchPrompt' => ($needsAsyncMessages && $this->isSearchable()) ? $messages['searchPrompt'] : '',
            'initialOptionLabel' => (blank($state) || $isMultiple) ? null : $this->getInitialTriggerLabel(),
            'initialOptionLabels' => $initialOptionLabels,
            'initialSelectedUserEntries' => ($isUserSelectField && method_exists($this, 'getInitialSelectedUserEntriesForJs'))
                ? $this->getInitialSelectedUserEntriesForJs()
                : [],
            'isUserSelectField' => $isUserSelectField,
            'verifiedIconHtml' => $isUserSelectField
                ? $iconHtml(GravityIcon::SealCheck, IconSize::ExtraSmall)
                : '',
            'tagRemoveIconHtml' => $isUserSelectField ? $chipRemoveIconHtml : $clearIconHtml,
            'userSelectNoOptionsIconHtml' => $isUserSelectField
                ? $iconHtml(GravityIcon::Persons, IconSize::Large)
                : '',
            'userSelectNoResultsIconHtml' => $isUserSelectField
                ? $iconHtml(GravityIcon::Magnifier, IconSize::Large)
                : '',
            'selectNoOptionsIconHtml' => ! $isUserSelectField
                ? $iconHtml(GravityIcon::LayoutCells, IconSize::Large)
                : '',
            'selectNoResultsIconHtml' => (! $isUserSelectField && $this->isSearchable())
                ? $iconHtml(GravityIcon::Magnifier, IconSize::Large)
                : '',
            'selectEmptyStateHints' => $this->isSearchable() || $needsAsyncMessages
                ? $this->getSelectEmptyStateHintsForJs()
                : [],
            'userSelectEmptyStateHints' => ($isUserSelectField && method_exists($this, 'getUserSelectEmptyStateHintsForJs'))
                ? $this->getUserSelectEmptyStateHintsForJs()
                : [],
            'canOptionLabelsWrap' => $this->canOptionLabelsWrap(),
            'isReorderable' => $this->isReorderable(),
            'smartSuggestEnabled' => $smartSuggest['enabled'],
            'recentOptionValues' => $smartSuggest['recent'],
            'suggestedOptionValues' => $smartSuggest['suggested'],
            'allowCreateOption' => $smartSuggest['allowCreate'],
            'createOptionLabel' => $smartSuggest['createLabel'],
            'entityMentionsEnabled' => $smartSuggest['entityMentions'],
            'mentionTrigger' => $smartSuggest['mentionTrigger'],
            'entityMentionSectionLabel' => __('filament-flex-fields::default.select_field.entity_mentions.section'),
            'inlineSearch' => $this->hasInlineSearch(),
            'optionGroupSeparators' => $this->hasOptionGroupSeparators(),
            'dropdownAlign' => $this->getDropdownAlign(),
            'matchTriggerWidth' => $this->getVariant() !== 'item-card',
        ];
    }

    protected function resolveHeadlessLivewireId(): ?string
    {
        try {
            return $this->getLivewire()?->getId();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{
     *     pleaseWait: string,
     *     minSearchLength: string,
     *     filterList: string,
     *     tryDifferentSearch: string,
     *     noOptionsAvailable: string,
     *     allOptionsSelected: string,
     * }
     */
    public function getSelectEmptyStateHintsForJs(): array
    {
        $minSearchLength = method_exists($this, 'getMinSearchLength')
            ? (int) $this->getMinSearchLength()
            : 0;

        return [
            'pleaseWait' => Translations::get('filament-flex-fields::default.select_field.empty_hint.please_wait'),
            'minSearchLength' => Translations::get('filament-flex-fields::default.select_field.empty_hint.min_search_length', [
                'count' => $minSearchLength,
            ]),
            'filterList' => Translations::get('filament-flex-fields::default.select_field.empty_hint.filter_list'),
            'tryDifferentSearch' => Translations::get('filament-flex-fields::default.select_field.empty_hint.try_different_search'),
            'noOptionsAvailable' => Translations::get('filament-flex-fields::default.select_field.empty_hint.no_options_available'),
            'allOptionsSelected' => Translations::get('filament-flex-fields::default.select_field.empty_hint.all_options_selected'),
        ];
    }

    /**
     * @return array{
     *     loading: string,
     *     searching: string,
     *     loadingMore: string,
     *     noOptions: string,
     *     noMoreOptions: string,
     *     noSearchResults: string,
     *     searchPrompt: string,
     * }
     */
    public function getSelectMessagesForJs(): array
    {
        return [
            'loading' => $this->stringifySelectMessage($this->getLoadingMessage()),
            'searching' => $this->stringifySelectMessage($this->getSearchingMessage()),
            'loadingMore' => Translations::get('filament-flex-fields::default.select_field.loading_more'),
            'noOptions' => $this->stringifySelectMessage($this->getNoOptionsMessage()),
            'noMoreOptions' => Translations::get('filament-flex-fields::default.select_field.no_more_options'),
            'noSearchResults' => $this->stringifySelectMessage($this->getNoSearchResultsMessage()),
            'searchPrompt' => $this->stringifySelectMessage($this->getSearchPrompt()),
        ];
    }

    protected function stringifySelectMessage(Htmlable|string $message): string
    {
        return $message instanceof Htmlable ? $message->toHtml() : $message;
    }

    /**
     * Options embedded in the headless Alpine payload on first paint.
     *
     * Closure-based option lists are fetched lazily when the dropdown opens unless preloaded.
     *
     * @return list<array<string, mixed>>
     */
    public function getHeadlessInitialOptionsForJs(): array
    {
        if ($this->shouldDeferHeadlessOptionsUntilOpen()) {
            return [];
        }

        return array_values($this->getOptionsForJs());
    }

    /**
     * Selected-value labels for the headless Alpine payload.
     *
     * Unlike {@see Select::getOptionLabelsForJs()}, this keeps rich option metadata
     * (chip labels, descriptions, icons) so trigger chips match SSR on first paint.
     *
     * @return list<array{value: string, label: string, triggerLabel?: string}>
     */
    public function getHeadlessInitialOptionLabelsForJs(): array
    {
        $state = $this->resolveStateForItemCardTrigger();

        if (! is_array($state) || $state === []) {
            return [];
        }

        if ($this->hasDynamicOptions() && ! $this->isPreloaded()) {
            $entries = [];

            foreach ($state as $value) {
                if ($value instanceof BackedEnum) {
                    $value = $value->value;
                }

                $stringValue = (string) $value;

                $entries[] = [
                    'value' => $stringValue,
                    'label' => $stringValue,
                    'triggerLabel' => $stringValue,
                ];
            }

            return $entries;
        }

        $entries = [];
        $options = $this->getOptions();

        foreach ($state as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $stringValue = (string) $value;
            $label = $this->findOptionLabel($options, $value);

            if (is_array($label)) {
                $normalized = $this->normalizeOption($value, $label);
                $dropdownLabel = $this->formatOptionLabelForJs($normalized, compact: false);
                $triggerLabel = $this->formatOptionLabelForJs($normalized, compact: true);

                $entry = [
                    'value' => $stringValue,
                    'label' => $dropdownLabel,
                    'triggerLabel' => $triggerLabel,
                ];

                $entries[] = $entry;

                continue;
            }

            $textLabel = is_string($label) ? $label : $stringValue;

            $entries[] = [
                'value' => $stringValue,
                'label' => $textLabel,
                'triggerLabel' => $textLabel,
            ];
        }

        return $entries;
    }

    protected function shouldDeferHeadlessOptionsUntilOpen(): bool
    {
        if ($this->isPreloaded()) {
            return false;
        }

        if ($this->options instanceof Closure) {
            return true;
        }

        if ($this->hasRelationship()) {
            return $this->hasDynamicOptions();
        }

        return false;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getFlatOptionsForHeadlessJs(): array
    {
        return $this->flattenOptionsForHeadless($this->getOptionsForJs());
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @return list<array{value: string, label: string}>
     */
    protected function flattenOptionsForHeadless(array $options): array
    {
        $flat = [];

        foreach ($options as $option) {
            if (isset($option['options']) && is_array($option['options'])) {
                $flat = array_merge($flat, $this->flattenOptionsForHeadless($option['options']));

                continue;
            }

            $flat[] = [
                'value' => (string) ($option['value'] ?? ''),
                'label' => (string) ($option['triggerLabel'] ?? $option['label'] ?? $option['value'] ?? ''),
            ];
        }

        return $flat;
    }
}

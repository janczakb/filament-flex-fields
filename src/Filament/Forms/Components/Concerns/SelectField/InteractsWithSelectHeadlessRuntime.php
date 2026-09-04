<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Select\HeadlessSelectFeatureFlags;
use Bjanczak\FilamentFlexFields\Support\Translations;
use Closure;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @mixin SelectField
 */
trait InteractsWithSelectHeadlessRuntime
{
    protected bool $allowDeferredOptionResolution = false;

    public function shouldUseHeadlessEngine(): bool
    {
        return HeadlessSelectFeatureFlags::isFieldEligible($this);
    }

    /**
     * @return array{
     *     pleaseWait: string,
     *     minSearchLength: string,
     *     filterList: string,
     *     tryDifferentSearch: string,
     *     noOptionsAvailable: string,
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
        ];
    }

    /**
     * @return array{
     *     loading: string,
     *     searching: string,
     *     loadingMore: string,
     *     noOptions: string,
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
            'noSearchResults' => $this->stringifySelectMessage($this->getNoSearchResultsMessage()),
            'searchPrompt' => $this->stringifySelectMessage($this->getSearchPrompt()),
        ];
    }

    protected function stringifySelectMessage(Htmlable|string $message): string
    {
        return $message instanceof Htmlable ? $message->toHtml() : $message;
    }

    /**
     * @return array{
     *     tryDifferentSearch: string,
     *     minSearchLength: string,
     *     noUsersAvailable: string,
     * }
     */
    public function getUserSelectEmptyStateHintsForJs(): array
    {
        $minSearchLength = method_exists($this, 'getMinSearchLength')
            ? (int) $this->getMinSearchLength()
            : 0;

        return [
            'tryDifferentSearch' => Translations::get('filament-flex-fields::default.user_select_field.empty_hint.try_different_search'),
            'minSearchLength' => Translations::get('filament-flex-fields::default.user_select_field.empty_hint.min_search_length', [
                'count' => $minSearchLength,
            ]),
            'noUsersAvailable' => Translations::get('filament-flex-fields::default.user_select_field.empty_hint.no_users_available'),
        ];
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

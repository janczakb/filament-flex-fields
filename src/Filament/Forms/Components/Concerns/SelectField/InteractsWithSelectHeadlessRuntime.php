<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Select\HeadlessSelectFeatureFlags;
use Closure;

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
            'pleaseWait' => (string) __('filament-flex-fields::default.select_field.empty_hint.please_wait'),
            'minSearchLength' => (string) __('filament-flex-fields::default.select_field.empty_hint.min_search_length', [
                'count' => $minSearchLength,
            ]),
            'filterList' => (string) __('filament-flex-fields::default.select_field.empty_hint.filter_list'),
            'tryDifferentSearch' => (string) __('filament-flex-fields::default.select_field.empty_hint.try_different_search'),
            'noOptionsAvailable' => (string) __('filament-flex-fields::default.select_field.empty_hint.no_options_available'),
        ];
    }

    /**
     * @return array{
     *     loading: string,
     *     searching: string,
     *     noOptions: string,
     *     noSearchResults: string,
     *     searchPrompt: string,
     * }
     */
    public function getSelectMessagesForJs(): array
    {
        return [
            'loading' => $this->getLoadingMessage(),
            'searching' => $this->getSearchingMessage(),
            'loadingMore' => (string) __('filament-flex-fields::default.select_field.loading_more'),
            'noOptions' => $this->getNoOptionsMessage(),
            'noSearchResults' => $this->getNoSearchResultsMessage(),
            'searchPrompt' => $this->getSearchPrompt(),
        ];
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
            'tryDifferentSearch' => (string) __('filament-flex-fields::default.user_select_field.empty_hint.try_different_search'),
            'minSearchLength' => (string) __('filament-flex-fields::default.user_select_field.empty_hint.min_search_length', [
                'count' => $minSearchLength,
            ]),
            'noUsersAvailable' => (string) __('filament-flex-fields::default.user_select_field.empty_hint.no_users_available'),
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

        return $this->getOptionsForJs();
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
     * @param  list<array<string, mixed>>  $options
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

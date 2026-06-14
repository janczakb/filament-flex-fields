<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Concerns\InteractsWithRestrictedModelQueries;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\HasSelectFieldIcons;
use Bjanczak\FilamentFlexFields\Support\HtmlSanitizer;
use Bjanczak\FilamentFlexFields\Support\Select\RichOptionJsTransformer;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class SelectField extends Select
{
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasSelectFieldIcons;
    use InteractsWithRestrictedModelQueries;

    protected string $view = 'filament-flex-fields::forms.components.select-field';

    protected string|Closure $variant = 'bordered';

    protected string|Closure|null $color = null;

    protected string|Closure $chipColor = 'neutral';

    protected bool|Closure|null $usesRichOptions = null;

    protected string|Closure $optionLayout = 'list';

    protected bool|Closure $inlineFieldLabel = false;

    protected bool|Closure $inlineSearch = false;

    protected bool|Closure|null $clearable = null;

    protected string|Closure|null $dropdownAlign = null;

    protected ?RichOptionJsTransformer $richOptionJsTransformer = null;

    protected ?HtmlSanitizer $htmlSanitizer = null;

    /**
     * @var array<string, array<int|string, string>>
     */
    protected array $searchResultsCache = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->native(false);

        $this->transformOptionsForJsUsing(function (SelectField $component, array $options): array {
            return $component->transformRichOptionsForJs($options);
        });

        parent::selectablePlaceholder(function (SelectField $component): bool {
            return $component->isClearable();
        });
    }

    public function relationship(string|Closure|null $name = null, string|Closure|null $titleAttribute = null, ?Closure $modifyQueryUsing = null, bool $ignoreRecord = false): static
    {
        $userModifier = $modifyQueryUsing;

        $modifyQueryUsing = function (SelectField $component, Builder $query, ?string $search = null) use ($userModifier): Builder {
            $query = $component->restrictRelationshipQueryColumns($query);

            if ($userModifier === null) {
                return $query;
            }

            return $component->evaluate($userModifier, [
                'query' => $query,
                'search' => $search,
            ]) ?? $query;
        };

        return parent::relationship($name, $titleAttribute, $modifyQueryUsing, $ignoreRecord);
    }

    public function clearable(bool|Closure $condition = true): static
    {
        $this->clearable = $condition;

        parent::selectablePlaceholder($condition);

        return $this;
    }

    public function isClearable(): bool
    {
        if ($this->clearable !== null) {
            return (bool) $this->evaluate($this->clearable);
        }

        return $this->getVariant() !== 'item-card';
    }

    public function selectablePlaceholder(bool|Closure $condition = true): static
    {
        return parent::selectablePlaceholder($condition);
    }

    public function canSelectPlaceholder(): bool
    {
        return $this->isClearable();
    }

    public function dropdownAlign(string|Closure $align): static
    {
        $this->dropdownAlign = $align;

        return $this;
    }

    public function getDropdownAlign(): string
    {
        if ($this->dropdownAlign !== null) {
            $align = (string) $this->evaluate($this->dropdownAlign);

            if (! in_array($align, ['start', 'end'], true)) {
                throw new InvalidArgumentException("Select dropdown align [{$align}] is not supported.");
            }

            return $align;
        }

        return $this->getVariant() === 'item-card' ? 'end' : 'start';
    }

    public function shouldUseRichListDropdownLayout(): bool
    {
        return $this->usesRichOptionHtml() && $this->getOptionLayout() === 'list';
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function chipColor(string|Closure $chipColor): static
    {
        $this->chipColor = $chipColor;

        return $this;
    }

    public function richOptions(bool|Closure $condition = true): static
    {
        $this->usesRichOptions = $condition;

        return $this;
    }

    public function optionLayout(string|Closure $layout): static
    {
        $this->optionLayout = $layout;

        return $this;
    }

    public function inlineFieldLabel(bool|Closure $condition = true): static
    {
        $this->inlineFieldLabel = $condition;

        return $this;
    }

    public function hasInlineFieldLabel(): bool
    {
        return (bool) $this->evaluate($this->inlineFieldLabel);
    }

    public function inlineSearch(bool|Closure $condition = true): static
    {
        $this->inlineSearch = $condition;

        return $this;
    }

    public function hasInlineSearch(): bool
    {
        return $this->isSearchable()
            && ! $this->isMultiple()
            && (bool) $this->evaluate($this->inlineSearch);
    }

    public function getOptionLayout(): string
    {
        $layout = (string) $this->evaluate($this->optionLayout);

        if (! in_array($layout, ['list', 'grid'], true)) {
            throw new InvalidArgumentException("Select option layout [{$layout}] is not supported.");
        }

        return $layout;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['bordered', 'secondary', 'flat', 'faded', 'soft', 'underlined', 'item-card'], true)) {
            throw new InvalidArgumentException("Select variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function getColor(): ?string
    {
        $color = $this->evaluate($this->color);

        return filled($color) ? (string) $color : null;
    }

    public function getChipColor(): string
    {
        return (string) $this->evaluate($this->chipColor);
    }

    public function usesRichOptionHtml(): bool
    {
        if ($this->getOptionLayout() === 'grid') {
            return true;
        }

        if ($this->isHtmlAllowed()) {
            return true;
        }

        if ($this->usesRichOptions !== null) {
            return (bool) $this->evaluate($this->usesRichOptions);
        }

        return $this->optionsContainRichShape($this->getOptions());
    }

    public function hasClientSideOptionList(): bool
    {
        if ($this->hasRelationship()) {
            return false;
        }

        if ($this->isPreloaded()) {
            return false;
        }

        if ($this->options instanceof Closure) {
            return false;
        }

        if ($this->hasDynamicDisabledOptions()) {
            return false;
        }

        return true;
    }

    public function hasDynamicOptions(): bool
    {
        if ($this->hasClientSideOptionList()) {
            return false;
        }

        return parent::hasDynamicOptions();
    }

    public function hasInitialNoOptionsMessage(): bool
    {
        if ($this->hasClientSideOptionList()) {
            return false;
        }

        if ($this->options instanceof Closure) {
            return true;
        }

        return parent::hasInitialNoOptionsMessage();
    }

    public function hasDynamicSearchResults(): bool
    {
        return ! $this->hasClientSideOptionList();
    }

    /**
     * @param  array<string | int, string | array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    public function transformRichOptionsForJs(array $options): array
    {
        return $this->getRichOptionJsTransformer()->transform(
            $options,
            fn (string|int $value, array|string $label): array => $this->normalizeOption($value, $label),
            fn (array $option, bool $compact = false): string => $this->formatOptionLabelForJs($option, $compact),
            fn (array $option): bool => $this->isOptionGroupArray($option),
            fn (array $option): bool => $this->isRichOptionArray($option),
            fn (?string $html): ?string => $this->shouldSanitizeTransformerOutput($html),
            $this->getOptionLayout(),
        );
    }

    protected function shouldSanitizeTransformerOutput(?string $html): ?string
    {
        return $this->sanitizeUserProvidedHtml($html);
    }

    protected function getRichOptionJsTransformer(): RichOptionJsTransformer
    {
        return $this->richOptionJsTransformer ??= new RichOptionJsTransformer($this->getHtmlSanitizer());
    }

    protected function getHtmlSanitizer(): HtmlSanitizer
    {
        return $this->htmlSanitizer ??= app(HtmlSanitizer::class);
    }

    protected function sanitizeUserProvidedHtml(?string $html): ?string
    {
        if ($html === null || ! $this->isHtmlAllowed()) {
            return $html;
        }

        return $this->getHtmlSanitizer()->sanitize($html);
    }

    protected function sanitizeRichHtml(?string $html): ?string
    {
        if ($html === null || ! $this->shouldSanitizeRichHtml()) {
            return $html;
        }

        return $this->getHtmlSanitizer()->sanitize($html);
    }

    protected function shouldSanitizeRichHtml(): bool
    {
        return $this->isHtmlAllowed();
    }

    /**
     * @param  array<string | int, string | array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    protected function transformRichOptionsForJsLegacy(array $options): array
    {
        return collect($options)
            ->map(function (array|string $label, string|int $value): array {
                if (is_array($label) && $this->isOptionGroupArray($label)) {
                    return [
                        'label' => (string) $value,
                        'options' => $this->transformRichOptionsForJs($label),
                    ];
                }

                $normalized = $this->normalizeOption($value, $label);
                $dropdownLabel = $this->formatOptionLabelForJs($normalized);
                $triggerLabel = $this->formatOptionLabelForJs($normalized, compact: true);

                $option = [
                    'label' => $dropdownLabel,
                    'value' => (string) $value,
                    'isDisabled' => $normalized['disabled'],
                ];

                if ($triggerLabel !== $dropdownLabel) {
                    $option['triggerLabel'] = $triggerLabel;
                }

                return $option;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string | int, string | array<string, mixed>>  $options
     */
    protected function optionsContainRichShape(array $options): bool
    {
        foreach ($options as $label) {
            if (is_array($label) && $this->isRichOptionArray($label)) {
                return true;
            }

            if (is_array($label) && $this->isOptionGroupArray($label)) {
                if ($this->optionsContainRichShape($label)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function isOptionGroupArray(array $options): bool
    {
        if ($this->isRichOptionArray($options)) {
            return false;
        }

        foreach ($options as $item) {
            if (is_string($item) || (is_array($item) && $this->isRichOptionArray($item))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $option
     */
    protected function isRichOptionArray(array $option): bool
    {
        return array_key_exists('label', $option);
    }

    /**
     * @return array{
     *     value: string|int,
     *     label: string,
     *     description: ?string,
     *     icon: string|BackedEnum|Htmlable|null,
     *     image: ?string,
     *     badge: ?string,
     *     badge_color: ?string,
     *     disabled: bool,
     * }
     */
    protected function normalizeOption(string|int $value, array|string $label): array
    {
        if (is_string($label)) {
            return [
                'value' => $value,
                'label' => $label,
                'description' => null,
                'icon' => null,
                'image' => null,
                'badge' => null,
                'badge_color' => null,
                'disabled' => $this->isOptionDisabled($value, $label),
            ];
        }

        return [
            'value' => $value,
            'label' => (string) ($label['label'] ?? $value),
            'description' => filled($label['description'] ?? null) ? (string) $label['description'] : null,
            'icon' => $label['icon'] ?? null,
            'image' => filled($label['image'] ?? null) ? (string) $label['image'] : null,
            'badge' => filled($label['badge'] ?? null) ? (string) $label['badge'] : null,
            'badge_color' => filled($label['badge_color'] ?? null) ? (string) $label['badge_color'] : null,
            'disabled' => (bool) ($label['disabled'] ?? $this->isOptionDisabled($value, (string) ($label['label'] ?? $value))),
        ];
    }

    /**
     * @param  array{
     *     value: string|int,
     *     label: string,
     *     description: ?string,
     *     icon: string|BackedEnum|Htmlable|null,
     *     badge: ?string,
     *     badge_color: ?string,
     *     disabled: bool,
     * }  $option
     */
    protected function formatOptionLabelForJs(array $option, bool $compact = false): string
    {
        if (! $this->usesRichOptionHtml()) {
            $label = $option['label'];

            return $this->isHtmlAllowed()
                ? (string) ($this->sanitizeUserProvidedHtml($label) ?? '')
                : $label;
        }

        if ($compact) {
            return $this->renderRichOptionLabel($option, layout: 'trigger');
        }

        if ($this->getOptionLayout() === 'grid') {
            return $this->renderRichOptionLabel($option, layout: 'grid');
        }

        if (! filled($option['description']) && ! filled($option['icon']) && ! filled($option['image']) && ! filled($option['badge'])) {
            $label = $option['label'];

            return $this->isHtmlAllowed()
                ? (string) ($this->sanitizeRichHtml($label) ?? '')
                : $label;
        }

        return $this->renderRichOptionLabel($option, layout: 'list');
    }

    /**
     * @param  array{
     *     value: string|int,
     *     label: string,
     *     description: ?string,
     *     icon: string|BackedEnum|Htmlable|null,
     *     image: ?string,
     *     badge: ?string,
     *     badge_color: ?string,
     *     disabled: bool,
     * }  $option
     */
    protected function renderRichOptionLabel(array $option, string $layout = 'list'): string
    {
        /** @var View $view */
        $view = view('filament-flex-fields::forms.components.partials.select-rich-option', [
            'label' => $option['label'],
            'description' => $layout === 'list' ? $option['description'] : null,
            'icon' => $option['icon'],
            'image' => $option['image'],
            'badge' => $layout === 'list' ? $option['badge'] : null,
            'badgeColor' => $option['badge_color'] ?? 'primary',
            'layout' => $layout,
            'iconSize' => match ($this->getSize()) {
                'sm' => IconSize::Small,
                'lg' => IconSize::Large,
                default => IconSize::Medium,
            },
        ]);

        return $view->render();
    }

    /**
     * @return array<string, string>
     */
    public function getTriggerOptionLabelsForJs(): array
    {
        $labels = [];

        foreach ($this->getOptions() as $value => $label) {
            if (is_array($label) && $this->isOptionGroupArray($label)) {
                foreach ($label as $groupedValue => $groupedLabel) {
                    $labels[(string) $groupedValue] = $this->renderRichOptionLabel(
                        $this->normalizeOption($groupedValue, $groupedLabel),
                        layout: 'trigger',
                    );
                }

                continue;
            }

            $labels[(string) $value] = $this->renderRichOptionLabel(
                $this->normalizeOption($value, $label),
                layout: 'trigger',
            );
        }

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-select-field',
            'fff-select-field--'.$this->getSize(),
            'fff-select-field--'.$this->getVariant(),
            'fff-select-field--layout-'.$this->getOptionLayout(),
            'fff-select-field--chips-'.$this->getChipColor(),
        ];

        if ($color = $this->getColor()) {
            $classes['fi-color-'.$color] = $color;
        }

        if ($this->hasInlineFieldLabel() && filled($this->getLabel()) && ! $this->isLabelHidden()) {
            $classes['fff-select-field--inline-field-label'] = true;
        }

        if ($this->hasInlineSearch()) {
            $classes['fff-select-field--inline-search'] = true;
        }

        if ($this->isMultiple()) {
            $classes['fff-select-field--multiple'] = true;
        }

        if (! $this->isClearable()) {
            $classes['fff-select-field--not-clearable'] = true;
        } elseif ($this->hasSelectedValueForClearButton() && ! $this->isLocallyDisabled()) {
            $classes['fff-select-field--clearable-has-value'] = true;
        }

        if ($this->usesRichOptionHtml() && $this->getOptionLayout() === 'list' && ! $this->isMultiple()) {
            $classes['fff-select-field--rich-list-trigger'] = true;
        }

        if ($this->shouldShowFocusOutline()) {
            $classes['has-focus-outline'] = true;
        }

        return $classes;
    }

    public function getSearchableOptionFields(): array
    {
        $fields = parent::getSearchableOptionFields();

        if ($this->usesRichOptionHtml()) {
            $fields = array_values(array_unique([...$fields, 'description']));
        }

        return $fields;
    }

    public function getInitialTriggerLabel(): ?string
    {
        if ($this->isNative() || $this->getVariant() === 'item-card' || $this->isMultiple()) {
            return null;
        }

        return $this->resolveInitialTriggerLabel();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getInitialTriggerBadges(): array
    {
        if ($this->isNative() || $this->getVariant() === 'item-card' || ! $this->isMultiple()) {
            return [];
        }

        $state = $this->resolveStateForItemCardTrigger();

        if (! is_array($state) || $state === []) {
            return [];
        }

        $badges = [];
        $options = $this->getOptions();

        foreach ($state as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $label = $this->findOptionLabel($options, $value);

            if (is_array($label)) {
                $badges[] = [
                    'value' => (string) $value,
                    'label' => $this->formatOptionLabelForJs($this->normalizeOption($value, $label), compact: true),
                ];

                continue;
            }

            $badges[] = [
                'value' => (string) $value,
                'label' => is_string($label) ? $label : (string) $value,
            ];
        }

        return $badges;
    }

    protected function resolveInitialTriggerLabel(): string
    {
        $state = $this->resolveStateForItemCardTrigger();

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        if (blank($state)) {
            return (string) ($this->getPlaceholder() ?? '');
        }

        $label = $this->findOptionLabel($this->getOptions(), $state);

        if ($label === null) {
            return (string) $state;
        }

        if (is_array($label)) {
            $normalized = $this->normalizeOption($state, $label);

            if ($this->getOptionLayout() === 'grid') {
                return $this->renderRichOptionLabel($normalized, layout: 'trigger');
            }

            if ($this->usesRichOptionHtml()) {
                return $this->formatOptionLabelForJs($normalized);
            }

            return $this->formatOptionLabelForJs($normalized, compact: true);
        }

        return (string) $label;
    }

    public function getItemCardInitialTriggerLabel(): ?string
    {
        if ($this->getVariant() !== 'item-card') {
            return null;
        }

        return $this->resolveInitialTriggerLabel();
    }

    protected function resolveStateForItemCardTrigger(): mixed
    {
        try {
            $state = $this->getState();
        } catch (\Throwable) {
            $state = null;
        }

        if ($state === null || ($state === '' && ! is_array($state))) {
            return $this->getDefaultState();
        }

        return $state;
    }

    protected function isLocallyDisabled(): bool
    {
        return (bool) $this->evaluate($this->isDisabled);
    }

    protected function hasSelectedValueForClearButton(): bool
    {
        $state = $this->resolveStateForItemCardTrigger();

        if (is_array($state)) {
            return $state !== [];
        }

        return filled($state);
    }

    public function getOptionLabel(bool $withDefault = true): ?string
    {
        $state = $this->getState();

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        $label = $this->findOptionLabel($this->getOptions(), $state);

        if ($label === null) {
            return parent::getOptionLabel($withDefault);
        }

        if (is_array($label)) {
            $normalized = $this->normalizeOption($state, $label);

            if ($this->getOptionLayout() === 'grid') {
                return $this->renderRichOptionLabel($normalized, layout: 'trigger');
            }

            return $this->formatOptionLabelForJs($normalized);
        }

        return (string) $label;
    }

    /**
     * @return array<string, string>
     */
    public function getOptionLabels(bool $withDefaults = true): array
    {
        if ($this->getOptionLabelsUsing) {
            return parent::getOptionLabels($withDefaults);
        }

        $labels = [];
        $options = $this->getOptions();

        foreach ($this->getState() ?? [] as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $label = $this->findOptionLabel($options, $value);

            if (is_array($label)) {
                $labels[$value] = $this->formatOptionLabelForJs($this->normalizeOption($value, $label), compact: true);

                continue;
            }

            if (is_string($label)) {
                $labels[$value] = $label;

                continue;
            }

            if ($withDefaults) {
                $labels[$value] = (string) $value;
            }
        }

        return $labels;
    }

    /**
     * @param  array<string | int, string | array<string, mixed>>  $options
     */
    protected function findOptionLabel(array $options, mixed $state): array|string|null
    {
        foreach ($options as $value => $label) {
            if (is_array($label) && $this->isOptionGroupArray($label)) {
                $found = $this->findOptionLabel($label, $state);

                if ($found !== null) {
                    return $found;
                }

                continue;
            }

            if ((string) $value === (string) $state) {
                return $label;
            }
        }

        return null;
    }

    public function getSearchResults(string $search): array
    {
        $cacheKey = $this->searchCacheKey($search);

        if (isset($this->searchResultsCache[$cacheKey])) {
            return $this->searchResultsCache[$cacheKey];
        }

        return $this->searchResultsCache[$cacheKey] = parent::getSearchResults($search);
    }

    public function getSearchResultsFromRelationship(?string $search): array
    {
        $cacheKey = $this->searchCacheKey($search);

        if (isset($this->searchResultsCache[$cacheKey])) {
            return $this->searchResultsCache[$cacheKey];
        }

        return $this->searchResultsCache[$cacheKey] = parent::getSearchResultsFromRelationship($search);
    }

    protected function searchCacheKey(?string $search): string
    {
        return md5(($this->getName() ?? 'select').'|'.trim((string) $search));
    }
}

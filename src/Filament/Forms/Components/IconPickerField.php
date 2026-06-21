<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\HasSelectFieldIcons;
use Bjanczak\FilamentFlexFields\Support\Icons\IconCatalogIndex;
use Bjanczak\FilamentFlexFields\Support\Icons\IconCatalogResolver;
use Bjanczak\FilamentFlexFields\Support\Icons\IconSvgCache;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasAffixes;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Livewire\Attributes\Renderless;

class IconPickerField extends Field
{
    public const int MAX_SVG_PREVIEW_BATCH = 48;

    use CanBeReadOnly;
    use HasAffixes;
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasPlaceholder;
    use HasSelectFieldIcons;

    protected string $view = 'filament-flex-fields::forms.components.icon-picker-field';

    protected string|Closure $variant = 'bordered';

    protected bool|Closure|null $clearable = null;

    /**
     * @var list<string>|string|Closure|null
     */
    protected array|string|Closure|null $sets = null;

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $icons = null;

    /**
     * @var list<string>|Closure
     */
    protected array|Closure $excludeIcons = [];

    protected string|Closure $searchResultsLayout = 'icons';

    protected bool|Closure $closeOnSelect = true;

    protected int|Closure $gridColumns = 8;

    protected bool|Closure $preload = false;

    protected int|Closure|null $limitPerSet = null;

    protected int|Closure $perPage = IconCatalogResolver::DEFAULT_PER_PAGE;

    /**
     * @var array<string, array{prefix: string, label: string, icons: list<string>}>|null
     */
    protected ?array $memoizedCatalog = null;

    protected ?IconCatalogIndex $memoizedIndex = null;

    protected ?string $memoizedSearchFingerprint = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);

        $this->rule(function (IconPickerField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if ($value === null || $value === '') {
                    if ($component->isRequired()) {
                        $fail(__('validation.required', ['attribute' => $component->getLabel()]));
                    }

                    return;
                }

                if (! is_string($value)) {
                    $fail(__('filament-flex-fields::default.validation.icon_picker.invalid'));

                    return;
                }

                if (! $component->isAllowedIcon($value)) {
                    $fail(__('filament-flex-fields::default.validation.icon_picker.invalid'));
                }
            };
        });
    }

    public function clearable(bool|Closure $condition = true): static
    {
        $this->clearable = $condition;

        return $this;
    }

    public function isClearable(): bool
    {
        if ($this->clearable !== null) {
            return (bool) $this->evaluate($this->clearable);
        }

        return true;
    }

    /**
     * @param  list<string>|string|Closure|null  $sets
     */
    public function sets(array|string|Closure|null $sets = null): static
    {
        $this->sets = $sets;

        return $this;
    }

    /**
     * @param  list<string>|Closure  $icons
     */
    public function icons(array|Closure $icons): static
    {
        $this->icons = $icons;

        return $this;
    }

    /**
     * @param  list<string>|Closure  $icons
     */
    public function excludeIcons(array|Closure $icons): static
    {
        $this->excludeIcons = $icons;

        return $this;
    }

    public function searchResultsLayout(string|Closure $layout): static
    {
        $this->searchResultsLayout = $layout;

        return $this;
    }

    public function grid(): static
    {
        return $this->searchResultsLayout('grid');
    }

    public function list(): static
    {
        return $this->searchResultsLayout('list');
    }

    public function iconsOnly(): static
    {
        return $this->searchResultsLayout('icons');
    }

    public function closeOnSelect(bool|Closure $condition = true): static
    {
        $this->closeOnSelect = $condition;

        return $this;
    }

    public function gridColumns(int|Closure $columns): static
    {
        $this->gridColumns = $columns;

        return $this;
    }

    public function preload(bool|Closure $condition = true): static
    {
        $this->preload = $condition;

        return $this;
    }

    public function limitPerSet(int|Closure|null $limit): static
    {
        $this->limitPerSet = $limit;

        return $this;
    }

    public function perPage(int|Closure $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if ($variant === 'primary') {
            $variant = 'bordered';
        }

        if (! in_array($variant, ['bordered', 'secondary', 'flat', 'soft', 'faded', 'underlined'], true)) {
            throw new InvalidArgumentException("Icon picker variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    /**
     * @return list<string>|null
     */
    public function getConfiguredSets(): ?array
    {
        $sets = $this->evaluate($this->sets);

        if ($sets === null) {
            $defaultSets = config('filament-flex-fields.ui.icon_picker_sets');

            if (is_array($defaultSets) && $defaultSets !== []) {
                return array_values(array_map(static fn (mixed $value): string => (string) $value, $defaultSets));
            }

            return null;
        }

        if (is_string($sets)) {
            return [$sets];
        }

        return array_values(array_map(static fn (mixed $value): string => (string) $value, $sets));
    }

    /**
     * @return list<string>
     */
    public function getResolvedSetNames(): array
    {
        return $this->getCatalogResolver()->resolveSetNames($this->getConfiguredSets());
    }

    /**
     * @return list<string>
     */
    public function getWhitelistedIcons(): array
    {
        $icons = $this->evaluate($this->icons);

        if (! is_array($icons)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $value): string => trim((string) $value), $icons),
            static fn (string $value): bool => $value !== '',
        ));
    }

    /**
     * @return list<string>
     */
    public function getExcludedIcons(): array
    {
        $icons = $this->evaluate($this->excludeIcons);

        if (! is_array($icons)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $value): string => trim((string) $value), $icons),
            static fn (string $value): bool => $value !== '',
        ));
    }

    public function getSearchResultsLayout(): string
    {
        $layout = (string) $this->evaluate($this->searchResultsLayout);

        if (! in_array($layout, ['grid', 'list', 'icons'], true)) {
            throw new InvalidArgumentException("Icon picker search layout [{$layout}] is not supported.");
        }

        return $layout;
    }

    public function shouldCloseOnSelect(): bool
    {
        return (bool) $this->evaluate($this->closeOnSelect);
    }

    public function getGridColumns(): int
    {
        return max(2, min(12, (int) $this->evaluate($this->gridColumns)));
    }

    public function shouldPreload(): bool
    {
        return (bool) $this->evaluate($this->preload);
    }

    public function getLimitPerSet(): ?int
    {
        $limit = $this->evaluate($this->limitPerSet);

        return is_int($limit) ? max(0, $limit) : null;
    }

    public function getPerPage(): int
    {
        return max(1, min((int) $this->evaluate($this->perPage), IconCatalogResolver::MAX_PER_PAGE));
    }

    /**
     * @return array<string, array{prefix: string, label: string, icons: list<string>}>
     */
    public function getIconCatalog(): array
    {
        return $this->memoizedCatalog ??= $this->getCatalogResolver()->catalogFor($this->getResolvedSetNames());
    }

    protected function getCatalogIndex(): IconCatalogIndex
    {
        return $this->memoizedIndex ??= $this->getCatalogResolver()->indexFor(
            catalog: $this->getIconCatalog(),
            whitelist: $this->getWhitelistedIcons(),
            exclude: $this->getExcludedIcons(),
            limitPerSet: $this->getLimitPerSet(),
        );
    }

    /**
     * @return list<array{key: string, prefix: string, label: string, count: int}>
     */
    public function getAvailableSetsForJs(): array
    {
        return $this->getCatalogIndex()->setSummaries();
    }

    public function isAllowedIcon(string $icon): bool
    {
        $icon = trim($icon);

        if ($icon === '') {
            return true;
        }

        return $this->getCatalogIndex()->isAllowed($icon);
    }

    /**
     * @return array{
     *     icons: list<array{name: string, label: string}>,
     *     total: int,
     *     page: int,
     *     perPage: int,
     *     hasMore: bool,
     *     sets: list<array{key: string, prefix: string, label: string, count: int}>
     * }
     */
    public function searchIcons(string $query = '', ?string $set = null, int $page = 1): array
    {
        $query = trim($query);
        $perPage = $this->getPerPage();
        $includeSetSummaries = $page === 1 && $query === '' && $set === null;

        $minutes = (int) config('filament-flex-fields.ui.icon_picker_search_cache_minutes', 60);

        $whitelisted = $this->getWhitelistedIcons();
        $excluded = $this->getExcludedIcons();

        if ($minutes <= 0 || ! empty($whitelisted) || ! empty($excluded)) {
            return $this->getCatalogIndex()->search(
                query: $query,
                set: $set,
                page: $page,
                perPage: $perPage,
                includeSetSummaries: $includeSetSummaries,
            );
        }

        $cacheKey = sprintf(
            'fff.icon-search.%s.%s',
            $this->getSearchCacheFingerprint(),
            md5(json_encode([$query, $set, $page, $perPage, $includeSetSummaries], JSON_THROW_ON_ERROR)),
        );

        return Cache::remember($cacheKey, now()->addMinutes($minutes), fn (): array => $this->getCatalogIndex()->search(
            query: $query,
            set: $set,
            page: $page,
            perPage: $perPage,
            includeSetSummaries: $includeSetSummaries,
        ));
    }

    protected function getSearchCacheFingerprint(): string
    {
        return $this->memoizedSearchFingerprint ??= md5(json_encode([
            $this->getResolvedSetNames(),
            $this->getWhitelistedIcons(),
            $this->getExcludedIcons(),
            $this->getLimitPerSet(),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param  list<string>  $icons
     * @return list<array{name: string, html: string}>
     */
    public function renderIconSvgs(array $icons): array
    {
        $icons = array_values(array_filter(
            array_map(static fn (mixed $icon): string => trim((string) $icon), $icons),
            fn (string $icon): bool => $icon !== '' && $this->isAllowedIcon($icon),
        ));

        if ($icons === []) {
            return [];
        }

        $cached = $this->getSvgCache()->rememberMany(
            $icons,
            fn (array $missing): array => $this->renderMissingIconSvgs($missing),
        );

        return array_map(
            static fn (string $icon): array => [
                'name' => $icon,
                'html' => $cached[$icon] ?? '',
            ],
            $icons,
        );
    }

    /**
     * @param  list<string>  $icons
     * @return array<string, string>
     */
    protected function renderMissingIconSvgs(array $icons): array
    {
        $rendered = [];

        foreach ($icons as $icon) {
            $html = $this->renderIconHtml($icon);

            if ($html !== '') {
                $rendered[$icon] = $html;
            }
        }

        return $rendered;
    }

    public function renderIconHtml(?string $icon): string
    {
        if (blank($icon)) {
            return '';
        }

        $html = \Filament\Support\generate_icon_html($icon, size: IconSize::Medium);

        return $html instanceof Htmlable ? $html->toHtml() : '';
    }

    /**
     * @return array<string, bool|string>
     */
    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-select-field' => true,
            'fff-select-field--'.$this->getSize() => true,
            'fff-select-field--'.$this->getVariant() => true,
            'fi-color-primary' => true,
            'fff-icon-picker-field' => true,
            'fff-icon-picker-field--layout-'.$this->getSearchResultsLayout() => true,
        ];

        if (! $this->isClearable()) {
            $classes['fff-select-field--not-clearable'] = true;
        } elseif ($this->hasSelectedValueForClearButton() && ! $this->isLocallyDisabled()) {
            $classes['fff-select-field--clearable-has-value'] = true;
        }

        if ($this->shouldShowFocusOutline()) {
            $classes['has-focus-outline'] = true;
        }

        return $classes;
    }

    protected function isLocallyDisabled(): bool
    {
        return (bool) $this->evaluate($this->isDisabled)
            || (bool) $this->evaluate($this->isReadOnly);
    }

    protected function hasSelectedValueForClearButton(): bool
    {
        try {
            $state = $this->getState();
        } catch (\Throwable) {
            $state = null;
        }

        if ($state === null || ($state === '' && ! is_array($state))) {
            $state = $this->getDefaultState();
        }

        return filled($state);
    }

    #[ExposedLivewireMethod]
    #[Renderless]
    public function getIconPickerSearchResults(string $query = '', ?string $set = null, int $page = 1): array
    {
        return $this->searchIcons($query, $set, $page);
    }

    /**
     * @param  list<string>  $icons
     * @return list<array{name: string, html: string}>
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getIconPickerSvgPreviews(array $icons = []): array
    {
        if (count($icons) > self::MAX_SVG_PREVIEW_BATCH) {
            $icons = array_slice($icons, 0, self::MAX_SVG_PREVIEW_BATCH);
        }

        return $this->renderIconSvgs($icons);
    }

    protected function getCatalogResolver(): IconCatalogResolver
    {
        return app(IconCatalogResolver::class);
    }

    protected function getSvgCache(): IconSvgCache
    {
        return app(IconSvgCache::class);
    }
}

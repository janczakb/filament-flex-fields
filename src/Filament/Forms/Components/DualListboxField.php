<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\OptionsArrayStateCast;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class DualListboxField extends Field
{
    use HasControlSize;

    public const int VIRTUAL_SCROLL_THRESHOLD = 100;

    public const int LARGE_OPTIONS_THRESHOLD = 100;

    protected string $view = 'filament-flex-fields::forms.components.dual-listbox-field';

    /**
     * @var array<string | int, string | array<string, mixed>> | Closure
     */
    protected array|Closure $options = [];

    /**
     * @var array<string, array{label: string, description: ?string, disabled: bool}>|null
     */
    protected ?array $normalizedOptionsCache = null;

    /**
     * @var array<string | int> | Closure
     */
    protected array|Closure $disabledOptions = [];

    protected string|Closure $variant = 'bordered';

    protected string|Closure $listHeight = '16rem';

    protected bool|Closure $isSearchable = true;

    protected bool|Closure $isReorderable = true;

    protected bool|Closure $moveOnDoubleClick = true;

    protected bool|Closure $showTransferButtons = true;

    protected string|Closure|null $availableLabel = null;

    protected string|Closure|null $selectedLabel = null;

    protected int|Closure|null $minItems = null;

    protected int|Closure|null $maxItems = null;

    protected string|BackedEnum|Htmlable|Closure|null $searchIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $moveAllRightIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $moveRightIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $swapIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $moveLeftIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $moveAllLeftIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $moveUpIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $moveDownIcon = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(function (DualListboxField $component, mixed $state): void {
            $component->state($component->normalizeState(is_array($state) ? $state : []));
        });

        $this->dehydrateStateUsing(fn (DualListboxField $component, mixed $state): array => $component->normalizeState(is_array($state) ? $state : []));

        $this->rule(function (DualListboxField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (! is_array($value)) {
                    $fail(__('validation.array', ['attribute' => $attribute]));

                    return;
                }

                $allowedKeys = array_keys($component->getNormalizedOptions());

                foreach ($value as $item) {
                    if (! in_array((string) $item, $allowedKeys, true)) {
                        $fail(__('filament-flex-fields::default.validation.dual_listbox.invalid_option'));

                        return;
                    }
                }

                $count = count($value);

                if ($exact = $component->getExactItems()) {
                    if ($count !== $exact) {
                        $fail(__('filament-flex-fields::default.validation.dual_listbox.exact', ['count' => $exact]));

                        return;
                    }
                }

                if ($min = $component->getMinItems()) {
                    if ($count < $min) {
                        $fail(__('filament-flex-fields::default.validation.dual_listbox.min', ['count' => $min]));

                        return;
                    }
                }

                if ($max = $component->getMaxItems()) {
                    if ($count > $max) {
                        $fail(__('filament-flex-fields::default.validation.dual_listbox.max', ['count' => $max]));
                    }
                }
            };
        });
    }

    /**
     * @param  array<string | int, string | array<string, mixed>> | Closure  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  array<string | int> | Closure  $keys
     */
    public function disabledOptions(array|Closure $keys): static
    {
        $this->disabledOptions = $keys;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function listHeight(string|Closure $height): static
    {
        $this->listHeight = $height;

        return $this;
    }

    public function searchable(bool|Closure $condition = true): static
    {
        $this->isSearchable = $condition;

        return $this;
    }

    public function reorderable(bool|Closure $condition = true): static
    {
        $this->isReorderable = $condition;

        return $this;
    }

    public function moveOnDoubleClick(bool|Closure $condition = true): static
    {
        $this->moveOnDoubleClick = $condition;

        return $this;
    }

    public function showTransferButtons(bool|Closure $condition = true): static
    {
        $this->showTransferButtons = $condition;

        return $this;
    }

    public function availableLabel(string|Closure|null $label): static
    {
        $this->availableLabel = $label;

        return $this;
    }

    public function selectedLabel(string|Closure|null $label): static
    {
        $this->selectedLabel = $label;

        return $this;
    }

    public function minItems(int|Closure|null $count): static
    {
        $this->minItems = $count;

        return $this;
    }

    public function maxItems(int|Closure|null $count): static
    {
        $this->maxItems = $count;

        return $this;
    }

    public function exactItems(int|Closure|null $count): static
    {
        $this->minItems = $count;
        $this->maxItems = $count;

        return $this;
    }

    /**
     * @param  array{
     *     search?: string|BackedEnum|Htmlable,
     *     move_all_right?: string|BackedEnum|Htmlable,
     *     move_right?: string|BackedEnum|Htmlable,
     *     swap?: string|BackedEnum|Htmlable,
     *     move_left?: string|BackedEnum|Htmlable,
     *     move_all_left?: string|BackedEnum|Htmlable,
     *     move_up?: string|BackedEnum|Htmlable,
     *     move_down?: string|BackedEnum|Htmlable,
     * }  $icons
     */
    public function icons(array|Closure $icons): static
    {
        $icons = $this->evaluate($icons);

        if (isset($icons['search'])) {
            $this->searchIcon = $icons['search'];
        }

        if (isset($icons['move_all_right'])) {
            $this->moveAllRightIcon = $icons['move_all_right'];
        }

        if (isset($icons['move_right'])) {
            $this->moveRightIcon = $icons['move_right'];
        }

        if (isset($icons['swap'])) {
            $this->swapIcon = $icons['swap'];
        }

        if (isset($icons['move_left'])) {
            $this->moveLeftIcon = $icons['move_left'];
        }

        if (isset($icons['move_all_left'])) {
            $this->moveAllLeftIcon = $icons['move_all_left'];
        }

        if (isset($icons['move_up'])) {
            $this->moveUpIcon = $icons['move_up'];
        }

        if (isset($icons['move_down'])) {
            $this->moveDownIcon = $icons['move_down'];
        }

        return $this;
    }

    public function searchIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->searchIcon = $icon;

        return $this;
    }

    public function moveAllRightIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->moveAllRightIcon = $icon;

        return $this;
    }

    public function moveRightIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->moveRightIcon = $icon;

        return $this;
    }

    public function swapIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->swapIcon = $icon;

        return $this;
    }

    public function moveLeftIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->moveLeftIcon = $icon;

        return $this;
    }

    public function moveAllLeftIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->moveAllLeftIcon = $icon;

        return $this;
    }

    public function moveUpIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->moveUpIcon = $icon;

        return $this;
    }

    public function moveDownIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->moveDownIcon = $icon;

        return $this;
    }

    public function getDefaultSearchIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveDefaultIcon('dual_listbox_search_icon', GravityIcon::Magnifier);
    }

    public function getDefaultMoveAllRightIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveDefaultIcon('dual_listbox_move_all_right_icon', GravityIcon::ArrowChevronRight);
    }

    public function getDefaultMoveRightIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveDefaultIcon('dual_listbox_move_right_icon', GravityIcon::ArrowRight);
    }

    public function getDefaultSwapIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveDefaultIcon('dual_listbox_swap_icon', GravityIcon::ArrowRightArrowLeft);
    }

    public function getDefaultMoveLeftIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveDefaultIcon('dual_listbox_move_left_icon', GravityIcon::ArrowLeft);
    }

    public function getDefaultMoveAllLeftIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveDefaultIcon('dual_listbox_move_all_left_icon', GravityIcon::ArrowChevronLeft);
    }

    public function getDefaultMoveUpIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveDefaultIcon('dual_listbox_move_up_icon', GravityIcon::ChevronUp);
    }

    public function getDefaultMoveDownIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveDefaultIcon('dual_listbox_move_down_icon', GravityIcon::ChevronDown);
    }

    public function getSearchIcon(): string|BackedEnum|Htmlable
    {
        return $this->evaluate($this->searchIcon) ?? $this->getDefaultSearchIcon();
    }

    public function getMoveAllRightIcon(): string|BackedEnum|Htmlable
    {
        return $this->evaluate($this->moveAllRightIcon) ?? $this->getDefaultMoveAllRightIcon();
    }

    public function getMoveRightIcon(): string|BackedEnum|Htmlable
    {
        return $this->evaluate($this->moveRightIcon) ?? $this->getDefaultMoveRightIcon();
    }

    public function getSwapIcon(): string|BackedEnum|Htmlable
    {
        return $this->evaluate($this->swapIcon) ?? $this->getDefaultSwapIcon();
    }

    public function getMoveLeftIcon(): string|BackedEnum|Htmlable
    {
        return $this->evaluate($this->moveLeftIcon) ?? $this->getDefaultMoveLeftIcon();
    }

    public function getMoveAllLeftIcon(): string|BackedEnum|Htmlable
    {
        return $this->evaluate($this->moveAllLeftIcon) ?? $this->getDefaultMoveAllLeftIcon();
    }

    public function getMoveUpIcon(): string|BackedEnum|Htmlable
    {
        return $this->evaluate($this->moveUpIcon) ?? $this->getDefaultMoveUpIcon();
    }

    public function getMoveDownIcon(): string|BackedEnum|Htmlable
    {
        return $this->evaluate($this->moveDownIcon) ?? $this->getDefaultMoveDownIcon();
    }

    protected function resolveDefaultIcon(string $configKey, string|BackedEnum|Htmlable $fallback): string|BackedEnum|Htmlable
    {
        $icon = config("filament-flex-fields.ui.{$configKey}");

        if (is_string($icon) && filled($icon)) {
            return $icon;
        }

        return $fallback;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['bordered', 'flat', 'faded'], true)) {
            throw new InvalidArgumentException("Dual listbox variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function getListHeight(): string
    {
        return (string) $this->evaluate($this->listHeight);
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->isSearchable);
    }

    public function isReorderable(): bool
    {
        return (bool) $this->evaluate($this->isReorderable);
    }

    public function isMoveOnDoubleClick(): bool
    {
        return (bool) $this->evaluate($this->moveOnDoubleClick);
    }

    public function showsTransferButtons(): bool
    {
        return (bool) $this->evaluate($this->showTransferButtons);
    }

    public function getAvailableLabel(): string
    {
        $label = $this->evaluate($this->availableLabel);

        return filled($label)
            ? (string) $label
            : __('filament-flex-fields::default.dual_listbox.available');
    }

    public function getSelectedLabel(): string
    {
        $label = $this->evaluate($this->selectedLabel);

        return filled($label)
            ? (string) $label
            : __('filament-flex-fields::default.dual_listbox.selected');
    }

    public function getMinItems(): ?int
    {
        $count = $this->evaluate($this->minItems);

        return $count === null ? null : (int) $count;
    }

    public function getMaxItems(): ?int
    {
        $count = $this->evaluate($this->maxItems);

        return $count === null ? null : (int) $count;
    }

    public function getExactItems(): ?int
    {
        $min = $this->getMinItems();
        $max = $this->getMaxItems();

        if ($min !== null && $min === $max) {
            return $min;
        }

        return null;
    }

    /**
     * @return array<string, array{label: string, description: ?string, disabled: bool}>
     */
    public function getNormalizedOptions(): array
    {
        if ($this->normalizedOptionsCache !== null) {
            return $this->normalizedOptionsCache;
        }

        $disabledOptions = collect($this->getDisabledOptions())->map(fn ($key): string => (string) $key);
        $normalized = [];

        foreach ($this->evaluate($this->options) as $value => $option) {
            $key = (string) $value;

            if (is_string($option)) {
                $normalized[$key] = [
                    'label' => $option,
                    'description' => null,
                    'disabled' => $disabledOptions->contains($key),
                ];

                continue;
            }

            if (is_array($option)) {
                $normalized[$key] = [
                    'label' => (string) ($option['label'] ?? $key),
                    'description' => filled($option['description'] ?? null) ? (string) $option['description'] : null,
                    'disabled' => (bool) ($option['disabled'] ?? false) || $disabledOptions->contains($key),
                ];
            }
        }

        return $this->normalizedOptionsCache = $normalized;
    }

    /**
     * @return list<array{value: string, label: string, description: ?string, disabled: bool}>
     */
    public function getOptionsForJs(): array
    {
        $options = $this->getNormalizedOptions();

        return $this->formatOptionsForJs(
            $options,
            lean: count($options) > self::LARGE_OPTIONS_THRESHOLD,
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getInitialOptionsForJs(): array
    {
        if (! $this->hasDeferredOptions()) {
            return $this->formatOptionsForJs($this->getNormalizedOptions(), lean: true);
        }

        return [];
    }

    public function hasDeferredOptions(): bool
    {
        return count($this->getNormalizedOptions()) > self::LARGE_OPTIONS_THRESHOLD;
    }

    /**
     * @param  array<string, array{label: string, description: ?string, disabled: bool}>  $options
     * @return list<array{value: string, label: string, description?: ?string, disabled?: bool}>
     */
    public function formatOptionsForJs(array $options, bool $lean = false): array
    {
        return collect($options)
            ->map(function (array $option, string $value) use ($lean): array {
                $formatted = [
                    'value' => $value,
                    'label' => $option['label'],
                ];

                if ($lean) {
                    return $formatted;
                }

                return [
                    ...$formatted,
                    'description' => $option['description'],
                    'disabled' => $option['disabled'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string, description: ?string, disabled: bool}>
     */
    protected function getFullOptionsForJs(): array
    {
        return collect($this->getNormalizedOptions())
            ->map(fn (array $option, string $value): array => [
                'value' => $value,
                'label' => $option['label'],
                'description' => $option['description'],
                'disabled' => $option['disabled'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string | int>
     */
    public function getDisabledOptions(): array
    {
        return Arr::wrap($this->evaluate($this->disabledOptions));
    }

    /**
     * @param  array<int|string, mixed>  $state
     * @return list<string>
     */
    public function normalizeState(array $state): array
    {
        $allowedKeys = array_keys($this->getNormalizedOptions());
        $normalized = [];

        foreach ($state as $value) {
            $key = (string) $value;

            if (! in_array($key, $allowedKeys, true)) {
                continue;
            }

            if ($this->getNormalizedOptions()[$key]['disabled'] ?? false) {
                continue;
            }

            if (in_array($key, $normalized, true)) {
                continue;
            }

            $normalized[] = $key;
        }

        return $normalized;
    }

    public function getVirtualScrollThreshold(): int
    {
        return self::VIRTUAL_SCROLL_THRESHOLD;
    }

    /**
     * @return array<string, string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-dual-listbox-field',
            'fff-dual-listbox-field--'.$this->getSize(),
            'fff-dual-listbox-field--'.$this->getVariant(),
        ];
    }

    public function getStateCast(): ?StateCast
    {
        return app(OptionsArrayStateCast::class, ['isNullable' => false]);
    }
}

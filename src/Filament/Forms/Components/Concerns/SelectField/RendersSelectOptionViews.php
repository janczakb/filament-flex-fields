<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;

/**
 * Custom Blade (or Closure) rendering per option — server-side equivalent of render props.
 *
 * @mixin SelectField
 */
trait RendersSelectOptionViews
{
    protected string|Closure|null $optionView = null;

    protected string|Closure|null $optionTriggerView = null;

    protected array|Closure $optionViewData = [];

    /**
     * Blade view or Closure for dropdown rows (and trigger when no trigger view is set).
     *
     * View / Closure receives: `$option`, `$layout`, `$field`, `$value`, `$label`, `$description`, plus `optionViewData()`.
     * Closure may return `string`, `Htmlable`, `View`, or a view name to render with the same data.
     */
    public function optionView(string|Closure $view): static
    {
        $this->optionView = $view;
        $this->allowHtml();
        $this->forgetUsesRichOptionHtmlCache();

        return $this;
    }

    /**
     * Optional Blade view or Closure for the closed trigger and multi-select chips.
     * Falls back to `optionView()` when not set.
     */
    public function optionTriggerView(string|Closure|null $view): static
    {
        $this->optionTriggerView = $view;
        $this->allowHtml();

        return $this;
    }

    /**
     * Extra data merged into every option view invocation.
     *
     * @param  array<string, mixed>|Closure(): array<string, mixed>  $data
     */
    public function optionViewData(array|Closure $data): static
    {
        $this->optionViewData = $data;

        return $this;
    }

    public function hasOptionView(): bool
    {
        return $this->optionView !== null;
    }

    public function getOptionView(): string|Closure|null
    {
        return $this->optionView;
    }

    public function getOptionTriggerView(): string|Closure|null
    {
        return $this->optionTriggerView;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptionViewData(array $option, string $layout): array
    {
        $extra = $this->optionViewData === [] ? [] : $this->evaluate($this->optionViewData);

        if (! is_array($extra)) {
            throw new InvalidArgumentException('SelectField optionViewData must resolve to an array.');
        }

        return array_merge([
            'option' => $option,
            'layout' => $layout,
            'field' => $this,
            'value' => $option['value'],
            'label' => $option['label'],
            'description' => $option['description'] ?? null,
            'image' => $option['image'] ?? null,
            'icon' => $option['icon'] ?? null,
            'badge' => $option['badge'] ?? null,
            'badgeColor' => $option['badge_color'] ?? 'primary',
            'chipLabel' => $option['chip_label'] ?? null,
            'disabled' => (bool) ($option['disabled'] ?? false),
        ], $extra);
    }

    /**
     * @param  array{
     *     value: string|int,
     *     label: string,
     *     description: ?string,
     *     icon: mixed,
     *     image: ?string,
     *     badge: ?string,
     *     badge_color: ?string,
     *     chip_label: ?string,
     *     disabled: bool,
     * }  $option
     */
    protected function renderOptionView(array $option, string $layout = 'list'): string
    {
        $view = in_array($layout, ['trigger', 'chip'], true)
            ? ($this->getOptionTriggerView() ?? $this->getOptionView())
            : $this->getOptionView();

        if ($view === null) {
            throw new InvalidArgumentException('SelectField optionView is not configured.');
        }

        $data = $this->getOptionViewData($option, $layout);

        if ($view instanceof Closure) {
            $result = $this->evaluate($view, $data);

            return $this->normalizeOptionViewOutput($result, $data);
        }

        return view($view, $data)->render();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function normalizeOptionViewOutput(mixed $result, array $data): string
    {
        if ($result instanceof Htmlable) {
            return $result->toHtml();
        }

        if ($result instanceof View) {
            return $result->render();
        }

        if (! is_string($result)) {
            throw new InvalidArgumentException('SelectField optionView must return string, Htmlable, View, or a view name.');
        }

        if ($result !== '' && ! str_contains($result, '<') && view()->exists($result)) {
            return view($result, $data)->render();
        }

        return $result;
    }

    protected function resolveOptionViewLayout(bool $compact): string
    {
        if ($compact) {
            if ($this->shouldUseRichListTriggerDisplay()) {
                return $this->getOptionLayout() === 'grid' ? 'grid' : 'list';
            }

            return 'trigger';
        }

        if ($this->getOptionLayout() === 'grid') {
            return 'grid';
        }

        return 'list';
    }
}

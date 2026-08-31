<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\HtmlSanitizer;
use Bjanczak\FilamentFlexFields\Support\Select\RichOptionJsTransformer;
use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

/**
 * @mixin SelectField
 */
trait TransformsSelectRichOptions
{
    protected ?RichOptionJsTransformer $richOptionJsTransformer = null;

    protected ?HtmlSanitizer $htmlSanitizer = null;

    /**
     * @param  array<string | int, string | array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    public function transformRichOptionsForJs(array $options): array
    {
        $this->forgetUsesRichOptionHtmlCache();

        if (
            $this->usesRichOptions === null
            && ! $this->hasOptionView()
            && $this->getOptionLayout() !== 'grid'
            && ! $this->isHtmlAllowed()
        ) {
            $this->usesRichOptionHtmlResolved = $this->optionsContainRichShape($options);
        }

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
     *     chip_label: ?string,
     *     disabled: bool,
     * }
     */
    protected function normalizeOption(string|int $value, array|string $label): array
    {
        if (is_string($label)) {
            $disabled = $this->isOptionDisabled($value, $label);

            return [
                'value' => $value,
                'label' => $label,
                'description' => null,
                'icon' => null,
                'image' => null,
                'badge' => null,
                'badge_color' => null,
                'chip_label' => null,
                'disabled' => $disabled,
                'isDisabled' => $disabled,
            ];
        }

        $disabled = (bool) ($label['disabled'] ?? $label['isDisabled'] ?? $this->isOptionDisabled($value, (string) ($label['label'] ?? $value)));

        return [
            'value' => $value,
            'label' => (string) ($label['label'] ?? $value),
            'description' => filled($label['description'] ?? $label['desc'] ?? null)
                ? (string) ($label['description'] ?? $label['desc'])
                : null,
            'icon' => $label['icon'] ?? null,
            'image' => filled($label['image'] ?? null) ? (string) $label['image'] : null,
            'badge' => filled($label['badge'] ?? null) ? (string) $label['badge'] : null,
            'badge_color' => filled($label['badge_color'] ?? null) ? (string) $label['badge_color'] : null,
            'chip_label' => filled($label['chip_label'] ?? $label['chipLabel'] ?? null)
                ? (string) ($label['chip_label'] ?? $label['chipLabel'])
                : null,
            'disabled' => $disabled,
            'isDisabled' => $disabled,
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
     *     chip_label: ?string,
     *     disabled: bool,
     * }  $option
     */
    protected function formatOptionLabelForJs(array $option, bool $compact = false): string
    {
        if ($this->hasOptionView()) {
            if ($compact && filled($option['chip_label'] ?? null)) {
                $chipOption = $option;
                $chipOption['label'] = (string) $option['chip_label'];

                return $this->renderOptionView($chipOption, layout: 'chip');
            }

            return $this->renderOptionView($option, layout: $this->resolveOptionViewLayout($compact));
        }

        if ($compact && filled($option['chip_label'] ?? null)) {
            $chipLabel = $option['chip_label'];

            return $this->isHtmlAllowed()
                ? (string) ($this->sanitizeUserProvidedHtml($chipLabel) ?? '')
                : $chipLabel;
        }

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
        if ($this->hasOptionView()) {
            return $this->renderOptionView($option, $layout);
        }

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
                    $labels[(string) $groupedValue] = $this->formatOptionLabelForJs(
                        $this->normalizeOption($groupedValue, $groupedLabel),
                        compact: ! $this->shouldUseRichListTriggerDisplay(),
                    );
                }

                continue;
            }

            $labels[(string) $value] = $this->formatOptionLabelForJs(
                $this->normalizeOption($value, $label),
                compact: ! $this->shouldUseRichListTriggerDisplay(),
            );
        }

        return $labels;
    }
}

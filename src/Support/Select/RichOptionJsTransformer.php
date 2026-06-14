<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Select;

use Bjanczak\FilamentFlexFields\Support\HtmlSanitizer;
use Closure;

class RichOptionJsTransformer
{
    public function __construct(
        protected HtmlSanitizer $htmlSanitizer,
    ) {}

    /**
     * @param  array<string | int, string | array<string, mixed>>  $options
     * @param  Closure(string|int, array|string): array<string, mixed>  $normalizeOption
     * @param  Closure(array<string, mixed>, bool): string  $formatOptionLabelForJs
     * @param  Closure(array<string, mixed>): bool  $isOptionGroupArray
     * @param  Closure(array<string, mixed>): bool  $isRichOptionArray
     * @param  Closure(string|null): ?string  $sanitizeHtml
     * @return list<array<string, mixed>>
     */
    public function transform(
        array $options,
        Closure $normalizeOption,
        Closure $formatOptionLabelForJs,
        Closure $isOptionGroupArray,
        Closure $isRichOptionArray,
        Closure $sanitizeHtml,
        string $optionLayout = 'list',
    ): array {
        $transformed = [];

        foreach ($options as $value => $label) {
            if (is_array($label) && $isOptionGroupArray($label)) {
                $transformed[] = [
                    'label' => (string) $value,
                    'options' => $this->transform(
                        $label,
                        $normalizeOption,
                        $formatOptionLabelForJs,
                        $isOptionGroupArray,
                        $isRichOptionArray,
                        $sanitizeHtml,
                        $optionLayout,
                    ),
                ];

                continue;
            }

            $normalized = $normalizeOption($value, $label);
            $dropdownLabel = $sanitizeHtml($formatOptionLabelForJs($normalized));
            $triggerLabel = $sanitizeHtml($formatOptionLabelForJs($normalized, compact: true));

            $option = [
                'label' => $dropdownLabel,
                'value' => (string) $value,
                'isDisabled' => $normalized['disabled'],
            ];

            if ($triggerLabel !== $dropdownLabel) {
                $option['triggerLabel'] = $triggerLabel;
            }

            if ($this->isClientRenderable($normalized, $optionLayout)) {
                $option['fffClientRender'] = true;

                if (filled($normalized['description'] ?? null)) {
                    $option['description'] = $normalized['description'];
                }

                if (filled($normalized['image'] ?? null)) {
                    $option['image'] = $normalized['image'];
                }

                if (($normalized['verified'] ?? false) === true) {
                    $option['verified'] = true;
                }
            }

            $transformed[] = $option;
        }

        return $transformed;
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
     *     disabled: bool,
     *     verified?: bool,
     * }  $normalized
     */
    public function isClientRenderable(array $normalized, string $optionLayout = 'list'): bool
    {
        if ($optionLayout === 'grid') {
            return false;
        }

        if (filled($normalized['icon'] ?? null) || filled($normalized['badge'] ?? null)) {
            return false;
        }

        return filled($normalized['description'] ?? null)
            || filled($normalized['image'] ?? null)
            || (($normalized['verified'] ?? false) === true);
    }

    public function sanitizeHtml(?string $html, bool $shouldSanitize): ?string
    {
        if (! $shouldSanitize || $html === null) {
            return $html;
        }

        return $this->htmlSanitizer->sanitize($html);
    }
}

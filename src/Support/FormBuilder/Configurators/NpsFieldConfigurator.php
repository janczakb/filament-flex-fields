<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NpsField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class NpsFieldConfigurator implements FieldConfigurator
{
    /** @var array<int, string> */
    private const EMOJI_OPTIONS = [
        0 => 'Awful',
        1 => 'Poor',
        2 => 'Neutral',
        3 => 'Good',
        4 => 'Excellent',
    ];

    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof NpsField);

        return $this->configureNpsField($field, $config);
    }

    public function configureNpsField(NpsField $field, array $config): NpsField
    {
        $variant = (string) ($config['variant'] ?? 'pills');
        $options = $variant === 'emojis'
            ? $this->resolveEmojiOptions($config)
            : $this->resolveNumericOptions($config);

        $field = $field
            ->options($options)
            ->variant($variant)
            ->colorCoded((bool) ($config['color_coded'] ?? false))
            ->size((string) ($config['size'] ?? 'md'));

        if ($variant !== 'emojis') {
            if (array_key_exists('min_label', $config) && $config['min_label'] !== null && $config['min_label'] !== '') {
                $field->minLabel((string) $config['min_label']);
            }

            if (array_key_exists('max_label', $config) && $config['max_label'] !== null && $config['max_label'] !== '') {
                $field->maxLabel((string) $config['max_label']);
            }
        }

        if (isset($config['rounding']) && is_string($config['rounding']) && $config['rounding'] !== '') {
            $field->rounding($config['rounding']);
        }

        if (isset($config['disabled_options']) && is_array($config['disabled_options'])) {
            $field->disabledOptions($config['disabled_options']);
        }

        if (isset($config['icons']) && is_array($config['icons'])) {
            $field->icons($config['icons']);
        }

        return $field;
    }

    /**
     * Always five moods (0–4); labels come from Studio options when present.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    private function resolveEmojiOptions(array $config): array
    {
        $normalized = $this->normalizeOptions($config['options'] ?? null);
        $options = self::EMOJI_OPTIONS;

        foreach ($options as $key => $defaultLabel) {
            $label = $normalized[$key] ?? $normalized[(string) $key] ?? null;

            if (is_string($label) && trim($label) !== '') {
                $options[$key] = trim($label);
            }
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string|int, string>
     */
    private function resolveNumericOptions(array $config): array
    {
        $options = $this->normalizeOptions($config['options'] ?? null);

        if ($options !== []) {
            return $options;
        }

        if (array_key_exists('scale_max', $config) && $config['scale_max'] !== null && $config['scale_max'] !== '') {
            $scaleMax = $this->normalizeScaleMax($config['scale_max']);

            return array_combine(range(0, $scaleMax), range(0, $scaleMax));
        }

        return array_combine(range(0, 10), range(0, 10));
    }

    private function normalizeScaleMax(mixed $raw): int
    {
        $value = is_numeric($raw) ? (int) $raw : 10;

        return max(5, min(10, $value));
    }

    /**
     * Accept Studio list rows `{value,label}` or Filament assoc maps.
     *
     * @return array<string|int, string>
     */
    private function normalizeOptions(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        if (array_is_list($raw)) {
            $options = [];

            foreach ($raw as $index => $row) {
                if (is_string($row) || is_int($row)) {
                    $options[(string) $row] = (string) $row;

                    continue;
                }

                if (! is_array($row)) {
                    continue;
                }

                $label = (string) ($row['label'] ?? $row['value'] ?? "Option {$index}");
                $value = $row['value'] ?? $label;
                $options[is_int($value) ? $value : (string) $value] = $label;
            }

            return $options;
        }

        $options = [];

        foreach ($raw as $key => $label) {
            if (is_array($label)) {
                $resolvedLabel = (string) ($label['label'] ?? $label['value'] ?? $key);
                $resolvedValue = $label['value'] ?? $key;
                $options[is_int($resolvedValue) ? $resolvedValue : (string) $resolvedValue] = $resolvedLabel;

                continue;
            }

            $options[is_int($key) ? $key : (string) $key] = (string) $label;
        }

        return $options;
    }
}

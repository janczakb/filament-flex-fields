<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ColorSwatchField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class ColorSwatchFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof ColorSwatchField);

        return $this->configureColorSwatchField($field, $config);
    }

    public function configureColorSwatchField(ColorSwatchField $field, array $config): ColorSwatchField
    {
        $field = $field->colors($this->normalizeColors($config['colors'] ?? []));

        if (isset($config['section_label'])) {
            $field->sectionLabel($config['section_label']);
        }

        if (isset($config['section_icon'])) {
            $field->sectionIcon($config['section_icon']);
        }

        if (isset($config['size'])) {
            $field->size($config['size']);
        }

        if (array_key_exists('tooltips', $config)) {
            $tooltips = $config['tooltips'];

            $field->tooltips(is_array($tooltips) ? $tooltips : (bool) $tooltips);
        }

        return $field;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeColors(mixed $colors): array
    {
        if (! is_array($colors) || $colors === []) {
            return [];
        }

        if (array_is_list($colors)) {
            $normalized = [];

            foreach ($colors as $index => $row) {
                if (is_string($row) || is_int($row)) {
                    $key = (string) $row;

                    if ($key !== '') {
                        $normalized[$key] = $key;
                    }

                    continue;
                }

                if (! is_array($row)) {
                    continue;
                }

                $hex = (string) ($row['label'] ?? $row['color'] ?? $row['value'] ?? '');
                $key = (string) ($row['value'] ?? $hex);

                if ($key === '' || $hex === '') {
                    continue;
                }

                $normalized[$key] = $hex;
            }

            return $normalized;
        }

        $normalized = [];

        foreach ($colors as $key => $hex) {
            if (is_string($hex) || is_int($hex)) {
                $normalized[(string) $key] = (string) $hex;
            }
        }

        return $normalized;
    }
}

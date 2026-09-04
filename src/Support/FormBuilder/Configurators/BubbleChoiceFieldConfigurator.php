<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\BubbleChoiceField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class BubbleChoiceFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof BubbleChoiceField);

        return $this->configureBubbleChoiceField($field, $config);
    }

    public function configureBubbleChoiceField(BubbleChoiceField $field, array $config): BubbleChoiceField
    {
        $field = $field
            ->options($config['options'] ?? [])
            ->size($config['size'] ?? config('filament-flex-fields.ui.bubble_choice_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.bubble_choice_variant', 'soft'));

        if (isset($config['arena_height'])) {
            $field->arenaHeight((string) $config['arena_height']);
        }

        if (isset($config['selected_shape'])) {
            $field->selectedShape((string) $config['selected_shape']);
        }

        if (isset($config['bubble_color'])) {
            $field->bubbleColor((string) $config['bubble_color']);
        }

        if (isset($config['selected_bubble_color'])) {
            $field->selectedBubbleColor((string) $config['selected_bubble_color']);
        }

        if (isset($config['arena_color'])) {
            $field->arenaColor((string) $config['arena_color']);
        }

        $layoutKeys = [
            'bubble_size' => 'size',
            'min_size' => 'minSize',
            'gutter' => 'gutter',
            'num_cols' => 'numCols',
            'fringe_width' => 'fringeWidth',
            'y_radius' => 'yRadius',
            'x_radius' => 'xRadius',
            'corner_radius' => 'cornerRadius',
            'compact' => 'compact',
            'gravitation' => 'gravitation',
            'provide_props' => 'provideProps',
        ];

        $layout = [];

        foreach ($layoutKeys as $configKey => $layoutKey) {
            if (array_key_exists($configKey, $config)) {
                $layout[$layoutKey] = $config[$configKey];
            }
        }

        if ($layout !== []) {
            $field->layoutOptions($layout);
        }

        if (isset($config['disabled_options']) && is_array($config['disabled_options'])) {
            $field->disabledOptions($config['disabled_options']);
        }

        if (array_key_exists('min_items', $config)) {
            $field->minItems($config['min_items']);
        }

        if (array_key_exists('max_items', $config)) {
            $field->maxItems($config['max_items']);
        }

        if (array_key_exists('exact_items', $config)) {
            $field->exactItems($config['exact_items']);
        }

        return $field;
    }
}

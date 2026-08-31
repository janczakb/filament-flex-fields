<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ImageChoiceCards;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\Concerns\NormalizesStudioChoiceOptions;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class ImageChoiceCardsFieldConfigurator implements FieldConfigurator
{
    use NormalizesStudioChoiceOptions;

    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof ImageChoiceCards);

        return $this->configureImageChoiceCardsField($field, $config);
    }

    public function configureImageChoiceCardsField(ImageChoiceCards $field, array $config): ImageChoiceCards
    {
        $columns = $config['columns'] ?? $config['grid_columns'] ?? 4;

        if (is_string($columns) && is_numeric($columns)) {
            $columns = (int) $columns;
        }

        $field = $field
            ->options($this->normalizeStudioChoiceOptions(
                $config['options'] ?? [],
                ['image', 'alt', 'disabled'],
            ))
            ->multiple((bool) ($config['multiple'] ?? false))
            ->gridColumns($columns)
            ->size($config['size'] ?? config('filament-flex-fields.ui.choice_cards_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.choice_cards_variant', 'default'))
            ->color(filled($config['color'] ?? null) ? $config['color'] : null)
            ->imageAspectRatio($config['image_aspect_ratio'] ?? '3/4')
            ->imageFit($config['image_fit'] ?? 'cover')
            ->ripple((bool) ($config['ripple'] ?? false));

        if (isset($config['rounding'])) {
            $field->rounding($config['rounding']);
        }

        if (isset($config['indicator'])) {
            $field->indicator($config['indicator']);
        }

        if (isset($config['disabled_options']) && is_array($config['disabled_options'])) {
            $field->disabledOptions($config['disabled_options']);
        }

        if (array_key_exists('min_selections', $config)) {
            $field->minSelections($config['min_selections']);
        }

        if (array_key_exists('max_selections', $config)) {
            $field->maxSelections($config['max_selections']);
        }

        if (array_key_exists('exact_selections', $config)) {
            $field->exactSelections($config['exact_selections']);
        }

        return $field;
    }
}

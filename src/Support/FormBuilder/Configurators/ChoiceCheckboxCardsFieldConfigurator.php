<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCheckboxCards;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class ChoiceCheckboxCardsFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof ChoiceCheckboxCards);

        return $this->configureChoiceCheckboxCardsField($field, $config);
    }

    public function configureChoiceCheckboxCardsField(ChoiceCheckboxCards $field, array $config): ChoiceCheckboxCards
    {
        $field = $field
            ->options($config['options'] ?? [])
            ->layout($config['layout'] ?? 'stack')
            ->gridColumns($config['grid_columns'] ?? $config['columns'] ?? 1)
            ->size($config['size'] ?? config('filament-flex-fields.ui.choice_cards_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.choice_cards_variant', 'default'))
            ->color($config['color'] ?? null)
            ->ripple((bool) ($config['ripple'] ?? false));

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

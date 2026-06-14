<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class ChoiceCardsFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof ChoiceCards);

        return $this->configureChoiceCardsField($field, $config);
    }

    public function configureChoiceCardsField(ChoiceCards $field, array $config): ChoiceCards
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

        return $field;
    }
}

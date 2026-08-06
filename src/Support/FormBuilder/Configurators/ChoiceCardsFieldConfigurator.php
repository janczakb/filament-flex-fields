<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\Concerns\NormalizesStudioChoiceOptions;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class ChoiceCardsFieldConfigurator implements FieldConfigurator
{
    use NormalizesStudioChoiceOptions;

    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof ChoiceCards);

        return $this->configureChoiceCardsField($field, $config);
    }

    public function configureChoiceCardsField(ChoiceCards $field, array $config): ChoiceCards
    {
        $gridColumns = $config['grid_columns'] ?? $config['columns'] ?? 1;

        if (is_string($gridColumns) && is_numeric($gridColumns)) {
            $gridColumns = (int) $gridColumns;
        }

        $field = $field
            ->options($this->normalizeStudioChoiceOptions(
                $config['options'] ?? [],
                ['description', 'price', 'price_suffix', 'suffix', 'meta', 'icon', 'badge', 'badge_color', 'disabled'],
            ))
            ->layout($config['layout'] ?? 'stack')
            ->gridColumns($gridColumns)
            ->size($config['size'] ?? config('filament-flex-fields.ui.choice_cards_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.choice_cards_variant', 'default'))
            ->color(filled($config['color'] ?? null) ? $config['color'] : null)
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

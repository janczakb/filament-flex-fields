<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class RatingFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof RatingField);

        return $this->configureRatingField($field, $config);
    }

    public function configureRatingField(RatingField $field, array $config): RatingField
    {
        $field = $field
            ->stars($config['max'] ?? 5)
            ->size($config['size'] ?? config('filament-flex-fields.ui.rating_size', 'md'))
            ->color($config['color'] ?? 'warning');

        if (isset($config['icon'])) {
            $field->icon($config['icon']);
        }

        if (array_key_exists('show_value', $config)) {
            $field->showValue((bool) $config['show_value']);
        }

        if (array_key_exists('read_only', $config) && (bool) $config['read_only']) {
            $field->readOnly();
        }

        return $field;
    }
}

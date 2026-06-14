<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class CurrencyFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof CurrencyField);

        return $this->configureCurrencyField($field, $config);
    }

    public function configureCurrencyField(CurrencyField $field, array $config): CurrencyField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.currency_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.currency_variant', 'primary'))
            ->currency($config['currency'] ?? 'PLN');

        if (array_key_exists('locale', $config) && filled($config['locale'])) {
            $field->locale($config['locale']);
        }

        if (array_key_exists('currencies', $config) && is_array($config['currencies'])) {
            $field->currencies($config['currencies']);
        }

        if (array_key_exists('min', $config)) {
            $field->min($config['min']);
        }

        if (array_key_exists('max', $config)) {
            $field->max($config['max']);
        }

        if (array_key_exists('allow_negative', $config)) {
            $field->allowNegative((bool) $config['allow_negative']);
        }

        if (array_key_exists('animated', $config)) {
            $field->animated((bool) $config['animated']);
        }

        if (array_key_exists('commit_decimals_on_blur', $config)) {
            $field->commitDecimalsOnBlur((bool) $config['commit_decimals_on_blur']);
        }

        if (array_key_exists('searchable', $config)) {
            $field->searchable((bool) $config['searchable']);
        }

        return $field;
    }
}

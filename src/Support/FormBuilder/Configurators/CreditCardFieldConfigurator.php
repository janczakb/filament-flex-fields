<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CreditCardField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class CreditCardFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof CreditCardField);

        return $this->configureCreditCardField($field, $config);
    }

    public function configureCreditCardField(CreditCardField $field, array $config): CreditCardField
    {
        $variant = (string) ($config['variant'] ?? config('filament-flex-fields.ui.credit_card_variant', 'midnight'));
        $inputVariant = (string) ($config['input_variant'] ?? config('filament-flex-fields.ui.credit_card_input_variant', 'primary'));
        $size = (string) ($config['size'] ?? config('filament-flex-fields.ui.credit_card_size', 'md'));

        if (! in_array($variant, ['midnight', 'ocean', 'sunset', 'slate'], true)) {
            $variant = 'midnight';
        }

        if (! in_array($inputVariant, ['primary', 'secondary', 'flat'], true)) {
            $inputVariant = 'primary';
        }

        if (! in_array($size, ['sm', 'md', 'lg'], true)) {
            $size = 'md';
        }

        $field = $field
            ->size($size)
            ->variant($variant)
            ->inputVariant($inputVariant);

        if (array_key_exists('flip_on_cvv_focus', $config)) {
            $field->flipOnCvvFocus((bool) $config['flip_on_cvv_focus']);
        }

        if (array_key_exists('mark', $config) && filled($config['mark'])) {
            $field->mark((string) $config['mark']);
        }

        if (array_key_exists('number_label', $config)) {
            $field->numberLabel($config['number_label']);
        }

        if (array_key_exists('name_label', $config)) {
            $field->nameLabel($config['name_label']);
        }

        if (array_key_exists('expiry_label', $config)) {
            $field->expiryLabel($config['expiry_label']);
        }

        if (array_key_exists('cvv_label', $config)) {
            $field->cvvLabel($config['cvv_label']);
        }

        return $field;
    }
}

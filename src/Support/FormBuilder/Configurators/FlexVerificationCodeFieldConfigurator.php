<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class FlexVerificationCodeFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof FlexVerificationCode);

        return $this->configureFlexVerificationCodeField($field, $config);
    }

    public function configureFlexVerificationCodeField(FlexVerificationCode $field, array $config): FlexVerificationCode
    {
        $field = $field
            ->length($config['length'] ?? 6)
            ->allowedCharacters($config['allowed_characters'] ?? 'numeric')
            ->size($config['size'] ?? 'md')
            ->color($config['color'] ?? 'primary');

        if (array_key_exists('groups', $config)) {
            $field->groups($config['groups']);
        }

        if (array_key_exists('group_separator', $config)) {
            $field->groupSeparator($config['group_separator']);
        }

        if (array_key_exists('auto_submit', $config)) {
            $field->autoSubmit((bool) $config['auto_submit']);
        }

        if (isset($config['auto_submit_method']) && filled($config['auto_submit_method'])) {
            $field->autoSubmitMethod($config['auto_submit_method']);
        }

        if (array_key_exists('loading', $config)) {
            $field->loading((bool) $config['loading']);
        }

        return $field;
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class FlexRadiolistFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof FlexRadiolist);

        return $this->configureFlexRadiolistField($field, $config);
    }

    public function configureFlexRadiolistField(FlexRadiolist $field, array $config): FlexRadiolist
    {
        $field = $field
            ->options($config['options'] ?? [])
            ->size($config['size'] ?? config('filament-flex-fields.ui.flex_radiolist_size', 'md'));

        if (isset($config['variant'])) {
            $field->variant($config['variant']);
        }

        if (isset($config['icons']) && is_array($config['icons'])) {
            $field->icons($config['icons']);
        }

        if (isset($config['descriptions']) && is_array($config['descriptions'])) {
            $field->descriptions($config['descriptions']);
        }

        if (isset($config['desc']) && is_array($config['desc'])) {
            $field->descriptions($config['desc']);
        }

        if (isset($config['disabled_options']) && is_array($config['disabled_options'])) {
            $field->disabledOptions($config['disabled_options']);
        }

        if (isset($config['color'])) {
            $field->color($config['color']);
        }

        return $field;
    }
}

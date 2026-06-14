<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class SwitchFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof SwitchField);

        return $this->configureSwitchField($field, $config);
    }

    public function configureSwitchField(SwitchField $field, array $config): SwitchField
    {
        $field = $field
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.switch_variant', 'default'))
            ->layout($config['layout'] ?? 'row')
            ->size($config['size'] ?? config('filament-flex-fields.ui.switch_size', 'md'))
            ->color($config['color'] ?? null);

        if (isset($config['badge'])) {
            $field->badge($config['badge']);
        }

        if (isset($config['badge_color'])) {
            $field->badgeColor($config['badge_color']);
        }

        if (isset($config['description'])) {
            $field->description($config['description']);
        }

        if (isset($config['on_color'])) {
            $field->onColor($config['on_color']);
        }

        if (isset($config['off_color'])) {
            $field->offColor($config['off_color']);
        }

        if (isset($config['on_icon'])) {
            $field->onIcon($config['on_icon']);
        }

        if (isset($config['off_icon'])) {
            $field->offIcon($config['off_icon']);
        }

        if (isset($config['label_position'])) {
            $field->labelPosition($config['label_position']);
        }

        if (array_key_exists('ripple', $config)) {
            $field->ripple((bool) $config['ripple']);
        }

        if (array_key_exists('compact', $config)) {
            $field->compact((bool) $config['compact']);
        }

        if (array_key_exists('inline', $config)) {
            $field->inline((bool) $config['inline']);
        }

        if (array_key_exists('inline_with_label', $config) && (bool) $config['inline_with_label']) {
            $field->inlineWithLabel();
        }

        return $field;
    }
}

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
            ->color(filled($config['color'] ?? null) ? $config['color'] : null);

        if (filled($config['badge'] ?? null)) {
            $field->badge((string) $config['badge']);
        }

        if (filled($config['badge_color'] ?? null)) {
            $field->badgeColor((string) $config['badge_color']);
        }

        if (filled($config['description'] ?? null)) {
            $field->description((string) $config['description']);
        }

        if (filled($config['on_color'] ?? null)) {
            $field->onColor((string) $config['on_color']);
        }

        if (filled($config['off_color'] ?? null)) {
            $field->offColor((string) $config['off_color']);
        }

        if (filled($config['on_icon'] ?? null)) {
            $field->onIcon((string) $config['on_icon']);
        }

        if (filled($config['off_icon'] ?? null)) {
            $field->offIcon((string) $config['off_icon']);
        }

        if (isset($config['label_position']) && is_string($config['label_position']) && $config['label_position'] !== '') {
            $field->labelPosition($config['label_position']);
        }

        if (array_key_exists('ripple', $config)) {
            $field->ripple((bool) $config['ripple']);
        }

        if (array_key_exists('compact', $config)) {
            $field->compact((bool) $config['compact']);
        }

        if (array_key_exists('inline_with_label', $config) && (bool) $config['inline_with_label']) {
            $field->inlineWithLabel();
        } elseif (array_key_exists('inline', $config)) {
            $field->inline((bool) $config['inline']);
        }

        if (array_key_exists('disabled', $config)) {
            $field->disabled((bool) $config['disabled']);
        }

        return $field;
    }
}

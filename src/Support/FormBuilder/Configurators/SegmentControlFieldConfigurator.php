<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\Concerns\NormalizesStudioChoiceOptions;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class SegmentControlFieldConfigurator implements FieldConfigurator
{
    use NormalizesStudioChoiceOptions;

    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof SegmentControl);

        return $this->configureSegmentControlField($field, $config);
    }

    public function configureSegmentControlField(SegmentControl $field, array $config): SegmentControl
    {
        $field = $field
            ->options($this->normalizeStudioChoiceOptions($config['options'] ?? [], ['icon', 'disabled', 'tooltip']))
            ->size($config['size'] ?? config('filament-flex-fields.ui.segment_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.segment_variant', 'default'))
            ->fullWidth((bool) ($config['full_width'] ?? false));

        if (array_key_exists('separators', $config)) {
            $field->separators((bool) $config['separators']);
        }

        if (array_key_exists('icon_only', $config)) {
            $field->iconOnly((bool) $config['icon_only']);
        }

        if (array_key_exists('expand_selected_label', $config)) {
            $field->expandSelectedLabel((bool) $config['expand_selected_label']);
        }

        if (isset($config['icons']) && is_array($config['icons'])) {
            $field->icons($config['icons']);
        }

        if (isset($config['disabled_options']) && is_array($config['disabled_options'])) {
            $field->disabledOptions($config['disabled_options']);
        }

        if (isset($config['color']) && filled($config['color'])) {
            $field->color($config['color']);
        }

        return $field;
    }
}

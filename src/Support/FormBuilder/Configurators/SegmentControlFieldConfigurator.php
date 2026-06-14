<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class SegmentControlFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof SegmentControl);

        return $this->configureSegmentControlField($field, $config);
    }

    public function configureSegmentControlField(SegmentControl $field, array $config): SegmentControl
    {
        return $field
            ->options($config['options'] ?? [])
            ->size($config['size'] ?? config('filament-flex-fields.ui.segment_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.segment_variant', 'default'))
            ->fullWidth((bool) ($config['full_width'] ?? false));
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexChecklist;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class FlexChecklistFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof FlexChecklist);

        return $this->configureFlexChecklistField($field, $config);
    }

    public function configureFlexChecklistField(FlexChecklist $field, array $config): FlexChecklist
    {
        $field = $field
            ->options($config['options'] ?? [])
            ->size($config['size'] ?? config('filament-flex-fields.ui.flex_checklist_size', 'md'));

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

        if (array_key_exists('min_selections', $config)) {
            $field->minSelections($config['min_selections']);
        }

        if (array_key_exists('max_selections', $config)) {
            $field->maxSelections($config['max_selections']);
        }

        if (array_key_exists('exact_selections', $config)) {
            $field->exactSelections($config['exact_selections']);
        }

        return $field;
    }
}

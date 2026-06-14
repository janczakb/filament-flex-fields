<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\DualListboxField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class DualListboxFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof DualListboxField);

        return $this->configureDualListboxField($field, $config);
    }

    public function configureDualListboxField(DualListboxField $field, array $config): DualListboxField
    {
        $field = $field
            ->options($config['options'] ?? [])
            ->size($config['size'] ?? config('filament-flex-fields.ui.dual_listbox_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.dual_listbox_variant', 'bordered'));

        if (isset($config['list_height'])) {
            $field->listHeight((string) $config['list_height']);
        }

        if (array_key_exists('searchable', $config)) {
            $field->searchable((bool) $config['searchable']);
        }

        if (array_key_exists('reorderable', $config)) {
            $field->reorderable((bool) $config['reorderable']);
        }

        if (array_key_exists('move_on_double_click', $config)) {
            $field->moveOnDoubleClick((bool) $config['move_on_double_click']);
        }

        if (array_key_exists('show_transfer_buttons', $config)) {
            $field->showTransferButtons((bool) $config['show_transfer_buttons']);
        }

        if (isset($config['available_label'])) {
            $field->availableLabel($config['available_label']);
        }

        if (isset($config['selected_label'])) {
            $field->selectedLabel($config['selected_label']);
        }

        if (isset($config['disabled_options']) && is_array($config['disabled_options'])) {
            $field->disabledOptions($config['disabled_options']);
        }

        if (array_key_exists('min_items', $config)) {
            $field->minItems($config['min_items']);
        }

        if (array_key_exists('max_items', $config)) {
            $field->maxItems($config['max_items']);
        }

        if (array_key_exists('exact_items', $config)) {
            $field->exactItems($config['exact_items']);
        }

        if (isset($config['icons']) && is_array($config['icons'])) {
            $field->icons($config['icons']);
        }

        return $field;
    }
}

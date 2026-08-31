<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\Concerns\NormalizesStudioChoiceOptions;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class SelectFieldConfigurator implements FieldConfigurator
{
    use NormalizesStudioChoiceOptions;

    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof SelectField);

        return $this->configureSelectField($field, $config);
    }

    public function configureSelectField(SelectField $field, array $config): SelectField
    {
        $field = $field
            // Studio keeps list rows `{ value, label }`; SelectField expects `value => label`.
            ->options($this->normalizeStudioLabeledList($config['options'] ?? []))
            ->size($config['size'] ?? config('filament-flex-fields.ui.select_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.select_variant', 'bordered'))
            ->chipColor($config['chip_color'] ?? 'neutral');

        if (array_key_exists('multiple', $config)) {
            $field->multiple((bool) $config['multiple']);
        }

        if (array_key_exists('keep_selected_options_in_dropdown', $config)) {
            $field->keepSelectedOptionsInDropdown((bool) $config['keep_selected_options_in_dropdown']);
        }

        if (isset($config['color'])) {
            $field->color($config['color']);
        }

        if (array_key_exists('rich_options', $config)) {
            $field->richOptions((bool) $config['rich_options']);
        }

        if (isset($config['option_view'])) {
            $field->optionView((string) $config['option_view']);
        }

        if (array_key_exists('option_trigger_view', $config)) {
            $triggerView = $config['option_trigger_view'];

            $field->optionTriggerView(is_string($triggerView) ? $triggerView : null);
        }

        if (array_key_exists('rich_list_trigger_display', $config)) {
            $field->richListTriggerDisplay((bool) $config['rich_list_trigger_display']);
        }

        if (isset($config['option_layout'])) {
            $field->optionLayout((string) $config['option_layout']);
        }

        if ((bool) ($config['searchable'] ?? false)) {
            $field->searchable();
        }

        if ((bool) ($config['inline_search'] ?? false)) {
            $field->inlineSearch();
        }

        if (array_key_exists('native', $config)) {
            $field->native((bool) $config['native']);
        }

        if (array_key_exists('clearable', $config)) {
            $field->clearable((bool) $config['clearable']);
        }

        if (isset($config['dropdown_align'])) {
            $field->dropdownAlign((string) $config['dropdown_align']);
        }

        if (isset($config['chevron_icon'])) {
            $field->chevronIcon($config['chevron_icon']);
        }

        if (isset($config['clear_icon'])) {
            $field->clearIcon($config['clear_icon']);
        }

        if (array_key_exists('disabled', $config)) {
            $field->disabled((bool) $config['disabled']);
        }

        return $field;
    }
}

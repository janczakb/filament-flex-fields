<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;

final class FlexTextareaFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof FlexTextareaField);

        return $this->configureFlexTextareaField($field, $config);
    }

    public function configureFlexTextareaField(FlexTextareaField $field, array $config): FlexTextareaField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.flex_textarea_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.flex_textarea_variant', 'primary'));

        if (array_key_exists('character_counter', $config)) {
            $field->characterCounter((bool) $config['character_counter']);
        }

        if (array_key_exists('animated_autosize', $config)) {
            $field->animatedAutosize((bool) $config['animated_autosize']);
        }

        if (array_key_exists('max_height', $config)) {
            $field->maxHeight($config['max_height']);
        }

        if (array_key_exists('footer', $config)) {
            $field->footer($config['footer']);
        }

        if (isset($config['rows'])) {
            $field->rows((int) $config['rows']);
        }

        if (array_key_exists('max_length', $config)) {
            $field->maxLength($config['max_length']);
        }

        if (array_key_exists('speech_dictation', $config)) {
            $field->speechDictation((bool) $config['speech_dictation']);
        }

        if (array_key_exists('speech_dictation_language', $config)) {
            $field->speechDictationLanguage($config['speech_dictation_language']);
        }

        if (array_key_exists('emoji_picker', $config)) {
            $field->emojiPicker((bool) $config['emoji_picker']);
        }

        if (array_key_exists('emoji_picker_locale', $config)) {
            $field->emojiPickerLocale($config['emoji_picker_locale']);
        }

        foreach ($this->resolveToolbarSelectConfigs($config) as $selectConfig) {
            $field->toolbarSelect(
                (string) ($selectConfig['state_path'] ?? $selectConfig['statePath'] ?? ''),
                $selectConfig['options'] ?? [],
                $selectConfig['icon'] ?? null,
                $selectConfig['placeholder'] ?? null,
            );
        }

        if (isset($config['submit_action']) && is_array($config['submit_action'])) {
            $actionConfig = $config['submit_action'];
            $action = Action::make((string) ($actionConfig['name'] ?? 'submit'));

            if (isset($actionConfig['label'])) {
                $action->label((string) $actionConfig['label']);
            }

            if (isset($actionConfig['icon'])) {
                $action->icon($actionConfig['icon']);
            }

            $field->submitAction($action);
        }

        return $field;
    }

    private function resolveToolbarSelectConfigs(array $config): array
    {
        if (isset($config['toolbar_selects']) && is_array($config['toolbar_selects'])) {
            return array_values($config['toolbar_selects']);
        }

        if (isset($config['toolbar_select']) && is_array($config['toolbar_select'])) {
            return [$config['toolbar_select']];
        }

        return [];
    }
}

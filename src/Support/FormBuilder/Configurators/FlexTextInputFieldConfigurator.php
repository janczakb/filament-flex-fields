<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class FlexTextInputFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof FlexTextInput);

        return $this->configureFlexTextInputField($field, $config);
    }

    public function configureFlexTextInputField(FlexTextInput $field, array $config): FlexTextInput
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.flex_text_input_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.flex_text_input_variant', 'primary'));

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

        if (array_key_exists('character_counter', $config)) {
            $field->characterCounter((bool) $config['character_counter']);
        }

        if (array_key_exists('clearable', $config)) {
            $field->clearable((bool) $config['clearable']);
        }

        if (array_key_exists('loading', $config)) {
            $field->loading((bool) $config['loading']);
        }

        if (array_key_exists('validating', $config)) {
            $field->validating((bool) $config['validating']);
        }

        if (array_key_exists('password_strength', $config)) {
            $field->passwordStrength((bool) $config['password_strength']);
        }

        if (array_key_exists('verification_status', $config)) {
            $field->verificationStatus($config['verification_status']);
        }

        if (array_key_exists('verification_status_icon', $config)) {
            $field->verificationStatusIcon($config['verification_status_icon']);
        }

        if (array_key_exists('verification_status_color', $config)) {
            $field->verificationStatusColor($config['verification_status_color']);
        }

        return $field;
    }
}

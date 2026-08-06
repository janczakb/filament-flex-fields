<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;
use Filament\Support\Colors\Color;

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

        if (isset($config['min_length']) && is_numeric($config['min_length'])) {
            $field->minLength((int) $config['min_length']);
        }

        if (isset($config['max_length']) && is_numeric($config['max_length'])) {
            $field->maxLength((int) $config['max_length']);
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

        if (filled($config['prefix'] ?? null) && is_string($config['prefix'])) {
            $field->prefix($config['prefix']);
        }

        if (filled($config['suffix'] ?? null) && is_string($config['suffix'])) {
            $field->suffix($config['suffix']);
        }

        if (filled($config['prefix_icon'] ?? null) && is_string($config['prefix_icon'])) {
            $field->prefixIcon($config['prefix_icon']);
        }

        if (filled($config['suffix_icon'] ?? null) && is_string($config['suffix_icon'])) {
            $field->suffixIcon($config['suffix_icon']);
        }

        if (filled($config['suffix_icon_color'] ?? null) && is_string($config['suffix_icon_color'])) {
            $color = $config['suffix_icon_color'];

            $field->suffixIconColor(
                str_starts_with($color, '#')
                    ? Color::hex($color)
                    : $color,
            );
        }

        $mask = self::resolveMask($config);

        if (filled($mask)) {
            $field->mask($mask);
        }

        if (array_key_exists('focus_outline', $config)) {
            $field->focusOutline((bool) $config['focus_outline']);
        }

        if (array_key_exists('trim', $config) && (bool) $config['trim']) {
            $field->trim();
        }

        if (array_key_exists('read_only', $config) && (bool) $config['read_only']) {
            $field->readOnly();
        }

        return $field;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function resolveMask(array $config): ?string
    {
        $presets = self::maskPresets();
        $preset = (string) ($config['mask_preset'] ?? '');

        if ($preset === 'custom') {
            $custom = trim((string) ($config['mask'] ?? ''));

            return $custom !== '' ? $custom : null;
        }

        if ($preset !== '' && isset($presets[$preset])) {
            return $presets[$preset];
        }

        $direct = trim((string) ($config['mask'] ?? ''));

        return $direct !== '' ? $direct : null;
    }

    /**
     * @return array<string, string>
     */
    public static function maskPresets(): array
    {
        return [
            'date' => '99/99/9999',
            'time' => '99:99',
            'phone' => '(999) 999-9999',
            'card' => '9999 9999 9999 9999',
            'postal' => '99-999',
        ];
    }
}

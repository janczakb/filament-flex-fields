<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AudioField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class AudioFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof AudioField);

        return $this->configureAudioField($field, $config);
    }

    public function configureAudioField(AudioField $field, array $config): AudioField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.audio_size', 'md'))
            ->fullWidth((bool) ($config['full_width'] ?? false));

        if (array_key_exists('src', $config)) {
            $field->src($config['src']);
        }

        if (array_key_exists('loop', $config)) {
            $field->loop((bool) $config['loop']);
        }

        if (array_key_exists('waveform', $config) && is_array($config['waveform'])) {
            $field->waveform($config['waveform']);
        }

        return $field;
    }
}

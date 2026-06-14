<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class SignatureFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof SignatureField);

        return $this->configureSignatureField($field, $config);
    }

    public function configureSignatureField(SignatureField $field, array $config): SignatureField
    {
        if (array_key_exists('pen_color', $config)) {
            $field->penColor((string) $config['pen_color']);
        }

        if (array_key_exists('pen_width', $config)) {
            $field->penWidth((float) $config['pen_width']);
        }

        if (array_key_exists('background_color', $config)) {
            $field->backgroundColor(is_string($config['background_color']) ? $config['background_color'] : null);
        }

        if (array_key_exists('fullscreen', $config)) {
            $field->fullscreen((bool) $config['fullscreen']);
        }

        if (array_key_exists('undoable', $config)) {
            $field->undoable((bool) $config['undoable']);
        }

        if (array_key_exists('max_size_kb', $config)) {
            $field->maxSizeKb((int) $config['max_size_kb']);
        }

        if (array_key_exists('min_strokes', $config)) {
            $field->minStrokes((int) $config['min_strokes']);
        }

        if (array_key_exists('smoothing', $config)) {
            $field->smoothing((bool) $config['smoothing']);
        }

        if (array_key_exists('download_format', $config)) {
            $format = $config['download_format'];

            $field->downloadable(is_string($format) ? $format : null);
        }

        if (array_key_exists('download_filename', $config)) {
            $field->downloadFilename((string) $config['download_filename']);
        }

        if (array_key_exists('webp_quality', $config)) {
            $field->webpQuality((float) $config['webp_quality']);
        }

        return $field;
    }
}

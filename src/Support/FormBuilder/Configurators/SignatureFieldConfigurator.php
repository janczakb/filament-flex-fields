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

        if (array_key_exists('view_box_width', $config) && array_key_exists('view_box_height', $config)) {
            if (is_numeric($config['view_box_width']) && is_numeric($config['view_box_height'])) {
                $field->viewBox((int) $config['view_box_width'], (int) $config['view_box_height']);
            }
        }

        if (array_key_exists('trackpad_glide', $config)) {
            $field->trackpadGlide((bool) $config['trackpad_glide']);
        }

        if (array_key_exists('trackpad_glide_key', $config) && filled($config['trackpad_glide_key'])) {
            $field->trackpadGlideKey((string) $config['trackpad_glide_key']);
        }

        if (array_key_exists('guidelines', $config)) {
            $field->guidelines((bool) $config['guidelines']);
        }

        if (array_key_exists('read_only', $config) && (bool) $config['read_only']) {
            $field->readOnly();
        }

        if (array_key_exists('undo_icon', $config)) {
            $field->undoIcon($config['undo_icon']);
        }

        if (array_key_exists('clear_icon', $config)) {
            $field->clearIcon($config['clear_icon']);
        }

        if (array_key_exists('download_icon', $config)) {
            $field->downloadIcon($config['download_icon']);
        }

        if (array_key_exists('fullscreen_icon', $config)) {
            $field->fullscreenIcon($config['fullscreen_icon']);
        }

        if (array_key_exists('close_icon', $config)) {
            $field->closeIcon($config['close_icon']);
        }

        return $field;
    }
}

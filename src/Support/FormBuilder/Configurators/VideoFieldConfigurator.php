<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VideoField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class VideoFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof VideoField);

        return $this->configureVideoField($field, $config);
    }

    public function configureVideoField(VideoField $field, array $config): VideoField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.video_size', 'md'))
            ->ratio($config['ratio'] ?? '16:9')
            ->fullWidth((bool) ($config['full_width'] ?? false))
            ->skipSeconds($config['skip_seconds'] ?? 10);

        if (array_key_exists('poster', $config)) {
            $field->poster($config['poster']);
        }

        if (array_key_exists('placeholder', $config) && ! array_key_exists('poster', $config)) {
            $field->placeholder($config['placeholder']);
        }

        if (array_key_exists('src', $config)) {
            $field->src($config['src']);
        }

        if (array_key_exists('title', $config)) {
            $field->title($config['title']);
        }

        if (array_key_exists('subtitle', $config)) {
            $field->subtitle($config['subtitle']);
        }

        if (array_key_exists('controls', $config)) {
            $field->controls((bool) $config['controls']);
        }

        if (array_key_exists('native_controls', $config)) {
            $field->nativeControls((bool) $config['native_controls']);
        }

        if (array_key_exists('autoplay', $config)) {
            $field->autoplay((bool) $config['autoplay']);
        }

        if (array_key_exists('loop', $config)) {
            $field->loop((bool) $config['loop']);
        }

        if (array_key_exists('muted', $config)) {
            $field->muted((bool) $config['muted']);
        }

        if (array_key_exists('plays_inline', $config)) {
            $field->playsInline((bool) $config['plays_inline']);
        }

        if (array_key_exists('fullscreenable', $config)) {
            $field->fullscreenable((bool) $config['fullscreenable']);
        }

        if (array_key_exists('auto_hide_controls', $config)) {
            $field->autoHideControls((bool) $config['auto_hide_controls']);
        }

        if (array_key_exists('picture_in_picture', $config)) {
            $field->pictureInPictureable((bool) $config['picture_in_picture']);
        }

        if (array_key_exists('volume_control', $config)) {
            $field->volumeControl((bool) $config['volume_control']);
        }

        if (array_key_exists('allow_youtube', $config)) {
            $field->allowYoutube((bool) $config['allow_youtube']);
        }

        if (array_key_exists('youtube_no_cookie', $config)) {
            $field->youtubeNoCookie((bool) $config['youtube_no_cookie']);
        }

        if (array_key_exists('controls_layout', $config)) {
            $field->controlsLayout((string) $config['controls_layout']);
        } elseif (array_key_exists('compact_controls', $config)) {
            $field->compactControls((bool) $config['compact_controls']);
        }

        return $field;
    }
}

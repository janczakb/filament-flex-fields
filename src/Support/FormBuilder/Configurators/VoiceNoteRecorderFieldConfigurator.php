<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class VoiceNoteRecorderFieldConfigurator implements FieldConfigurator
{
    public function __construct(
        private readonly FlexFileUploadFieldConfigurator $fileUpload = new FlexFileUploadFieldConfigurator,
    ) {}

    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof VoiceNoteRecorderField);

        return $this->configureVoiceNoteRecorderField($field, $config);
    }

    public function configureVoiceNoteRecorderField(VoiceNoteRecorderField $field, array $config): VoiceNoteRecorderField
    {
        /** @var VoiceNoteRecorderField $field */
        $field = $this->fileUpload->configure($field, $config);

        if (array_key_exists('max_duration', $config)) {
            $field->maxDuration((int) $config['max_duration']);
        }

        if (array_key_exists('upload_immediately', $config)) {
            $field->uploadImmediately((bool) $config['upload_immediately']);
        }

        if (isset($config['size']) && is_string($config['size']) && $config['size'] !== '') {
            $field->size($config['size']);
        }

        if (! array_key_exists('directory', $config) || blank($config['directory'])) {
            $field->directory('voice-notes');
        }

        return $field;
    }
}

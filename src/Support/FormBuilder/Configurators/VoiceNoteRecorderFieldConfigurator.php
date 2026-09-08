<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\VoiceNoteSpatieRecorderField;
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
        if ($field instanceof VoiceNoteSpatieRecorderField) {
            return $this->configureVoiceNoteSpatieRecorderField($field, $config);
        }

        assert($field instanceof VoiceNoteRecorderField);

        return $this->configureVoiceNoteRecorderField($field, $config);
    }

    public function configureVoiceNoteRecorderField(VoiceNoteRecorderField $field, array $config): VoiceNoteRecorderField
    {
        /** @var VoiceNoteRecorderField $field */
        $field = $this->fileUpload->configure($field, $config);

        return $this->applyVoiceNoteOptions($field, $config, applyDirectoryDefault: true);
    }

    public function configureVoiceNoteSpatieRecorderField(
        VoiceNoteSpatieRecorderField $field,
        array $config,
    ): VoiceNoteSpatieRecorderField {
        /** @var VoiceNoteSpatieRecorderField $field */
        $field = $this->fileUpload->configure($field, $config);

        return $this->applyVoiceNoteOptions($field, $config, applyDirectoryDefault: false);
    }

    /**
     * @template T of VoiceNoteRecorderField|VoiceNoteSpatieRecorderField
     *
     * @param  T  $field
     * @param  array<string, mixed>  $config
     * @return T
     */
    private function applyVoiceNoteOptions(
        VoiceNoteRecorderField|VoiceNoteSpatieRecorderField $field,
        array $config,
        bool $applyDirectoryDefault,
    ): VoiceNoteRecorderField|VoiceNoteSpatieRecorderField {
        if (array_key_exists('max_duration', $config)) {
            $field->maxDuration((int) $config['max_duration']);
        }

        if (array_key_exists('upload_immediately', $config)) {
            $field->uploadImmediately((bool) $config['upload_immediately']);
        }

        if (isset($config['size']) && is_string($config['size']) && $config['size'] !== '') {
            $field->size($config['size']);
        }

        if ($applyDirectoryDefault && (! array_key_exists('directory', $config) || blank($config['directory']))) {
            $field->directory('voice-notes');
        }

        return $field;
    }
}

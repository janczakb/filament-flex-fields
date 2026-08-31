<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AudioField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\BarcodeScannerField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexImageUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SocialLinksField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VideoField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\AudioFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\BarcodeScannerFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexSpatieMediaLibraryFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexFileUploadFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\MapPickerFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\SignatureFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\SocialLinksFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\VideoFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\VoiceNoteRecorderFieldConfigurator;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Component;

final class MediaFieldTypeHandler extends AbstractFieldTypeHandler
{
    public function __construct(
        private readonly FlexFileUploadFieldConfigurator $fileUpload = new FlexFileUploadFieldConfigurator,
        private readonly FlexSpatieMediaLibraryFieldConfigurator $spatieFileUpload = new FlexSpatieMediaLibraryFieldConfigurator,
        private readonly VideoFieldConfigurator $video = new VideoFieldConfigurator,
        private readonly AudioFieldConfigurator $audio = new AudioFieldConfigurator,
        private readonly VoiceNoteRecorderFieldConfigurator $voiceNote = new VoiceNoteRecorderFieldConfigurator,
        private readonly MapPickerFieldConfigurator $mapPicker = new MapPickerFieldConfigurator,
        private readonly SocialLinksFieldConfigurator $socialLinks = new SocialLinksFieldConfigurator,
        private readonly SignatureFieldConfigurator $signature = new SignatureFieldConfigurator,
        private readonly BarcodeScannerFieldConfigurator $barcode = new BarcodeScannerFieldConfigurator,
    ) {}

    protected function supportedTypesList(): array
    {
        return [
            FieldType::File,
            FieldType::Image,
            FieldType::Video,
            FieldType::Audio,
            FieldType::VoiceNote,
            FieldType::MapPicker,
            FieldType::SocialLinks,
            FieldType::Signature,
            FieldType::Barcode,
        ];
    }

    public function make(FlexFieldDefinition $definition, string $statePath): Component
    {
        $config = $definition->config;

        return match ($definition->type) {
            FieldType::File => $this->configureFileUpload($statePath, $config),
            FieldType::Image => $this->configureImageUpload($statePath, $config),
            FieldType::Video => $this->video->configure(VideoField::make($statePath), $config),
            FieldType::Audio => $this->audio->configure(AudioField::make($statePath), $config),
            FieldType::VoiceNote => $this->voiceNote->configure(VoiceNoteRecorderField::make($statePath), $config),
            FieldType::MapPicker => $this->mapPicker->configure(MapPickerField::make($statePath), $config),
            FieldType::SocialLinks => $this->socialLinks->configure(SocialLinksField::make($statePath), $config),
            FieldType::Signature => $this->signature->configure(SignatureField::make($statePath), $config),
            FieldType::Barcode => $this->barcode->configure(BarcodeScannerField::make($statePath), $config),
            default => throw new \InvalidArgumentException("Unsupported field type [{$definition->type->value}] for media handler."),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function configureFileUpload(string $statePath, array $config): Component
    {
        if (($config['use_spatie_media_library'] ?? false) && class_exists(SpatieMediaLibraryFileUpload::class)) {
            return $this->spatieFileUpload->configure(
                FlexSpatieMediaLibraryFileUpload::make($statePath)->withRecommendedDefaults(),
                $config,
            );
        }

        return $this->fileUpload->configure(
            FlexFileUpload::make($statePath)->withRecommendedDefaults(),
            $config,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function configureImageUpload(string $statePath, array $config): Component
    {
        if (($config['use_spatie_media_library'] ?? false) && class_exists(SpatieMediaLibraryFileUpload::class)) {
            return $this->spatieFileUpload->configure(
                FlexSpatieMediaLibraryFileUpload::make($statePath)->withRecommendedDefaults()->imagesOnly(),
                $config,
            );
        }

        return $this->fileUpload->configure(
            FlexImageUpload::make($statePath)->withRecommendedDefaults(),
            $config,
        );
    }
}

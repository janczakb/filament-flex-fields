<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AudioField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexImageUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VideoField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\AudioFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexFileUploadFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\MapPickerFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\SignatureFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\VideoFieldConfigurator;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Component;

final class MediaFieldTypeHandler extends AbstractFieldTypeHandler
{
    public function __construct(
        private readonly FlexFileUploadFieldConfigurator $fileUpload = new FlexFileUploadFieldConfigurator,
        private readonly VideoFieldConfigurator $video = new VideoFieldConfigurator,
        private readonly AudioFieldConfigurator $audio = new AudioFieldConfigurator,
        private readonly MapPickerFieldConfigurator $mapPicker = new MapPickerFieldConfigurator,
        private readonly SignatureFieldConfigurator $signature = new SignatureFieldConfigurator,
    ) {}

    protected function supportedTypesList(): array
    {
        return [
            FieldType::File,
            FieldType::Image,
            FieldType::Video,
            FieldType::Audio,
            FieldType::MapPicker,
            FieldType::Signature,
        ];
    }

    public function make(FlexFieldDefinition $definition, string $statePath): Component
    {
        $config = $definition->config;

        return match ($definition->type) {
            FieldType::File => $this->fileUpload->configure(
                (($config['use_spatie_media_library'] ?? false) && class_exists(SpatieMediaLibraryFileUpload::class))
                    ? FlexSpatieMediaLibraryFileUpload::make($statePath)->withRecommendedDefaults()
                    : FlexFileUpload::make($statePath)->withRecommendedDefaults(),
                $config,
            ),
            FieldType::Image => $this->fileUpload->configure(
                (($config['use_spatie_media_library'] ?? false) && class_exists(SpatieMediaLibraryFileUpload::class))
                    ? FlexSpatieMediaLibraryFileUpload::make($statePath)->withRecommendedDefaults()->imagesOnly()
                    : FlexImageUpload::make($statePath)->withRecommendedDefaults(),
                $config,
            ),
            FieldType::Video => $this->video->configure(VideoField::make($statePath), $config),
            FieldType::Audio => $this->audio->configure(AudioField::make($statePath), $config),
            FieldType::MapPicker => $this->mapPicker->configure(MapPickerField::make($statePath), $config),
            FieldType::Signature => $this->signature->configure(SignatureField::make($statePath), $config),
            default => throw new \InvalidArgumentException("Unsupported field type [{$definition->type->value}] for media handler."),
        };
    }
}

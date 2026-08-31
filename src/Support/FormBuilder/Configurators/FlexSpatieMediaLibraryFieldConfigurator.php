<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class FlexSpatieMediaLibraryFieldConfigurator implements FieldConfigurator
{
    public function __construct(
        private readonly FlexFileUploadFieldConfigurator $fileUpload = new FlexFileUploadFieldConfigurator,
    ) {}

    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof FlexSpatieMediaLibraryFileUpload);

        return $this->configureFlexSpatieMediaLibraryField($field, $config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function configureFlexSpatieMediaLibraryField(FlexSpatieMediaLibraryFileUpload $field, array $config): FlexSpatieMediaLibraryFileUpload
    {
        /** @var FlexSpatieMediaLibraryFileUpload $field */
        $field = $this->fileUpload->configure($field, $config);

        if (filled($config['media_collection'] ?? null)) {
            $field->collection((string) $config['media_collection']);
        }

        if (array_key_exists('conversion', $config)) {
            $field->conversion($config['conversion'] !== null ? (string) $config['conversion'] : null);
        }

        if (array_key_exists('conversions_disk', $config) && filled($config['conversions_disk'])) {
            $field->conversionsDisk((string) $config['conversions_disk']);
        }

        if (array_key_exists('responsive_images', $config)) {
            $field->responsiveImages((bool) $config['responsive_images']);
        }

        if (array_key_exists('custom_properties', $config) && is_array($config['custom_properties'])) {
            /** @var array<string, mixed> $customProperties */
            $customProperties = $config['custom_properties'];
            $field->customProperties($customProperties);
        }

        return $field;
    }
}

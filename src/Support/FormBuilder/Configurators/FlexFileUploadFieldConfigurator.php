<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class FlexFileUploadFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        return $this->configureFlexFileUploadField($field, $config);
    }

    public function configureFlexFileUploadField(Component $field, array $config): Component
    {
        if (filled($config['disk'] ?? null)) {
            $field->disk((string) $config['disk']);
        }

        if (array_key_exists('directory', $config) && filled($config['directory'])) {
            $field->directory((string) $config['directory']);
        }

        if (array_key_exists('visibility', $config) && filled($config['visibility'])) {
            $field->visibility((string) $config['visibility']);
        }

        if (array_key_exists('multiple', $config) && (bool) $config['multiple']) {
            $field->multiple();
        }

        if (array_key_exists('max_size_kb', $config)) {
            $field->maxSize((int) $config['max_size_kb']);
        } elseif (array_key_exists('max_size', $config)) {
            $field->maxSize((int) $config['max_size']);
        }

        if (array_key_exists('accepted_types', $config) && is_array($config['accepted_types'])) {
            $field->acceptedFileTypes($config['accepted_types']);
        }

        if (array_key_exists('min_files', $config)) {
            $field->minFiles((int) $config['min_files']);
        }

        if (array_key_exists('max_files', $config)) {
            $field->maxFiles((int) $config['max_files']);
        }

        if (array_key_exists('max_total_size_kb', $config)) {
            $field->maxTotalSizeKb((int) $config['max_total_size_kb']);
        }

        if (array_key_exists('documents_only', $config) && (bool) $config['documents_only']) {
            $field->documentsOnly();
        }

        if (array_key_exists('images_only', $config) && (bool) $config['images_only']) {
            $field->imagesOnly();
        }

        if (array_key_exists('variant', $config) && filled($config['variant'])) {
            $field->variant((string) $config['variant']);
        }

        if (array_key_exists('size', $config) && filled($config['size'])) {
            $field->size($config['size']);
        }

        if (array_key_exists('store_metadata_in', $config) && filled($config['store_metadata_in'])) {
            $field->storeMetadataIn((string) $config['store_metadata_in']);
        }

        if (array_key_exists('scoped_directory', $config)) {
            if ((bool) $config['scoped_directory']) {
                $field->scopedDirectory(is_string($config['scoped_directory']) ? $config['scoped_directory'] : 'uploads');
            }
        }

        if (array_key_exists('optimize_images', $config)) {
            $field->optimizeImages((bool) $config['optimize_images']);
        }

        if (array_key_exists('max_image_width', $config)) {
            $field->maxImageWidth((int) $config['max_image_width']);
        }

        if (array_key_exists('max_image_height', $config)) {
            $field->maxImageHeight((int) $config['max_image_height']);
        }

        if (array_key_exists('allow_url_upload', $config) && (bool) $config['allow_url_upload']) {
            $field->allowUrlUpload();
        }

        if (array_key_exists('allow_webcam_upload', $config) && (bool) $config['allow_webcam_upload']) {
            $field->allowWebcamUpload();
        }

        return $field;
    }
}

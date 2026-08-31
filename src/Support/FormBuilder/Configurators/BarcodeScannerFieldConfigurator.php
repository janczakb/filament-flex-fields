<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\BarcodeScannerField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class BarcodeScannerFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof BarcodeScannerField);

        return $this->configureBarcodeScannerField($field, $config);
    }

    public function configureBarcodeScannerField(BarcodeScannerField $field, array $config): BarcodeScannerField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.barcode_scanner_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.barcode_scanner_variant', 'primary'));

        if (array_key_exists('formats', $config)) {
            $field->formats($config['formats']);
        }

        if (array_key_exists('continuous', $config)) {
            $field->continuous((bool) $config['continuous']);
        }

        if (array_key_exists('beep_on_scan', $config)) {
            $field->beepOnScan((bool) $config['beep_on_scan']);
        }

        if (array_key_exists('auto_submit', $config)) {
            $field->autoSubmit((bool) $config['auto_submit']);
        }

        if (array_key_exists('camera_facing', $config) && filled($config['camera_facing'])) {
            $field->cameraFacing((string) $config['camera_facing']);
        }

        if (array_key_exists('scan_delay', $config)) {
            $field->scanDelay((int) $config['scan_delay']);
        }

        if (array_key_exists('scan_interval', $config)) {
            $field->scanInterval((int) $config['scan_interval']);
        }

        if (array_key_exists('decode_fps', $config)) {
            $field->decodeFps((int) $config['decode_fps']);
        }

        if (array_key_exists('allow_camera_switch', $config)) {
            $field->allowCameraSwitch((bool) $config['allow_camera_switch']);
        }

        if (array_key_exists('preferred_device_id', $config) && filled($config['preferred_device_id'])) {
            $field->preferredDeviceId((string) $config['preferred_device_id']);
        }

        if (array_key_exists('store_detected_format', $config)) {
            $field->storeDetectedFormat((bool) $config['store_detected_format']);
        }

        if (array_key_exists('pause_when_hidden', $config)) {
            $field->pauseWhenHidden((bool) $config['pause_when_hidden']);
        }

        if (array_key_exists('allow_manual_input', $config)) {
            $field->allowManualInput((bool) $config['allow_manual_input']);
        }

        if (array_key_exists('validate_checksum', $config)) {
            $field->validateChecksum((bool) $config['validate_checksum']);
        }

        if (array_key_exists('scan_button_label', $config) && filled($config['scan_button_label'])) {
            $field->scanButtonLabel((string) $config['scan_button_label']);
        }

        if (array_key_exists('modal_heading', $config) && filled($config['modal_heading'])) {
            $field->modalHeading((string) $config['modal_heading']);
        }

        return $field;
    }
}

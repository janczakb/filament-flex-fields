<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\BarcodeFormat;
use Bjanczak\FilamentFlexFields\Enums\FieldCategory;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\BarcodeScannerField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;

it('declares barcode field type in media category', function () {
    expect(FieldType::Barcode)->toBeInstanceOf(FieldType::class)
        ->and(FieldType::Barcode->value)->toBe('barcode')
        ->and(FieldType::Barcode->category())->toBe(FieldCategory::Media)
        ->and(FieldType::Barcode->icon())->toBe('heroicon-o-qr-code')
        ->and(FieldType::Barcode->isCustomComponent())->toBeTrue()
        ->and(FieldType::Barcode->assetComponents())->toBe(['barcode-scanner-field']);
});

it('maps barcode field type to barcode scanner field via form builder', function () {
    $builder = new FlexFieldFormBuilder;

    $field = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'sku',
            'label' => 'Product SKU',
            'type' => FieldType::Barcode->value,
            'config' => [
                'formats' => [BarcodeFormat::Ean13->value, BarcodeFormat::Qr->value],
                'continuous' => true,
                'beep_on_scan' => false,
                'auto_submit' => true,
                'camera_facing' => 'user',
                'scan_delay' => 400,
                'store_detected_format' => true,
                'validate_checksum' => true,
                'allow_manual_input' => false,
            ],
        ]),
    );

    expect($field)->toBeInstanceOf(BarcodeScannerField::class)
        ->and($field->getSupportedFormats())->toBe([BarcodeFormat::Ean13, BarcodeFormat::Qr])
        ->and($field->isContinuous())->toBeTrue()
        ->and($field->shouldBeepOnScan())->toBeFalse()
        ->and($field->shouldAutoSubmit())->toBeTrue()
        ->and($field->getCameraFacing())->toBe('user')
        ->and($field->getScanDelay())->toBe(400)
        ->and($field->shouldStoreDetectedFormat())->toBeTrue()
        ->and($field->shouldValidateChecksum())->toBeTrue()
        ->and($field->allowsManualInput())->toBeFalse();
});

it('exposes barcode defaults from field type registry config', function () {
    $defaults = FieldType::Barcode->defaultConfig();

    expect($defaults)->toMatchArray([
        'size' => 'md',
        'variant' => 'primary',
        'continuous' => false,
        'beep_on_scan' => true,
        'auto_submit' => false,
        'camera_facing' => 'environment',
        'scan_delay' => 750,
        'scan_interval' => 120,
        'allow_camera_switch' => true,
        'store_detected_format' => false,
        'pause_when_hidden' => true,
        'allow_manual_input' => true,
        'validate_checksum' => false,
    ]);
});

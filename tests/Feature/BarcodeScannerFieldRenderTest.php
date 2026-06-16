<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\BarcodeFormat;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\BarcodeScannerField;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableBarcodeScannerForm;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    TestableBarcodeScannerForm::$formSchema = [];
});

it('renders barcode scanner field shell and alpine configuration', function (): void {
    TestableBarcodeScannerForm::$formSchema = [
        BarcodeScannerField::make('sku')
            ->required(),
    ];

    $html = Livewire::test(TestableBarcodeScannerForm::class)->html(false);

    expect($html)
        ->toContain('fff-barcode-scanner')
        ->toContain('barcodeScannerFieldFormComponent({')
        ->toContain('fff-barcode-scanner__scan-btn')
        ->toContain('fff-barcode-scanner__scan-line')
        ->toContain('fi-modal')
        ->toContain('beepUrl');
});

it('fails server validation when required barcode is empty', function (): void {
    TestableBarcodeScannerForm::$formSchema = [
        BarcodeScannerField::make('sku')->required(),
    ];

    $component = Livewire::test(TestableBarcodeScannerForm::class)
        ->set('data.sku', null);

    expect(fn () => $component->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('fails server validation for invalid ean13 checksum', function (): void {
    TestableBarcodeScannerForm::$formSchema = [
        BarcodeScannerField::make('sku')
            ->formats([BarcodeFormat::Ean13])
            ->validateChecksum(),
    ];

    $component = Livewire::test(TestableBarcodeScannerForm::class)
        ->set('data.sku', '5901234123450');

    expect(fn () => $component->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('passes server validation for valid ean13 barcode', function (): void {
    TestableBarcodeScannerForm::$formSchema = [
        BarcodeScannerField::make('sku')
            ->formats([BarcodeFormat::Ean13])
            ->validateChecksum()
            ->required(),
    ];

    Livewire::test(TestableBarcodeScannerForm::class)
        ->set('data.sku', '5901234123457')
        ->call('save')
        ->assertHasNoErrors();
});

it('passes server validation for allowed code39 values', function (): void {
    TestableBarcodeScannerForm::$formSchema = [
        BarcodeScannerField::make('sku')
            ->formats([BarcodeFormat::Code39]),
    ];

    Livewire::test(TestableBarcodeScannerForm::class)
        ->set('data.sku', 'ABC-123')
        ->call('save')
        ->assertHasNoErrors();
});

it('passes server validation for structured state with store detected format', function (): void {
    TestableBarcodeScannerForm::$formSchema = [
        BarcodeScannerField::make('sku')
            ->formats([BarcodeFormat::Ean13])
            ->storeDetectedFormat()
            ->required(),
    ];

    Livewire::test(TestableBarcodeScannerForm::class)
        ->set('data.sku', ['value' => '5901234123457', 'format' => 'ean_13'])
        ->call('save')
        ->assertHasNoErrors();
});

it('renders switch camera button in modal viewport', function (): void {
    TestableBarcodeScannerForm::$formSchema = [
        BarcodeScannerField::make('sku')->allowCameraSwitch(),
    ];

    $html = Livewire::test(TestableBarcodeScannerForm::class)->html(false);

    expect($html)
        ->toContain('fff-barcode-scanner__switch-camera-btn')
        ->toContain('fff-barcode-switch-camera');
});

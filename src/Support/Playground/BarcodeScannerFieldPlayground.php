<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Enums\BarcodeFormat;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\BarcodeScannerField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;

class BarcodeScannerFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'barcode__default' => '5901234123457',
            'barcode__ean_only' => null,
            'barcode__continuous' => null,
            'barcode__checksum' => null,
            'barcode__manual_only' => 'SKU-128-TEST',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Barcode scanner field')
                ->description('FlexTextInput shell with camera scan modal, multi-format support, optional checksum validation, torch toggle, and manual entry fallback.')
                ->schema([
                    BarcodeScannerField::make('barcode__default')
                        ->label('Product barcode')
                        ->placeholder('Scan or type barcode…')
                        ->required(),

                    BarcodeScannerField::make('barcode__ean_only')
                        ->label('EAN / UPC only')
                        ->formats([BarcodeFormat::Ean13, BarcodeFormat::Ean8, BarcodeFormat::UpcA, BarcodeFormat::UpcE])
                        ->validateChecksum()
                        ->variant('soft'),

                    BarcodeScannerField::make('barcode__continuous')
                        ->label('Continuous inventory scan')
                        ->continuous()
                        ->beepOnScan()
                        ->scanDelay(500)
                        ->variant('secondary'),

                    BarcodeScannerField::make('barcode__checksum')
                        ->label('Checksum enforced')
                        ->formats([BarcodeFormat::Ean13])
                        ->validateChecksum()
                        ->helperText('Try 5901234123457 (valid) vs 5901234123450 (invalid checksum).'),

                    BarcodeScannerField::make('barcode__manual_only')
                        ->label('Scan only (manual disabled)')
                        ->allowManualInput(false)
                        ->variant('flat'),
                ]),
        ];
    }
}

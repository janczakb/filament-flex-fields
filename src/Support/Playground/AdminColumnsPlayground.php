<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\MapPinColumn;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\ProgressColumn;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\SignaturePreviewColumn;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\StatusChipColumn;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;

class AdminColumnsPlayground
{
    /** Stroked path — unstyled fill-only strokes are invisible in the preview frame. */
    private const DEMO_SIGNATURE = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"><path d="M80 220 C 180 40, 320 40, 420 180 S 620 280, 720 120 S 880 40, 940 100" fill="none" stroke="#18181b" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/><path d="M200 250 C 280 200, 360 210, 440 240" fill="none" stroke="#18181b" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Admin columns')
                ->description('Read-only table columns for ProgressColumn, StatusChipColumn, SignaturePreviewColumn, and MapPinColumn — rendered via format*Display helpers.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    View::make('filament-flex-fields::partials.playground.admin-columns-demo')
                        ->viewData([
                            'rows' => $this->demoRows(),
                        ]),
                ]),
        ];
    }

    /**
     * @return list<array{title: string, progress: string, status: string, signature: string, location: string}>
     */
    protected function demoRows(): array
    {
        $progressColumn = ProgressColumn::make('completion')
            ->progressSize(ControlSize::Sm)
            ->progressColor('success')
            ->showValue();

        $statusColumn = StatusChipColumn::make('status')
            ->chipSize(ControlSize::Md)
            ->chipColor('warning');

        $signatureColumn = SignaturePreviewColumn::make('signature')
            ->previewSize(ControlSize::Md);

        $mapColumn = MapPinColumn::make('location')
            ->pinSize(ControlSize::Sm)
            ->showLabel();

        return [
            [
                'title' => 'Onboarding checklist',
                'progress' => $progressColumn->formatProgressDisplay(72.5),
                'status' => $statusColumn->formatChipDisplay(['label' => 'In progress', 'color' => 'primary']),
                'signature' => $signatureColumn->formatSignaturePreview(self::DEMO_SIGNATURE),
                'location' => $mapColumn->formatMapPinDisplay([
                    'label' => 'Gdansk, Poland',
                    'lat' => 54.352,
                    'lng' => 18.646,
                ]),
            ],
            [
                'title' => 'Contract renewal',
                'progress' => $progressColumn->formatProgressDisplay(['value' => 3, 'max' => 4, 'label' => 'Q1']),
                'status' => $statusColumn->formatChipDisplay('Pending review'),
                'signature' => $signatureColumn->formatSignaturePreview(null),
                'location' => $mapColumn->formatMapPinDisplay('Monaco Marina'),
            ],
        ];
    }
}

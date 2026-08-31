<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\MapPinColumn;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\ProgressColumn;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\SignaturePreviewColumn;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\StatusChipColumn;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Bjanczak\FilamentFlexFields\Support\MapPinColumnRenderCache;
use Bjanczak\FilamentFlexFields\Support\Playground\AdminColumnsPlayground;
use Bjanczak\FilamentFlexFields\Support\ProgressColumnRenderCache;
use Bjanczak\FilamentFlexFields\Support\SignaturePreviewColumnRenderCache;
use Bjanczak\FilamentFlexFields\Support\StatusChipColumnRenderCache;

const ADMIN_SURFACE_TEST_SIGNATURE = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"><path d="M80 220 C 180 40, 320 40, 420 180 S 620 280, 720 120 S 880 40, 940 100" fill="none" stroke="#18181b" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/><path d="M200 250 C 280 200, 360 210, 440 240" fill="none" stroke="#18181b" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

it('instantiates progress column and formats percentage track', function () {
    $column = ProgressColumn::make('completion')
        ->progressSize(ControlSize::Sm)
        ->progressColor('success')
        ->showValue();

    $html = $column->formatProgressDisplay(72.5);

    expect($html)
        ->toContain('fff-progress-column')
        ->toContain('fff-progress-column--sm')
        ->toContain('fi-color-success')
        ->toContain('role="progressbar"')
        ->toContain('73%')
        ->toContain('fff-progress-bar__fill');
});

it('normalizes progress column state from arrays and clamps values', function () {
    $column = ProgressColumn::make('completion');

    expect($column->normalizeProgressFromState(['value' => 3, 'max' => 4, 'label' => 'Q1']))
        ->toMatchArray(['percentage' => 75.0, 'label' => 'Q1'])
        ->and($column->normalizeProgressFromState(150))
        ->toMatchArray(['percentage' => 100.0, 'label' => null])
        ->and($column->normalizeProgressFromState(null))
        ->toBeNull();
});

it('instantiates status chip column with color and size', function () {
    $column = StatusChipColumn::make('status')
        ->chipSize(ControlSize::Lg)
        ->chipColor('warning');

    $html = $column->formatChipDisplay(['label' => 'Pending review', 'color' => 'warning']);

    expect($html)
        ->toContain('fff-status-chip-column')
        ->toContain('fff-status-chip-column--lg')
        ->toContain('fi-color-warning')
        ->toContain('Pending review');
});

it('instantiates signature preview column with svg and empty placeholder', function () {
    SignaturePreviewColumnRenderCache::flush();

    $column = SignaturePreviewColumn::make('signature')
        ->previewSize(ControlSize::Md);

    $html = $column->formatSignaturePreview(ADMIN_SURFACE_TEST_SIGNATURE);

    expect($html)
        ->toContain('fff-signature-preview-column')
        ->toContain('fff-signature-preview-column--md')
        ->toContain('<path')
        ->toContain('stroke="#18181b"')
        ->not->toContain('is-empty');

    $empty = SignaturePreviewColumn::make('signature')->formatSignaturePreview(null);

    expect($empty)
        ->toContain('fff-signature-preview-column__empty')
        ->toContain('is-empty');
});

it('renders admin columns playground demo rows with a visible signature preview', function () {
    SignaturePreviewColumnRenderCache::flush();

    $playground = app(AdminColumnsPlayground::class);
    $rows = (new ReflectionClass($playground))
        ->getMethod('demoRows')
        ->invoke($playground);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['signature'])->toContain('fff-signature-preview-column')
        ->and($rows[0]['signature'])->toContain('stroke="#18181b"')
        ->and($rows[0]['signature'])->not->toContain('is-empty')
        ->and($rows[1]['signature'])->toContain('fff-signature-preview-column__empty')
        ->and($rows[1]['signature'])->toContain('is-empty');
});

it('instantiates map pin column with label and coordinates tooltip', function () {
    $column = MapPinColumn::make('location')
        ->pinSize(ControlSize::Sm)
        ->showLabel();

    $html = $column->formatMapPinDisplay([
        'label' => 'Gdansk, Poland',
        'lat' => 54.352,
        'lng' => 18.646,
    ]);

    expect($html)
        ->toContain('fff-map-pin-column')
        ->toContain('fff-map-pin-column--sm')
        ->toContain('Gdansk, Poland')
        ->toContain('title="54.352,18.646"');
});

it('registers lazy css bundles for admin surface columns', function () {
    foreach ([
        'progress-column',
        'status-chip-column',
        'signature-preview-column',
        'map-pin-column',
    ] as $component) {
        expect(FlexFieldAssets::hasLazyStylesheet($component))->toBeTrue()
            ->and(FlexFieldAssets::stylesheetsFor($component))->toBe([$component])
            ->and(FlexFieldStylesheetQueue::enqueueFor($component))->toBe([$component]);
    }
});

it('caches admin surface column renders within the same request', function () {
    ProgressColumnRenderCache::flush();
    StatusChipColumnRenderCache::flush();
    MapPinColumnRenderCache::flush();

    $progress = ProgressColumn::make('completion');
    $progress->formatProgressDisplay(40);
    $progress->formatProgressDisplay(40);

    $chip = StatusChipColumn::make('status');
    $chip->formatChipDisplay('Active');
    $chip->formatChipDisplay('Active');

    $pin = MapPinColumn::make('location');
    $pin->formatMapPinDisplay('Paris');
    $pin->formatMapPinDisplay('Paris');

    expect(ProgressColumnRenderCache::entries())->toHaveCount(1)
        ->and(StatusChipColumnRenderCache::entries())->toHaveCount(1)
        ->and(MapPinColumnRenderCache::entries())->toHaveCount(1);
});

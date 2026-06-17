<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\IconColumn;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Bjanczak\FilamentFlexFields\Support\IconColumnRenderCache;
use Bjanczak\FilamentFlexFields\Support\Playground\IconColumnPlayground;

it('extends text column and formats icon-only cells', function () {
    $column = IconColumn::make('menu_icon');

    $html = $column->formatIconDisplay('heroicon-o-star');

    expect($html)
        ->toContain('fff-icon-column')
        ->toContain('fff-icon-column--md')
        ->toContain('fff-icon-column__icon')
        ->not->toContain('fff-icon-column__label');
});

it('supports label name color and size options', function () {
    $column = IconColumn::make('status_icon')
        ->iconSize(ControlSize::Lg)
        ->iconColor('success')
        ->showLabel()
        ->showName()
        ->labelUsing(fn (): string => 'Custom label');

    $html = $column->formatIconDisplay('heroicon-o-check-circle');

    expect($html)
        ->toContain('fff-icon-column--lg')
        ->toContain('fi-color-success')
        ->toContain('fff-icon-column__label')
        ->toContain('Custom label')
        ->toContain('fff-icon-column__name')
        ->toContain('heroicon-o-check-circle');
});

it('resolves human labels from icon names by default', function () {
    $column = IconColumn::make('menu_icon')->showLabel();

    $html = $column->formatIconDisplay('heroicon-o-check-circle');

    expect($column->resolveIconLabel('heroicon-o-check-circle'))->toBe('O Check Circle')
        ->and($html)->toContain('O Check Circle');
});

it('returns empty string for blank or invalid state', function () {
    $column = IconColumn::make('menu_icon');

    expect($column->formatIconDisplay(null))->toBe('')
        ->and($column->formatIconDisplay(''))->toBe('')
        ->and($column->formatIconDisplay(123))->toBe('');
});

it('normalizes icon state to trimmed strings', function () {
    $column = IconColumn::make('menu_icon');

    expect($column->normalizeIconFromState(' heroicon-o-star '))->toBe('heroicon-o-star')
        ->and($column->normalizeIconFromState(null))->toBeNull();
});

it('caches identical icon renders within the same request', function () {
    IconColumnRenderCache::flush();

    $column = IconColumn::make('menu_icon');

    $column->formatIconDisplay('heroicon-o-star');
    $column->formatIconDisplay('heroicon-o-star');

    expect(IconColumnRenderCache::entries())->toHaveCount(1);
});

it('registers icon column playground after icon picker in registry', function () {
    $definitions = FlexFieldsPlaygroundRegistry::definitions();

    expect($definitions['icon-column']['sort'])
        ->toBeGreaterThan($definitions['icon-picker-field']['sort']);
});

it('renders icon column playground demo rows', function () {
    $playground = app(IconColumnPlayground::class);
    $column = IconColumn::make('menu_icon')->showLabel();

    expect($column->formatIconDisplay('heroicon-o-home'))->toContain('fff-icon-column')
        ->and($column->formatIconDisplay(null))->toBe('');

    $section = collect($playground->components())->first();

    expect($section)->not->toBeNull()
        ->and($section->getHeading())->toBe('IconColumn');
});

it('does not load stylesheets from table column blade partial', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/tables/columns/icon-column.blade.php');

    expect($blade)->not->toContain('load-stylesheet');
});

it('registers table column stylesheets during column setup', function () {
    FlexFieldStylesheetQueue::reset();

    IconColumn::make('menu_icon');

    expect(FlexFieldStylesheetQueue::registered())->toBe(['icon-column']);
});

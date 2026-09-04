<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMatrixTable;

it('exposes flex matrix table configuration via fluent api', function (): void {
    $field = FlexMatrixTable::make('specs')
        ->rows([
            'length' => 'Overall Length',
            'beam' => ['label' => 'Beam', 'description' => 'Max width', 'disabled' => true],
        ])
        ->columnWidths([
            'value' => '2fr',
        ])
        ->size('lg');

    $rows = $field->getNormalizedRows();

    expect($field->getRowKeys())->toBe(['length', 'beam'])
        ->and($rows['length']['label'])->toBe('Overall Length')
        ->and($rows['beam']['description'])->toBe('Max width')
        ->and($rows['beam']['disabled'])->toBeTrue()
        ->and($field->getColumnWidths())->toBe(['value' => '2fr'])
        ->and($field->getSize())->toBe('lg')
        ->and($field->getWrapperClasses())->toContain('fff-matrix-choice--lg');
});

it('dehydrates matrix state arrays', function (): void {
    $field = FlexMatrixTable::make('specs')
        ->rows([
            'length' => 'Length',
            'beam' => 'Beam',
        ]);

    expect($field->dehydrateItems([
        'length' => ['value' => '24.5'],
        'beam' => [],
    ]))->toBe([
        'length' => ['value' => '24.5'],
        'beam' => [],
    ])
        ->and($field->dehydrateItems(null))->toBe([]);
});

it('caches normalized rows after first resolution', function (): void {
    $evaluations = 0;

    $field = FlexMatrixTable::make('specs')
        ->rows(function () use (&$evaluations): array {
            $evaluations++;

            return ['length' => 'Length'];
        });

    expect($evaluations)->toBe(0);

    $field->getNormalizedRows();
    $field->getNormalizedRows();

    expect($evaluations)->toBe(1);
});

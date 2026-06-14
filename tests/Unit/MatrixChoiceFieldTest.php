<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MatrixChoiceField;
use Illuminate\Support\Facades\Validator;

it('exposes matrix choice configuration via fluent api', function () {
    $field = MatrixChoiceField::make('mood')
        ->mode('radio')
        ->rows([
            'saturday' => 'Saturday',
            'sunday' => ['label' => 'Sunday', 'required' => true],
        ])
        ->matrixColumns([
            'happy' => '🙂',
            'sad' => ['label' => '☹️', 'icon' => 'heroicon-o-face-frown'],
        ])
        ->columnIcons(['happy' => 'heroicon-o-face-smile'])
        ->requiredRows(['monday'])
        ->disabledRows(['holiday'])
        ->disabledCells(['saturday' => ['sad']])
        ->size('lg')
        ->color('success');

    $rows = $field->getNormalizedRows();
    $columns = $field->getNormalizedColumns();

    expect($field->getMode())->toBe('radio')
        ->and($field->isCheckboxMode())->toBeFalse()
        ->and($rows['sunday']['required'])->toBeTrue()
        ->and($columns['happy']['label'])->toBe('🙂')
        ->and($columns['happy']['icon'])->toBe('heroicon-o-face-smile')
        ->and($columns['sad']['icon'])->toBe('heroicon-o-face-frown')
        ->and($field->getSize())->toBe('lg')
        ->and($field->getColor())->toBe('success')
        ->and($field->isCellDisabled('saturday', 'sad'))->toBeTrue()
        ->and($field->isCellDisabled('saturday', 'happy'))->toBeFalse();
});

it('supports checkbox mode with per-row selection limits', function () {
    $field = MatrixChoiceField::make('features')
        ->mode('checkbox')
        ->rows([
            'dark_mode' => [
                'label' => 'Dark mode',
                'required' => true,
                'max_selections' => 1,
            ],
        ])
        ->matrixColumns([
            'low' => 'Low',
            'high' => 'High',
        ]);

    expect($field->isCheckboxMode())->toBeTrue()
        ->and($field->getNormalizedRows()['dark_mode']['max_selections'])->toBe(1);
});

it('dehydrates only valid row and column keys', function () {
    $field = MatrixChoiceField::make('mood')
        ->mode('radio')
        ->rows([
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ])
        ->matrixColumns([
            'happy' => '🙂',
            'sad' => '☹️',
        ]);

    $dehydrated = $field->dehydrateValue([
        'saturday' => 'happy',
        'sunday' => 'invalid',
        'orphan' => 'happy',
    ]);

    expect($dehydrated)->toBe([
        'saturday' => 'happy',
    ]);
});

it('dehydrates checkbox selections as string arrays', function () {
    $field = MatrixChoiceField::make('features')
        ->mode('checkbox')
        ->rows(['dark_mode' => 'Dark mode'])
        ->matrixColumns(['low' => 'Low', 'high' => 'High']);

    $dehydrated = $field->dehydrateValue([
        'dark_mode' => ['low', 'high', 'invalid'],
        'orphan' => ['low'],
    ]);

    expect($dehydrated)->toBe([
        'dark_mode' => ['low', 'high'],
    ]);
});

it('validates required rows in radio mode', function () {
    $field = MatrixChoiceField::make('mood')
        ->mode('radio')
        ->rows([
            'saturday' => ['label' => 'Saturday', 'required' => true],
            'sunday' => 'Sunday',
        ])
        ->matrixColumns([
            'happy' => '🙂',
            'sad' => '☹️',
        ]);

    $validator = Validator::make(
        ['mood' => ['saturday' => null, 'sunday' => 'happy']],
        ['mood' => $field->getValidationRules()],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('mood'))->toContain('Saturday');
});

it('validates per-row min and max selections in checkbox mode', function () {
    $field = MatrixChoiceField::make('features')
        ->mode('checkbox')
        ->rows([
            'csv_export' => [
                'label' => 'CSV export',
                'min_selections' => 1,
                'max_selections' => 2,
            ],
        ])
        ->matrixColumns([
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
        ]);

    $tooMany = Validator::make(
        ['features' => ['csv_export' => ['low', 'medium', 'high']]],
        ['features' => $field->getValidationRules()],
    );

    $tooFew = Validator::make(
        ['features' => ['csv_export' => []]],
        ['features' => $field->getValidationRules()],
    );

    expect($tooMany->fails())->toBeTrue()
        ->and($tooFew->fails())->toBeTrue();
});

it('rejects invalid column selections', function () {
    $field = MatrixChoiceField::make('mood')
        ->mode('radio')
        ->rows(['saturday' => 'Saturday'])
        ->matrixColumns(['happy' => '🙂']);

    $validator = Validator::make(
        ['mood' => ['saturday' => 'unknown']],
        ['mood' => $field->getValidationRules()],
    );

    expect($validator->fails())->toBeTrue();
});

it('defaults to primary color wrapper classes', function () {
    $field = MatrixChoiceField::make('mood');

    expect($field->getWrapperClasses())->toBe([
        'fff-matrix-choice',
        'fff-matrix-choice--md',
        'fff-matrix-choice--radio',
        'fi-color-primary',
    ]);
});

it('supports reactive disableCellWhen and disableRowWhen rules', function () {
    $field = MatrixChoiceField::make('features')
        ->mode('checkbox')
        ->rows([
            'dark_mode' => 'Dark mode',
            'csv_export' => 'CSV export',
            'api_access' => 'API access',
        ])
        ->matrixColumns([
            'low' => 'Low',
            'high' => 'High',
        ])
        ->disableCellWhen('csv_export', 'high', 'dark_mode', 'high')
        ->disableRowWhen('api_access', 'dark_mode', 'low');

    $state = [
        'dark_mode' => ['high'],
        'csv_export' => ['medium'],
    ];

    expect($field->matchesConditionalDisableRule($field->getConditionalDisableRules()[0], $state))->toBeTrue()
        ->and($field->isCellConditionallyDisabled('csv_export', 'high', $state))->toBeTrue()
        ->and($field->isCellDisabled('csv_export', 'high', $state))->toBeTrue()
        ->and($field->isRowConditionallyDisabled('api_access', ['dark_mode' => ['low']]))->toBeTrue()
        ->and($field->isCellDisabled('csv_export', 'high', ['dark_mode' => ['low']]))->toBeFalse();
});

it('validates conditionally disabled cells on submit', function () {
    $field = MatrixChoiceField::make('features')
        ->mode('checkbox')
        ->rows(['dark_mode' => 'Dark mode', 'csv_export' => 'CSV export'])
        ->matrixColumns(['low' => 'Low', 'high' => 'High'])
        ->disableCellWhen('csv_export', 'high', 'dark_mode', 'high');

    $validator = Validator::make(
        ['features' => ['dark_mode' => ['high'], 'csv_export' => ['high']]],
        ['features' => $field->getValidationRules()],
    );

    expect($validator->fails())->toBeTrue();
});

it('serializes conditional disable rules to alpine', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/matrix-choice-field.blade.php');

    expect($blade)->toContain('conditionalDisableRules')
        ->and($blade)->toContain('matchesConditionalRule')
        ->and($blade)->toContain('pruneDisabledSelections');
});

it('routes checkbox and radio clicks through the cell handler only', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/matrix-choice-field.blade.php');

    expect($blade)->toContain('x-on:click="interact(@js($rowKey), @js($columnKey))"')
        ->and($blade)->not->toContain('x-on:click.stop="toggleCheckbox')
        ->and($blade)->not->toContain('x-on:click.stop="selectRadio');
});

it('rejects unsupported matrix choice modes', function () {
    MatrixChoiceField::make('mood')->mode('toggle')->getMode();
})->throws(InvalidArgumentException::class);

it('caches normalized rows, columns, and disabled cells map', function () {
    $rowEvaluations = 0;
    $colEvaluations = 0;
    $cellsEvaluations = 0;

    $field = MatrixChoiceField::make('features')
        ->rows(function () use (&$rowEvaluations) {
            $rowEvaluations++;

            return ['row1' => 'Row 1'];
        })
        ->matrixColumns(function () use (&$colEvaluations) {
            $colEvaluations++;

            return ['col1' => 'Col 1'];
        })
        ->disabledCells(function () use (&$cellsEvaluations) {
            $cellsEvaluations++;

            return ['row1' => ['col1']];
        });

    expect($rowEvaluations)->toBe(0)
        ->and($colEvaluations)->toBe(0)
        ->and($cellsEvaluations)->toBe(0);

    // First resolution
    $rows1 = $field->getNormalizedRows();
    $cols1 = $field->getNormalizedColumns();
    $cells1 = $field->getDisabledCellsMap();

    expect($rowEvaluations)->toBe(1)
        ->and($colEvaluations)->toBe(1)
        ->and($cellsEvaluations)->toBe(1);

    // Second resolution (should hit cache)
    $rows2 = $field->getNormalizedRows();
    $cols2 = $field->getNormalizedColumns();
    $cells2 = $field->getDisabledCellsMap();

    expect($rowEvaluations)->toBe(1)
        ->and($colEvaluations)->toBe(1)
        ->and($cellsEvaluations)->toBe(1)
        ->and($rows2)->toBe($rows1)
        ->and($cols2)->toBe($cols1)
        ->and($cells2)->toBe($cells1);
});

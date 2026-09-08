<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;

it('marks static select fields as headless eligible', function (): void {
    $field = SelectField::make('status')
        ->options([
            'draft' => 'Draft',
            'published' => 'Published',
        ])
        ->searchable()
        ->multiple()
        ->keepSelectedOptionsInDropdown()
        ->variant('soft');

    expect($field->shouldUseHeadlessEngine())->toBeTrue();
});

it('marks user select async and rich option fields as headless eligible', function (): void {
    $userSelect = UserSelect::make('assignee')
        ->options([
            'jane' => [
                'label' => 'Jane Cooper',
                'description' => 'jane@example.com',
            ],
        ])
        ->searchable();

    $richField = SelectField::make('plan')
        ->options([
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced analytics',
            ],
        ])
        ->richOptions()
        ->searchable();

    $dynamicField = SelectField::make('status')
        ->options(fn (): array => ['draft' => 'Draft'])
        ->searchable();

    expect($userSelect->shouldUseHeadlessEngine())->toBeTrue()
        ->and($richField->shouldUseHeadlessEngine())->toBeTrue()
        ->and($dynamicField->shouldUseHeadlessEngine())->toBeTrue();
});

it('preserves grouped option structure for the headless dropdown renderer', function (): void {
    $field = SelectField::make('status')
        ->options([
            'In process' => [
                'draft' => 'Draft',
                'reviewing' => 'Reviewing',
            ],
            'Reviewed' => [
                'published' => 'Published',
            ],
        ]);

    expect($field->getOptionsForJs()[0])->toMatchArray([
        'label' => 'In process',
    ])->and($field->getOptionsForJs()[0]['options'][0]['value'])->toBe('draft');
});

it('flattens grouped options for label lookup helpers', function (): void {
    $field = SelectField::make('status')
        ->options([
            'In process' => [
                'draft' => 'Draft',
                'reviewing' => 'Reviewing',
            ],
            'Reviewed' => [
                'published' => 'Published',
            ],
        ]);

    expect($field->getFlatOptionsForHeadlessJs())->toBe([
        ['value' => 'draft', 'label' => 'Draft'],
        ['value' => 'reviewing', 'label' => 'Reviewing'],
        ['value' => 'published', 'label' => 'Published'],
    ]);
});

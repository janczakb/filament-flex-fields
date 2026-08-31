<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Support\Select\HeadlessSelectFeatureFlags;

it('always enables the headless select engine at runtime regardless of config', function (): void {
    config()->set('filament-flex-fields.select.use_headless_engine', null);

    expect(HeadlessSelectFeatureFlags::useHeadlessEngine())->toBeTrue();

    config()->set('filament-flex-fields.select.use_headless_engine', false);

    expect(HeadlessSelectFeatureFlags::useHeadlessEngine())->toBeTrue();
});

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

    expect(HeadlessSelectFeatureFlags::isFieldEligible($field))->toBeTrue()
        ->and($field->shouldUseHeadlessEngine())->toBeTrue();
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

    expect(HeadlessSelectFeatureFlags::isFieldEligible($userSelect))->toBeTrue()
        ->and(HeadlessSelectFeatureFlags::isFieldEligible($richField))->toBeTrue()
        ->and(HeadlessSelectFeatureFlags::isFieldEligible($dynamicField))->toBeTrue()
        ->and($userSelect->shouldUseHeadlessEngine())->toBeTrue()
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

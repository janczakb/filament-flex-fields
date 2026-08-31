<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Support\Select\HeadlessSelectFeatureFlags;

it('uses headless runtime for eligible static select fields by default', function (): void {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft'])
        ->searchable();

    expect(HeadlessSelectFeatureFlags::isFieldEligible($field))->toBeTrue()
        ->and($field->shouldUseHeadlessEngine())->toBeTrue();
});

it('includes rich html select fields in headless migration', function (): void {
    $richField = SelectField::make('plan')
        ->options([
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced analytics',
            ],
        ])
        ->richOptions()
        ->searchable();

    expect(HeadlessSelectFeatureFlags::isFieldEligible($richField))->toBeTrue()
        ->and($richField->shouldUseHeadlessEngine())->toBeTrue();
});

it('excludes native select fields from headless migration', function (): void {
    $nativeField = SelectField::make('status')
        ->options(['draft' => 'Draft'])
        ->native();

    $richField = SelectField::make('plan')
        ->options([
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced analytics',
            ],
        ])
        ->richOptions()
        ->searchable();

    $userSelect = UserSelect::make('assignee')
        ->options([
            'jane' => [
                'label' => 'Jane Cooper',
                'description' => 'jane@example.com',
            ],
        ])
        ->searchable();

    expect(HeadlessSelectFeatureFlags::isFieldEligible($nativeField))->toBeFalse()
        ->and(HeadlessSelectFeatureFlags::isFieldEligible($userSelect))->toBeTrue()
        ->and(HeadlessSelectFeatureFlags::isFieldEligible($richField))->toBeTrue();
});

it('includes relationship style async search selects in headless migration', function (): void {
    $field = SelectField::make('status')
        ->searchable()
        ->getSearchResultsUsing(fn (): array => ['draft' => 'Draft'])
        ->preload();

    expect($field->hasDynamicSearchResults())->toBeTrue()
        ->and(HeadlessSelectFeatureFlags::isFieldEligible($field))->toBeTrue();
});

it('uses headless runtime for async search fields', function (): void {
    $field = SelectField::make('status')
        ->searchable()
        ->getSearchResultsUsing(fn (): array => ['draft' => 'Draft'])
        ->preload();

    expect(HeadlessSelectFeatureFlags::isFieldEligible($field))->toBeTrue()
        ->and($field->shouldUseHeadlessEngine())->toBeTrue();
});

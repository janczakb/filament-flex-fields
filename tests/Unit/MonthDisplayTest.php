<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\MonthDisplay;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMonthPicker;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeMonthLabels;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeSegmentHydrator;

it('formats and parses textual month labels for polish locale', function () {
    expect(DateTimeMonthLabels::format(6, 'pl_PL', MonthDisplay::Long))->toBe('czerwiec')
        ->and(DateTimeMonthLabels::format(6, 'pl_PL', MonthDisplay::Short))->toBe('cze')
        ->and(DateTimeMonthLabels::format(6, 'pl_PL', MonthDisplay::Numeric))->toBe('06')
        ->and(DateTimeMonthLabels::parse('czerwiec', 'pl_PL', MonthDisplay::Long))->toBe(6)
        ->and(DateTimeMonthLabels::parse('cze', 'pl_PL', MonthDisplay::Short))->toBe(6)
        ->and(DateTimeMonthLabels::parse('06', 'pl_PL', MonthDisplay::Numeric))->toBe(6);
});

it('hydrates month segments using month display mode', function () {
    $field = FlexMonthPicker::make('period')
        ->locale('pl_PL')
        ->monthDisplay(MonthDisplay::Long)
        ->default('2026-06');

    expect($field->getViewSegments()['single']['month'])->toBe('czerwiec');

    $dateField = FlexDateField::make('starts_on')
        ->locale('pl_PL')
        ->monthDisplay(MonthDisplay::Short)
        ->default('2026-06-15');

    expect($dateField->getViewSegments()['single']['month'])->toBe('cze')
        ->and(DateTimeSegmentHydrator::segmentMaxLength('month', MonthDisplay::Long, 'pl_PL'))->toBeGreaterThan(2);
});

it('exposes month display in alpine configuration', function () {
    $field = FlexDateField::make('starts_on')
        ->monthDisplay(MonthDisplay::Short);

    expect($field->getAlpineConfiguration()['monthDisplay'])->toBe('short');
});
